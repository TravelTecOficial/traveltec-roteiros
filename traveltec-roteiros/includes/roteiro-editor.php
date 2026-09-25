<?php
/**
 * Tela de edição do roteiro (caixa "Dados do roteiro" em abas), salvamento e colunas da lista.
 * Os dados e a lógica de leitura/gravação estão em roteiro-dados.php; o comportamento na tela, em
 * assets/roteiro-editor.js. Grava o mesmo formato do import do n8n.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** O site já tem grupo de campos do ACF para roteiros? Então a caixa do plugin não aparece (o ACF cuida). */
function tt_roteiro_acf_ativo() {
	return function_exists( 'acf_get_field_groups' ) && acf_get_field_groups( array( 'post_type' => 'roteiros' ) );
}

/* ------------------------------------------------------------------ Peças da caixa */

function tt_roteiro_campo_texto( $chave, $rotulo, $valor, $ajuda = '', $placeholder = '' ) {
	echo '<div class="tt-rot__campo"><label for="tt_rot_' . esc_attr( $chave ) . '">' . esc_html( $rotulo ) . '</label>';
	echo '<input type="text" class="widefat" id="tt_rot_' . esc_attr( $chave ) . '" name="tt_roteiros[' . esc_attr( $chave ) . ']" value="' . esc_attr( $valor ) . '" placeholder="' . esc_attr( $placeholder ) . '">';
	if ( '' !== $ajuda ) {
		echo '<p class="description">' . esc_html( $ajuda ) . '</p>';
	}
	echo '</div>';
}

/** Texto longo: a <textarea> vira o editor visual do WordPress quando a aba abre (assets/roteiro-editor.js). */
function tt_roteiro_campo_editor( $chave, $rotulo, $valor, $ajuda = '' ) {
	echo '<div class="tt-rot__campo"><label for="tt_rot_' . esc_attr( $chave ) . '">' . esc_html( $rotulo ) . '</label>';
	echo '<textarea class="widefat" rows="8" id="tt_rot_' . esc_attr( $chave ) . '" name="tt_roteiros[' . esc_attr( $chave ) . ']" data-tt-editor>' . esc_textarea( $valor ) . '</textarea>';
	if ( '' !== $ajuda ) {
		echo '<p class="description">' . esc_html( $ajuda ) . '</p>';
	}
	echo '</div>';
}

function tt_roteiro_painel_resumo( $v ) {
	tt_roteiro_campo_texto( 'subtitulo', 'Subtítulo', $v['subtitulo'], 'Uma frase curta que aparece abaixo do título, no card e na página do roteiro.', 'Ex.: Dubai e Abu Dhabi: luxo, deserto e Réveillon' );
	tt_roteiro_campo_editor( 'texto_de_apresentacao', 'Texto de apresentação', $v['texto_de_apresentacao'], 'O texto de abertura da página do roteiro.' );
}

function tt_roteiro_painel_preco( $v ) {
	$d      = tt_roteiro_preco_ler( $v['preco_de_referencia'] );
	$livre  = 'livre' === $d['modo'];
	$nomes  = array( 'USD' => 'Dólar (USD)', 'EUR' => 'Euro (EUR)', 'BRL' => 'Real (R$)' );
	echo '<div class="tt-rot__preco" data-tt-preco>';
	echo '<input type="hidden" name="tt_preco[alterado]" value="0" data-tt-alterado>';
	echo '<input type="hidden" name="tt_preco[modo]" value="' . ( $livre ? 'livre' : 'campos' ) . '" data-tt-preco-modo>';
	echo '<div class="tt-rot__grade" data-tt-modo-campos' . ( $livre ? ' hidden' : '' ) . '>';
	echo '<div class="tt-rot__campo"><label for="tt_preco_prefixo">Antes do valor</label><input type="text" id="tt_preco_prefixo" name="tt_preco[prefixo]" value="' . esc_attr( $livre ? 'A partir de' : $d['prefixo'] ) . '" placeholder="A partir de"></div>';
	echo '<div class="tt-rot__campo"><label for="tt_preco_moeda">Moeda</label><select id="tt_preco_moeda" name="tt_preco[moeda]">';
	foreach ( tt_roteiro_moedas() as $codigo => $simbolo ) {
		echo '<option value="' . esc_attr( $codigo ) . '"' . selected( $livre ? 'USD' : $d['moeda'], $codigo, false ) . '>' . esc_html( $nomes[ $codigo ] ) . '</option>';
	}
	echo '</select></div>';
	echo '<div class="tt-rot__campo"><label for="tt_preco_valor">Valor</label><input type="text" id="tt_preco_valor" name="tt_preco[valor]" inputmode="decimal" value="' . esc_attr( $livre ? '' : $d['valor'] ) . '" placeholder="1.200"></div>';
	echo '<div class="tt-rot__campo tt-rot__campo--larga"><label for="tt_preco_complemento">Depois do valor</label><input type="text" id="tt_preco_complemento" name="tt_preco[complemento]" value="' . esc_attr( $livre ? '' : $d['complemento'] ) . '" placeholder="por pessoa em duplo"></div>';
	echo '</div>';
	echo '<div class="tt-rot__campo" data-tt-modo-livre' . ( $livre ? '' : ' hidden' ) . '><label for="tt_preco_livre">Texto do preço</label><input type="text" class="widefat" id="tt_preco_livre" name="tt_preco[livre]" value="' . esc_attr( $livre ? $d['livre'] : '' ) . '"></div>';
	echo '<p class="tt-rot__previa">Como aparece: <strong data-tt-previa></strong></p>';
	echo '<p><button type="button" class="button-link" data-tt-trocar-modo>' . ( $livre ? 'Usar os campos (prefixo, moeda e valor)' : 'Usar texto livre' ) . '</button></p>';
	echo '<p class="description">Para mostrar “Consulte-nos” no lugar do preço em todos os roteiros: Voucher Tec › Configuração › Roteiros.</p>';
	echo '</div>';
}

function tt_roteiro_painel_fotos( $v ) {
	$fotos = tt_roteiro_galeria_ler( $v['galeria'] );
	$json  = wp_json_encode( $fotos );
	echo '<div class="tt-rot__fotos" data-tt-fotos data-urls="' . esc_attr( $json ) . '">';
	echo '<input type="hidden" name="tt_galeria[alterado]" value="0" data-tt-alterado>';
	echo '<input type="hidden" name="tt_galeria[urls]" value="' . esc_attr( $json ) . '" data-tt-fotos-json>';
	echo '<p class="description">A faixa de fotos que anda na página do roteiro. Arraste com as setas para mudar a ordem. A capa é a Imagem destacada.</p>';
	echo '<ul class="tt-rot__miniaturas" data-tt-lista></ul>';
	echo '<p><button type="button" class="button button-primary" data-tt-add-fotos>Adicionar da Biblioteca de Mídia</button> <span class="tt-rot__contagem" data-tt-contagem></span></p>';
	echo '</div>';
}

function tt_roteiro_painel_programacao( $v ) {
	tt_roteiro_campo_editor( 'programacao', 'Programação', $v['programacao'], 'Use uma lista numerada, um item por dia (ex.: Dia 1 — Chegada a Dubai).' );
}

function tt_roteiro_painel_hospedagem( $v ) {
	tt_roteiro_campo_editor( 'hospedagem', 'Hospedagem', $v['hospedagem'], 'Hotéis e tipos de quarto. Uma lista com marcadores funciona bem.' );
}

function tt_roteiro_painel_inclui( $v ) {
	tt_roteiro_campo_editor( 'inclui', 'O que está incluído', $v['inclui'] );
	tt_roteiro_campo_editor( 'nao_inclui', 'O que não está incluído', $v['nao_inclui'] );
}

function tt_roteiro_painel_condicoes( $v ) {
	tt_roteiro_campo_editor( 'pagamento', 'Pagamento', $v['pagamento'] );
	tt_roteiro_campo_editor( 'cancelamento', 'Cancelamento', $v['cancelamento'] );
	tt_roteiro_campo_editor( 'documentacao', 'Documentação', $v['documentacao'] );
}

/** A caixa "Dados do roteiro". */
function tt_roteiro_desenhar_caixa( $post ) {
	wp_nonce_field( 'tt_roteiros_salvar', 'tt_roteiros_nonce' );
	$v = array();
	foreach ( array_keys( tt_roteiros_campos() ) as $chave ) {
		$v[ $chave ] = (string) get_post_meta( $post->ID, $chave, true );
	}
	echo '<div class="tt-rot" data-tt-rot>';
	echo '<p class="tt-rot__topo">A capa do roteiro é a <strong>Imagem destacada</strong> (coluna ao lado). O que ficar vazio não aparece no site.</p>';
	echo '<div class="tt-rot__abas" role="tablist" aria-label="Seções do roteiro">';
	$primeira = true;
	foreach ( tt_roteiro_abas() as $id => $aba ) {
		$cheia = tt_roteiro_aba_preenchida( $aba[1], $v );
		echo '<button type="button" role="tab" class="tt-rot__aba' . ( $primeira ? ' is-ativa' : '' ) . '" id="tt-rot-aba-' . esc_attr( $id ) . '" aria-controls="tt-rot-painel-' . esc_attr( $id ) . '" aria-selected="' . ( $primeira ? 'true' : 'false' ) . '" data-tt-aba="' . esc_attr( $id ) . '">'
			. esc_html( $aba[0] ) . '<span class="tt-rot__ponto' . ( $cheia ? ' is-cheio' : '' ) . '" title="' . ( $cheia ? 'Preenchida' : 'Vazia' ) . '"></span></button>';
		$primeira = false;
	}
	echo '</div>';
	$primeira = true;
	foreach ( array_keys( tt_roteiro_abas() ) as $id ) {
		echo '<div class="tt-rot__painel" role="tabpanel" id="tt-rot-painel-' . esc_attr( $id ) . '" aria-labelledby="tt-rot-aba-' . esc_attr( $id ) . '" data-tt-painel="' . esc_attr( $id ) . '"' . ( $primeira ? '' : ' hidden' ) . '>';
		call_user_func( 'tt_roteiro_painel_' . $id, $v );
		echo '</div>';
		$primeira = false;
	}
	echo '</div>';
}

add_action( 'add_meta_boxes', function () {
	if ( tt_roteiro_acf_ativo() ) {
		return;
	}
	add_meta_box( 'tt_roteiros_campos', 'Dados do roteiro', 'tt_roteiro_desenhar_caixa', 'roteiros', 'normal', 'high' );
} );

/* ------------------------------------------------------------------ Salvar */

/** Grava o que veio do formulário. Devolve a lista de chaves gravadas (vazia se o pedido não passou nas conferências). */
function tt_roteiro_salvar( $post_id ) {
	if ( ! isset( $_POST['tt_roteiros_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tt_roteiros_nonce'] ) ), 'tt_roteiros_salvar' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return array();
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return array();
	}
	$pega = function ( $nome ) {
		return isset( $_POST[ $nome ] ) && is_array( $_POST[ $nome ] ) ? wp_unslash( $_POST[ $nome ] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- conferido acima; wp_kses_post na gravação
	};
	$valores = tt_roteiro_valores_a_gravar( $pega( 'tt_roteiros' ), $pega( 'tt_preco' ), $pega( 'tt_galeria' ) );
	foreach ( $valores as $chave => $valor ) {
		update_post_meta( $post_id, $chave, wp_kses_post( $valor ) );
	}
	return array_keys( $valores );
}
add_action( 'save_post_roteiros', 'tt_roteiro_salvar' );

/* ------------------------------------------------------------------ Arquivos da tela */

/** Lista de roteiros: só o CSS (colunas Capa e Preço). Editar/novo roteiro: mídia, editor visual, CSS e JS da caixa. */
function tt_roteiro_arquivos( $gancho ) {
	$tela = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $tela || 'roteiros' !== $tela->post_type ) {
		return;
	}
	if ( 'edit.php' === $gancho ) {
		wp_enqueue_style( 'tt-roteiro-editor', TT_ROTEIROS_URL . 'assets/roteiro-editor.css', array(), TT_ROTEIROS_VERSION );
		return;
	}
	if ( ! in_array( $gancho, array( 'post.php', 'post-new.php' ), true ) || tt_roteiro_acf_ativo() ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_editor();
	wp_enqueue_style( 'tt-roteiro-editor', TT_ROTEIROS_URL . 'assets/roteiro-editor.css', array(), TT_ROTEIROS_VERSION );
	wp_enqueue_script( 'tt-roteiro-editor', TT_ROTEIROS_URL . 'assets/roteiro-editor.js', array( 'jquery', 'editor' ), TT_ROTEIROS_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'tt_roteiro_arquivos' );

/* ------------------------------------------------------------------ Lista "Todos os roteiros" */

/** Colunas novas: Capa antes do título e Preço depois dele. */
function tt_roteiro_colunas( $colunas ) {
	$novas = array();
	foreach ( $colunas as $chave => $rotulo ) {
		if ( 'title' === $chave ) {
			$novas['tt_capa'] = 'Capa';
		}
		$novas[ $chave ] = $rotulo;
		if ( 'title' === $chave ) {
			$novas['tt_preco'] = 'Preço';
		}
	}
	return $novas;
}
add_filter( 'manage_roteiros_posts_columns', 'tt_roteiro_colunas' );

function tt_roteiro_coluna_valor( $coluna, $post_id ) {
	if ( 'tt_capa' === $coluna ) {
		$img = get_the_post_thumbnail( $post_id, array( 56, 56 ) );
		echo $img ? $img : '<span class="tt-rot__semcapa">—</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML da miniatura vem do WordPress
	}
	if ( 'tt_preco' === $coluna ) {
		$texto = tt_roteiro_texto_simples( get_post_meta( $post_id, 'preco_de_referencia', true ) );
		echo '' === $texto ? '—' : esc_html( $texto );
		if ( '' !== $texto && function_exists( 'tt_voucher_preco_roteiros' ) && 'consulte' === tt_voucher_preco_roteiros() ) {
			echo ' <span class="tt-rot__selo">oculto no site</span>';
		}
	}
}
add_action( 'manage_roteiros_posts_custom_column', 'tt_roteiro_coluna_valor', 10, 2 );
