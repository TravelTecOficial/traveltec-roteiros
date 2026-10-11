<?php
/**
 * Dados do roteiro para a tela de edição: abas, indicador de preenchimento, leitura e gravação do preço e das
 * fotos, e o que gravar a partir do formulário. Funções puras (só usam wp_strip_all_tags e esc_*), para testar
 * sem WordPress. O formato gravado é o do import do n8n — modelos do Elementor e API não mudam.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Abas da caixa "Dados do roteiro": id => array( rótulo, chaves de tt_roteiros_campos() ). */
function tt_roteiro_abas() {
	return array(
		'resumo'      => array( 'Resumo', array( 'subtitulo', 'texto_de_apresentacao' ) ),
		'preco'       => array( 'Preço', array( 'preco_de_referencia' ) ),
		'fotos'       => array( 'Fotos', array( 'galeria' ) ),
		'programacao' => array( 'Programação', array( 'programacao' ) ),
		'hospedagem'  => array( 'Hospedagem', array( 'hospedagem' ) ),
		'inclui'      => array( 'Inclui / Não inclui', array( 'inclui', 'nao_inclui' ) ),
		'condicoes'   => array( 'Condições', array( 'pagamento', 'cancelamento', 'documentacao' ) ),
	);
}

/** Texto sem tags, com entidades decodificadas e espaços colapsados. */
function tt_roteiro_texto_simples( $html ) {
	$t = html_entity_decode( wp_strip_all_tags( (string) $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return trim( preg_replace( '/\s+/u', ' ', $t ) );
}

/** Tem texto útil? O import grava "nao informado" quando falta dado — conta como vazio. */
function tt_roteiro_tem_conteudo( $valor ) {
	if ( preg_match( '/<img\b/i', (string) $valor ) ) {
		return true; // galeria: só imagens, sem texto
	}
	$t = strtolower( tt_roteiro_texto_simples( $valor ) );
	return '' !== $t && 'nao informado' !== $t && 'não informado' !== $t;
}

/** Alguma das chaves da aba tem conteúdo? */
function tt_roteiro_aba_preenchida( $chaves, $valores ) {
	foreach ( $chaves as $chave ) {
		if ( isset( $valores[ $chave ] ) && tt_roteiro_tem_conteudo( $valores[ $chave ] ) ) {
			return true;
		}
	}
	return false;
}

/* ------------------------------------------------------------------ Preço */

/** Moedas do seletor: valor do campo => texto que vai para o preço. */
function tt_roteiro_moedas() {
	return array(
		'USD' => 'USD',
		'EUR' => 'EUR',
		'BRL' => 'R$',
	);
}

/**
 * Lê o preço gravado. Devolve array( 'modo' => 'campos', 'prefixo', 'moeda', 'valor', 'complemento' ) ou,
 * quando o texto não tem moeda e valor, array( 'modo' => 'livre', 'livre' => texto ). Mesma expressão que o
 * modelo da página do roteiro usa para separar "a partir de", o valor e o complemento.
 */
function tt_roteiro_preco_ler( $html ) {
	$texto = tt_roteiro_texto_simples( $html );
	if ( '' === $texto ) {
		return array( 'modo' => 'campos', 'prefixo' => 'A partir de', 'moeda' => 'USD', 'valor' => '', 'complemento' => '' );
	}
	if ( ! preg_match( '/^(.*?)((?:USD|US\$|U\$|EUR|€|R\$)\s*[\d.,]*\d)\s*,?\s*(.*)$/iu', $texto, $m )
		|| ! preg_match( '/^(USD|US\$|U\$|EUR|€|R\$)\s*(.+)$/iu', $m[2], $p ) ) {
		return array( 'modo' => 'livre', 'livre' => $texto );
	}
	$moeda = 'USD';
	if ( '€' === $p[1] || 'EUR' === strtoupper( $p[1] ) ) {
		$moeda = 'EUR';
	} elseif ( 'R$' === $p[1] ) {
		$moeda = 'BRL';
	}
	$prefixo = trim( $m[1] );
	return array(
		'modo'        => 'campos',
		'prefixo'     => '' !== $prefixo ? $prefixo : 'A partir de',
		'moeda'       => $moeda,
		'valor'       => trim( $p[2] ),
		'complemento' => trim( $m[3] ),
	);
}

/** Monta o texto do preço no formato do import: <p>a partir de USD 1.044, por pessoa em duplo</p>. */
function tt_roteiro_preco_montar( $d ) {
	$modo = isset( $d['modo'] ) ? $d['modo'] : 'campos';
	if ( 'livre' === $modo ) {
		$livre = trim( isset( $d['livre'] ) ? (string) $d['livre'] : '' );
		return '' === $livre ? '' : '<p>' . esc_html( $livre ) . '</p>';
	}
	$valor = preg_replace( '/[^0-9.,]/', '', isset( $d['valor'] ) ? (string) $d['valor'] : '' );
	if ( '' === $valor ) {
		return '';
	}
	$moedas = tt_roteiro_moedas();
	$moeda  = isset( $d['moeda'], $moedas[ $d['moeda'] ] ) ? $moedas[ $d['moeda'] ] : 'USD';
	$texto  = trim( trim( isset( $d['prefixo'] ) ? (string) $d['prefixo'] : '' ) . ' ' . $moeda . ' ' . $valor );
	$compl  = trim( isset( $d['complemento'] ) ? (string) $d['complemento'] : '' );
	if ( '' !== $compl ) {
		$texto .= ', ' . $compl;
	}
	return '<p>' . esc_html( $texto ) . '</p>';
}

/* ------------------------------------------------------------------ Fotos */

/**
 * Lê as fotos do campo galeria: lista de array( 'url', 'alt' ). O import grava a faixa duplicada (o CSS anda
 * -50% para voltar sem salto); se a lista é a mesma metade repetida, devolve uma cópia só.
 */
function tt_roteiro_galeria_ler( $html ) {
	if ( ! preg_match_all( '/<img\b[^>]*>/i', (string) $html, $tags ) ) {
		return array();
	}
	$itens = array();
	foreach ( $tags[0] as $tag ) {
		if ( ! preg_match( '/\bsrc\s*=\s*"([^"]*)"/i', $tag, $src ) ) {
			continue;
		}
		$alt     = preg_match( '/\balt\s*=\s*"([^"]*)"/i', $tag, $a ) ? html_entity_decode( $a[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : '';
		$itens[] = array( 'url' => html_entity_decode( $src[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ), 'alt' => $alt );
	}
	$n = count( $itens );
	if ( $n >= 2 && 0 === $n % 2 ) {
		$meio = $n / 2;
		if ( array_column( array_slice( $itens, 0, $meio ), 'url' ) === array_column( array_slice( $itens, $meio ), 'url' ) ) {
			return array_slice( $itens, 0, $meio );
		}
	}
	return $itens;
}

/** Monta o HTML da galeria no formato do import, com a lista duas vezes e a duração proporcional (4 s por foto, mínimo 20 s). */
function tt_roteiro_galeria_montar( $itens ) {
	$itens = array_values( array_filter( (array) $itens, function ( $i ) {
		return is_array( $i ) && ! empty( $i['url'] );
	} ) );
	if ( ! $itens ) {
		return '';
	}
	$imgs = '';
	foreach ( array_merge( $itens, $itens ) as $i ) {
		$imgs .= '<img src="' . esc_url( $i['url'] ) . '" alt="' . esc_attr( isset( $i['alt'] ) ? $i['alt'] : '' ) . '" style="height:220px;width:auto;flex:0 0 auto;border-radius:8px" />';
	}
	$duracao = max( 20, 4 * count( $itens ) );
	return '<div class="rt-galeria" style="padding:4px 0"><div class="rt-galeria-track" style="display:flex;gap:12px;width:max-content;flex-wrap:nowrap;--rt-duracao:' . $duracao . 's">' . $imgs . '</div></div>';
}

/* ------------------------------------------------------------------ Gravação */

/**
 * O que gravar a partir do formulário. $enviado = tt_roteiros[...] (campos simples), $preco = tt_preco[...] e
 * $galeria = tt_galeria[...]. Preço e fotos só entram quando a tela marcou "alterado" — sem isso o valor
 * gravado (por exemplo, o de um import fora do padrão) fica como está. Devolve chave => valor.
 */
function tt_roteiro_valores_a_gravar( $enviado, $preco, $galeria ) {
	$saida   = array();
	$simples = array( 'subtitulo', 'texto_de_apresentacao', 'programacao', 'hospedagem', 'inclui', 'nao_inclui', 'pagamento', 'cancelamento', 'documentacao' );
	foreach ( $simples as $chave ) {
		if ( is_array( $enviado ) && isset( $enviado[ $chave ] ) ) {
			$saida[ $chave ] = (string) $enviado[ $chave ];
		}
	}
	if ( is_array( $preco ) && ! empty( $preco['alterado'] ) ) {
		$saida['preco_de_referencia'] = tt_roteiro_preco_montar( $preco );
	}
	if ( is_array( $galeria ) && ! empty( $galeria['alterado'] ) ) {
		$lista            = json_decode( isset( $galeria['urls'] ) ? (string) $galeria['urls'] : '[]', true );
		$saida['galeria'] = tt_roteiro_galeria_montar( is_array( $lista ) ? $lista : array() );
	}
	return $saida;
}
