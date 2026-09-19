<?php
/**
 * Plugin Name: TravelTec Roteiros
 * Description: Sistema de roteiros do site: tipo de conteúdo "Roteiros", campos, recebimento pela API (n8n / Cliente Ideal) e as páginas do Elementor (galeria, card e roteiro individual). Sem ACF nem CPT UI.
 * Version:     1.3.1
 * Author:      TravelTec
 * Text Domain: traveltec-roteiros
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TT_ROTEIROS_VERSION', '1.3.1' );
define( 'TT_ROTEIROS_DIR', plugin_dir_path( __FILE__ ) );
define( 'TT_ROTEIROS_URL', plugin_dir_url( __FILE__ ) );
define( 'TT_ROTEIROS_BASENAME', plugin_basename( __FILE__ ) );
// Última versão em que os modelos do Elementor mudaram. Quem instalou antes disso
// precisa reinstalar os modelos; atualizar só o código não muda o layout.
define( 'TT_ROTEIROS_VERSAO_MODELOS', '1.1.1' );
define( 'TT_ROTEIROS_OPT', 'tt_roteiros_modelos' );

require_once TT_ROTEIROS_DIR . 'includes/atualizador.php';

/**
 * Campos do roteiro. A chave é o nome gravado no banco e o nome que o fluxo n8n envia em "acf".
 * 'imagem_destaque' não é meta: vira a imagem destacada do post.
 */
function tt_roteiros_campos() {
	return array(
		'subtitulo'             => array( 'Subtítulo', 'text' ),
		'texto_de_apresentacao' => array( 'Texto de apresentação', 'html' ),
		'preco_de_referencia'   => array( 'Preço de referência', 'text' ),
		'galeria'               => array( 'Galeria (HTML)', 'html' ),
		'programacao'           => array( 'Programação', 'html' ),
		'hospedagem'            => array( 'Hospedagem', 'html' ),
		'inclui'                => array( 'Inclui', 'html' ),
		'nao_inclui'            => array( 'Não inclui', 'html' ),
		'pagamento'             => array( 'Pagamento', 'html' ),
		'cancelamento'          => array( 'Cancelamento', 'html' ),
		'documentacao'          => array( 'Documentação', 'html' ),
	);
}

/* ------------------------------------------------------------------ Tipo de conteúdo e campos */

function tt_roteiros_registrar_tipo() {
	// Se o site já tem o tipo (CPT UI, ACF...), não registra de novo.
	if ( ! post_type_exists( 'roteiros' ) ) {
		register_post_type( 'roteiros', array(
			'labels'       => array(
				'name'          => 'Roteiros',
				'singular_name' => 'Roteiro',
				'add_new_item'  => 'Adicionar novo roteiro',
				'edit_item'     => 'Editar roteiro',
				'all_items'     => 'Todos os roteiros',
				'search_items'  => 'Buscar roteiros',
				'not_found'     => 'Nenhum roteiro encontrado.',
				// Sem isto a aba do navegador em /roteiros/ fica "Roteiros Archive".
				'archives'      => 'Roteiros',
			),
			'public'       => true,
			'has_archive'  => 'roteiros', // /roteiros/ lista os roteiros; /roteiros/<slug>/ abre um
			'show_in_rest' => true,
			'rest_base'    => 'roteiros',
			'menu_icon'    => 'dashicons-palmtree',
			'menu_position' => 21,
			'supports'     => array( 'title', 'thumbnail', 'excerpt', 'revisions' ),
			'rewrite'      => array( 'slug' => 'roteiros', 'with_front' => false ),
		) );
	}
}

add_action( 'init', function () {
	tt_roteiros_registrar_tipo();
	foreach ( array_keys( tt_roteiros_campos() ) as $chave ) {
		register_post_meta( 'roteiros', $chave, array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => 'wp_kses_post',
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		) );
	}
}, 20 );

/**
 * Campo "acf" na API REST — o mesmo formato que o fluxo n8n já envia
 * ({ title, status, featured_media, acf: { subtitulo, ..., imagem_destaque } }).
 */
add_action( 'rest_api_init', function () {
	if ( function_exists( 'acf_get_field_groups' ) && acf_get_field_groups( array( 'post_type' => 'roteiros' ) ) ) {
		return; // o ACF do site já cuida disso
	}
	$props = array( 'imagem_destaque' => array( 'type' => array( 'integer', 'null' ) ) );
	foreach ( array_keys( tt_roteiros_campos() ) as $chave ) {
		$props[ $chave ] = array( 'type' => array( 'string', 'null' ) );
	}
	register_rest_field( 'roteiros', 'acf', array(
		'get_callback'    => function ( $post ) {
			$saida = array( 'imagem_destaque' => (int) get_post_thumbnail_id( $post['id'] ) ?: null );
			foreach ( array_keys( tt_roteiros_campos() ) as $chave ) {
				$saida[ $chave ] = (string) get_post_meta( $post['id'], $chave, true );
			}
			return $saida;
		},
		'update_callback' => function ( $valor, $post ) {
			if ( ! is_array( $valor ) ) {
				return true;
			}
			foreach ( array_keys( tt_roteiros_campos() ) as $chave ) {
				if ( array_key_exists( $chave, $valor ) ) {
					update_post_meta( $post->ID, $chave, wp_kses_post( (string) $valor[ $chave ] ) );
				}
			}
			if ( ! empty( $valor['imagem_destaque'] ) && get_post( (int) $valor['imagem_destaque'] ) ) {
				set_post_thumbnail( $post->ID, (int) $valor['imagem_destaque'] );
			}
			return true;
		},
		'schema'          => array( 'description' => 'Campos do roteiro', 'type' => 'object', 'properties' => $props ),
	) );
} );

/* ------------------------------------------------------------------ Galeria */

/**
 * A animação da galeria mora aqui, não no HTML do roteiro: o `<style>` que vem
 * junto do campo é descartado pelo wp_kses na gravação, e sem estas regras a
 * faixa de fotos fica parada, só com rolagem manual.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_singular( 'roteiros' ) ) {
		return;
	}
	wp_enqueue_style( 'tt-roteiros-galeria', TT_ROTEIROS_URL . 'assets/galeria.css', array(), TT_ROTEIROS_VERSION );
} );

/* ------------------------------------------------------------------ Edição no painel */

add_action( 'add_meta_boxes', function () {
	if ( function_exists( 'acf_get_field_groups' ) && acf_get_field_groups( array( 'post_type' => 'roteiros' ) ) ) {
		return;
	}
	add_meta_box( 'tt_roteiros_campos', 'Dados do roteiro', function ( $post ) {
		wp_nonce_field( 'tt_roteiros_salvar', 'tt_roteiros_nonce' );
		echo '<p class="description">A imagem de capa é a <strong>Imagem destacada</strong> (coluna ao lado).</p>';
		foreach ( tt_roteiros_campos() as $chave => $def ) {
			$valor = get_post_meta( $post->ID, $chave, true );
			echo '<p><label for="tt_' . esc_attr( $chave ) . '"><strong>' . esc_html( $def[0] ) . '</strong></label><br>';
			if ( 'text' === $def[1] ) {
				echo '<input type="text" class="widefat" id="tt_' . esc_attr( $chave ) . '" name="tt_roteiros[' . esc_attr( $chave ) . ']" value="' . esc_attr( $valor ) . '">';
			} else {
				echo '<textarea class="widefat" rows="6" id="tt_' . esc_attr( $chave ) . '" name="tt_roteiros[' . esc_attr( $chave ) . ']">' . esc_textarea( $valor ) . '</textarea>';
			}
			echo '</p>';
		}
	}, 'roteiros', 'normal', 'high' );
} );

add_action( 'save_post_roteiros', function ( $post_id ) {
	if ( ! isset( $_POST['tt_roteiros_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tt_roteiros_nonce'] ) ), 'tt_roteiros_salvar' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$enviado = isset( $_POST['tt_roteiros'] ) && is_array( $_POST['tt_roteiros'] ) ? wp_unslash( $_POST['tt_roteiros'] ) : array();
	foreach ( array_keys( tt_roteiros_campos() ) as $chave ) {
		if ( isset( $enviado[ $chave ] ) ) {
			update_post_meta( $post_id, $chave, wp_kses_post( $enviado[ $chave ] ) );
		}
	}
} );

/* ------------------------------------------------------------------ Modelos do Elementor */

/** Elementor Pro (Theme Builder e Loop Grid) é obrigatório para as páginas. */
function tt_roteiros_tem_elementor() {
	return did_action( 'elementor/loaded' ) && defined( 'ELEMENTOR_PRO_VERSION' );
}

function tt_roteiros_json( $arquivo, $subs = array() ) {
	$txt = file_get_contents( TT_ROTEIROS_DIR . 'templates/' . $arquivo . '.json' );
	foreach ( $subs as $de => $para ) {
		$txt = str_replace( $de, addcslashes( $para, '"\\' ), $txt );
	}
	return $txt;
}

/** Cria ou atualiza um post de modelo do Elementor. Devolve o ID. */
function tt_roteiros_gravar( $chave, $args, $meta ) {
	$ids = get_option( TT_ROTEIROS_OPT, array() );
	$id  = isset( $ids[ $chave ] ) ? (int) $ids[ $chave ] : 0;
	if ( $id && ( ! get_post( $id ) || 'trash' === get_post_status( $id ) ) ) {
		$id = 0;
	}
	if ( $id ) {
		$args['ID'] = $id;
		wp_update_post( wp_slash( $args ) );
	} else {
		$id = wp_insert_post( wp_slash( $args ) );
	}
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, is_string( $v ) ? wp_slash( $v ) : $v );
	}
	$ids[ $chave ] = $id;
	update_option( TT_ROTEIROS_OPT, $ids );
	return $id;
}

function tt_roteiros_instalar_modelos() {
	if ( ! tt_roteiros_tem_elementor() ) {
		return new WP_Error( 'sem_elementor', 'Elementor e Elementor Pro precisam estar ativos.' );
	}
	$nome_site = get_bloginfo( 'name' );
	$base      = array( '_elementor_edit_mode' => 'builder', '_elementor_version' => defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0' );

	// 1) Card (Loop Item)
	$card = tt_roteiros_gravar( 'card', array(
		'post_type' => 'elementor_library', 'post_status' => 'publish', 'post_title' => 'Roteiros — card',
	), $base + array(
		'_elementor_template_type' => 'loop-item',
		'_elementor_data'         => tt_roteiros_json( 'card' ),
		'_elementor_page_settings' => array(),
	) );
	if ( $card ) {
		wp_set_object_terms( $card, 'loop-item', 'elementor_library_type' );
	}

	// 2) Roteiro individual (Single) — vale para todo o tipo "roteiros"
	$single = tt_roteiros_gravar( 'single', array(
		'post_type' => 'elementor_library', 'post_status' => 'publish', 'post_title' => 'Roteiros — página do roteiro',
	), $base + array(
		'_elementor_template_type'  => 'single-page',
		'_elementor_data'           => tt_roteiros_json( 'single' ),
		'_elementor_page_settings'  => array( 'page_template' => 'elementor_header_footer', 'preview_type' => 'single/roteiros' ),
		'_elementor_conditions'     => array( 'include/singular/roteiros' ),
	) );
	if ( $single ) {
		wp_set_object_terms( $single, 'single-page', 'elementor_library_type' );
	}

	// 3) Arquivo: a lista em /roteiros/ (é o que o menu do site aponta)
	$arquivo = tt_roteiros_gravar( 'arquivo', array(
		'post_type' => 'elementor_library', 'post_status' => 'publish', 'post_title' => 'Roteiros — lista (/roteiros/)',
	), $base + array(
		'_elementor_template_type' => 'archive',
		'_elementor_data'          => tt_roteiros_json( 'arquivo', array( '{{CARD_ID}}' => (string) $card, '{{NOME_SITE}}' => $nome_site ) ),
		'_elementor_page_settings' => array( 'page_template' => 'elementor_header_footer', 'preview_type' => 'archive/roteiros' ),
		'_elementor_conditions'    => array( 'include/archive/roteiros_archive' ),
	) );
	if ( $arquivo ) {
		wp_set_object_terms( $arquivo, 'archive', 'elementor_library_type' );
	}

	// O Elementor Pro só reconhece a condição depois que o cache dele é refeito.
	try {
		$modulo = \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'theme-builder' );
		if ( $modulo ) {
			$modulo->get_conditions_manager()->get_cache()->regenerate();
		}
	} catch ( \Throwable $e ) {
		error_log( 'TravelTec Roteiros: cache de condições — ' . $e->getMessage() );
	}
	try {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	} catch ( \Throwable $e ) {
		error_log( 'TravelTec Roteiros: limpar CSS — ' . $e->getMessage() );
	}
	flush_rewrite_rules( true );
	update_option( 'tt_roteiros_versao_modelos', TT_ROTEIROS_VERSAO_MODELOS );
	return array( 'card' => $card, 'single' => $single, 'arquivo' => $arquivo );
}

register_activation_hook( __FILE__, function () {
	// O tipo precisa existir antes de regravar as regras de URL.
	tt_roteiros_registrar_tipo();
	if ( tt_roteiros_tem_elementor() && ! get_option( TT_ROTEIROS_OPT ) ) {
		tt_roteiros_instalar_modelos();
	} else {
		set_transient( 'tt_roteiros_aviso', 1, DAY_IN_SECONDS );
		flush_rewrite_rules( false );
	}
} );

/* ------------------------------------------------------------------ Tela "Roteiros > Modelos" */

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=roteiros', 'Modelos do Elementor', 'Modelos do Elementor', 'manage_options', 'tt-roteiros-modelos', 'tt_roteiros_tela' );
} );

add_action( 'admin_post_tt_roteiros_instalar', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Sem permissão.' );
	}
	check_admin_referer( 'tt_roteiros_instalar' );
	$r = tt_roteiros_instalar_modelos();
	wp_safe_redirect( add_query_arg( array( 'post_type' => 'roteiros', 'page' => 'tt-roteiros-modelos', 'ok' => is_wp_error( $r ) ? 0 : 1 ), admin_url( 'edit.php' ) ) );
	exit;
} );

add_action( 'admin_post_tt_roteiros_verificar', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Sem permissão.' );
	}
	check_admin_referer( 'tt_roteiros_verificar' );
	delete_site_transient( TT_ROTEIROS_CACHE );
	tt_roteiros_release( true );
	delete_site_transient( 'update_plugins' ); // força o WordPress a perguntar de novo
	wp_safe_redirect( add_query_arg( array( 'post_type' => 'roteiros', 'page' => 'tt-roteiros-modelos', 'checado' => 1 ), admin_url( 'edit.php' ) ) );
	exit;
} );

/** Linha de versão: o que está instalado, o que existe no repositório e o botão de procurar. */
function tt_roteiros_bloco_versao() {
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

function tt_roteiros_tela() {
	$ids    = get_option( TT_ROTEIROS_OPT, array() );
	$rotulo = array( 'arquivo' => 'Lista em /roteiros/', 'card' => 'Card do roteiro', 'single' => 'Página do roteiro' );
	echo '<div class="wrap"><h1>Roteiros — modelos do Elementor</h1>';
	tt_roteiros_bloco_versao();
	if ( isset( $_GET['ok'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '1' === $_GET['ok'] ? '<div class="notice notice-success"><p>Modelos instalados.</p></div>'
			: '<div class="notice notice-error"><p>Não foi possível instalar: Elementor e Elementor Pro precisam estar ativos.</p></div>'; // phpcs:ignore
	}
	if ( ! tt_roteiros_tem_elementor() ) {
		echo '<div class="notice notice-warning"><p><strong>Elementor Pro não está ativo.</strong> As páginas dependem dele (Theme Builder e Loop Grid).</p></div>';
	}
	echo '<table class="widefat striped" style="max-width:640px"><tbody>';
	foreach ( $rotulo as $k => $r ) {
		$id   = isset( $ids[ $k ] ) ? (int) $ids[ $k ] : 0;
		$viva = $id && get_post( $id ) && 'trash' !== get_post_status( $id );
		echo '<tr><td>' . esc_html( $r ) . '</td><td>' . ( $viva ? '<a href="' . esc_url( get_edit_post_link( $id ) ) . '">Instalado (#' . (int) $id . ')</a>' : 'Não instalado' ) . '</td>';
		$ver = $viva && 'arquivo' === $k ? '<a href="' . esc_url( get_post_type_archive_link( 'roteiros' ) ) . '" target="_blank">Ver lista</a>' : '';
		echo '<td>' . $ver . '</td></tr>';
	}
	echo '</tbody></table>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:16px">';
	wp_nonce_field( 'tt_roteiros_instalar' );
	echo '<input type="hidden" name="action" value="tt_roteiros_instalar">';
	submit_button( $ids ? 'Reinstalar modelos' : 'Instalar modelos', 'primary', 'submit', false );
	echo '</form><p class="description" style="max-width:640px">Reinstalar volta os três modelos ao padrão — o que você editou neles no Elementor é substituído. Os roteiros não são afetados.<br>As cores e fontes vêm do Kit do Elementor do site (Global Colors e tipografia), então cada site sai com a própria marca.</p></div>';
}

add_action( 'admin_notices', function () {
	if ( get_transient( 'tt_roteiros_aviso' ) && current_user_can( 'manage_options' ) ) {
		delete_transient( 'tt_roteiros_aviso' );
		echo '<div class="notice notice-warning is-dismissible"><p><strong>TravelTec Roteiros:</strong> ative o Elementor Pro e depois clique em <em>Roteiros › Modelos do Elementor › Instalar modelos</em>.</p></div>';
	}
} );
