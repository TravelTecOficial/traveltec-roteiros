<?php
/**
 * Menu único do painel: Voucher Tec › Roteiros · Adicionar roteiro · Categorias · Tags · Configuração.
 *
 * O menu de topo "Roteiros" (tipo de conteúdo) sai e seus itens entram sob "Voucher Tec". O menu de topo
 * "Voucher Tec" é criado em admin.php (o painel em abas); aqui ele ganha os itens de roteiros na frente e o item
 * que o WordPress cria sozinho para o próprio menu de topo vira "Configuração". Funciona também quando o tipo
 * `roteiros` veio de outro plugin (CPT UI): só mexemos no menu.
 *
 * Como o primeiro item passa a ser "Roteiros" (e não o próprio menu de topo), o WordPress (wp-admin/includes/menu.php)
 * troca o menu de topo para `edit.php?post_type=roteiros` e COPIA os itens do submenu por índice para esse pai. Por isso
 * o submenu antigo do tipo de conteúdo precisa ser esvaziado aqui — senão os itens dele ficam ao lado dos nossos,
 * duplicados. E por isso não há filtros parent_file/submenu_file: depois dessa troca o WordPress já abre o menu e destaca o
 * item certo nas telas de roteiros (um filtro devolvendo 'voucher-tec' fecharia o menu).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Tipos de conteúdo que moram no menu Voucher Tec: tipo => array( nome da lista, nome do "adicionar" ). */
function tt_voucher_menu_tipos() {
	return array(
		'roteiros' => array( 'Roteiros', 'Adicionar roteiro' ),
	);
}

/**
 * Ordem final do submenu: para cada tipo, a lista, o "adicionar" e as taxonomias dele; depois os outros itens;
 * Configuração por último. Renomeia o item do menu de topo.
 */
function tt_voucher_menu_ordenar( $itens ) {
	$grupos = array();
	foreach ( array_keys( tt_voucher_menu_tipos() ) as $tipo ) {
		$grupos[ $tipo ] = array( 'lista' => array(), 'novo' => array(), 'tax' => array() );
	}
	$config = array();
	$outros = array();
	foreach ( (array) $itens as $item ) {
		$slug = html_entity_decode( (string) $item[2] );
		if ( 'voucher-tec' === $slug ) {
			$item[0] = 'Configuração';
			$item[1] = 'manage_options';
			$item[3] = 'Voucher Tec — Configuração';
			$config  = $item;
			continue;
		}
		$achou = false;
		foreach ( array_keys( $grupos ) as $tipo ) {
			if ( 'edit.php?post_type=' . $tipo === $slug ) {
				$grupos[ $tipo ]['lista'] = $item;
				$achou                    = true;
			} elseif ( 'post-new.php?post_type=' . $tipo === $slug ) {
				$grupos[ $tipo ]['novo'] = $item;
				$achou                   = true;
			} elseif ( 0 === strpos( $slug, 'edit-tags.php?' ) && preg_match( '/[?&]post_type=' . preg_quote( $tipo, '/' ) . '$/', $slug ) ) {
				$grupos[ $tipo ]['tax'][] = $item;
				$achou                    = true;
			}
		}
		if ( ! $achou ) {
			$outros[] = $item;
		}
	}
	$saida = array();
	foreach ( $grupos as $g ) {
		$saida = array_merge( $saida, array( $g['lista'], $g['novo'] ), $g['tax'] );
	}
	return array_values( array_filter( array_merge( $saida, $outros, array( $config ) ) ) );
}

function tt_voucher_menu_roteiros() {
	global $submenu;
	foreach ( tt_voucher_menu_tipos() as $tipo => $nomes ) {
		$pai = 'edit.php?post_type=' . $tipo;
		$tax = array(); // Categorias, Tags...: o WordPress só os registra no submenu do tipo, que é esvaziado abaixo
		foreach ( isset( $submenu[ $pai ] ) ? (array) $submenu[ $pai ] : array() as $item ) {
			if ( 0 === strpos( (string) $item[2], 'edit-tags.php?' ) ) {
				$tax[] = $item;
			}
		}
		remove_menu_page( $pai );
		unset( $submenu[ $pai ] ); // itens do tipo de conteúdo (Todos, Adicionar novo): passam a ser os nossos
		add_submenu_page( 'voucher-tec', $nomes[0], $nomes[0], 'edit_posts', $pai );
		add_submenu_page( 'voucher-tec', $nomes[1], $nomes[1], 'edit_posts', 'post-new.php?post_type=' . $tipo );
		foreach ( $tax as $item ) {
			$submenu['voucher-tec'][] = $item; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- mesmo formato que o WordPress usa
		}
	}
	if ( isset( $submenu['voucher-tec'] ) ) {
		$submenu['voucher-tec'] = tt_voucher_menu_ordenar( $submenu['voucher-tec'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
}
// Depois de admin.php criar o menu de topo (prioridade 10) e de o WordPress registrar os menus dos tipos de conteúdo.
add_action( 'admin_menu', 'tt_voucher_menu_roteiros', 99 );

/* ------------------------------------------------------------------ Endereço antigo (1.x) */

/**
 * O painel da 1.x ficava em Roteiros › Modelos do Elementor (edit.php?post_type=roteiros&page=tt-roteiros-modelos).
 * Devolve o endereço do painel atual quando o pedido é esse, ou '' para qualquer outro.
 */
function tt_voucher_endereco_antigo( $pedido ) {
	return isset( $pedido['page'] ) && 'tt-roteiros-modelos' === $pedido['page'] ? tt_voucher_url_painel() : '';
}

// No admin_init: roda antes de o WordPress conferir se a página existe (ela já não está registrada no menu).
add_action( 'admin_init', function () {
	$destino = tt_voucher_endereco_antigo( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- só lê o nome da página para redirecionar
	if ( '' !== $destino && current_user_can( 'manage_options' ) ) {
		wp_safe_redirect( $destino );
		exit;
	}
} );
