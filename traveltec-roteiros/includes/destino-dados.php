<?php
/**
 * Dados do destino: campos, abas da tela, limpeza/validação (v1 e v2), leitura para a REST e o HTML que vai para o
 * site. Funções puras, para testar sem WordPress — usam só esc_*, wp_kses_post, wp_json_encode e as funções de
 * texto de roteiro-dados.php.
 *
 * Contrato dos campos v2 (Supabase ⇄ plugin 2.3.0): clienteideal/docs/superpowers/specs/2026-10-14-destinos-v2-campos.md.
 * Destino v1 (sem "versao") continua valendo: só tem abertura, o que fazer, quando ir e galeria.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Campos do destino. A chave é o nome gravado no banco e o nome aceito em "acf" na API REST.
 * Nome = título do post; capa = imagem destacada; resumo (card) = resumo do post.
 * array( rótulo, tipo [, limite de caracteres] ). Tipos: html, texto, linhas, ids, json, perfis, escopo, versao, data.
 */
function tt_destinos_campos() {
	return array(
		'versao'            => array( 'Versão', 'versao' ),
		'escopo'            => array( 'Nacional ou internacional', 'escopo' ),
		'subchamada'        => array( 'Subchamada', 'texto', 160 ),
		'texto_de_abertura' => array( 'Visão geral', 'html' ),
		'perfis'            => array( 'Perfis', 'perfis' ),
		'duracao_ideal'     => array( 'Duração ideal', 'texto', 60 ),
		'quando_ir'         => array( 'Quando ir', 'html' ),
		'clima'             => array( 'Clima por estação', 'json' ),
		'como_chegar'       => array( 'Como chegar', 'html' ),
		'documentacao'      => array( 'Documentação', 'html' ),
		'moeda'             => array( 'Moeda', 'texto', 60 ),
		'idioma'            => array( 'Idioma', 'texto', 60 ),
		'fuso'              => array( 'Fuso horário', 'texto', 60 ),
		'pontos_turisticos' => array( 'Pontos turísticos', 'json' ),
		'passeios'          => array( 'Passeios', 'json' ),
		'gastronomia'       => array( 'Gastronomia', 'json' ),
		'onde_ficar'        => array( 'Onde ficar', 'json' ),
		'faq'               => array( 'Perguntas frequentes', 'json' ),
		'atualizado_em'     => array( 'Atualizado em', 'data' ),
		'o_que_fazer'       => array( 'O que fazer (lista simples)', 'linhas' ),
		'galeria'           => array( 'Fotos', 'ids' ),
	);
}

/** Campos que só valem para destino internacional (num nacional a seção não aparece). */
function tt_destino_campos_internacionais() {
	return array( 'documentacao', 'moeda', 'idioma', 'fuso' );
}

/**
 * Listas gravadas como JSON: máximo de itens, sub-campos (limite de caracteres, 'int', 'id' ou 'estacao') e os
 * sub-campos obrigatórios (item sem eles é descartado). Mínimos do contrato são do Supabase; aqui só o máximo —
 * quem edita à mão no painel pode ter menos. A exceção é na exibição: passeios com menos de 3 não aparecem.
 */
function tt_destino_listas() {
	return array(
		'clima'             => array( 4, array( 'estacao' => 'estacao', 'meses' => 40, 'max' => 'int', 'min' => 'int', 'chuva_mm' => 'int' ), array( 'estacao' ) ),
		'pontos_turisticos' => array( 8, array( 'nome' => 80, 'descricao' => 160, 'foto' => 'id' ), array( 'nome' ) ),
		'passeios'          => array( 6, array( 'titulo' => 120, 'duracao' => 40, 'foto' => 'id' ), array( 'titulo' ) ),
		'gastronomia'       => array( 6, array( 'nome' => 80, 'descricao' => 160 ), array( 'nome' ) ),
		'onde_ficar'        => array( 5, array( 'bairro' => 80, 'descricao' => 240, 'perfil' => 60 ), array( 'bairro' ) ),
		'faq'               => array( 8, array( 'pergunta' => 160, 'resposta' => 600 ), array( 'pergunta', 'resposta' ) ),
	);
}

/** Estações na ordem da página: chave => rótulo. */
function tt_destino_estacoes() {
	return array( 'verao' => 'Verão', 'outono' => 'Outono', 'inverno' => 'Inverno', 'primavera' => 'Primavera' );
}

/** Perfis aceitos (lista fixa do contrato): chave => rótulo. */
function tt_destino_perfis() {
	return array(
		'romantico'   => 'Romântico',
		'familia'     => 'Família',
		'aventura'    => 'Aventura',
		'praia'       => 'Praia',
		'cultura'     => 'Cultura',
		'gastronomia' => 'Gastronomia',
		'natureza'    => 'Natureza',
		'compras'     => 'Compras',
		'neve'        => 'Neve',
	);
}

/** Abas da caixa "Dados do destino": id => array( rótulo, chaves que contam para o indicador ). */
function tt_destino_abas() {
	return array(
		'resumo'   => array( 'Resumo', array( 'resumo', 'subchamada', 'texto_de_abertura', 'perfis', 'duracao_ideal' ) ),
		'praticas' => array( 'Informações práticas', array( 'quando_ir', 'clima', 'como_chegar', 'documentacao', 'moeda', 'idioma', 'fuso' ) ),
		'fazer'    => array( 'O que fazer', array( 'pontos_turisticos', 'passeios', 'gastronomia', 'o_que_fazer' ) ),
		'ficar'    => array( 'Onde ficar', array( 'onde_ficar' ) ),
		'faq'      => array( 'FAQ', array( 'faq' ) ),
		'fotos'    => array( 'Fotos', array( 'galeria' ) ),
	);
}

/* ------------------------------------------------------------------ Texto simples com limite */

/** Texto sem HTML, numa linha, cortado em $limite caracteres (UTF-8). */
function tt_destino_texto_curto( $valor, $limite ) {
	if ( ! is_scalar( $valor ) ) {
		return '';
	}
	$texto = tt_roteiro_texto_simples( $valor );
	if ( $limite > 0 && preg_match( '/^.{' . (int) $limite . '}/us', $texto, $m ) && $m[0] !== $texto ) {
		$texto = rtrim( $m[0] );
	}
	return $texto;
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

/* ------------------------------------------------------------------ Perfis */

/** Perfis válidos, no máximo 3, sem repetir. Aceita lista, um por linha ou separado por vírgula, com ou sem acento. */
function tt_destino_perfis_ler( $valor ) {
	$partes = is_array( $valor ) ? $valor : preg_split( '/[\r\n,;]+/', (string) $valor );
	$validos = tt_destino_perfis();
	$saida   = array();
	foreach ( (array) $partes as $parte ) {
		if ( ! is_scalar( $parte ) ) {
			continue;
		}
		$chave = strtr( strtolower( trim( (string) $parte ) ), array( 'â' => 'a', 'á' => 'a', 'ã' => 'a', 'à' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c', 'Â' => 'a', 'Á' => 'a', 'Í' => 'i' ) );
		if ( isset( $validos[ $chave ] ) && ! in_array( $chave, $saida, true ) && count( $saida ) < 3 ) {
			$saida[] = $chave;
		}
	}
	return $saida;
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

/* ------------------------------------------------------------------ Listas em JSON */

/** Número inteiro (aceita "12", 12.6, "-3") ou null. */
function tt_destino_inteiro( $valor ) {
	if ( is_bool( $valor ) || ! is_scalar( $valor ) || ! is_numeric( trim( (string) $valor ) ) ) {
		return null;
	}
	return (int) round( (float) $valor );
}

/**
 * Itens limpos de uma lista JSON ($chave em tt_destino_listas()). Aceita array (REST) ou o texto JSON gravado.
 * Sub-campo de texto: sem HTML e cortado no limite. 'int': inteiro ou null. 'id': id positivo ou null.
 * Item sem os obrigatórios sai; acima do máximo, sobra fora. Clima: uma linha por estação, na ordem do ano.
 */
function tt_destino_json_ler( $chave, $valor ) {
	$listas = tt_destino_listas();
	if ( ! isset( $listas[ $chave ] ) ) {
		return array();
	}
	list( $maximo, $campos, $obrigatorios ) = $listas[ $chave ];
	if ( is_string( $valor ) ) {
		$valor = '' === trim( $valor ) ? array() : json_decode( $valor, true );
	}
	if ( ! is_array( $valor ) ) {
		return array();
	}
	$estacoes = tt_destino_estacoes();
	$itens    = array();
	foreach ( $valor as $bruto ) {
		if ( ! is_array( $bruto ) ) {
			continue;
		}
		$item = array();
		foreach ( $campos as $campo => $regra ) {
			$v = isset( $bruto[ $campo ] ) ? $bruto[ $campo ] : null;
			if ( 'int' === $regra ) {
				$item[ $campo ] = tt_destino_inteiro( $v );
			} elseif ( 'id' === $regra ) {
				$id             = tt_destino_inteiro( $v );
				$item[ $campo ] = $id && $id > 0 ? $id : null;
			} elseif ( 'estacao' === $regra ) {
				$e              = is_scalar( $v ) ? strtr( strtolower( trim( (string) $v ) ), array( 'ã' => 'a' ) ) : '';
				$item[ $campo ] = isset( $estacoes[ $e ] ) ? $e : '';
			} else {
				$item[ $campo ] = null === $v ? '' : tt_destino_texto_curto( $v, $regra );
			}
		}
		foreach ( $obrigatorios as $campo ) {
			if ( '' === $item[ $campo ] ) {
				continue 2;
			}
		}
		if ( 'clima' === $chave ) {
			if ( null !== $item['chuva_mm'] && $item['chuva_mm'] < 0 ) {
				$item['chuva_mm'] = null;
			}
			$vazia = '' === $item['meses'] && null === $item['max'] && null === $item['min'] && null === $item['chuva_mm'];
			if ( ! $vazia && ! isset( $itens[ $item['estacao'] ] ) ) {
				$itens[ $item['estacao'] ] = $item;
			}
			continue;
		}
		if ( count( $itens ) < $maximo ) {
			$itens[] = $item;
		}
	}
	if ( 'clima' === $chave ) {
		$ordem = array();
		foreach ( array_keys( $estacoes ) as $e ) {
			if ( isset( $itens[ $e ] ) ) {
				$ordem[] = $itens[ $e ];
			}
		}
		return $ordem;
	}
	return $itens;
}

/** Texto JSON para gravar ('' quando a lista fica vazia). */
function tt_destino_json_montar( $chave, $valor ) {
	$itens = tt_destino_json_ler( $chave, $valor );
	return $itens ? (string) wp_json_encode( $itens, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : '';
}

/* ------------------------------------------------------------------ Gravação */

/** Valor pronto para gravar, conforme o tipo do campo. Chave desconhecida: vazio. */
function tt_destino_limpar( $chave, $valor ) {
	$campos = tt_destinos_campos();
	if ( ! isset( $campos[ $chave ] ) ) {
		return '';
	}
	switch ( $campos[ $chave ][1] ) {
		case 'linhas':
			return tt_destino_lista_montar( $valor );
		case 'ids':
			return tt_destino_galeria_montar( $valor );
		case 'json':
			return tt_destino_json_montar( $chave, $valor );
		case 'perfis':
			return implode( "\n", tt_destino_perfis_ler( $valor ) );
		case 'texto':
			return tt_destino_texto_curto( $valor, $campos[ $chave ][2] );
		case 'escopo':
			$v = is_scalar( $valor ) ? strtolower( trim( (string) $valor ) ) : '';
			return in_array( $v, array( 'nacional', 'internacional' ), true ) ? $v : '';
		case 'versao':
			$n = tt_destino_inteiro( $valor );
			return $n && $n > 0 ? (string) $n : '';
		case 'data':
			$v = is_scalar( $valor ) ? substr( trim( (string) $valor ), 0, 10 ) : '';
			return preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m ) && checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? $v : '';
	}
	return is_scalar( $valor ) ? wp_kses_post( (string) $valor ) : '';
}

/**
 * Valor gravado → formato da API REST (campo "acf"): listas como array, versão como número, o resto como texto.
 * Destino v1 lido assim continua com abertura, o que fazer, quando ir e galeria; os campos novos vêm vazios.
 */
function tt_destino_valor_rest( $chave, $gravado ) {
	$campos = tt_destinos_campos();
	if ( ! isset( $campos[ $chave ] ) ) {
		return null;
	}
	switch ( $campos[ $chave ][1] ) {
		case 'linhas':
			return tt_destino_lista_ler( $gravado );
		case 'ids':
			return tt_destino_galeria_ids( $gravado );
		case 'json':
			return tt_destino_json_ler( $chave, $gravado );
		case 'perfis':
			return tt_destino_perfis_ler( $gravado );
		case 'versao':
			$n = tt_destino_inteiro( $gravado );
			return $n && $n > 0 ? $n : null;
	}
	return (string) $gravado;
}

/** Grava um campo já limpo. O update_post_meta tira uma camada de barras: sem o wp_slash o JSON (\") quebraria. */
function tt_destino_gravar_meta( $post_id, $chave, $valor ) {
	update_post_meta( $post_id, $chave, function_exists( 'wp_slash' ) ? wp_slash( $valor ) : $valor );
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
	foreach ( array( 'o_que_fazer', 'perfis' ) as $chave ) {
		if ( ! empty( $enviado[ $chave . '_presente' ] ) && ! isset( $saida[ $chave ] ) ) {
			$saida[ $chave ] = '';
		}
	}
	return $saida;
}

/* ------------------------------------------------------------------ HTML para o site */

function tt_destino_titulo_html( $titulo ) {
	return '' === (string) $titulo ? '' : '<h2 class="tt-destino-titulo">' . esc_html( $titulo ) . '</h2>';
}

function tt_destino_subtitulo_html( $titulo ) {
	return '' === (string) $titulo ? '' : '<h3 class="tt-destino-subtitulo">' . esc_html( $titulo ) . '</h3>';
}

/** Seção da página: título + partes. Sem nenhuma parte com conteúdo, nada (nem o título). */
function tt_destino_secao_html( $classe, $titulo, $partes ) {
	$corpo = implode( '', array_filter( (array) $partes, 'strlen' ) );
	if ( '' === $corpo ) {
		return '';
	}
	return '<section class="tt-destino-secao tt-destino-secao--' . esc_attr( $classe ) . '">' . tt_destino_titulo_html( $titulo ) . $corpo . '</section>';
}

/** Bloco de texto (abertura, quando ir). Vazio ou "nao informado": nada, nem o título. */
function tt_destino_texto_html( $html, $titulo = '' ) {
	if ( ! tt_roteiro_tem_conteudo( $html ) ) {
		return '';
	}
	return tt_destino_titulo_html( $titulo ) . '<div class="tt-destino-texto">' . $html . '</div>';
}

/** Texto dentro de uma seção (subtítulo h3). */
function tt_destino_bloco_html( $html, $subtitulo = '' ) {
	if ( ! tt_roteiro_tem_conteudo( $html ) ) {
		return '';
	}
	return tt_destino_subtitulo_html( $subtitulo ) . '<div class="tt-destino-texto">' . $html . '</div>';
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

/** Subchamada (hero ou card). $estilo: hero | card. */
function tt_destino_subchamada_html( $texto, $estilo = 'hero' ) {
	$texto = tt_destino_texto_curto( $texto, 160 );
	if ( '' === $texto ) {
		return '';
	}
	return '<p class="tt-destino-subchamada tt-destino-subchamada--' . ( 'card' === $estilo ? 'card' : 'hero' ) . '">' . esc_html( $texto ) . '</p>';
}

/** Destaques da visão geral: perfis (etiquetas) e duração ideal. */
function tt_destino_destaques_html( $perfis, $duracao ) {
	$rotulos = tt_destino_perfis();
	$itens   = '';
	foreach ( tt_destino_perfis_ler( $perfis ) as $p ) {
		$itens .= '<li class="tt-destino-perfil">' . esc_html( $rotulos[ $p ] ) . '</li>';
	}
	$duracao = tt_destino_texto_curto( $duracao, 60 );
	$html    = '';
	if ( '' !== $itens ) {
		$html .= '<div class="tt-destino-destaque"><span class="tt-destino-destaque__rotulo">Ideal para</span><ul class="tt-destino-perfis">' . $itens . '</ul></div>';
	}
	if ( '' !== $duracao ) {
		$html .= '<div class="tt-destino-destaque"><span class="tt-destino-destaque__rotulo">Duração ideal</span><strong class="tt-destino-destaque__valor">' . esc_html( $duracao ) . '</strong></div>';
	}
	return '' === $html ? '' : '<div class="tt-destino-destaques">' . $html . '</div>';
}

/** Clima: um card por estação, com máxima/mínima (°C) e chuva (mm). */
function tt_destino_clima_html( $valor, $subtitulo = '' ) {
	$estacoes = tt_destino_estacoes();
	$cards    = '';
	foreach ( tt_destino_json_ler( 'clima', $valor ) as $c ) {
		$dados = '';
		if ( null !== $c['max'] || null !== $c['min'] ) {
			$dados .= '<li class="tt-destino-clima__temp">'
				. ( null !== $c['max'] ? '<span>máx <strong>' . (int) $c['max'] . ' °C</strong></span>' : '' )
				. ( null !== $c['min'] ? '<span>mín <strong>' . (int) $c['min'] . ' °C</strong></span>' : '' ) . '</li>';
		}
		if ( null !== $c['chuva_mm'] ) {
			$dados .= '<li class="tt-destino-clima__chuva">chuva <strong>' . (int) $c['chuva_mm'] . ' mm</strong></li>';
		}
		$cards .= '<div class="tt-destino-clima__card"><h4 class="tt-destino-clima__estacao">' . esc_html( $estacoes[ $c['estacao'] ] ) . '</h4>'
			. ( '' !== $c['meses'] ? '<p class="tt-destino-clima__meses">' . esc_html( $c['meses'] ) . '</p>' : '' )
			. ( '' !== $dados ? '<ul>' . $dados . '</ul>' : '' ) . '</div>';
	}
	return '' === $cards ? '' : tt_destino_subtitulo_html( $subtitulo ) . '<div class="tt-destino-clima">' . $cards . '</div>';
}

/** Moeda, idioma e fuso (só internacional). */
function tt_destino_info_html( $moeda, $idioma, $fuso ) {
	$cards = '';
	foreach ( array( 'Moeda' => $moeda, 'Idioma' => $idioma, 'Fuso horário' => $fuso ) as $rotulo => $valor ) {
		$valor = tt_destino_texto_curto( $valor, 60 );
		if ( '' !== $valor ) {
			$cards .= '<div class="tt-destino-info__item"><span class="tt-destino-info__rotulo">' . esc_html( $rotulo ) . '</span><strong>' . esc_html( $valor ) . '</strong></div>';
		}
	}
	return '' === $cards ? '' : '<div class="tt-destino-info">' . $cards . '</div>';
}

/**
 * Cards com foto (pontos turísticos, passeios). $itens: os da lista JSON, cada um com 'foto_url' já resolvido
 * (foto que saiu da Biblioteca: sem imagem). $titulo/$texto: sub-campos do título e da linha de baixo.
 */
function tt_destino_cards_html( $itens, $titulo, $texto, $subtitulo = '', $credito = '' ) {
	$cards = '';
	foreach ( (array) $itens as $item ) {
		if ( ! is_array( $item ) || empty( $item[ $titulo ] ) ) {
			continue;
		}
		$foto   = ! empty( $item['foto_url'] )
			? '<figure class="tt-destino-card__foto"><img src="' . esc_url( $item['foto_url'] ) . '" alt="' . esc_attr( $item[ $titulo ] ) . '" loading="lazy">'
				. ( '' !== $credito ? '<figcaption>' . esc_html( $credito ) . '</figcaption>' : '' ) . '</figure>'
			: '';
		$linha  = isset( $item[ $texto ] ) && '' !== $item[ $texto ] ? '<p>' . esc_html( $item[ $texto ] ) . '</p>' : '';
		$cards .= '<article class="tt-destino-card' . ( '' === $foto ? ' tt-destino-card--sem-foto' : '' ) . '">' . $foto
			. '<div class="tt-destino-card__corpo"><h4>' . esc_html( $item[ $titulo ] ) . '</h4>' . $linha . '</div></article>';
	}
	return '' === $cards ? '' : tt_destino_subtitulo_html( $subtitulo ) . '<div class="tt-destino-cards">' . $cards . '</div>';
}

/** Gastronomia: prato + 1 linha. */
function tt_destino_gastronomia_html( $valor, $subtitulo = '' ) {
	$itens = '';
	foreach ( tt_destino_json_ler( 'gastronomia', $valor ) as $p ) {
		$itens .= '<li><strong>' . esc_html( $p['nome'] ) . '</strong>' . ( '' !== $p['descricao'] ? '<span>' . esc_html( $p['descricao'] ) . '</span>' : '' ) . '</li>';
	}
	return '' === $itens ? '' : tt_destino_subtitulo_html( $subtitulo ) . '<ul class="tt-destino-pratos">' . $itens . '</ul>';
}

/** Onde ficar: um card por bairro, com o perfil indicado. */
function tt_destino_onde_ficar_html( $valor ) {
	$cards = '';
	foreach ( tt_destino_json_ler( 'onde_ficar', $valor ) as $b ) {
		$cards .= '<article class="tt-destino-bairro"><h3>' . esc_html( $b['bairro'] ) . '</h3>'
			. ( '' !== $b['perfil'] ? '<span class="tt-destino-bairro__perfil">' . esc_html( $b['perfil'] ) . '</span>' : '' )
			. ( '' !== $b['descricao'] ? '<p>' . esc_html( $b['descricao'] ) . '</p>' : '' ) . '</article>';
	}
	return '' === $cards ? '' : '<div class="tt-destino-bairros">' . $cards . '</div>';
}

/** FAQ: perguntas que abrem (details/summary). */
function tt_destino_faq_html( $valor ) {
	$itens = '';
	foreach ( tt_destino_json_ler( 'faq', $valor ) as $q ) {
		$itens .= '<details class="tt-destino-faq__item"><summary>' . esc_html( $q['pergunta'] ) . '</summary><div class="tt-destino-faq__resposta"><p>' . esc_html( $q['resposta'] ) . '</p></div></details>';
	}
	return '' === $itens ? '' : '<div class="tt-destino-faq">' . $itens . '</div>';
}

/** JSON-LD FAQPage (para o Google) ou '' sem perguntas. */
function tt_destino_faq_jsonld( $valor ) {
	$perguntas = array();
	foreach ( tt_destino_json_ler( 'faq', $valor ) as $q ) {
		$perguntas[] = array(
			'@type'          => 'Question',
			'name'           => $q['pergunta'],
			'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $q['resposta'] ),
		);
	}
	if ( ! $perguntas ) {
		return '';
	}
	$json = wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $perguntas ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG );
	return '<script type="application/ld+json" class="tt-destino-faq-schema">' . $json . '</script>';
}

/** "Atualizado em dd/mm/aaaa" a partir de AAAA-MM-DD; data inválida: nada. */
function tt_destino_atualizado_html( $data ) {
	$data = tt_destino_limpar( 'atualizado_em', $data );
	if ( '' === $data ) {
		return '';
	}
	list( $a, $m, $d ) = explode( '-', $data );
	return '<p class="tt-destino-atualizado">Atualizado em ' . esc_html( $d . '/' . $m . '/' . $a ) . '</p>';
}
