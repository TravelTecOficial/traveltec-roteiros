<?php
/**
 * Destinos: tipo de conteúdo, campos, API REST (campo "acf", como nos roteiros), shortcodes da página do destino
 * e os arquivos que ela usa. A tela de edição está em destino-editor.php; a lógica dos campos, em destino-dados.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ Tipo de conteúdo e campos */

/**
 * Os destinos estão ligados neste site? Só depois de instalada a aba Destinos (o modelo da página do destino existe).
 * Antes disso o tipo existe no painel e na REST, mas não toma os endereços /destinos/ e /destinos/<nome>/ —
 * há sites com PÁGINAS nesses endereços, e atualizar o plugin não pode tirá-las do ar.
 */
function tt_destinos_ativo() {
	$ids = tt_voucher_ids();
	return ! empty( $ids['docs']['destino-single'] ) && tt_voucher_vivo( (int) $ids['docs']['destino-single'] );
}

/**
 * Registra o tipo. $de_novo: registra outra vez o tipo do próprio plugin (a instalação da aba Destinos usa, para
 * ligar os endereços na mesma hora).
 */
function tt_destinos_registrar_tipo( $de_novo = false ) {
	if ( post_type_exists( 'destinos' ) ) {
		// Se o site já tem o tipo (CPT UI, ACF...), não registra por cima.
		if ( ! $de_novo || empty( $GLOBALS['tt_destinos_do_plugin'] ) ) {
			return;
		}
	}
	$ativo                            = tt_destinos_ativo();
	$GLOBALS['tt_destinos_do_plugin'] = true;
	register_post_type( 'destinos', array(
		'labels'              => array(
			'name'          => 'Destinos',
			'singular_name' => 'Destino',
			'add_new_item'  => 'Adicionar novo destino',
			'edit_item'     => 'Editar destino',
			'all_items'     => 'Todos os destinos',
			'search_items'  => 'Buscar destinos',
			'not_found'     => 'Nenhum destino encontrado.',
			'archives'      => 'Destinos',
		),
		'public'              => $ativo,
		'publicly_queryable'  => $ativo,
		'exclude_from_search' => ! $ativo,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => $ativo,
		'has_archive'         => $ativo ? 'destinos' : false, // /destinos/ lista os destinos; /destinos/<slug>/ abre um
		'show_in_rest'        => true,
		'rest_base'           => 'destinos',
		'menu_icon'           => 'dashicons-location-alt',
		'menu_position'       => 22,
		'supports'            => array( 'title', 'thumbnail', 'excerpt', 'revisions' ),
		'rewrite'             => $ativo ? array( 'slug' => 'destinos', 'with_front' => false ) : false,
	) );
}

add_action( 'init', function () {
	tt_destinos_registrar_tipo();
	foreach ( array_keys( tt_destinos_campos() ) as $chave ) {
		register_post_meta( 'destinos', $chave, array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => function ( $valor ) use ( $chave ) {
				return tt_destino_limpar( $chave, $valor );
			},
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		) );
	}
}, 20 );

// O tipo é novo: quem atualiza o plugin precisa das regras de endereço refeitas uma vez (senão /destinos/ dá 404).
add_action( 'init', function () {
	if ( get_option( 'tt_destinos_regras' ) !== TT_ROTEIROS_VERSION ) {
		flush_rewrite_rules( false );
		update_option( 'tt_destinos_regras', TT_ROTEIROS_VERSION );
	}
}, 99 );

// Destino publicado (com a aba Destinos instalada): o item "Destinos" entra nos menus do site.
// Prioridade 20: depois de o WordPress zerar a contagem de posts do tipo.
add_action( 'transition_post_status', function ( $novo, $antigo, $post ) {
	if ( 'publish' === $novo && 'publish' !== $antigo && 'destinos' === $post->post_type && tt_destinos_ativo() ) {
		tt_voucher_menus_ligar();
	}
}, 20, 3 );

/** Campo "acf" na API REST — mesmo formato dos roteiros: { title, excerpt, acf: { ..., imagem_destaque } }. */
add_action( 'rest_api_init', function () {
	$props = array(
		'imagem_destaque'   => array( 'type' => array( 'integer', 'null' ) ),
		'texto_de_abertura' => array( 'type' => array( 'string', 'null' ) ),
		'o_que_fazer'       => array( 'type' => array( 'string', 'array', 'null' ), 'items' => array( 'type' => 'string' ) ),
		'quando_ir'         => array( 'type' => array( 'string', 'null' ) ),
		'galeria'           => array( 'type' => array( 'string', 'array', 'null' ), 'items' => array( 'type' => 'integer' ) ),
	);
	register_rest_field( 'destinos', 'acf', array(
		'get_callback'    => function ( $post ) {
			$saida = array( 'imagem_destaque' => (int) get_post_thumbnail_id( $post['id'] ) ?: null );
			foreach ( array_keys( tt_destinos_campos() ) as $chave ) {
				$saida[ $chave ] = (string) get_post_meta( $post['id'], $chave, true );
			}
			return $saida;
		},
		'update_callback' => function ( $valor, $post ) {
			if ( ! is_array( $valor ) ) {
				return true;
			}
			foreach ( array_keys( tt_destinos_campos() ) as $chave ) {
				if ( array_key_exists( $chave, $valor ) ) {
					update_post_meta( $post->ID, $chave, tt_destino_limpar( $chave, $valor[ $chave ] ) );
				}
			}
			if ( ! empty( $valor['imagem_destaque'] ) && get_post( (int) $valor['imagem_destaque'] ) ) {
				set_post_thumbnail( $post->ID, (int) $valor['imagem_destaque'] );
			}
			return true;
		},
		'schema'          => array( 'description' => 'Campos do destino', 'type' => 'object', 'properties' => $props ),
	) );
} );

/* ------------------------------------------------------------------ Página do destino (shortcodes do modelo) */

/** Formulário de cotação instalado pelo plugin (e ainda vivo), ou 0. */
function tt_destino_form_id() {
	$ids = tt_voucher_ids();
	$id  = isset( $ids['forms']['cotacao'] ) ? (int) $ids['forms']['cotacao'] : 0;
	// Sem o JetFormBuilder ativo o shortcode dele não existe e o código apareceria escrito na página.
	return $id && tt_voucher_vivo( $id ) && shortcode_exists( 'jet_fb_form' ) ? $id : 0;
}

/** [tt_destino_texto campo="texto_de_abertura|quando_ir" titulo="..."] */
add_shortcode( 'tt_destino_texto', function ( $atts ) {
	$atts  = shortcode_atts( array( 'campo' => '', 'titulo' => '' ), $atts, 'tt_destino_texto' );
	$campo = sanitize_key( $atts['campo'] );
	if ( ! in_array( $campo, array( 'texto_de_abertura', 'quando_ir' ), true ) ) {
		return '';
	}
	return tt_destino_texto_html( wpautop( (string) get_post_meta( get_the_ID(), $campo, true ) ), $atts['titulo'] );
} );

/** [tt_destino_lista titulo="O que fazer"] */
add_shortcode( 'tt_destino_lista', function ( $atts ) {
	$atts = shortcode_atts( array( 'titulo' => '' ), $atts, 'tt_destino_lista' );
	return tt_destino_lista_html( get_post_meta( get_the_ID(), 'o_que_fazer', true ), $atts['titulo'] );
} );

/** [tt_destino_galeria titulo="Galeria"] — foto que saiu da Biblioteca de Mídia é ignorada. */
add_shortcode( 'tt_destino_galeria', function ( $atts ) {
	$atts  = shortcode_atts( array( 'titulo' => '' ), $atts, 'tt_destino_galeria' );
	$fotos = array();
	foreach ( tt_destino_galeria_ids( get_post_meta( get_the_ID(), 'galeria', true ) ) as $id ) {
		$grande = wp_get_attachment_image_url( $id, 'large' );
		if ( ! $grande ) {
			continue;
		}
		$mini    = wp_get_attachment_image_url( $id, 'medium_large' );
		$fotos[] = array( 'grande' => $grande, 'mini' => $mini ? $mini : $grande, 'alt' => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
	}
	return tt_destino_galeria_html( $fotos, 'tt-destino-' . (int) get_the_ID(), $atts['titulo'] );
} );

/** [tt_destino_form titulo="..."] — o formulário de cotação do plugin; sem ele instalado, não mostra nada. */
add_shortcode( 'tt_destino_form', function ( $atts ) {
	$atts = shortcode_atts( array( 'titulo' => '' ), $atts, 'tt_destino_form' );
	$id   = tt_destino_form_id();
	if ( ! $id ) {
		return '';
	}
	return tt_destino_titulo_html( $atts['titulo'] )
		. do_shortcode( '[jet_fb_form form_id="' . $id . '" submit_type="ajax" required_mark="*" fields_layout="column" fields_label_tag="label" load_nonce="hide" use_csrf=""]' );
} );

/** Na página do destino: estilo da lista e da galeria, e o campo "Destino" do formulário já com o nome. */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_singular( 'destinos' ) ) {
		return;
	}
	wp_enqueue_style( 'tt-destinos', TT_ROTEIROS_URL . 'assets/destinos.css', array(), TT_ROTEIROS_VERSION );
	wp_enqueue_script( 'tt-destino', TT_ROTEIROS_URL . 'assets/destino.js', array(), TT_ROTEIROS_VERSION, true );
	wp_localize_script( 'tt-destino', 'ttDestino', array( 'nome' => html_entity_decode( get_the_title( get_queried_object_id() ), ENT_QUOTES, 'UTF-8' ) ) );
} );
