<?php
/**
 * Dados da agência — os mesmos campos da tabela `companies` do Cliente Ideal.
 *
 * Os modelos guardam marcadores em vez dos dados:
 *   %tt:campo%                 texto (telefone, e-mail, nome...)
 *   https://tt.token/<campo>   URL inteira (redes sociais, logo) — passa pelo esc_url do Elementor
 * Os marcadores são trocados na hora de exibir cada widget, então mudar um dado aqui muda o site
 * inteiro sem regravar página nenhuma.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TT_VOUCHER_OPT_DADOS', 'tt_voucher_cliente' );
define( 'TT_VOUCHER_WEBHOOK_PADRAO', 'https://exec.traveltec.com.br/webhook/recebe-forms' );

/** Campos aceitos (nome da coluna em `companies` => rótulo). Os três últimos não existem lá. */
function tt_voucher_campos() {
	return array(
		'id'                    => 'ID da empresa no Cliente Ideal (company_id)',
		'name'                  => 'Razão social / nome',
		'nome_fantasia'         => 'Nome fantasia',
		'celular_atendimento'   => 'WhatsApp / celular de atendimento',
		'email_atendimento'     => 'E-mail de atendimento',
		'logradouro'            => 'Logradouro',
		'numero'                => 'Número',
		'bairro'                => 'Bairro',
		'cidade'                => 'Cidade',
		'uf'                    => 'UF',
		'cep'                   => 'CEP',
		'horario_funcionamento' => 'Horário de atendimento',
		'instagram_url'         => 'Instagram (URL)',
		'facebook_url'          => 'Facebook (URL)',
		'linkedin_url'          => 'LinkedIn (URL)',
		'tiktok_url'            => 'TikTok (URL)',
		'logo_url'              => 'Logo (URL)',
		'description'           => 'Descrição curta',
		'history'               => 'História da agência',
		'sinopse'               => 'Sinopse',
		'site_oficial'          => 'Site oficial',
		'telefone'              => 'Telefone fixo (opcional)',
		'youtube_url'           => 'YouTube (URL)',
		'webhook_url'           => 'Webhook dos formulários',
	);
}

/** Campos de texto longo (textarea no painel). */
function tt_voucher_campos_longos() {
	return array( 'description', 'history', 'sinopse' );
}

function tt_voucher_dados() {
	$salvo = get_option( TT_VOUCHER_OPT_DADOS, array() );
	$dados = array_fill_keys( array_keys( tt_voucher_campos() ), '' );
	$dados['webhook_url'] = TT_VOUCHER_WEBHOOK_PADRAO;
	return array_merge( $dados, is_array( $salvo ) ? array_intersect_key( $salvo, $dados ) : array() );
}

/** Grava só os campos conhecidos que vieram. Devolve os campos alterados. */
function tt_voucher_salvar_dados( $entrada ) {
	$atual    = tt_voucher_dados();
	$alterado = array();
	foreach ( tt_voucher_campos() as $campo => $rotulo ) {
		if ( ! array_key_exists( $campo, (array) $entrada ) || null === $entrada[ $campo ] ) {
			continue;
		}
		$valor = $entrada[ $campo ];
		if ( '_url' === substr( $campo, -4 ) || 'site_oficial' === $campo ) {
			$valor = esc_url_raw( tt_voucher_normalizar_rede( $campo, (string) $valor ) );
		} elseif ( 'email_atendimento' === $campo ) {
			$valor = sanitize_email( (string) $valor );
		} elseif ( in_array( $campo, tt_voucher_campos_longos(), true ) ) {
			$valor = sanitize_textarea_field( (string) $valor );
		} else {
			$valor = sanitize_text_field( (string) $valor );
		}
		if ( $valor !== $atual[ $campo ] ) {
			$alterado[]       = $campo;
			$atual[ $campo ] = $valor;
		}
	}
	if ( '' === $atual['webhook_url'] ) {
		$atual['webhook_url'] = TT_VOUCHER_WEBHOOK_PADRAO;
	}
	update_option( TT_VOUCHER_OPT_DADOS, $atual );
	return $alterado;
}

/**
 * Redes cadastradas só com o perfil (@perfil, "http://@perfil", "instagram.com/perfil") viram URL completa.
 */
function tt_voucher_normalizar_rede( $campo, $valor ) {
	$valor = trim( $valor );
	$bases = array(
		'instagram_url' => 'https://www.instagram.com/%s/',
		'tiktok_url'    => 'https://www.tiktok.com/@%s',
		'youtube_url'   => 'https://www.youtube.com/@%s',
	);
	if ( '' === $valor || ! isset( $bases[ $campo ] ) ) {
		return $valor;
	}
	$dominio = array( 'instagram_url' => 'instagram.com', 'tiktok_url' => 'tiktok.com', 'youtube_url' => 'youtube.com' )[ $campo ];
	if ( false !== stripos( $valor, $dominio . '/' ) && false === strpos( $valor, '://@' ) ) {
		return $valor; // já é a URL do perfil
	}
	$perfil = preg_replace( '#^(https?://)?(www\.)?(' . preg_quote( $dominio, '#' ) . '/)?@?#i', '', $valor );
	$perfil = trim( preg_replace( '#[/?].*$#', '', $perfil ), '@ ' );
	return '' === $perfil ? '' : sprintf( $bases[ $campo ], $perfil );
}

function tt_voucher_digitos( $txt ) {
	return preg_replace( '/\D+/', '', (string) $txt );
}

/** 11999998888 / 5511999998888 -> (11) 99999-8888 */
function tt_voucher_formatar_fone( $txt ) {
	$d = tt_voucher_digitos( $txt );
	if ( strlen( $d ) >= 12 && 0 === strpos( $d, '55' ) ) {
		$d = substr( $d, 2 );
	}
	if ( 11 === strlen( $d ) ) {
		return '(' . substr( $d, 0, 2 ) . ') ' . substr( $d, 2, 5 ) . '-' . substr( $d, 7 );
	}
	if ( 10 === strlen( $d ) ) {
		return '(' . substr( $d, 0, 2 ) . ') ' . substr( $d, 2, 4 ) . '-' . substr( $d, 6 );
	}
	return trim( (string) $txt );
}

/** Dígitos com o 55 na frente (wa.me e tel:). */
function tt_voucher_e164( $txt ) {
	$d = tt_voucher_digitos( $txt );
	if ( '' === $d ) {
		return '';
	}
	return ( strlen( $d ) <= 11 ? '55' : '' ) . $d;
}

/**
 * Valor de um marcador. Além dos campos, há derivados:
 * nome, nome_url, whatsapp, whatsapp_digitos, telefone, telefone_digitos, endereco, mapa_q,
 * history_html, ano, company_id e os endereços url_cotacao, url_contato, url_sobre, url_blog, url_roteiros.
 */
function tt_voucher_valor( $campo ) {
	$d = tt_voucher_dados();
	switch ( $campo ) {
		case 'nome':
			return $d['nome_fantasia'] ? $d['nome_fantasia'] : ( $d['name'] ? $d['name'] : get_bloginfo( 'name' ) );
		case 'nome_url':
			return rawurlencode( tt_voucher_valor( 'nome' ) );
		case 'whatsapp':
			return tt_voucher_formatar_fone( $d['celular_atendimento'] );
		case 'whatsapp_digitos':
			return tt_voucher_e164( $d['celular_atendimento'] );
		case 'telefone':
			return tt_voucher_formatar_fone( $d['telefone'] ? $d['telefone'] : $d['celular_atendimento'] );
		case 'telefone_digitos':
			return tt_voucher_e164( $d['telefone'] ? $d['telefone'] : $d['celular_atendimento'] );
		case 'endereco':
			$rua    = trim( $d['logradouro'] . ( $d['numero'] ? ', ' . $d['numero'] : '' ) );
			$cidade = trim( $d['cidade'] . ( $d['uf'] ? '/' . strtoupper( $d['uf'] ) : '' ) );
			$partes = array_filter( array( $rua, $d['bairro'], $cidade, $d['cep'] ? 'CEP ' . $d['cep'] : '' ) );
			return implode( ' – ', $partes );
		case 'mapa_q':
			$q = implode( ', ', array_filter( array( trim( $d['logradouro'] . ' ' . $d['numero'] ), $d['cidade'], $d['uf'], $d['cep'] ) ) );
			return urlencode( $q ? $q : tt_voucher_valor( 'nome' ) );
		case 'horario_funcionamento':
			return $d['horario_funcionamento'] ? $d['horario_funcionamento'] : 'Seg. a sex., das 9h às 18h';
		case 'description':
			// a sinopse do Cliente Ideal é texto interno (orienta a IA) — nunca vai para o site
			return $d['description'] ? $d['description'] : 'Especialistas em transformar a sua próxima viagem em uma experiência inesquecível.';
		case 'history_html':
			$txt = $d['history'] ? $d['history'] : tt_voucher_valor( 'description' );
			return wpautop( esc_html( $txt ) );
		case 'ano':
			return wp_date( 'Y' );
		case 'company_id':
			return $d['id'];
		case 'url_cotacao':
			return tt_voucher_url_pagina( 'cotacao', array( 'cotacao-de-viagens', 'cotacao-de-viagem', 'cotacao', 'orcamento' ), 'url_contato' );
		case 'url_contato':
			return tt_voucher_url_pagina( 'contato', array( 'contato', 'fale-conosco', 'contatos' ), '' );
		case 'url_sobre':
			return tt_voucher_url_pagina( 'sobre', array( 'sobre', 'quem-somos', 'sobre-nos' ), '' );
		case 'url_blog':
			$blog = (int) get_option( 'page_for_posts' );
			return $blog ? get_permalink( $blog ) : home_url( '/blog/' );
		case 'url_roteiros':
			$url = get_post_type_archive_link( 'roteiros' );
			return $url ? $url : home_url( '/roteiros/' );
	}
	return isset( $d[ $campo ] ) ? (string) $d[ $campo ] : '';
}

/**
 * Endereço de uma página do site: a instalada pelo plugin, senão a que já existir com um dos endereços comuns
 * (sites antigos usam /cotacao-de-viagem/, /fale-conosco/, /quem-somos/...). Sem nenhuma, usa o marcador
 * $reserva (ou a home).
 */
function tt_voucher_url_pagina( $chave, $slugs, $reserva ) {
	$id = (int) ( tt_voucher_ids()['docs'][ $chave ] ?? 0 );
	if ( $id && 'publish' === get_post_status( $id ) ) {
		return get_permalink( $id );
	}
	foreach ( $slugs as $slug ) {
		$p = get_page_by_path( $slug );
		if ( $p && 'publish' === $p->post_status ) {
			return get_permalink( $p );
		}
	}
	return $reserva ? tt_voucher_valor( $reserva ) : home_url( '/' );
}

/** Troca os marcadores de um trecho de HTML. */
function tt_voucher_resolver( $html ) {
	if ( ! is_string( $html ) || ( false === strpos( $html, '%tt:' ) && false === strpos( $html, 'tt.token/' ) ) ) {
		return $html;
	}
	// URL inteira: rede social vazia vira #tt-vazio e o CSS esconde o ícone.
	$html = preg_replace_callback( '#https?://tt\.token/([a-z_]+)#', function ( $m ) {
		$url = tt_voucher_valor( $m[1] );
		return $url ? esc_url( $url ) : '#tt-vazio';
	}, $html );
	return preg_replace_callback( '/%tt:([a-z_]+)%/', function ( $m ) {
		$valor = tt_voucher_valor( $m[1] );
		return '_html' === substr( $m[1], -5 ) ? $valor : esc_html( $valor );
	}, $html );
}

add_filter( 'elementor/widget/render_content', 'tt_voucher_resolver', 20 );

/** [tt dado="whatsapp"] — para usar os dados em qualquer texto. */
add_shortcode( 'tt', function ( $atts ) {
	$atts = shortcode_atts( array( 'dado' => '' ), $atts, 'tt' );
	$v    = tt_voucher_valor( sanitize_key( $atts['dado'] ) );
	return '_html' === substr( $atts['dado'], -5 ) ? $v : esc_html( $v );
} );

/**
 * Cabeçalho transparente sobre o topo da página: roteiros, a home e as páginas marcadas com
 * o meta _tt_topo_transparente (a Sobre já vem marcada). O CSS mora no modelo do cabeçalho.
 */
add_filter( 'body_class', function ( $classes ) {
	$transparente = is_singular( 'roteiros' )
		|| ( is_front_page() && get_option( 'tt_voucher_home_transparente', '1' ) )
		|| ( is_singular() && get_post_meta( get_queried_object_id(), '_tt_topo_transparente', true ) );
	if ( $transparente ) {
		$classes[] = 'tt-topo-transparente';
	}
	return $classes;
} );

add_action( 'wp_head', function () {
	// Os marcadores só existem nos modelos do plugin: sem o site padrão instalado, nada a acrescentar.
	if ( ! tt_voucher_ids_documentos() ) {
		return;
	}
	echo '<style id="tt-voucher-vazio">a[href="#tt-vazio"],.elementor-icon-list-item:has(>a[href="#tt-vazio"]){display:none!important}</style>' . "\n";
	// Só nos sites com o site padrão instalado: logo retangular (o da referência é redondo) não cresce demais.
	if ( ! empty( tt_voucher_ids()['docs']['header'] ) ) {
		echo '<style id="tt-voucher-logo">.elementor-location-header .elementor-widget-theme-site-logo img{max-height:64px;width:auto!important;max-width:100%;object-fit:contain}@media (max-width:1024px){.elementor-location-header .elementor-widget-theme-site-logo img{max-height:52px}}</style>' . "\n";
	}
}, 5 );
