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

/* ---------------------------------------------------------------- registro */
$GLOBALS['submenu'] = array( 'voucher-tec' => array( $auto ) );
tt_voucher_menu_roteiros();
$acoes = array_map( function ( $a ) { return $a[0] . ':' . ( 'remove' === $a[0] ? $a[1] : $a[4] ); }, $GLOBALS['tt_menu'] );
igual( array( 'remove:edit.php?post_type=roteiros', 'sub:edit.php?post_type=roteiros', 'sub:post-new.php?post_type=roteiros' ), $acoes, 'registro: tira o menu de topo "Roteiros" e põe Roteiros e Adicionar sob Voucher Tec' );
igual( array( 'edit.php?post_type=roteiros', 'post-new.php?post_type=roteiros', 'voucher-tec' ), array_column( $GLOBALS['submenu']['voucher-tec'], 2 ), 'registro: submenu final já ordenado' );
ok( isset( $GLOBALS['tt_acoes']['admin_menu'] ), 'registro: ligado ao gancho admin_menu' );

/* ---------------------------------------------------------------- destaque do item ao editar */
$tela = function ( $post_type, $base, $action = '' ) { return (object) array( 'post_type' => $post_type, 'base' => $base, 'action' => $action ); };
igual( array( 'voucher-tec', 'edit.php?post_type=roteiros' ), tt_voucher_menu_destaque( 'edit.php?post_type=roteiros', null, $tela( 'roteiros', 'edit' ) ), 'destaque: lista de roteiros' );
igual( array( 'voucher-tec', 'edit.php?post_type=roteiros' ), tt_voucher_menu_destaque( 'edit.php?post_type=roteiros', null, $tela( 'roteiros', 'post' ) ), 'destaque: editando um roteiro' );
igual( array( 'voucher-tec', 'post-new.php?post_type=roteiros' ), tt_voucher_menu_destaque( 'edit.php?post_type=roteiros', null, $tela( 'roteiros', 'post', 'add' ) ), 'destaque: roteiro novo' );
igual( array( 'edit.php', null ), tt_voucher_menu_destaque( 'edit.php', null, $tela( 'post', 'edit' ) ), 'destaque: outros tipos de conteúdo não mudam' );
igual( array( 'index.php', 'x' ), tt_voucher_menu_destaque( 'index.php', 'x', null ), 'destaque: sem tela, nada muda' );
igual( array( 'voucher-tec', 'voucher-tec' ), tt_voucher_menu_destaque( 'voucher-tec', 'voucher-tec', $tela( '', 'toplevel_page_voucher-tec' ) ), 'destaque: o painel de Configuração continua como está' );
