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

/**
 * Campo "acf" na API REST — mesmo formato dos roteiros: { title, excerpt, acf: { ..., imagem_destaque } }.
 * Leitura: listas como array (perfis, o_que_fazer, galeria e as listas JSON), versao como número. Gravação: aceita
 * array ou texto, limpa e corta nos limites do contrato (tt_destino_limpar). Campo que não veio fica como estava.
 */
function tt_destino_rest_ler( $post_id ) {
	$saida = array( 'imagem_destaque' => (int) get_post_thumbnail_id( $post_id ) ?: null );
	foreach ( array_keys( tt_destinos_campos() ) as $chave ) {
		$saida[ $chave ] = tt_destino_valor_rest( $chave, get_post_meta( $post_id, $chave, true ) );
	}
	return $saida;
}

function tt_destino_rest_gravar( $valor, $post_id ) {
	if ( ! is_array( $valor ) ) {
		return true;
	}
	foreach ( array_keys( tt_destinos_campos() ) as $chave ) {
		if ( array_key_exists( $chave, $valor ) ) {
			tt_destino_gravar_meta( $post_id, $chave, tt_destino_limpar( $chave, $valor[ $chave ] ) );
		}
	}
	if ( ! empty( $valor['imagem_destaque'] ) && get_post( (int) $valor['imagem_destaque'] ) ) {
		set_post_thumbnail( $post_id, (int) $valor['imagem_destaque'] );
	}
	return true;
}

add_action( 'rest_api_init', function () {
	$props = array( 'imagem_destaque' => array( 'type' => array( 'integer', 'null' ) ) );
	foreach ( tt_destinos_campos() as $chave => $campo ) {
		if ( in_array( $campo[1], array( 'linhas', 'ids', 'json', 'perfis' ), true ) ) {
			$props[ $chave ] = array( 'type' => array( 'string', 'array', 'null' ) );
		} elseif ( 'versao' === $campo[1] ) {
			$props[ $chave ] = array( 'type' => array( 'integer', 'string', 'null' ) );
		} else {
			$props[ $chave ] = array( 'type' => array( 'string', 'null' ) );
		}
	}
	register_rest_field( 'destinos', 'acf', array(
		'get_callback'    => function ( $post ) {
			return tt_destino_rest_ler( $post['id'] );
		},
		'update_callback' => function ( $valor, $post ) {
			return tt_destino_rest_gravar( $valor, $post->ID );
		},
		'schema'          => array( 'description' => 'Campos do destino', 'type' => 'object', 'properties' => $props ),
	) );
} );

/* ------------------------------------------------------------------ Página do destino (shortcodes do modelo) */

/*
 * Cada seção da página é um shortcode que devolve '' quando não há dado — o Elementor não imprime widget vazio, e
 * a seção some com o título. Destino v1 (só abertura, o que fazer, quando ir e galeria) sai com as seções que tiver.
 * Os shortcodes da 2.2 (tt_destino_texto, tt_destino_lista) continuam, para os sites com o modelo antigo.
 */

/** Formulário de cotação instalado pelo plugin (e ainda vivo), ou 0. */
function tt_destino_form_id() {
	$ids = tt_voucher_ids();
	$id  = isset( $ids['forms']['cotacao'] ) ? (int) $ids['forms']['cotacao'] : 0;
	// Sem o JetFormBuilder ativo o shortcode dele não existe e o código apareceria escrito na página.
	return $id && tt_voucher_vivo( $id ) && shortcode_exists( 'jet_fb_form' ) ? $id : 0;
}

function tt_destino_meta( $chave ) {
	return get_post_meta( get_the_ID(), $chave, true );
}

/** Destino nacional: as seções só de internacional (documentação, moeda, idioma, fuso) não aparecem. */
function tt_destino_nacional() {
	return 'nacional' === tt_destino_meta( 'escopo' );
}

/** Itens da lista JSON com 'foto_url' (foto que saiu da Biblioteca de Mídia: sem imagem). */
function tt_destino_com_fotos( $itens ) {
	foreach ( $itens as $i => $item ) {
		$url                     = ! empty( $item['foto'] ) ? wp_get_attachment_image_url( (int) $item['foto'], 'medium_large' ) : false;
		$itens[ $i ]['foto_url'] = $url ? $url : '';
	}
	return $itens;
}

/** [tt_destino_texto campo="texto_de_abertura|quando_ir|como_chegar|documentacao" titulo="..."] */
add_shortcode( 'tt_destino_texto', function ( $atts ) {
	$atts  = shortcode_atts( array( 'campo' => '', 'titulo' => '' ), $atts, 'tt_destino_texto' );
	$campo = sanitize_key( $atts['campo'] );
	if ( ! in_array( $campo, array( 'texto_de_abertura', 'quando_ir', 'como_chegar', 'documentacao' ), true ) ) {
		return '';
	}
	if ( 'documentacao' === $campo && tt_destino_nacional() ) {
		return '';
	}
	return tt_destino_texto_html( wpautop( (string) tt_destino_meta( $campo ) ), $atts['titulo'] );
} );

/** [tt_destino_lista titulo="O que fazer"] — lista simples da v1. */
add_shortcode( 'tt_destino_lista', function ( $atts ) {
	$atts = shortcode_atts( array( 'titulo' => '' ), $atts, 'tt_destino_lista' );
	return tt_destino_lista_html( tt_destino_meta( 'o_que_fazer' ), $atts['titulo'] );
} );

/** [tt_destino_galeria titulo="Galeria"] — foto que saiu da Biblioteca de Mídia é ignorada. */
add_shortcode( 'tt_destino_galeria', function ( $atts ) {
	$atts  = shortcode_atts( array( 'titulo' => '' ), $atts, 'tt_destino_galeria' );
	$fotos = array();
	foreach ( tt_destino_galeria_ids( tt_destino_meta( 'galeria' ) ) as $id ) {
		$grande = wp_get_attachment_image_url( $id, 'large' );
		if ( ! $grande ) {
			continue;
		}
		$mini    = wp_get_attachment_image_url( $id, 'medium_large' );
		$fotos[] = array( 'grande' => $grande, 'mini' => $mini ? $mini : $grande, 'alt' => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
	}
	return tt_destino_galeria_html( $fotos, 'tt-destino-' . (int) get_the_ID(), $atts['titulo'] );
} );

/** [tt_destino_subchamada estilo="hero|card"] — a frase de benefício, no topo da página e no card da lista. */
add_shortcode( 'tt_destino_subchamada', function ( $atts ) {
	$atts = shortcode_atts( array( 'estilo' => 'hero' ), $atts, 'tt_destino_subchamada' );
	return tt_destino_subchamada_html( tt_destino_meta( 'subchamada' ), $atts['estilo'] );
} );

/** [tt_destino_visao_geral titulo="Visão geral"] — abertura + destaques (perfis e duração ideal). */
add_shortcode( 'tt_destino_visao_geral', function ( $atts ) {
	$atts = shortcode_atts( array( 'titulo' => 'Visão geral' ), $atts, 'tt_destino_visao_geral' );
	return tt_destino_secao_html( 'visao-geral', $atts['titulo'], array(
		tt_destino_bloco_html( wpautop( (string) tt_destino_meta( 'texto_de_abertura' ) ) ),
		tt_destino_destaques_html( tt_destino_meta( 'perfis' ), tt_destino_meta( 'duracao_ideal' ) ),
	) );
} );

/** [tt_destino_praticas titulo="Informações práticas"] — quando ir, clima, como chegar e (internacional) documentação, moeda, idioma, fuso. */
add_shortcode( 'tt_destino_praticas', function ( $atts ) {
	$atts     = shortcode_atts( array( 'titulo' => 'Informações práticas' ), $atts, 'tt_destino_praticas' );
	$nacional = tt_destino_nacional();
	return tt_destino_secao_html( 'praticas', $atts['titulo'], array(
		tt_destino_bloco_html( wpautop( (string) tt_destino_meta( 'quando_ir' ) ), 'Quando ir' ),
		tt_destino_clima_html( tt_destino_meta( 'clima' ), 'Clima por estação' ),
		tt_destino_bloco_html( wpautop( (string) tt_destino_meta( 'como_chegar' ) ), 'Como chegar' ),
		$nacional ? '' : tt_destino_bloco_html( (string) tt_destino_meta( 'documentacao' ), 'Documentação' ),
		$nacional ? '' : tt_destino_info_html( tt_destino_meta( 'moeda' ), tt_destino_meta( 'idioma' ), tt_destino_meta( 'fuso' ) ),
	) );
} );

/** [tt_destino_o_que_fazer titulo="O que fazer"] — pontos turísticos, passeios (3 ou mais), gastronomia e a lista v1. */
add_shortcode( 'tt_destino_o_que_fazer', function ( $atts ) {
	$atts     = shortcode_atts( array( 'titulo' => 'O que fazer' ), $atts, 'tt_destino_o_que_fazer' );
	$passeios = tt_destino_json_ler( 'passeios', tt_destino_meta( 'passeios' ) );
	return tt_destino_secao_html( 'o-que-fazer', $atts['titulo'], array(
		tt_destino_cards_html( tt_destino_com_fotos( tt_destino_json_ler( 'pontos_turisticos', tt_destino_meta( 'pontos_turisticos' ) ) ), 'nome', 'descricao', 'Pontos turísticos' ),
		count( $passeios ) >= 3 ? tt_destino_cards_html( tt_destino_com_fotos( $passeios ), 'titulo', 'duracao', 'Passeios', 'Foto: Viator' ) : '',
		tt_destino_gastronomia_html( tt_destino_meta( 'gastronomia' ), 'Gastronomia' ),
		tt_destino_lista_html( tt_destino_meta( 'o_que_fazer' ) ),
	) );
} );

/** [tt_destino_onde_ficar titulo="Onde ficar"] */
add_shortcode( 'tt_destino_onde_ficar', function ( $atts ) {
	$atts = shortcode_atts( array( 'titulo' => 'Onde ficar' ), $atts, 'tt_destino_onde_ficar' );
	return tt_destino_secao_html( 'onde-ficar', $atts['titulo'], array( tt_destino_onde_ficar_html( tt_destino_meta( 'onde_ficar' ) ) ) );
} );

/** [tt_destino_faq titulo="Perguntas frequentes"] — o JSON-LD FAQPage vai no <head> (abaixo). */
add_shortcode( 'tt_destino_faq', function ( $atts ) {
	$atts = shortcode_atts( array( 'titulo' => 'Perguntas frequentes' ), $atts, 'tt_destino_faq' );
	return tt_destino_secao_html( 'faq', $atts['titulo'], array( tt_destino_faq_html( tt_destino_meta( 'faq' ) ) ) );
} );

/** [tt_destino_atualizado] — "Atualizado em dd/mm/aaaa"; sem a data do sistema, a da última edição do post. */
add_shortcode( 'tt_destino_atualizado', function () {
	$data = (string) tt_destino_meta( 'atualizado_em' );
	if ( '' === $data && function_exists( 'get_the_modified_date' ) ) {
		$data = (string) get_the_modified_date( 'Y-m-d' );
	}
	return tt_destino_atualizado_html( $data );
} );

/**
 * [tt_destino_form titulo="..."] — o formulário de cotação do plugin. Sem ele instalado: link do WhatsApp do site,
 * se houver; senão, nada.
 */
add_shortcode( 'tt_destino_form', function ( $atts ) {
	$atts = shortcode_atts( array( 'titulo' => '' ), $atts, 'tt_destino_form' );
	$id   = tt_destino_form_id();
	if ( ! $id ) {
		$fone = function_exists( 'tt_voucher_valor' ) ? preg_replace( '/\D/', '', (string) tt_voucher_valor( 'whatsapp_digitos' ) ) : '';
		if ( '' === $fone ) {
			return '';
		}
		$texto = rawurlencode( 'Olá! Quero uma cotação para ' . html_entity_decode( get_the_title(), ENT_QUOTES, 'UTF-8' ) . '.' );
		return tt_destino_titulo_html( $atts['titulo'] )
			. '<p><a class="elementor-button tt-destino-whatsapp" href="' . esc_url( 'https://wa.me/' . $fone . '?text=' . $texto ) . '" target="_blank" rel="noopener">Fale conosco no WhatsApp</a></p>';
	}
	return tt_destino_titulo_html( $atts['titulo'] )
		. do_shortcode( '[jet_fb_form form_id="' . $id . '" submit_type="ajax" required_mark="*" fields_layout="column" fields_label_tag="label" load_nonce="hide" use_csrf=""]' );
} );

/** FAQ marcado para o Google (FAQPage) na página do destino — não depende do Rank Math. */
add_action( 'wp_head', function () {
	if ( ! is_singular( 'destinos' ) ) {
		return;
	}
	echo tt_destino_faq_jsonld( get_post_meta( get_queried_object_id(), 'faq', true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON montado e escapado (JSON_HEX_TAG) em tt_destino_faq_jsonld
} );

/** Página do destino e lista /destinos/: estilo das seções; na página, o campo "Destino" do formulário já com o nome. */
add_action( 'wp_enqueue_scripts', function () {
	$pagina = is_singular( 'destinos' );
	if ( ! $pagina && ! is_post_type_archive( 'destinos' ) ) {
		return;
	}
	wp_enqueue_style( 'tt-destinos', TT_ROTEIROS_URL . 'assets/destinos.css', array(), TT_ROTEIROS_VERSION );
	if ( ! $pagina ) {
		return;
	}
	wp_enqueue_script( 'tt-destino', TT_ROTEIROS_URL . 'assets/destino.js', array(), TT_ROTEIROS_VERSION, true );
	wp_localize_script( 'tt-destino', 'ttDestino', array( 'nome' => html_entity_decode( get_the_title( get_queried_object_id() ), ENT_QUOTES, 'UTF-8' ) ) );
} );
