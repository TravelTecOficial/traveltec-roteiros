<?php
/**
 * Aplicar os dados do cliente e a API REST (voucher-tec/v1) usada pelo MCP.
 *
 *   GET  /wp-json/voucher-tec/v1/status    versão, dependências, o que está instalado, dados e paleta
 *   POST /wp-json/voucher-tec/v1/instalar  { substituir?: bool, modulos?: [chaves] }
 *   POST /wp-json/voucher-tec/v1/aplicar   linha da tabela companies do Cliente Ideal (+ telefone, youtube_url, webhook_url)
 *
 * Todas exigem um usuário com manage_options (Application Password).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Baixa o logo para a Biblioteca de Mídia e define como logo do site. */
function tt_voucher_definir_logo( $url ) {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	$id = media_sideload_image( $url, 0, 'Logo', 'id' );
	if ( is_wp_error( $id ) ) {
		return $id;
	}
	set_theme_mod( 'custom_logo', (int) $id );
	update_option( 'tt_voucher_logo_origem', $url );
	return (int) $id;
}

/**
 * Aplica os dados de uma agência (formato da tabela companies). Campos ausentes ficam como estão.
 * Devolve o relatório do que mudou.
 */
function tt_voucher_aplicar( $entrada ) {
	$entrada = is_array( $entrada ) ? $entrada : array();
	$rel     = array( 'dados_alterados' => array(), 'estilo' => null, 'logo' => null, 'avisos' => array() );

	$rel['dados_alterados'] = tt_voucher_salvar_dados( $entrada );
	$dados                  = tt_voucher_dados();

	if ( ! empty( $entrada['logo_url'] ) && get_option( 'tt_voucher_logo_origem' ) !== $dados['logo_url'] ) {
		$logo        = tt_voucher_definir_logo( $dados['logo_url'] );
		$rel['logo'] = is_wp_error( $logo ) ? 'erro: ' . $logo->get_error_message() : $logo;
	}
	$nome = tt_voucher_valor( 'nome' );
	if ( ( ! empty( $entrada['nome_fantasia'] ) || ! empty( $entrada['name'] ) ) && $nome ) {
		update_option( 'blogname', $nome );
	}
	if ( ! empty( $entrada['description'] ) ) {
		update_option( 'blogdescription', wp_trim_words( tt_voucher_valor( 'description' ), 20, '…' ) );
	}

	$chaves_estilo = array( 'cor_primaria', 'cor_secundaria', 'cor_terciaria', 'fonte_titulos', 'fonte_textos' );
	if ( array_intersect( $chaves_estilo, array_keys( array_filter( $entrada ) ) ) ) {
		$novo              = tt_voucher_estilo_de( $entrada, tt_voucher_estilo() );
		$rel['estilo']     = array( 'documentos_alterados' => tt_voucher_aplicar_estilo( $novo ), 'paleta' => tt_voucher_estilo() );
	}

	tt_voucher_atualizar_acoes_forms(); // e-mail da agência nos formulários
	tt_voucher_limpar_cache();
	return $rel;
}

function tt_voucher_status() {
	$ids    = tt_voucher_ids();
	$lista  = array();
	foreach ( $ids['docs'] as $chave => $id ) {
		$lista[ $chave ] = array(
			'id'     => (int) $id,
			'titulo' => get_the_title( $id ),
			'tipo'   => get_post_meta( $id, '_elementor_template_type', true ),
			'url'    => 'page' === get_post_type( $id ) ? get_permalink( $id ) : null,
			'editar' => admin_url( 'post.php?post=' . (int) $id . '&action=elementor' ),
			'existe' => tt_voucher_vivo( $id ),
		);
	}
	return array(
		'plugin'         => 'Voucher Tec - Travel Tec',
		'versao'         => TT_ROTEIROS_VERSION,
		'versao_modelos' => get_option( 'tt_roteiros_versao_modelos', '' ),
		'dependencias'   => tt_voucher_dependencias(),
		'instalado'      => ! empty( $ids['docs'] ),
		'instalacao_1x'  => (bool) get_option( TT_ROTEIROS_OPT ) && empty( $ids['docs'] ),
		'documentos'     => $lista,
		'formularios'    => $ids['forms'],
		'menus'          => $ids['menus'],
		'midia'          => $ids['midia'],
		'captacao'       => (bool) get_option( 'tt_voucher_captacao' ),
		'dados'          => tt_voucher_dados(),
		'estilo'         => tt_voucher_estilo(),
		'marcadores'     => array(
			'texto'    => '%tt:campo% — campos de dados + nome, nome_url, whatsapp, whatsapp_digitos, telefone, telefone_digitos, endereco, mapa_q, history_html, ano, company_id',
			'url'      => 'https://tt.token/<campo> — URL inteira (instagram_url, facebook_url, youtube_url, linkedin_url, tiktok_url, site_oficial); vazio esconde o link',
			'shortcode' => '[tt dado="campo"]',
		),
	);
}

add_action( 'rest_api_init', function () {
	$pode = function () {
		return current_user_can( 'manage_options' );
	};
	register_rest_route( 'voucher-tec/v1', '/status', array(
		'methods'             => 'GET',
		'permission_callback' => $pode,
		'callback'            => function () {
			return rest_ensure_response( tt_voucher_status() );
		},
	) );
	register_rest_route( 'voucher-tec/v1', '/instalar', array(
		'methods'             => 'POST',
		'permission_callback' => $pode,
		'callback'            => function ( WP_REST_Request $req ) {
			$r = tt_voucher_instalar( array(
				'substituir' => (bool) $req->get_param( 'substituir' ),
				'modulos'    => $req->get_param( 'modulos' ),
			) );
			return is_wp_error( $r ) ? $r : rest_ensure_response( $r );
		},
	) );
	register_rest_route( 'voucher-tec/v1', '/aplicar', array(
		'methods'             => 'POST',
		'permission_callback' => $pode,
		'callback'            => function ( WP_REST_Request $req ) {
			$corpo = $req->get_json_params();
			return rest_ensure_response( tt_voucher_aplicar( $corpo ? $corpo : $req->get_params() ) );
		},
	) );
} );
