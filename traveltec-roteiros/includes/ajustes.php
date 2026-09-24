<?php
/**
 * Ajustes automáticos nos sites que já estão no ar (layout antigo + peças do plugin).
 *
 * - Menus do site: as páginas instaladas entram nos menus que o site já usa. Item que já existe com o mesmo
 *   papel (ex.: "Quem Somos" → /quem-somos/) passa a apontar para a página nova; faltando, entra no fim do
 *   menu do cabeçalho. Pedido do dono (24/09/2026): "se está instalando tem que entrar".
 * - Assinatura TravelTec no rodapé: a versão antiga (em coluna, com filete) vira uma linha discreta, ao
 *   ativar ou atualizar o plugin — é marca da TravelTec, vale para todos os sites.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ Menus do site */

function tt_voucher_normalizar( $txt ) {
	return trim( strtolower( remove_accents( wp_strip_all_tags( (string) $txt ) ) ) );
}

/** Páginas do plugin que devem estar no menu, com os nomes/endereços que o site pode já usar para elas. */
function tt_voucher_alvos_menu( $ids ) {
	$alvos = array();
	$pag   = function ( $k ) use ( $ids ) {
		return ! empty( $ids['docs'][ $k ] ) && get_post( (int) $ids['docs'][ $k ] ) ? (int) $ids['docs'][ $k ] : 0;
	};
	if ( $pag( 'sobre' ) ) {
		$alvos['sobre'] = array( 'Sobre', $pag( 'sobre' ), '', array( 'sobre', 'quem-somos', 'sobre-nos', 'quem-sou', 'a-agencia' ), array( 'sobre', 'sobre nos', 'quem somos', 'a agencia' ) );
	}
	if ( $pag( 'contato' ) ) {
		$alvos['contato'] = array( 'Contato', $pag( 'contato' ), '', array( 'contato', 'fale-conosco', 'contatos' ), array( 'contato', 'fale conosco', 'contatos' ) );
	}
	if ( $pag( 'cotacao' ) ) {
		$alvos['cotacao'] = array( 'Cotação', $pag( 'cotacao' ), '', array( 'cotacao-de-viagens', 'cotacao-de-viagem', 'cotacao', 'orcamento' ), array( 'cotacao', 'cotacao de viagem', 'cotacao de viagens', 'orcamento', 'solicite um orcamento' ) );
	}
	// Roteiros só com roteiro publicado: lista vazia no menu não ajuda ninguém.
	$n = wp_count_posts( 'roteiros' );
	if ( $pag( 'lista' ) && ! empty( $n->publish ) ) {
		$url             = get_post_type_archive_link( 'roteiros' );
		$alvos['roteiros'] = array( 'Roteiros', 0, $url ? $url : home_url( '/roteiros/' ), array( 'roteiros', 'pacotes' ), array( 'roteiros', 'pacotes' ) );
	}
	return $alvos;
}

/** O item já cumpre o papel do alvo? (mesma página, mesmo endereço ou mesmo nome) */
function tt_voucher_item_do_alvo( $item, $alvo ) {
	list( , $page_id, $url, $slugs, $nomes ) = $alvo;
	if ( 'post_type' === $item->type && 'page' === $item->object ) {
		if ( $page_id && (int) $item->object_id === $page_id ) {
			return true;
		}
		if ( in_array( get_post_field( 'post_name', (int) $item->object_id ), $slugs, true ) ) {
			return true;
		}
	}
	if ( 'custom' === $item->type ) {
		$partes = wp_parse_url( (string) $item->url );
		$host   = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( empty( $partes['host'] ) || $partes['host'] === $host ) {
			$caminho = trim( isset( $partes['path'] ) ? $partes['path'] : '', '/' );
			$ancora  = isset( $partes['fragment'] ) ? $partes['fragment'] : '';
			if ( in_array( $caminho, $slugs, true ) || ( '' === $caminho && in_array( $ancora, $slugs, true ) ) ) {
				return true;
			}
		}
	}
	return in_array( tt_voucher_normalizar( $item->title ), $nomes, true );
}

/** O item já aponta para onde deve? */
function tt_voucher_item_certo( $item, $alvo ) {
	list( , $page_id, $url ) = $alvo;
	if ( $page_id ) {
		return 'post_type' === $item->type && (int) $item->object_id === $page_id;
	}
	return untrailingslashit( (string) $item->url ) === untrailingslashit( $url );
}

/**
 * Liga as páginas do plugin aos menus do site (os menus do próprio plugin ficam de fora: são refeitos na
 * instalação). Guarda como era cada item mudado em tt_voucher_menus_anteriores.
 */
function tt_voucher_menus_ligar( &$rel = null ) {
	$ids   = tt_voucher_ids();
	$alvos = tt_voucher_alvos_menu( $ids );
	if ( ! $alvos ) {
		return;
	}
	$locais  = array_filter( array_map( 'intval', (array) get_nav_menu_locations() ) );
	$proprios = array_map( 'intval', array_values( isset( $ids['menus'] ) ? (array) $ids['menus'] : array() ) );
	$menus   = array_diff( array_unique( array_values( $locais ) ), $proprios );
	// menu do cabeçalho: o da posição menu-1 (Hello Elementor) ou o primeiro com posição
	$cabecalho = ! empty( $locais['menu-1'] ) ? $locais['menu-1'] : ( $locais ? reset( $locais ) : 0 );
	$backup    = get_option( 'tt_voucher_menus_anteriores', array() );

	foreach ( $menus as $menu_id ) {
		$itens = (array) wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) );
		$ultima = 0;
		foreach ( $itens as $item ) {
			$ultima = max( $ultima, (int) $item->menu_order );
		}
		foreach ( $alvos as $chave => $alvo ) {
			$achou = false;
			foreach ( $itens as $item ) {
				if ( ! tt_voucher_item_do_alvo( $item, $alvo ) ) {
					continue;
				}
				$achou = true;
				if ( tt_voucher_item_certo( $item, $alvo ) ) {
					continue;
				}
				$backup[ $item->ID ] = array( 'menu' => $menu_id, 'type' => $item->type, 'object' => $item->object, 'object_id' => (int) $item->object_id, 'url' => $item->url );
				$base = array(
					'menu-item-title'     => $item->title,
					'menu-item-position'  => (int) $item->menu_order,
					'menu-item-parent-id' => (int) $item->menu_item_parent,
					'menu-item-status'    => 'publish',
					'menu-item-classes'   => implode( ' ', array_filter( (array) $item->classes ) ),
					'menu-item-target'    => $item->target,
				);
				wp_update_nav_menu_item( $menu_id, $item->ID, $base + tt_voucher_destino_item( $alvo ) );
				if ( is_array( $rel ) ) {
					$rel['atualizados'][] = 'menu: "' . $item->title . '" aponta para ' . $alvo[0];
				}
			}
			if ( ! $achou && (int) $menu_id === (int) $cabecalho ) {
				wp_update_nav_menu_item( $menu_id, 0, array(
					'menu-item-title'    => $alvo[0],
					'menu-item-position' => ++$ultima,
					'menu-item-status'   => 'publish',
				) + tt_voucher_destino_item( $alvo ) );
				if ( is_array( $rel ) ) {
					$rel['criados'][] = 'menu: item "' . $alvo[0] . '"';
				}
			}
		}
	}
	update_option( 'tt_voucher_menus_anteriores', $backup );
}

function tt_voucher_destino_item( $alvo ) {
	return $alvo[1]
		? array( 'menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $alvo[1] )
		: array( 'menu-item-type' => 'custom', 'menu-item-url' => $alvo[2] );
}

/* ------------------------------------------------------------------ Assinatura TravelTec */

/** Versão em linha discreta. Mantém a cor do texto e o filtro do logo da antiga (ex.: logo branco no fundo escuro). */
function tt_voucher_assinatura_html( $img, $filtro, $cor ) {
	$filtro = $filtro ? 'filter:' . $filtro . ';' : '';
	$cor    = $cor ? 'color:' . $cor : 'color:inherit;opacity:.6';
	return '<div class="tt-assinatura">' . "\n"
		. '  <span class="tt-assinatura__label">Desenvolvido por</span>' . "\n"
		. '  <a class="tt-assinatura__link" href="https://traveltec.com.br" target="_blank" rel="noopener" aria-label="Site desenvolvido por TravelTec (abre em nova aba)">' . "\n"
		. '    <img src="' . esc_url( $img ) . '" alt="TravelTec" width="140" height="32" loading="lazy" decoding="async">' . "\n"
		. '  </a>' . "\n"
		. '  <span class="tt-assinatura__sep" aria-hidden="true"></span>' . "\n"
		. '  <span class="tt-assinatura__tag">Premium Exclusive</span>' . "\n"
		. '</div>' . "\n"
		. '<style>' . "\n"
		. '/* assinatura TravelTec numa linha discreta (Voucher Tec) */' . "\n"
		. '.tt-assinatura{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:6px 14px;padding:10px 16px;text-align:center}' . "\n"
		. '.tt-assinatura__label,.tt-assinatura__tag{font-size:10px;line-height:1;letter-spacing:.18em;text-transform:uppercase;' . $cor . '}' . "\n"
		. '.tt-assinatura__link{display:inline-flex;' . $filtro . 'opacity:.6;transition:opacity .2s ease}' . "\n"
		. '.tt-assinatura__link:hover{opacity:.9}' . "\n"
		. '.tt-assinatura__link img{display:block;height:18px;width:auto}' . "\n"
		. '.tt-assinatura__sep{width:1px;height:12px;background:currentColor;opacity:.25}' . "\n"
		. '</style>';
}

/** Troca a assinatura antiga (em coluna, com filete) em todos os modelos do Elementor. Devolve quantos mudaram. */
function tt_voucher_assinatura_discreta() {
	global $wpdb;
	$ids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' AND meta_value LIKE '%tt-assinatura__rule%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$mudou = 0;
	foreach ( $ids as $id ) {
		$data = json_decode( (string) get_post_meta( (int) $id, '_elementor_data', true ), true );
		if ( ! is_array( $data ) ) {
			continue;
		}
		$trocou = false;
		$andar  = function ( &$els ) use ( &$andar, &$trocou ) {
			foreach ( $els as &$e ) {
				$html = isset( $e['settings']['html'] ) ? (string) $e['settings']['html'] : '';
				if ( false !== strpos( $html, 'tt-assinatura__rule' ) ) {
					$img    = preg_match( '/<img[^>]+src="([^"]+)"/', $html, $m ) ? $m[1] : '/wp-content/uploads/2026/09/traveltec-logo.png';
					$filtro = preg_match( '/\.tt-assinatura__link\s*\{[^}]*filter:\s*([^;}]+)/', $html, $f ) ? trim( $f[1] ) : '';
					$cor    = preg_match( '/--tt-fg:\s*(#[0-9a-fA-F]{3,8})/', $html, $c ) ? $c[1] : '';
					$e['settings']['html'] = tt_voucher_assinatura_html( $img, $filtro, $cor );
					$trocou = true;
				}
				if ( ! empty( $e['elements'] ) ) {
					$andar( $e['elements'] );
				}
			}
		};
		$andar( $data );
		if ( $trocou ) {
			update_post_meta( (int) $id, '_elementor_data', wp_slash( wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
			$mudou++;
		}
	}
	return $mudou;
}

/* ------------------------------------------------------------------ Quando rodar */

/** Uma vez por versão do plugin (ativação ou atualização): assinatura e, com páginas instaladas, os menus. */
function tt_voucher_ajustes_da_versao() {
	if ( get_option( 'tt_voucher_ajustes_versao' ) === TT_ROTEIROS_VERSION ) {
		return;
	}
	update_option( 'tt_voucher_ajustes_versao', TT_ROTEIROS_VERSION );
	$mudou = tt_voucher_assinatura_discreta();
	if ( tt_voucher_ids_documentos() ) {
		tt_voucher_menus_ligar();
	}
	if ( $mudou ) {
		tt_voucher_limpar_cache();
	}
}
add_action( 'admin_init', 'tt_voucher_ajustes_da_versao' );
