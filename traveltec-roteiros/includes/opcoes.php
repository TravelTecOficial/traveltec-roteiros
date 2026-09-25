<?php
/**
 * Opções de exibição escolhidas no painel (Voucher Tec):
 *
 * - Moeda do orçamento no formulário de cotação (aba Cotação): 'USD' (padrão) ou 'BRL'. Reescreve, no formulário de
 *   cotação instalado pelo plugin, o título e as faixas do campo field_orcamento. Sem conversão de câmbio: são
 *   faixas fixas em cada moeda (editáveis depois no JetFormBuilder).
 * - Preço nos roteiros (aba Roteiros): 'exibir' (padrão) ou 'consulte'. Na exibição, o campo preco_de_referencia sai
 *   como "Consulte-nos" (card e página do roteiro, sem reinstalar modelos). O dado gravado no roteiro não muda.
 *
 * Também aceitas em POST /wp-json/voucher-tec/v1/aplicar: moeda_orcamento (USD|BRL) e preco_roteiros (exibir|consulte).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TT_VOUCHER_OPT_MOEDA', 'tt_voucher_moeda_orcamento' );
define( 'TT_VOUCHER_OPT_PRECO', 'tt_voucher_preco_roteiros' );
define( 'TT_VOUCHER_TEXTO_CONSULTE', 'Consulte-nos' );

function tt_voucher_moeda_orcamento() {
	return 'BRL' === get_option( TT_VOUCHER_OPT_MOEDA, 'USD' ) ? 'BRL' : 'USD';
}

function tt_voucher_preco_roteiros() {
	return 'consulte' === get_option( TT_VOUCHER_OPT_PRECO, 'exibir' ) ? 'consulte' : 'exibir';
}

/* ------------------------------------------------------------------ Orçamento da cotação */

/** Título do campo e faixas de orçamento por pessoa em cada moeda. */
function tt_voucher_orcamento_moeda( $moeda ) {
	if ( 'BRL' === $moeda ) {
		return array(
			'titulo' => 'Orçamento por pessoa em reais',
			'faixas' => array( 'Entre R$ 10.000 e R$ 15.000', 'Entre R$ 15.000 e R$ 25.000', 'Entre R$ 25.000 e R$ 35.000', 'Entre R$ 35.000 e R$ 50.000', 'Acima de R$ 50.000' ),
		);
	}
	return array(
		'titulo' => 'Orçamento por pessoa em dólar americano',
		'faixas' => array( 'Entre $2.000 e $3.000', 'Entre $4.000 e $5.000', 'Entre $6.000 e $7.000', 'Entre $8.000 e $9.000', 'Acima de $10.000' ),
	);
}

/**
 * Troca, no conteúdo do formulário de cotação, o título e as faixas do campo de orçamento pela moeda escolhida.
 * Sem o campo, devolve o conteúdo como veio.
 */
function tt_voucher_orcamento_no_conteudo( $conteudo, $moeda ) {
	$conteudo = (string) $conteudo;
	$alvo     = tt_voucher_orcamento_moeda( $moeda );
	foreach ( array( 'USD', 'BRL' ) as $m ) {
		$conteudo = str_replace( tt_voucher_orcamento_moeda( $m )['titulo'], $alvo['titulo'], $conteudo );
	}
	$novo = preg_replace_callback(
		'/<!-- wp:jet-forms\/radio-field (\{.*?\}) \/-->/s',
		function ( $m ) use ( $alvo ) {
			$attrs = json_decode( $m[1], true );
			if ( ! is_array( $attrs ) || 'field_orcamento' !== ( isset( $attrs['name'] ) ? $attrs['name'] : '' ) ) {
				return $m[0];
			}
			$opcoes = array();
			foreach ( $alvo['faixas'] as $faixa ) {
				$opcoes[] = array( '__visible' => true, 'label' => $faixa, 'value' => $faixa );
			}
			$attrs['field_options'] = $opcoes;
			return '<!-- wp:jet-forms/radio-field ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ' /-->';
		},
		$conteudo
	);
	return null === $novo ? $conteudo : $novo;
}

/** Regrava o orçamento do formulário de cotação instalado. true = mudou, false = já estava assim, WP_Error = sem formulário. */
function tt_voucher_aplicar_moeda_orcamento( $moeda ) {
	$ids = tt_voucher_ids();
	$id  = isset( $ids['forms']['cotacao'] ) ? (int) $ids['forms']['cotacao'] : 0;
	if ( ! tt_voucher_vivo( $id ) ) {
		return new WP_Error( 'sem_form', 'O formulário de cotação do plugin não está instalado neste site — a escolha vale quando ele for instalado (aba Cotação › Instalar esta parte).' );
	}
	$atual = (string) get_post_field( 'post_content', $id );
	$novo  = tt_voucher_orcamento_no_conteudo( $atual, $moeda );
	if ( $novo === $atual ) {
		return false;
	}
	wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $novo ) ) );
	return true;
}

/* ------------------------------------------------------------------ Preço dos roteiros */

/**
 * Com "Consulte-nos" escolhido, o campo preco_de_referencia sai como o texto fixo em qualquer leitura do site
 * (Elementor lê pelo get_post_meta). Não vale no admin nem na REST — o n8n e o editor seguem vendo o valor gravado.
 */
add_filter( 'get_post_metadata', function ( $valor, $post_id, $chave, $single ) {
	if ( null !== $valor || 'preco_de_referencia' !== $chave || is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $valor;
	}
	if ( 'consulte' !== tt_voucher_preco_roteiros() || 'roteiros' !== get_post_type( $post_id ) ) {
		return $valor;
	}
	return $single ? TT_VOUCHER_TEXTO_CONSULTE : array( TT_VOUCHER_TEXTO_CONSULTE );
}, 10, 4 );

/* ------------------------------------------------------------------ Salvar (painel e REST) */

/** Grava as opções recebidas e aplica o que precisa ser aplicado. Devolve um relatório no formato do painel. */
function tt_voucher_salvar_opcoes( $entrada ) {
	$rel = array( 'dados_alterados' => array(), 'avisos' => array() );
	if ( isset( $entrada['moeda_orcamento'] ) ) {
		$moeda = 'BRL' === strtoupper( sanitize_text_field( (string) $entrada['moeda_orcamento'] ) ) ? 'BRL' : 'USD';
		update_option( TT_VOUCHER_OPT_MOEDA, $moeda );
		$r = tt_voucher_aplicar_moeda_orcamento( $moeda );
		if ( is_wp_error( $r ) ) {
			$rel['avisos'][] = $r->get_error_message();
		} else {
			$rel['dados_alterados'][] = 'Orçamento da cotação em ' . ( 'BRL' === $moeda ? 'reais' : 'dólar' ) . ( $r ? '' : ' (o formulário já estava assim)' );
		}
	}
	if ( isset( $entrada['preco_roteiros'] ) ) {
		$preco = 'consulte' === sanitize_key( (string) $entrada['preco_roteiros'] ) ? 'consulte' : 'exibir';
		update_option( TT_VOUCHER_OPT_PRECO, $preco );
		$rel['dados_alterados'][] = 'Preço dos roteiros: ' . ( 'consulte' === $preco ? TT_VOUCHER_TEXTO_CONSULTE : 'exibir o valor' );
	}
	return $rel;
}

add_action( 'admin_post_tt_voucher_opcoes', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Sem permissão.' );
	}
	check_admin_referer( 'tt_voucher_opcoes' );
	$entrada = array();
	foreach ( array( 'moeda_orcamento', 'preco_roteiros' ) as $campo ) {
		if ( isset( $_POST[ $campo ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce conferido acima
			$entrada[ $campo ] = sanitize_text_field( wp_unslash( $_POST[ $campo ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
	}
	set_transient( 'tt_voucher_relatorio', tt_voucher_salvar_opcoes( $entrada ), 300 );
	tt_voucher_limpar_cache();
	wp_safe_redirect( tt_voucher_url_painel( array( 'aba' => tt_voucher_aba_postada(), 'feito' => 'opcoes' ) ) );
	exit;
} );

/* ------------------------------------------------------------------ Painel */

/** Bloco do painel: uma escolha entre opções (botões de rádio) e o botão Salvar. */
function tt_voucher_form_opcao( $aba, $campo, $titulo, $descricao, $opcoes, $atual ) {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:8px 0 24px;max-width:860px">';
	wp_nonce_field( 'tt_voucher_opcoes' );
	echo '<input type="hidden" name="action" value="tt_voucher_opcoes"><input type="hidden" name="aba" value="' . esc_attr( $aba ) . '">';
	echo '<h2>' . esc_html( $titulo ) . '</h2><p class="description">' . esc_html( $descricao ) . '</p>';
	foreach ( $opcoes as $valor => $rotulo ) {
		echo '<label style="display:block;margin:6px 0"><input type="radio" name="' . esc_attr( $campo ) . '" value="' . esc_attr( $valor ) . '"' . checked( $atual, $valor, false ) . '> ' . esc_html( $rotulo ) . '</label>';
	}
	submit_button( 'Salvar', 'secondary', 'submit', false );
	echo '</form>';
}
