<?php
// Cabeçalho transparente sobre o topo da página (classe tt-topo-transparente no body) — includes/dados.php
require __DIR__ . '/bootstrap.php';

$GLOBALS['tt_singular'] = '';
$GLOBALS['tt_ids']      = array( 'docs' => array() );
function add_shortcode( $n, $cb ) {}
function is_singular( $tipo = '' ) { return '' === $tipo ? '' !== $GLOBALS['tt_singular'] : $GLOBALS['tt_singular'] === $tipo; }
function is_front_page() { return false; }
function get_option( $k, $p = false ) { return $p; }
function get_queried_object_id() { return 7; }
function tt_voucher_ids() { return $GLOBALS['tt_ids']; }
function tt_destinos_ativo() { return ! empty( $GLOBALS['tt_ids']['docs']['destino-single'] ); }

require '/p/includes/dados.php';

ok( function_exists( 'tt_voucher_topo_transparente' ), 'a regra do cabeçalho transparente é uma função' );
$GLOBALS['tt_singular'] = 'roteiros';
ok( tt_voucher_topo_transparente(), 'página do roteiro: cabeçalho transparente' );
// DSelection, 15/10/2026: na página do destino o cabeçalho ficava com fundo, diferente de roteiros e posts
$GLOBALS['tt_singular'] = 'destinos';
ok( ! tt_voucher_topo_transparente(), 'destino sem a página do plugin instalada: cabeçalho normal' );
$GLOBALS['tt_ids']['docs']['destino-single'] = 1225;
ok( tt_voucher_topo_transparente(), 'página do destino do plugin: cabeçalho transparente sobre a foto' );
$GLOBALS['tt_singular'] = 'page';
ok( ! tt_voucher_topo_transparente(), 'página comum sem a marca: cabeçalho normal' );
