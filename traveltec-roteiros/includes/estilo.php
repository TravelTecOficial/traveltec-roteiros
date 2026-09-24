<?php
/**
 * Cores e fontes.
 *
 * Os modelos saem do site de referência com a paleta dele gravada nos widgets e no CSS
 * (laranja #E98300, cinza #3C3D3D, amarelo #FFB700, Montserrat/Roboto). O plugin guarda qual é a
 * paleta atual e, ao aplicar uma nova, troca cor por cor e fonte por fonte em todos os documentos
 * que ele instalou — continua sendo cor comum no Elementor, editável pelo painel ou pelo MCP.
 * O Kit do Elementor (cores e fontes globais) recebe a mesma paleta.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TT_VOUCHER_OPT_ESTILO', 'tt_voucher_estilo' );

/** Paleta com que os modelos foram gerados (site de referência). */
function tt_voucher_estilo_padrao() {
	return array(
		'primaria'        => '#E98300',
		'primaria_escura' => '#C56F00',
		'primaria_clara'  => '#FFF1E0',
		'secundaria'      => '#3C3D3D',
		'terciaria'       => '#FFB700',
		'fonte_titulos'   => 'Montserrat',
		'fonte_textos'    => 'Roboto',
	);
}

function tt_voucher_estilo() {
	$salvo = get_option( TT_VOUCHER_OPT_ESTILO, array() );
	return array_merge( tt_voucher_estilo_padrao(), is_array( $salvo ) ? $salvo : array() );
}

function tt_voucher_hex( $cor ) {
	$cor = strtoupper( trim( (string) $cor ) );
	if ( preg_match( '/^#?([0-9A-F]{3})$/', $cor, $m ) ) {
		$cor = '#' . $m[1][0] . $m[1][0] . $m[1][1] . $m[1][1] . $m[1][2] . $m[1][2];
	}
	if ( preg_match( '/^#?([0-9A-F]{6})$/', $cor, $m ) ) {
		return '#' . $m[1];
	}
	return '';
}

function tt_voucher_rgb( $hex ) {
	$hex = ltrim( tt_voucher_hex( $hex ), '#' );
	return array( hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) );
}

/** Luminância relativa (0 = preto, 1 = branco). */
function tt_voucher_luminancia( $hex ) {
	$l = array();
	foreach ( tt_voucher_rgb( $hex ) as $c ) {
		$c   = $c / 255;
		$l[] = $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
	}
	return 0.2126 * $l[0] + 0.7152 * $l[1] + 0.0722 * $l[2];
}

/** Mistura a cor com preto ($alvo 0) ou branco ($alvo 255) na proporção $p (0..1). */
function tt_voucher_misturar( $hex, $alvo, $p ) {
	$saida = '#';
	foreach ( tt_voucher_rgb( $hex ) as $c ) {
		$saida .= sprintf( '%02X', (int) round( $c + ( $alvo - $c ) * $p ) );
	}
	return $saida;
}

/** Monta a paleta completa a partir das 3 cores do Cliente Ideal (escura e clara saem da primária). */
function tt_voucher_estilo_de( $entrada, $atual ) {
	$novo = $atual;
	$p    = tt_voucher_hex( $entrada['cor_primaria'] ?? '' );
	if ( $p ) {
		$novo['primaria']        = $p;
		$novo['primaria_escura'] = tt_voucher_misturar( $p, 0, 0.154 );   // #E98300 -> #C56F00
		$novo['primaria_clara']  = tt_voucher_misturar( $p, 255, 0.885 ); // #E98300 -> #FFF1E0 (aprox.)
	}
	// No layout a "secundária" é o cinza escuro dos textos e fundos escuros: a secundária da marca só
	// entra ali se for escura o bastante para texto; clara, vai só para o Kit (marca_secundaria).
	$s = tt_voucher_hex( $entrada['cor_secundaria'] ?? '' );
	if ( $s ) {
		$novo['marca_secundaria'] = $s;
		$novo['secundaria']       = tt_voucher_luminancia( $s ) <= 0.25 ? $s : tt_voucher_estilo_padrao()['secundaria'];
	}
	$t = tt_voucher_hex( $entrada['cor_terciaria'] ?? '' );
	if ( $t ) {
		$novo['terciaria'] = $t;
	}
	foreach ( array( 'fonte_titulos', 'fonte_textos' ) as $f ) {
		$v = trim( sanitize_text_field( (string) ( $entrada[ $f ] ?? '' ) ) );
		if ( '' !== $v ) {
			$novo[ $f ] = $v;
		}
	}
	return $novo;
}

/** Troca a paleta $de pela $para num texto (JSON do Elementor ou CSS). */
function tt_voucher_trocar_paleta( $txt, $de, $para ) {
	$cores = array();
	foreach ( array( 'primaria', 'primaria_escura', 'primaria_clara', 'secundaria', 'terciaria' ) as $k ) {
		if ( $de[ $k ] !== $para[ $k ] ) {
			$cores[ strtoupper( $de[ $k ] ) ] = $para[ $k ];
		}
	}
	if ( $cores ) {
		$txt = preg_replace_callback( '/#[0-9a-fA-F]{6}\b/', function ( $m ) use ( $cores ) {
			$k = strtoupper( $m[0] );
			return isset( $cores[ $k ] ) ? $cores[ $k ] : $m[0];
		}, $txt );
	}
	if ( $de['primaria'] !== $para['primaria'] ) {
		$txt = str_replace( 'rgba(' . implode( ',', tt_voucher_rgb( $de['primaria'] ) ) . ',', 'rgba(' . implode( ',', tt_voucher_rgb( $para['primaria'] ) ) . ',', $txt );
	}
	$fontes = array();
	foreach ( array( 'fonte_titulos', 'fonte_textos' ) as $k ) {
		if ( $de[ $k ] !== $para[ $k ] ) {
			$fontes[ $de[ $k ] ] = $para[ $k ];
		}
	}
	return $fontes ? strtr( $txt, $fontes ) : $txt;
}

/** Aplica a paleta nova em todos os documentos do plugin e no Kit. Devolve quantos documentos mudaram. */
function tt_voucher_aplicar_estilo( $novo ) {
	$atual = tt_voucher_estilo();
	$novo  = array_merge( $atual, $novo );
	$mudou = 0;
	if ( $novo !== $atual ) {
		foreach ( tt_voucher_ids_documentos() as $id ) {
			$data = get_post_meta( $id, '_elementor_data', true );
			if ( is_string( $data ) && '' !== $data ) {
				$nova = tt_voucher_trocar_paleta( $data, $atual, $novo );
				if ( $nova !== $data ) {
					update_post_meta( $id, '_elementor_data', wp_slash( $nova ) );
					$mudou++;
				}
			}
			$ps = get_post_meta( $id, '_elementor_page_settings', true );
			if ( is_array( $ps ) && $ps ) {
				$json = wp_json_encode( $ps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
				$novo_json = tt_voucher_trocar_paleta( $json, $atual, $novo );
				if ( $novo_json !== $json ) {
					update_post_meta( $id, '_elementor_page_settings', json_decode( $novo_json, true ) );
				}
			}
		}
		update_option( TT_VOUCHER_OPT_ESTILO, $novo );
	}
	tt_voucher_estilo_no_kit( $novo );
	tt_voucher_limpar_cache();
	return $mudou;
}

/** Cores e fontes globais do Kit do Elementor. */
function tt_voucher_estilo_no_kit( $e ) {
	$kit = (int) get_option( 'elementor_active_kit' );
	if ( ! $kit || ! get_post( $kit ) ) {
		return;
	}
	$s = get_post_meta( $kit, '_elementor_page_settings', true );
	$s = is_array( $s ) ? $s : array();

	$cores = array(
		'primary'   => array( 'Primária', $e['primaria'] ),
		'secondary' => array( 'Secundária', ! empty( $e['marca_secundaria'] ) ? $e['marca_secundaria'] : $e['secundaria'] ),
		'text'      => array( 'Texto', $e['secundaria'] ),
		'accent'    => array( 'Destaque', $e['terciaria'] ),
	);
	$lista = array();
	foreach ( $cores as $id => $c ) {
		$lista[] = array( '_id' => $id, 'title' => $c[0], 'color' => $c[1] );
	}
	$s['system_colors'] = $lista;

	$tipos = array(
		'primary'   => array( 'Títulos', $e['fonte_titulos'], '600' ),
		'secondary' => array( 'Subtítulos', $e['fonte_titulos'], '400' ),
		'text'      => array( 'Texto', $e['fonte_textos'], '400' ),
		'accent'    => array( 'Destaque', $e['fonte_textos'], '500' ),
	);
	$lista = array();
	foreach ( $tipos as $id => $t ) {
		$lista[] = array(
			'_id'                    => $id,
			'title'                  => $t[0],
			'typography_typography'  => 'custom',
			'typography_font_family' => $t[1],
			'typography_font_weight' => $t[2],
		);
	}
	$s['system_typography'] = $lista;
	update_post_meta( $kit, '_elementor_page_settings', $s );
}

function tt_voucher_limpar_cache() {
	if ( class_exists( '\Elementor\Plugin' ) ) {
		try {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		} catch ( \Throwable $e ) {
			error_log( 'Voucher Tec: limpar CSS — ' . $e->getMessage() );
		}
	}
	do_action( 'litespeed_purge_all' );
}

/**
 * As fontes da paleta usadas no CSS das páginas (os widgets o Elementor já carrega sozinho).
 * Só com o site padrão instalado: nos sites da 1.x seria um download a mais sem uso.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! tt_voucher_ids_documentos() ) {
		return;
	}
	$e       = tt_voucher_estilo();
	$fams    = array_unique( array( $e['fonte_titulos'], $e['fonte_textos'] ) );
	$partes  = array();
	foreach ( $fams as $f ) {
		if ( $f && ! preg_match( '/^(arial|helvetica|georgia|verdana|tahoma|times|sans-serif|serif|system-ui)/i', $f ) ) {
			$partes[] = 'family=' . str_replace( ' ', '+', $f ) . ':wght@300;400;500;600;700';
		}
	}
	if ( $partes ) {
		wp_enqueue_style( 'tt-voucher-fontes', 'https://fonts.googleapis.com/css2?' . implode( '&', $partes ) . '&display=swap', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	}
} );
