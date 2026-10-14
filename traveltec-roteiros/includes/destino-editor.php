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

/** Campo de uma linha, com limite de caracteres (e contador). */
function tt_destino_campo_texto( $chave, $rotulo, $valor, $limite, $ajuda = '' ) {
	echo '<div class="tt-rot__campo"><label for="tt_dest_' . esc_attr( $chave ) . '">' . esc_html( $rotulo ) . '</label>';
	echo '<input type="text" class="widefat" id="tt_dest_' . esc_attr( $chave ) . '" name="tt_destinos[' . esc_attr( $chave ) . ']" value="' . esc_attr( $valor ) . '" maxlength="' . (int) $limite . '" data-tt-contador="' . (int) $limite . '">';
	echo '<p class="description">' . ( '' !== $ajuda ? esc_html( $ajuda ) . ' ' : '' ) . '<span class="tt-rot__contador" data-tt-contador-saida></span></p></div>';
}

/** Foto de um item (ponto turístico, passeio): id da Biblioteca de Mídia, escolhido pela janela de mídia. */
function tt_destino_campo_foto( $nome, $id ) {
	$url = $id ? wp_get_attachment_image_url( (int) $id, 'thumbnail' ) : false;
	echo '<span class="tt-dest-foto" data-tt-foto-unica>';
	echo '<img src="' . esc_url( $url ? $url : '' ) . '" alt="" data-tt-foto-previa' . ( $url ? '' : ' hidden' ) . '>';
	echo '<input type="hidden" name="' . esc_attr( $nome ) . '" value="' . esc_attr( $url ? (int) $id : '' ) . '" data-tt-foto-id>';
	echo '<button type="button" class="button" data-tt-foto-escolher>' . ( $url ? 'Trocar foto' : 'Escolher foto' ) . '</button> ';
	echo '<button type="button" class="button-link" data-tt-foto-tirar' . ( $url ? '' : ' hidden' ) . '>Tirar</button>';
	echo '</span>';
}

/**
 * Lista com sub-campos (pontos turísticos, passeios, gastronomia, onde ficar, FAQ): uma linha por item, até o máximo
 * do contrato. Linha em branco não grava. $colunas: sub-campo => array( rótulo, 'texto'|'area'|'foto' ).
 */
function tt_destino_campo_linhas( $chave, $rotulo, $valor, $colunas, $ajuda = '' ) {
	$listas = tt_destino_listas();
	$limite = $listas[ $chave ][1];
	$itens  = tt_destino_json_ler( $chave, $valor );
	echo '<fieldset class="tt-dest-linhas tt-dest-linhas--' . esc_attr( $chave ) . '"><legend>' . esc_html( $rotulo ) . '</legend>';
	if ( '' !== $ajuda ) {
		echo '<p class="description">' . esc_html( $ajuda ) . '</p>';
	}
	for ( $i = 0; $i < $listas[ $chave ][0]; $i++ ) {
		$item = isset( $itens[ $i ] ) ? $itens[ $i ] : array();
		echo '<div class="tt-dest-linha"><span class="tt-dest-linha__n">' . ( $i + 1 ) . '</span>';
		foreach ( $colunas as $sub => $col ) {
			$nome = 'tt_destinos[' . $chave . '][' . $i . '][' . $sub . ']';
			$v    = isset( $item[ $sub ] ) ? (string) $item[ $sub ] : '';
			echo '<label class="tt-dest-linha__campo tt-dest-linha__campo--' . esc_attr( $col[1] ) . '"><span>' . esc_html( $col[0] ) . '</span>';
			if ( 'foto' === $col[1] ) {
				tt_destino_campo_foto( $nome, $v );
			} elseif ( 'area' === $col[1] ) {
				echo '<textarea rows="3" class="widefat" name="' . esc_attr( $nome ) . '" maxlength="' . (int) $limite[ $sub ] . '">' . esc_textarea( $v ) . '</textarea>';
			} else {
				echo '<input type="text" class="widefat" name="' . esc_attr( $nome ) . '" value="' . esc_attr( $v ) . '" maxlength="' . (int) $limite[ $sub ] . '">';
			}
			echo '</label>';
		}
		echo '</div>';
	}
	echo '</fieldset>';
}

function tt_destino_painel_resumo( $v ) {
	echo '<div class="tt-rot__campo"><label for="tt_dest_escopo">Nacional ou internacional</label>';
	echo '<select id="tt_dest_escopo" name="tt_destinos[escopo]" data-tt-escopo>';
	foreach ( array( '' => '(não informado)', 'nacional' => 'Nacional (Brasil)', 'internacional' => 'Internacional' ) as $valor => $rotulo ) {
		echo '<option value="' . esc_attr( $valor ) . '"' . selected( $v['escopo'], $valor, false ) . '>' . esc_html( $rotulo ) . '</option>';
	}
	echo '</select><p class="description">Nacional: a página não mostra documentação, moeda, idioma nem fuso.</p></div>';
	tt_destino_campo_texto( 'subchamada', 'Subchamada', $v['subchamada'], 160, 'Uma frase de benefício. Aparece no topo da página, abaixo do nome, e no card da lista.' );
	// É o campo de resumo do próprio WordPress (name e id "excerpt"): ele grava sozinho no resumo do post, e o
	// salvamento automático lê o campo pelo id — com outro id, o rascunho perderia o resumo.
	echo '<div class="tt-rot__campo"><label for="excerpt">Resumo</label>';
	echo '<textarea class="widefat" rows="3" id="excerpt" name="excerpt" data-tt-contador="160">' . esc_textarea( $v['resumo'] ) . '</textarea>';
	echo '<p class="description">Uma ou duas frases. Aparece no card do destino, na lista /destinos/. <span class="tt-rot__contador" data-tt-contador-saida></span></p></div>';
	tt_destino_campo_editor( 'texto_de_abertura', 'Visão geral', $v['texto_de_abertura'], 'Dois ou três parágrafos sobre o destino. Abre a página, logo abaixo da foto de capa.' );
	$marcados = tt_destino_perfis_ler( $v['perfis'] );
	echo '<div class="tt-rot__campo"><span class="tt-rot__rotulo">Perfis (até 3)</span>';
	echo '<input type="hidden" name="tt_destinos[perfis_presente]" value="1"><div class="tt-dest-perfis">';
	foreach ( tt_destino_perfis() as $chave => $rotulo ) {
		echo '<label><input type="checkbox" name="tt_destinos[perfis][]" value="' . esc_attr( $chave ) . '"' . ( in_array( $chave, $marcados, true ) ? ' checked' : '' ) . '> ' . esc_html( $rotulo ) . '</label>';
	}
	echo '</div><p class="description">Para quem o destino é indicado. Marcando mais de 3, valem os 3 primeiros.</p></div>';
	tt_destino_campo_texto( 'duracao_ideal', 'Duração ideal', $v['duracao_ideal'], 60, 'Ex.: 5 a 7 dias.' );
	echo '<div class="tt-rot__campo"><label for="tt_dest_atualizado_em">Atualizado em</label>';
	echo '<input type="date" id="tt_dest_atualizado_em" name="tt_destinos[atualizado_em]" value="' . esc_attr( $v['atualizado_em'] ) . '">';
	echo '<p class="description">Aparece no fim da página. Vazio: a data da última edição.</p></div>';
}

function tt_destino_painel_praticas( $v ) {
	tt_destino_campo_editor( 'quando_ir', 'Quando ir', $v['quando_ir'], 'Melhor época e o que muda ao longo do ano.' );
	$clima    = array();
	foreach ( tt_destino_json_ler( 'clima', $v['clima'] ) as $c ) {
		$clima[ $c['estacao'] ] = $c;
	}
	echo '<fieldset class="tt-dest-linhas tt-dest-linhas--clima"><legend>Clima por estação</legend>';
	echo '<p class="description">Médias de máxima e mínima (°C, números inteiros) e chuva total da estação (mm). Estação em branco não aparece.</p>';
	$i = 0;
	foreach ( tt_destino_estacoes() as $estacao => $rotulo ) {
		$c    = isset( $clima[ $estacao ] ) ? $clima[ $estacao ] : array( 'meses' => '', 'max' => null, 'min' => null, 'chuva_mm' => null );
		$base = 'tt_destinos[clima][' . $i . ']';
		echo '<div class="tt-dest-linha"><strong class="tt-dest-linha__estacao">' . esc_html( $rotulo ) . '</strong>';
		echo '<input type="hidden" name="' . esc_attr( $base . '[estacao]' ) . '" value="' . esc_attr( $estacao ) . '">';
		echo '<label class="tt-dest-linha__campo"><span>Meses</span><input type="text" class="widefat" name="' . esc_attr( $base . '[meses]' ) . '" value="' . esc_attr( $c['meses'] ) . '" maxlength="40"></label>';
		foreach ( array( 'max' => 'Máx. °C', 'min' => 'Mín. °C', 'chuva_mm' => 'Chuva mm' ) as $sub => $rot ) {
			echo '<label class="tt-dest-linha__campo tt-dest-linha__campo--numero"><span>' . esc_html( $rot ) . '</span><input type="number" step="1" name="' . esc_attr( $base . '[' . $sub . ']' ) . '" value="' . esc_attr( null === $c[ $sub ] ? '' : (string) $c[ $sub ] ) . '"></label>';
		}
		echo '</div>';
		$i++;
	}
	echo '</fieldset>';
	tt_destino_campo_editor( 'como_chegar', 'Como chegar', $v['como_chegar'], 'Voos (diretos e conexões), tempo de viagem, aeroporto mais próximo, estrada.' );
	echo '<div class="tt-dest-inter" data-tt-so-inter' . ( 'nacional' === $v['escopo'] ? ' hidden' : '' ) . '>';
	echo '<div class="tt-rot__campo"><span class="tt-rot__rotulo">Documentação</span>';
	echo tt_roteiro_tem_conteudo( $v['documentacao'] ) ? '<div class="tt-dest-doc">' . wp_kses_post( $v['documentacao'] ) . '</div>' : '<p><em>Sem texto.</em></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post
	echo '<p class="description">Texto padrão do sistema, com os links oficiais (Portal Consular, Anvisa). Não é editado aqui.</p></div>';
	tt_destino_campo_texto( 'moeda', 'Moeda', $v['moeda'], 60, 'Ex.: Euro (EUR).' );
	tt_destino_campo_texto( 'idioma', 'Idioma', $v['idioma'], 60 );
	tt_destino_campo_texto( 'fuso', 'Fuso horário', $v['fuso'], 60, 'Ex.: +4 h em relação a Brasília.' );
	echo '</div>';
}

function tt_destino_painel_fazer( $v ) {
	tt_destino_campo_linhas( 'pontos_turisticos', 'Pontos turísticos', $v['pontos_turisticos'], array( 'nome' => array( 'Nome', 'texto' ), 'descricao' => array( 'Uma linha', 'texto' ), 'foto' => array( 'Foto', 'foto' ) ), 'De 4 a 8. Linha em branco não aparece.' );
	tt_destino_campo_linhas( 'passeios', 'Passeios', $v['passeios'], array( 'titulo' => array( 'Título', 'texto' ), 'duracao' => array( 'Duração', 'texto' ), 'foto' => array( 'Foto', 'foto' ) ), 'De 3 a 6. Com menos de 3, a seção não aparece no site. A foto sai com o crédito "Foto: Viator".' );
	tt_destino_campo_linhas( 'gastronomia', 'Gastronomia', $v['gastronomia'], array( 'nome' => array( 'Prato', 'texto' ), 'descricao' => array( 'Uma linha', 'texto' ) ), 'De 3 a 6 pratos típicos.' );
	$lista = tt_destino_lista_ler( $v['o_que_fazer'] );
	if ( ! $lista ) {
		return; // a lista simples é da v1: só aparece em destino que ainda a tem
	}
	echo '<div class="tt-rot__itens" data-tt-itens data-nome="tt_destinos[o_que_fazer][]">';
	echo '<input type="hidden" name="tt_destinos[o_que_fazer_presente]" value="1">';
	echo '<p class="tt-rot__rotulo">Lista simples (destino antigo)</p>';
	echo '<p class="description">Um passeio ou atração por linha. Use as setas para mudar a ordem. Aparece no fim de "O que fazer".</p>';
	echo '<ul class="tt-rot__itens-lista" data-tt-itens-lista>';
	foreach ( $lista as $item ) {
		echo '<li class="tt-rot__item"><input type="text" class="widefat" name="tt_destinos[o_que_fazer][]" value="' . esc_attr( $item ) . '"></li>';
	}
	echo '</ul>';
	echo '<p><button type="button" class="button button-primary" data-tt-add-item>Adicionar item</button></p>';
	echo '</div>';
}

function tt_destino_painel_ficar( $v ) {
	tt_destino_campo_linhas( 'onde_ficar', 'Onde ficar', $v['onde_ficar'], array( 'bairro' => array( 'Bairro ou região', 'texto' ), 'perfil' => array( 'Indicado para', 'texto' ), 'descricao' => array( 'Descrição (1 ou 2 linhas)', 'area' ) ), 'De 3 a 5 bairros ou regiões.' );
}

function tt_destino_painel_faq( $v ) {
	tt_destino_campo_linhas( 'faq', 'Perguntas frequentes', $v['faq'], array( 'pergunta' => array( 'Pergunta', 'texto' ), 'resposta' => array( 'Resposta', 'area' ) ), 'De 5 a 8. Vão para o Google como FAQ. Pergunta sem resposta não grava.' );
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
		tt_destino_gravar_meta( $post_id, $chave, $valor );
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
