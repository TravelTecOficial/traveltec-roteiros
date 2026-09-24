<?php
/**
 * Captação: UTMs e ids de clique + envio dos formulários ao webhook recebe-forms (n8n),
 * no mesmo formato das landing pages. Ligada quando os formulários do plugin são instalados
 * (option tt_voucher_captacao) — num site que já tem a captação por outro meio, fica desligada.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', function () {
	if ( ! get_option( 'tt_voucher_captacao' ) ) {
		return;
	}
	$forms = array();
	foreach ( tt_voucher_ids()['forms'] as $chave => $id ) {
		$forms[ (string) $id ] = $chave;
	}
	$dados = tt_voucher_dados();
	wp_enqueue_script( 'tt-voucher-captacao', TT_ROTEIROS_URL . 'assets/captacao.js', array( 'jquery' ), TT_ROTEIROS_VERSION, true );
	wp_add_inline_script( 'tt-voucher-captacao', 'window.TT_VOUCHER_CAPTACAO=' . wp_json_encode( array(
		'webhook'    => $dados['webhook_url'] ? $dados['webhook_url'] : TT_VOUCHER_WEBHOOK_PADRAO,
		'company_id' => $dados['id'],
		'forms'      => (object) $forms,
	) ) . ';', 'before' );
} );
