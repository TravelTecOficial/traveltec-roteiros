<?php
/**
 * Tela de edição do destino (caixa "Dados do destino" em abas), salvamento e colunas da lista.
 * Usa o mesmo CSS e JS da tela do roteiro (assets/roteiro-editor.*); a lógica dos campos está em destino-dados.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ Peças da caixa */

function tt_destino_campo_editor( $chave, $rotulo, $valor, $ajuda = '' ) {
	echo '<div class="tt-rot__campo"><label for="tt_dest_' . esc_attr( $chave ) . '">' . esc_html( $rotulo ) . '</label>';
	echo '<textarea class="widefat" rows="8" id="tt_dest_' . esc_attr( $chave ) . '" name="tt_destinos[' . esc_attr( $chave ) . ']" data-tt-editor>' . esc_textarea( $valor ) . '</textarea>';
	if ( '' !== $ajuda ) {
		echo '<p class="description">' . esc_html( $ajuda ) . '</p>';
	}
	echo '</div>';
}

function tt_destino_painel_resumo( $v ) {
	// É o campo de resumo do próprio WordPress (name e id "excerpt"): ele grava sozinho no resumo do post, e o
	// salvamento automático lê o campo pelo id — com outro id, o rascunho perderia o resumo.
	echo '<div class="tt-rot__campo"><label for="excerpt">Resumo</label>';
	echo '<textarea class="widefat" rows="3" id="excerpt" name="excerpt" data-tt-contador="160">' . esc_textarea( $v['resumo'] ) . '</textarea>';
	echo '<p class="description">Uma ou duas frases. Aparece no card do destino, na lista /destinos/. <span class="tt-rot__contador" data-tt-contador-saida></span></p></div>';
	tt_destino_campo_editor( 'texto_de_abertura', 'Texto de abertura', $v['texto_de_abertura'], 'O texto que abre a página do destino, logo abaixo da foto de capa.' );
}

function tt_destino_painel_fazer( $v ) {
	echo '<div class="tt-rot__itens" data-tt-itens data-nome="tt_destinos[o_que_fazer][]">';
	echo '<input type="hidden" name="tt_destinos[o_que_fazer_presente]" value="1">';
	echo '<p class="description">Um passeio ou atração por linha. Use as setas para mudar a ordem.</p>';
	echo '<ul class="tt-rot__itens-lista" data-tt-itens-lista>';
	foreach ( tt_destino_lista_ler( $v['o_que_fazer'] ) as $item ) {
		echo '<li class="tt-rot__item"><input type="text" class="widefat" name="tt_destinos[o_que_fazer][]" value="' . esc_attr( $item ) . '"></li>';
	}
	echo '</ul>';
	echo '<p><button type="button" class="button button-primary" data-tt-add-item>Adicionar item</button></p>';
	echo '</div>';
}

function tt_destino_painel_quando( $v ) {
	tt_destino_campo_editor( 'quando_ir', 'Quando ir', $v['quando_ir'], 'Melhor época, clima e o que muda ao longo do ano.' );
}

function tt_destino_painel_fotos( $v ) {
	$fotos = array();
	foreach ( tt_destino_galeria_ids( $v['galeria'] ) as $id ) {
		$url = wp_get_attachment_image_url( $id, 'medium' );
		if ( $url ) {
			$fotos[] = array( 'id' => $id, 'url' => $url, 'alt' => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		}
	}
	$json = wp_json_encode( $fotos );
	echo '<div class="tt-rot__fotos" data-tt-fotos data-urls="' . esc_attr( $json ) . '" data-titulo="Escolher fotos do destino" data-botao="Adicionar ao destino">';
	echo '<input type="hidden" value="0" data-tt-alterado>';
	echo '<input type="hidden" name="tt_destinos[galeria]" value="' . esc_attr( $json ) . '" data-tt-fotos-json>';
	echo '<p class="description">A galeria da página do destino. Use as setas para mudar a ordem. A capa é a Imagem destacada.</p>';
	echo '<ul class="tt-rot__miniaturas" data-tt-lista></ul>';
	echo '<p><button type="button" class="button button-primary" data-tt-add-fotos>Adicionar da Biblioteca de Mídia</button> <span class="tt-rot__contagem" data-tt-contagem></span></p>';
	echo '</div>';
}

/** A caixa "Dados do destino". */
function tt_destino_desenhar_caixa( $post ) {
	wp_nonce_field( 'tt_destinos_salvar', 'tt_destinos_nonce' );
	$v = array( 'resumo' => (string) $post->post_excerpt );
	foreach ( array_keys( tt_destinos_campos() ) as $chave ) {
		$v[ $chave ] = (string) get_post_meta( $post->ID, $chave, true );
	}
	echo '<div class="tt-rot tt-dest" data-tt-rot>';
	echo '<p class="tt-rot__topo">O nome do destino é o título acima e a foto de capa é a <strong>Imagem destacada</strong> (coluna ao lado). O que ficar vazio não aparece no site.</p>';
	echo '<div class="tt-rot__abas" role="tablist" aria-label="Seções do destino">';
	$primeira = true;
	foreach ( tt_destino_abas() as $id => $aba ) {
		$cheia = tt_roteiro_aba_preenchida( $aba[1], $v );
		echo '<button type="button" role="tab" class="tt-rot__aba' . ( $primeira ? ' is-ativa' : '' ) . '" id="tt-dest-aba-' . esc_attr( $id ) . '" aria-controls="tt-dest-painel-' . esc_attr( $id ) . '" aria-selected="' . ( $primeira ? 'true' : 'false' ) . '" data-tt-aba="' . esc_attr( $id ) . '">'
			. esc_html( $aba[0] ) . '<span class="tt-rot__ponto' . ( $cheia ? ' is-cheio' : '' ) . '" title="' . ( $cheia ? 'Preenchida' : 'Vazia' ) . '"></span></button>';
		$primeira = false;
	}
	echo '</div>';
	$primeira = true;
	foreach ( array_keys( tt_destino_abas() ) as $id ) {
		echo '<div class="tt-rot__painel" role="tabpanel" id="tt-dest-painel-' . esc_attr( $id ) . '" aria-labelledby="tt-dest-aba-' . esc_attr( $id ) . '" data-tt-painel="' . esc_attr( $id ) . '"' . ( $primeira ? '' : ' hidden' ) . '>';
		call_user_func( 'tt_destino_painel_' . $id, $v );
		echo '</div>';
		$primeira = false;
	}
	echo '</div>';
}

add_action( 'add_meta_boxes', function () {
	remove_meta_box( 'postexcerpt', 'destinos', 'normal' ); // o resumo está na aba Resumo (mesmo campo, name="excerpt")
	add_meta_box( 'tt_destinos_campos', 'Dados do destino', 'tt_destino_desenhar_caixa', 'destinos', 'normal', 'high' );
} );

/* ------------------------------------------------------------------ Salvar */

/** Grava o que veio do formulário. Devolve a lista de chaves gravadas (vazia se o pedido não passou nas conferências). */
function tt_destino_salvar( $post_id ) {
	if ( ! isset( $_POST['tt_destinos_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tt_destinos_nonce'] ) ), 'tt_destinos_salvar' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return array();
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return array();
	}
	$enviado = isset( $_POST['tt_destinos'] ) && is_array( $_POST['tt_destinos'] ) ? wp_unslash( $_POST['tt_destinos'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- conferido acima; limpo em tt_destino_valores_a_gravar
	$valores = tt_destino_valores_a_gravar( $enviado );
	foreach ( $valores as $chave => $valor ) {
		update_post_meta( $post_id, $chave, $valor );
	}
	return array_keys( $valores );
}
add_action( 'save_post_destinos', 'tt_destino_salvar' );

/* ------------------------------------------------------------------ Arquivos da tela */

/** Lista de destinos: só o CSS (colunas). Editar/novo destino: mídia, editor visual, CSS e JS da caixa. */
function tt_destino_arquivos( $gancho ) {
	$tela = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $tela || 'destinos' !== $tela->post_type ) {
		return;
	}
	wp_enqueue_style( 'tt-roteiro-editor', TT_ROTEIROS_URL . 'assets/roteiro-editor.css', array(), TT_ROTEIROS_VERSION );
	if ( ! in_array( $gancho, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_editor();
	wp_enqueue_script( 'tt-roteiro-editor', TT_ROTEIROS_URL . 'assets/roteiro-editor.js', array( 'jquery', 'editor' ), TT_ROTEIROS_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'tt_destino_arquivos' );

/* ------------------------------------------------------------------ Lista "Todos os destinos" */

/** Colunas novas: Capa antes do título e Resumo depois dele. */
function tt_destino_colunas( $colunas ) {
	$novas = array();
	foreach ( $colunas as $chave => $rotulo ) {
		if ( 'title' === $chave ) {
			$novas['tt_capa'] = 'Capa';
		}
		$novas[ $chave ] = $rotulo;
		if ( 'title' === $chave ) {
			$novas['tt_resumo'] = 'Resumo';
		}
	}
	return $novas;
}
add_filter( 'manage_destinos_posts_columns', 'tt_destino_colunas' );

function tt_destino_coluna_valor( $coluna, $post_id ) {
	if ( 'tt_capa' === $coluna ) {
		$img = get_the_post_thumbnail( $post_id, array( 56, 56 ) );
		echo $img ? $img : '<span class="tt-rot__semcapa">—</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML da miniatura vem do WordPress
	}
	if ( 'tt_resumo' === $coluna ) {
		$texto = tt_roteiro_texto_simples( get_post_field( 'post_excerpt', $post_id ) );
		echo '' === $texto ? '—' : esc_html( $texto );
	}
}
add_action( 'manage_destinos_posts_custom_column', 'tt_destino_coluna_valor', 10, 2 );
