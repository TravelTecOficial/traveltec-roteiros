<?php
/**
 * Painel "Voucher Tec": instalar o site padrão, dados da agência, cores e fontes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function tt_voucher_url_painel( $args = array() ) {
	return add_query_arg( array_merge( array( 'page' => 'voucher-tec' ), $args ), admin_url( 'admin.php' ) );
}

add_action( 'admin_menu', function () {
	add_menu_page( 'Voucher Tec', 'Voucher Tec', 'manage_options', 'voucher-tec', 'tt_voucher_tela', 'dashicons-tickets-alt', 58 );
	// endereço antigo (1.x): Roteiros › Modelos do Elementor
	add_submenu_page( 'edit.php?post_type=roteiros', 'Voucher Tec', 'Voucher Tec', 'manage_options', 'tt-roteiros-modelos', function () {
		echo '<script>location.href=' . wp_json_encode( tt_voucher_url_painel() ) . ';</script>';
	} );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'toplevel_page_voucher-tec' === $hook ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script( 'wp-color-picker', 'jQuery(function($){$(".tt-cor").wpColorPicker();});' );
	}
} );

add_action( 'admin_post_tt_voucher_instalar', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Sem permissão.' );
	}
	check_admin_referer( 'tt_voucher_instalar' );
	$r = tt_voucher_instalar( array( 'substituir' => ! empty( $_POST['substituir'] ) ) );
	set_transient( 'tt_voucher_relatorio', is_wp_error( $r ) ? array( 'erro' => $r->get_error_message() ) : $r, 300 );
	wp_safe_redirect( tt_voucher_url_painel( array( 'feito' => 'instalar' ) ) );
	exit;
} );

add_action( 'admin_post_tt_voucher_aplicar', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Sem permissão.' );
	}
	check_admin_referer( 'tt_voucher_aplicar' );
	$entrada = isset( $_POST['tt'] ) && is_array( $_POST['tt'] ) ? wp_unslash( $_POST['tt'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitizado em tt_voucher_salvar_dados
	set_transient( 'tt_voucher_relatorio', tt_voucher_aplicar( $entrada ), 300 );
	wp_safe_redirect( tt_voucher_url_painel( array( 'feito' => 'aplicar' ) ) );
	exit;
} );

add_action( 'admin_post_tt_roteiros_verificar', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Sem permissão.' );
	}
	check_admin_referer( 'tt_roteiros_verificar' );
	delete_site_transient( TT_ROTEIROS_CACHE );
	tt_roteiros_release( true );
	delete_site_transient( 'update_plugins' );
	wp_safe_redirect( tt_voucher_url_painel( array( 'checado' => 1 ) ) );
	exit;
} );

function tt_voucher_bloco_versao() {
	$release = tt_roteiros_release();
	echo '<p><strong>Versão instalada:</strong> ' . esc_html( TT_ROTEIROS_VERSION ) . ' — ';
	if ( empty( $release['versao'] ) ) {
		echo 'não foi possível consultar o repositório agora.';
	} elseif ( version_compare( $release['versao'], TT_ROTEIROS_VERSION, '>' ) ) {
		echo '<strong>versão ' . esc_html( $release['versao'] ) . ' disponível.</strong> Atualize em <a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">Plugins</a>.';
	} else {
		echo 'é a mais recente.';
	}
	echo ' <a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=tt_roteiros_verificar' ), 'tt_roteiros_verificar' ) ) . '">Procurar atualização</a></p>';
}

function tt_voucher_tela() {
	$st = tt_voucher_status();
	echo '<div class="wrap"><h1>Voucher Tec — Travel Tec</h1>';
	tt_voucher_bloco_versao();

	$rel = get_transient( 'tt_voucher_relatorio' );
	if ( $rel ) {
		delete_transient( 'tt_voucher_relatorio' );
		echo '<div class="notice notice-info"><p><strong>Resultado:</strong></p><pre style="white-space:pre-wrap;max-height:320px;overflow:auto">'
			. esc_html( wp_json_encode( $rel, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) . '</pre></div>';
	}
	foreach ( array( 'elementor' => 'Elementor', 'elementor_pro' => 'Elementor Pro / PRO Elements', 'jetformbuilder' => 'JetFormBuilder' ) as $k => $nome ) {
		if ( empty( $st['dependencias'][ $k ] ) ) {
			echo '<div class="notice notice-warning"><p><strong>' . esc_html( $nome ) . '</strong> não está ativo.</p></div>';
		}
	}
	if ( $st['instalacao_1x'] ) {
		echo '<div class="notice notice-warning"><p>Este site usa os modelos de roteiros da versão 1.x. Instalar o site padrão cria cabeçalho, rodapé, páginas e formulários novos — só faça isso num site em preparação.</p></div>';
	}

	// Instalação
	echo '<h2>Site padrão</h2><table class="widefat striped" style="max-width:820px"><thead><tr><th>Parte</th><th>Situação</th><th></th></tr></thead><tbody>';
	foreach ( tt_voucher_indice()['docs'] as $d ) {
		$i = $st['documentos'][ $d['chave'] ] ?? null;
		echo '<tr><td>' . esc_html( $d['titulo'] ) . '</td><td>' . ( $i && $i['existe'] ? 'Instalado (#' . (int) $i['id'] . ')' : 'Não instalado' ) . '</td><td>';
		if ( $i && $i['existe'] ) {
			echo '<a href="' . esc_url( $i['editar'] ) . '">Editar no Elementor</a>' . ( $i['url'] ? ' · <a href="' . esc_url( $i['url'] ) . '" target="_blank">Ver</a>' : '' );
		}
		echo '</td></tr>';
	}
	foreach ( tt_voucher_indice()['forms'] as $f ) {
		$id = $st['formularios'][ $f ] ?? 0;
		echo '<tr><td>Formulário: ' . esc_html( $f ) . '</td><td>' . ( $id && tt_voucher_vivo( $id ) ? 'Instalado (#' . (int) $id . ')' : 'Não instalado' ) . '</td><td></td></tr>';
	}
	echo '</tbody></table>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:14px 0 30px">';
	wp_nonce_field( 'tt_voucher_instalar' );
	echo '<input type="hidden" name="action" value="tt_voucher_instalar">';
	echo '<label><input type="checkbox" name="substituir" value="1"> Usar o layout do plugin também em páginas que já existem com o mesmo endereço (/contato/, /sobre/…)</label><br><br>';
	submit_button( $st['instalado'] ? 'Reinstalar site padrão' : 'Instalar site padrão', 'primary', 'submit', false );
	echo '<p class="description" style="max-width:820px">Reinstalar volta as páginas e modelos do plugin ao padrão (o que foi editado neles é substituído). Roteiros, posts e dados da agência não mudam.</p></form>';

	// Dados e estilo
	$d = $st['dados'];
	$e = $st['estilo'];
	echo '<h2>Dados da agência, cores e fontes</h2><p class="description">Os mesmos campos da tabela <code>companies</code> do Cliente Ideal. O MCP preenche tudo de uma vez por <code>POST /wp-json/voucher-tec/v1/aplicar</code>.</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><table class="form-table" style="max-width:900px"><tbody>';
	wp_nonce_field( 'tt_voucher_aplicar' );
	echo '<input type="hidden" name="action" value="tt_voucher_aplicar">';
	foreach ( array( 'cor_primaria' => array( 'Cor primária', $e['primaria'] ), 'cor_secundaria' => array( 'Cor secundária', $e['secundaria'] ), 'cor_terciaria' => array( 'Cor terciária', $e['terciaria'] ) ) as $k => $c ) {
		echo '<tr><th><label>' . esc_html( $c[0] ) . '</label></th><td><input type="text" class="tt-cor" name="tt[' . esc_attr( $k ) . ']" value="' . esc_attr( $c[1] ) . '"></td></tr>';
	}
	foreach ( array( 'fonte_titulos' => 'Fonte dos títulos (Google Fonts)', 'fonte_textos' => 'Fonte dos textos (Google Fonts)' ) as $k => $r ) {
		echo '<tr><th><label>' . esc_html( $r ) . '</label></th><td><input type="text" class="regular-text" name="tt[' . esc_attr( $k ) . ']" value="' . esc_attr( $e[ $k ] ) . '"></td></tr>';
	}
	foreach ( tt_voucher_campos() as $k => $r ) {
		echo '<tr><th><label>' . esc_html( $r ) . '</label></th><td>';
		if ( in_array( $k, tt_voucher_campos_longos(), true ) ) {
			echo '<textarea class="large-text" rows="4" name="tt[' . esc_attr( $k ) . ']">' . esc_textarea( $d[ $k ] ) . '</textarea>';
		} else {
			echo '<input type="text" class="regular-text" name="tt[' . esc_attr( $k ) . ']" value="' . esc_attr( $d[ $k ] ) . '">';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
	submit_button( 'Salvar e aplicar no site' );
	echo '</form></div>';
}

add_action( 'admin_notices', function () {
	if ( get_transient( 'tt_roteiros_aviso' ) && current_user_can( 'manage_options' ) ) {
		delete_transient( 'tt_roteiros_aviso' );
		echo '<div class="notice notice-warning is-dismissible"><p><strong>Voucher Tec:</strong> ative o Elementor Pro (e o JetFormBuilder) e depois clique em <a href="' . esc_url( tt_voucher_url_painel() ) . '">Voucher Tec › Instalar site padrão</a>.</p></div>';
	}
} );
