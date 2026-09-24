<?php
/**
 * Plugin Name: Voucher Tec - Travel Tec
 * Description: Site padrão das agências Travel Tec: roteiros (tipo de conteúdo, campos, API do n8n / Cliente Ideal), cabeçalho, rodapé, blog, Sobre, Contato, Cotação, páginas de obrigado, formulários e captação de leads (UTMs + webhook). Dados, cores e fontes da agência aplicados de uma vez pelo painel ou pela API.
 * Version:     2.0.9
 * Author:      TravelTec
 * Text Domain: traveltec-roteiros
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// O slug continua traveltec-roteiros: é assim que os sites da 1.x recebem esta versão pela atualização automática.
define( 'TT_ROTEIROS_VERSION', '2.0.9' );
define( 'TT_ROTEIROS_DIR', plugin_dir_path( __FILE__ ) );
define( 'TT_ROTEIROS_URL', plugin_dir_url( __FILE__ ) );
define( 'TT_ROTEIROS_BASENAME', plugin_basename( __FILE__ ) );
// Última versão em que os modelos do Elementor mudaram.
define( 'TT_VOUCHER_VERSAO_MODELOS', '2.0.9' );
// Option da 1.x (IDs dos 3 modelos de roteiros) — só serve para reconhecer sites antigos.
define( 'TT_ROTEIROS_OPT', 'tt_roteiros_modelos' );

require_once TT_ROTEIROS_DIR . 'includes/atualizador.php';
require_once TT_ROTEIROS_DIR . 'includes/dados.php';
require_once TT_ROTEIROS_DIR . 'includes/estilo.php';
require_once TT_ROTEIROS_DIR . 'includes/instalador.php';
require_once TT_ROTEIROS_DIR . 'includes/captacao.php';
require_once TT_ROTEIROS_DIR . 'includes/api.php';
require_once TT_ROTEIROS_DIR . 'includes/admin.php';

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
 * Em /roteiros/ quem escreve o título da aba é o Rank Math, com o modelo dele
 * ("%pt_plural% Archive %sep% %sitename%") — daí sair "Roteiros Archive".
 * Tira só esse "Archive" que o próprio Rank Math acrescenta; um título escrito à mão
 * em Rank Math › Títulos e Meta › Roteiros passa direto, sem ser mexido.
 */
add_filter( 'rank_math/frontend/title', function ( $titulo ) {
	$tipo = get_post_type_object( 'roteiros' );
	if ( ! is_post_type_archive( 'roteiros' ) || ! $tipo ) {
		return $titulo;
	}
	return str_replace( $tipo->labels->name . ' Archive', $tipo->labels->name, $titulo );
} );

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

/* ------------------------------------------------------------------ Ativação */

/**
 * Site em preparação (nada instalado, nem da 1.x) com Elementor Pro ativo: instala o site padrão
 * na hora. Nos outros casos só avisa — a instalação fica no painel Voucher Tec ou na API.
 */
register_activation_hook( __FILE__, function () {
	tt_roteiros_registrar_tipo();
	$novo = ! get_option( TT_ROTEIROS_OPT ) && ! get_option( TT_VOUCHER_OPT_IDS );
	$dep  = tt_voucher_dependencias();
	if ( $novo && $dep['elementor'] && $dep['elementor_pro'] ) {
		tt_voucher_instalar();
	} else {
		set_transient( 'tt_roteiros_aviso', 1, DAY_IN_SECONDS );
		flush_rewrite_rules( false );
	}
} );
