<?php
// Menu único Voucher Tec — includes/menu.php
require __DIR__ . '/bootstrap.php';

// stubs de menu (as chamadas ficam em $GLOBALS['tt_menu'])
function remove_menu_page( $slug ) { $GLOBALS['tt_menu'][] = array( 'remove', $slug ); }
function add_submenu_page( $pai, $titulo_pagina, $titulo, $cap, $slug, $cb = '' ) {
	$GLOBALS['tt_menu'][]                     = array( 'sub', $pai, $titulo, $cap, $slug );
	$GLOBALS['submenu'][ $pai ][]             = array( $titulo, $cap, $slug, $titulo_pagina );
	return 'hook';
}

require '/p/includes/menu.php';

/* ---------------------------------------------------------------- ordem e nomes */
$auto = array( 'Voucher Tec', 'edit_posts', 'voucher-tec', 'Voucher Tec' ); // item que o WordPress cria sozinho para o menu de topo
$itens = array(
	$auto,
	array( 'Adicionar roteiro', 'edit_posts', 'post-new.php?post_type=roteiros', 'Adicionar roteiro' ),
	array( 'Extra', 'manage_options', 'tt-extra', 'Extra' ),
	array( 'Roteiros', 'edit_posts', 'edit.php?post_type=roteiros', 'Roteiros' ),
);
$ord = tt_voucher_menu_ordenar( $itens );
igual( array( 'edit.php?post_type=roteiros', 'post-new.php?post_type=roteiros', 'tt-extra', 'voucher-tec' ), array_column( $ord, 2 ), 'ordem: Roteiros, Adicionar, (outros), Configuração por último' );
$config = end( $ord );
igual( array( 'Configuração', 'manage_options', 'Voucher Tec — Configuração' ), array( $config[0], $config[1], $config[3] ), 'o item do menu de topo vira "Configuração" e exige manage_options' );
$sem_roteiros = tt_voucher_menu_ordenar( array( $auto ) );
igual( array( 'voucher-tec' ), array_column( $sem_roteiros, 2 ), 'sem os itens de roteiros (sem permissão), sobra só a Configuração' );
igual( array(), tt_voucher_menu_ordenar( array() ), 'lista vazia continua vazia' );

/* ---------------------------------------------------------------- registro, com o menu como o WordPress de verdade o deixa */
// stub do que está em admin.php
function tt_voucher_url_painel( $args = array() ) { return 'https://exemplo.test/wp-admin/admin.php?page=voucher-tec'; }

// Repete o que wp-admin/includes/menu.php faz depois do gancho admin_menu: se o primeiro item do submenu não é o próprio
// menu de topo, o primeiro item vira o menu de topo e os itens do pai antigo são COPIADOS por índice para o novo pai.
function tt_teste_reparentar( $submenu, $pai ) {
	$primeiro = reset( $submenu[ $pai ] );
	$novo     = $primeiro[2];
	if ( $novo !== $pai ) {
		foreach ( $submenu[ $pai ] as $indice => $item ) {
			$submenu[ $novo ][ $indice ] = $item;
			unset( $submenu[ $pai ][ $indice ] );
		}
		unset( $submenu[ $pai ] );
	}
	return array( $novo, $submenu );
}

// estado no momento do admin_menu: o WordPress já criou o submenu do tipo de conteúdo ([5], [10]) e o plugin, o redirecionamento antigo ([11])
$GLOBALS['submenu'] = array(
	'voucher-tec'                 => array( $auto ),
	'edit.php?post_type=roteiros' => array(
		5  => array( 'Todos os roteiros', 'edit_posts', 'edit.php?post_type=roteiros' ),
		10 => array( 'Adicionar novo', 'edit_posts', 'post-new.php?post_type=roteiros' ),
		11 => array( 'Voucher Tec', 'manage_options', 'tt-roteiros-modelos' ),
	),
);
tt_voucher_menu_roteiros();
$acoes = array_map( function ( $a ) { return $a[0] . ':' . ( 'remove' === $a[0] ? $a[1] : $a[4] ); }, $GLOBALS['tt_menu'] );
igual( array( 'remove:edit.php?post_type=roteiros', 'sub:edit.php?post_type=roteiros', 'sub:post-new.php?post_type=roteiros' ), $acoes, 'registro: tira o menu de topo "Roteiros" e põe Roteiros e Adicionar sob Voucher Tec' );
ok( isset( $GLOBALS['tt_acoes']['admin_menu'] ), 'registro: ligado ao gancho admin_menu' );
list( $topo, $final ) = tt_teste_reparentar( $GLOBALS['submenu'], 'voucher-tec' );
igual( 'edit.php?post_type=roteiros', $topo, 'depois do WordPress reparentar, o menu de topo abre Roteiros' );
igual( array( 'edit.php?post_type=roteiros', 'post-new.php?post_type=roteiros', 'voucher-tec' ), array_column( $final[ $topo ], 2 ), 'submenu final: exatamente Roteiros, Adicionar e Configuração — sem itens duplicados do tipo de conteúdo nem o antigo' );
igual( array( 'Roteiros', 'Adicionar roteiro', 'Configuração' ), array_column( $final[ $topo ], 0 ), 'submenu final: nomes certos' );

/* ---------------------------------------------------------------- endereço antigo (1.x) e destaque do menu */
igual( 'https://exemplo.test/wp-admin/admin.php?page=voucher-tec', tt_voucher_endereco_antigo( array( 'post_type' => 'roteiros', 'page' => 'tt-roteiros-modelos' ) ), 'endereço antigo (Roteiros › Modelos do Elementor) leva ao painel' );
igual( '', tt_voucher_endereco_antigo( array( 'page' => 'outra-pagina' ) ), 'outras páginas não são redirecionadas' );
igual( '', tt_voucher_endereco_antigo( array() ), 'sem página, nada a redirecionar' );
ok( isset( $GLOBALS['tt_acoes']['admin_init'] ), 'endereço antigo: tratado no admin_init (antes da conferência de acesso do WordPress)' );
ok( ! isset( $GLOBALS['tt_acoes']['parent_file'] ) && ! isset( $GLOBALS['tt_acoes']['submenu_file'] ), 'sem filtros parent_file/submenu_file: depois de reparentar, o WordPress já abre o menu e destaca o item certo (o filtro antigo devolvia "voucher-tec" e fechava o menu)' );
