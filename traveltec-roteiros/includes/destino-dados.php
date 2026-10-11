<?php
/**
 * Dados do destino: campos, abas da tela, lista "o que fazer" (um item por linha), galeria (IDs da Biblioteca de
 * Mídia) e o HTML que vai para o site. Funções puras, para testar sem WordPress — usam só esc_*, wp_kses_post e
 * as funções de texto de roteiro-dados.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Campos do destino. A chave é o nome gravado no banco e o nome aceito em "acf" na API REST.
 * Nome = título do post; capa = imagem destacada; resumo (card) = resumo do post.
 */
function tt_destinos_campos() {
	return array(
		'texto_de_abertura' => array( 'Texto de abertura', 'html' ),
		'o_que_fazer'       => array( 'O que fazer', 'linhas' ),
		'quando_ir'         => array( 'Quando ir', 'html' ),
		'galeria'           => array( 'Fotos', 'ids' ),
	);
}

/** Abas da caixa "Dados do destino": id => array( rótulo, chaves que contam para o indicador ). */
function tt_destino_abas() {
	return array(
		'resumo' => array( 'Resumo', array( 'resumo', 'texto_de_abertura' ) ),
		'fazer'  => array( 'O que fazer', array( 'o_que_fazer' ) ),
		'quando' => array( 'Quando ir', array( 'quando_ir' ) ),
		'fotos'  => array( 'Fotos', array( 'galeria' ) ),
	);
}

/* ------------------------------------------------------------------ Lista (um item por linha) */

/** Lê a lista: texto com um item por linha, HTML colado (<li>, <p>, <br>) ou array. Devolve os itens, sem vazios. */
function tt_destino_lista_ler( $valor ) {
	$linhas = is_array( $valor ) ? $valor : preg_split( '/\r\n|\r|\n|<br\s*\/?>|<\/li>|<\/p>/i', (string) $valor );
	$itens  = array();
	foreach ( (array) $linhas as $linha ) {
		if ( ! is_scalar( $linha ) ) {
			continue;
		}
		$texto = preg_replace( '/^[\-\*•·]\s*/u', '', tt_roteiro_texto_simples( $linha ) );
		if ( '' !== $texto ) {
			$itens[] = $texto;
		}
	}
	return $itens;
}

function tt_destino_lista_montar( $valor ) {
	return implode( "\n", tt_destino_lista_ler( $valor ) );
}

/* ------------------------------------------------------------------ Galeria (IDs de anexos) */

/**
 * IDs das fotos, na ordem, sem repetição. Aceita "12,13", array de números (API) e o JSON da tela de edição
 * (lista de objetos com "id").
 */
function tt_destino_galeria_ids( $valor ) {
	if ( is_string( $valor ) && '' !== $valor && '[' === $valor[0] ) {
		$json  = json_decode( $valor, true );
		$valor = is_array( $json ) ? $json : array();
	}
	$partes = is_array( $valor ) ? $valor : preg_split( '/[\s,;]+/', (string) $valor );
	$ids    = array();
	foreach ( (array) $partes as $parte ) {
		if ( is_array( $parte ) ) {
			$parte = isset( $parte['id'] ) ? $parte['id'] : 0;
		}
		$id = is_scalar( $parte ) && ctype_digit( (string) $parte ) ? (int) $parte : 0;
		if ( $id > 0 && ! in_array( $id, $ids, true ) ) {
			$ids[] = $id;
		}
	}
	return $ids;
}

function tt_destino_galeria_montar( $valor ) {
	return implode( ',', tt_destino_galeria_ids( $valor ) );
}

/* ------------------------------------------------------------------ Gravação */

/** Valor pronto para gravar, conforme o tipo do campo. Chave desconhecida: vazio. */
function tt_destino_limpar( $chave, $valor ) {
	$campos = tt_destinos_campos();
	if ( ! isset( $campos[ $chave ] ) ) {
		return '';
	}
	if ( 'linhas' === $campos[ $chave ][1] ) {
		return tt_destino_lista_montar( $valor );
	}
	if ( 'ids' === $campos[ $chave ][1] ) {
		return tt_destino_galeria_montar( $valor );
	}
	return is_scalar( $valor ) ? wp_kses_post( (string) $valor ) : '';
}

/**
 * O que gravar a partir do formulário ($enviado = tt_destinos[...]). Devolve chave => valor.
 * A lista sem nenhum item não manda campo nenhum: por isso a tela envia o_que_fazer_presente.
 */
function tt_destino_valores_a_gravar( $enviado ) {
	$saida = array();
	if ( ! is_array( $enviado ) ) {
		return $saida;
	}
	foreach ( array_keys( tt_destinos_campos() ) as $chave ) {
		if ( array_key_exists( $chave, $enviado ) ) {
			$saida[ $chave ] = tt_destino_limpar( $chave, $enviado[ $chave ] );
		}
	}
	if ( ! empty( $enviado['o_que_fazer_presente'] ) && ! isset( $saida['o_que_fazer'] ) ) {
		$saida['o_que_fazer'] = '';
	}
	return $saida;
}

/* ------------------------------------------------------------------ HTML para o site */

function tt_destino_titulo_html( $titulo ) {
	return '' === (string) $titulo ? '' : '<h2 class="tt-destino-titulo">' . esc_html( $titulo ) . '</h2>';
}

/** Bloco de texto (abertura, quando ir). Vazio ou "nao informado": nada, nem o título. */
function tt_destino_texto_html( $html, $titulo = '' ) {
	if ( ! tt_roteiro_tem_conteudo( $html ) ) {
		return '';
	}
	return tt_destino_titulo_html( $titulo ) . '<div class="tt-destino-texto">' . $html . '</div>';
}

function tt_destino_lista_html( $valor, $titulo = '' ) {
	$itens = tt_destino_lista_ler( $valor );
	if ( ! $itens ) {
		return '';
	}
	$html = tt_destino_titulo_html( $titulo ) . '<ul class="tt-destino-lista">';
	foreach ( $itens as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}
	return $html . '</ul>';
}

/** $fotos: lista de array( 'grande' => url, 'mini' => url, 'alt' => texto ). $grupo junta as fotos na mesma ampliação. */
function tt_destino_galeria_html( $fotos, $grupo, $titulo = '' ) {
	$imgs = '';
	foreach ( (array) $fotos as $foto ) {
		if ( ! is_array( $foto ) || empty( $foto['grande'] ) ) {
			continue;
		}
		$mini  = ! empty( $foto['mini'] ) ? $foto['mini'] : $foto['grande'];
		$imgs .= '<a href="' . esc_url( $foto['grande'] ) . '" data-elementor-open-lightbox="yes" data-elementor-lightbox-slideshow="' . esc_attr( $grupo ) . '">'
			. '<img src="' . esc_url( $mini ) . '" alt="' . esc_attr( isset( $foto['alt'] ) ? $foto['alt'] : '' ) . '" loading="lazy"></a>';
	}
	return '' === $imgs ? '' : tt_destino_titulo_html( $titulo ) . '<div class="tt-destino-galeria">' . $imgs . '</div>';
}
