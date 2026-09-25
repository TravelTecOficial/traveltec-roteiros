<?php
/**
 * Menu único do painel: Voucher Tec › Roteiros · Adicionar roteiro · Configuração.
 *
 * O menu de topo "Roteiros" (tipo de conteúdo) sai e seus itens entram sob "Voucher Tec". O menu de topo
 * "Voucher Tec" é criado em admin.php (o painel em abas); aqui ele ganha os itens de roteiros na frente e o item
 * que o WordPress cria sozinho para o próprio menu de topo vira "Configuração". Funciona também quando o tipo
 * `roteiros` veio de outro plugin (CPT UI): só mexemos no menu.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Ordem final do submenu: Roteiros, Adicionar roteiro, (outros itens), Configuração. Renomeia o item do menu de topo. */
function tt_voucher_menu_ordenar( $itens ) {
	$roteiros  = array();
	$adicionar = array();
	$config    = array();
	$outros    = array();
	foreach ( (array) $itens as $item ) {
		switch ( $item[2] ) {
			case 'edit.php?post_type=roteiros':
				$roteiros = $item;
				break;
			case 'post-new.php?post_type=roteiros':
				$adicionar = $item;
				break;
			case 'voucher-tec':
				$item[0] = 'Configuração';
				$item[1] = 'manage_options';
				$item[3] = 'Voucher Tec — Configuração';
				$config  = $item;
				break;
			default:
				$outros[] = $item;
		}
	}
	return array_values( array_filter( array_merge( array( $roteiros, $adicionar ), $outros, array( $config ) ) ) );
}

function tt_voucher_menu_roteiros() {
	global $submenu;
	remove_menu_page( 'edit.php?post_type=roteiros' );
	add_submenu_page( 'voucher-tec', 'Roteiros', 'Roteiros', 'edit_posts', 'edit.php?post_type=roteiros' );
	add_submenu_page( 'voucher-tec', 'Adicionar roteiro', 'Adicionar roteiro', 'edit_posts', 'post-new.php?post_type=roteiros' );
	if ( isset( $submenu['voucher-tec'] ) ) {
		$submenu['voucher-tec'] = tt_voucher_menu_ordenar( $submenu['voucher-tec'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
}
// Depois de admin.php criar o menu de topo (prioridade 10) e de o WordPress registrar os menus dos tipos de conteúdo.
add_action( 'admin_menu', 'tt_voucher_menu_roteiros', 99 );

/**
 * Menu aberto e item destacado nas telas de roteiros (lista, editar, novo).
 * Devolve array( parent_file, submenu_file ).
 */
function tt_voucher_menu_destaque( $pai, $sub, $tela ) {
	if ( ! is_object( $tela ) || empty( $tela->post_type ) || 'roteiros' !== $tela->post_type || ! in_array( $tela->base, array( 'edit', 'post' ), true ) ) {
		return array( $pai, $sub );
	}
	$novo = 'post' === $tela->base && 'add' === $tela->action;
	return array( 'voucher-tec', $novo ? 'post-new.php?post_type=roteiros' : 'edit.php?post_type=roteiros' );
}

add_filter( 'parent_file', function ( $pai ) {
	$r = tt_voucher_menu_destaque( $pai, null, function_exists( 'get_current_screen' ) ? get_current_screen() : null );
	return $r[0];
} );

add_filter( 'submenu_file', function ( $sub ) {
	$r = tt_voucher_menu_destaque( null, $sub, function_exists( 'get_current_screen' ) ? get_current_screen() : null );
	return $r[1];
} );
