<?php
// Mapa da captação: instalados que existem + indicados à mão — includes/opcoes.php (2.4.1)
require __DIR__ . '/bootstrap.php';
$GLOBALS['tt_opts'] = array();
$GLOBALS['tt_posts'] = array( 943 => 'jet-form-builder', 256 => 'jet-form-builder', 1025 => 'jet-form-builder', 1147 => 'jet-form-builder', 50 => 'page' );
if ( ! function_exists( 'get_option' ) ) { function get_option( $k, $p = false ) { return array_key_exists( $k, $GLOBALS['tt_opts'] ) ? $GLOBALS['tt_opts'][ $k ] : $p; } }
if ( ! function_exists( 'update_option' ) ) { function update_option( $k, $v ) { $GLOBALS['tt_opts'][ $k ] = $v; return true; } }
if ( ! function_exists( 'get_post_type' ) ) { function get_post_type( $id ) { return isset( $GLOBALS['tt_posts'][ $id ] ) ? $GLOBALS['tt_posts'][ $id ] : false; } }
if ( ! function_exists( 'get_post' ) ) { function get_post( $id ) { return isset( $GLOBALS['tt_posts'][ $id ] ) ? (object) array( 'ID' => $id ) : null; } }
if ( ! function_exists( 'get_post_status' ) ) { function get_post_status( $id ) { return 'publish'; } }
if ( ! function_exists( 'absint' ) ) { function absint( $v ) { return abs( (int) $v ); } }
if ( ! function_exists( 'sanitize_text_field' ) ) { function sanitize_text_field( $s ) { return trim( (string) $s ); } }
if ( ! function_exists( 'sanitize_key' ) ) { function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) ); } }
define( 'TT_VOUCHER_OPT_IDS', 'tt_voucher_ids' );
function tt_voucher_ids() { $i = get_option( TT_VOUCHER_OPT_IDS, array() ); return array_merge( array( 'forms' => array() ), $i ); }
function tt_voucher_vivo( $id ) { return $id && get_post( $id ) && 'trash' !== get_post_status( $id ); }
require '/p/includes/opcoes.php';
function mapa() { $m = tt_voucher_forms_captacao(); ksort( $m ); return $m; }

// Rede Turística: mapa guardado com IDs de outro site (6010/6011) e os antigos 1025/1147.
update_option( TT_VOUCHER_OPT_IDS, array( 'forms' => array( 'cotacao' => 1025, 'contato' => 6010, 'newsletter' => 6011, 'newsletter-blog' => 1147 ) ) );
igual( array( '1025' => 'cotacao', '1147' => 'newsletter-blog' ), mapa(), 'IDs que não existem (6010/6011) saem do mapa' );

$r = tt_voucher_salvar_opcoes( array( 'form_newsletter' => '943', 'form_contato' => '#256' ) );
igual( array(), $r['avisos'], 'grava sem aviso' );
igual( array( '256' => 'contato', '943' => 'newsletter', '1025' => 'cotacao', '1147' => 'newsletter-blog' ), mapa(), '943 = newsletter, 256 = contato' );
igual( 1, get_option( 'tt_voucher_captacao' ), 'gravar formulário liga a captação' );

$r = tt_voucher_salvar_opcoes( array( 'form_cotacao' => '50' ) );
igual( 1, count( $r['avisos'] ), 'ID que não é formulário: aviso' );
igual( 'cotacao', tt_voucher_forms_captacao()['1025'], 'e o instalado continua' );

update_option( TT_VOUCHER_OPT_IDS, array( 'forms' => array( 'contato' => 1025 ) ) );
igual( array( '256' => 'contato', '943' => 'newsletter' ), mapa(), 'manual vale por cima do instalado do mesmo papel' );

tt_voucher_salvar_opcoes( array( 'form_contato' => '' ) );
igual( array( '943' => 'newsletter', '1025' => 'contato' ), mapa(), 'em branco volta ao instalado' );
