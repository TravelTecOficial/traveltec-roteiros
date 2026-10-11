<?php
// Tipo Destinos, shortcodes e formulário — includes/destinos.php
require __DIR__ . '/bootstrap.php';

$GLOBALS['tt_tipos']      = array();
$GLOBALS['tt_shortcodes'] = array();
$GLOBALS['tt_ids']        = array( 'docs' => array(), 'forms' => array(), 'menus' => array(), 'midia' => array() );
$GLOBALS['tt_vivos']      = array();
$GLOBALS['tt_anexos']     = array( 127 => 'https://x.test/n1.jpg', 128 => 'https://x.test/n2.jpg' );

function post_type_exists( $t ) { return isset( $GLOBALS['tt_tipos'][ $t ] ); }
function register_post_type( $t, $args ) { $GLOBALS['tt_tipos'][ $t ] = $args; }
function register_post_meta( $t, $chave, $args ) { $GLOBALS['tt_metas'][ $t ][ $chave ] = $args; }
function add_shortcode( $nome, $cb ) { $GLOBALS['tt_shortcodes'][ $nome ] = $cb; }
function shortcode_atts( $padrao, $atts ) { return array_merge( $padrao, array_intersect_key( (array) $atts, $padrao ) ); }
function do_shortcode( $s ) { return 'FORM(' . $s . ')'; }
function get_the_ID() { return 9; }
function wpautop( $s ) { return $s; }
function sanitize_key( $s ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $s ) ); }
function wp_get_attachment_image_url( $id, $tam = 'thumbnail' ) { return isset( $GLOBALS['tt_anexos'][ $id ] ) ? $GLOBALS['tt_anexos'][ $id ] . '?' . $tam : false; }
function tt_voucher_ids() { return $GLOBALS['tt_ids']; }
function tt_voucher_vivo( $id ) { return in_array( $id, $GLOBALS['tt_vivos'], true ); }

require '/p/includes/roteiro-dados.php';
require '/p/includes/destino-dados.php';
require '/p/includes/destinos.php';

/* ---------------------------------------------------------------- tipo de conteúdo */
tt_destinos_registrar_tipo();
$tipo = $GLOBALS['tt_tipos']['destinos'];
igual( 'destinos', $tipo['has_archive'], 'arquivo em /destinos/' );
igual( array( 'slug' => 'destinos', 'with_front' => false ), $tipo['rewrite'], 'endereço /destinos/<slug>/' );
ok( true === $tipo['public'] && true === $tipo['show_in_rest'] && 'destinos' === $tipo['rest_base'], 'público e na REST' );
igual( array( 'title', 'thumbnail', 'excerpt', 'revisions' ), $tipo['supports'], 'título, capa, resumo e revisões' );
$GLOBALS['tt_tipos']['destinos'] = 'de-outro-plugin';
tt_destinos_registrar_tipo();
igual( 'de-outro-plugin', $GLOBALS['tt_tipos']['destinos'], 'se o site já tem o tipo, não registra de novo' );
ok( isset( $GLOBALS['tt_acoes']['init'] ), 'ligado ao init' );

/* ---------------------------------------------------------------- shortcodes */
igual( array( 'tt_destino_texto', 'tt_destino_lista', 'tt_destino_galeria', 'tt_destino_form' ), array_keys( $GLOBALS['tt_shortcodes'] ), 'os quatro shortcodes' );
$sc = $GLOBALS['tt_shortcodes'];
$GLOBALS['tt_meta'][9] = array( 'texto_de_abertura' => '<p>Abertura</p>', 'quando_ir' => '', 'o_que_fazer' => "A\nB", 'galeria' => '127,999,128' );
igual( '<div class="tt-destino-texto"><p>Abertura</p></div>', $sc['tt_destino_texto']( array( 'campo' => 'texto_de_abertura' ) ), 'texto: abertura' );
igual( '', $sc['tt_destino_texto']( array( 'campo' => 'quando_ir', 'titulo' => 'Quando ir' ) ), 'texto: campo vazio some com o título' );
igual( '', $sc['tt_destino_texto']( array( 'campo' => 'galeria' ) ), 'texto: só aceita os campos de texto' );
igual( '', $sc['tt_destino_texto']( array() ), 'texto: sem campo, nada' );
contem( '<li>A</li><li>B</li>', $sc['tt_destino_lista']( array( 'titulo' => 'O que fazer' ) ), 'lista do destino' );
$gal = $sc['tt_destino_galeria']( array( 'titulo' => 'Galeria' ) );
igual( 2, substr_count( $gal, '<img' ), 'galeria: foto apagada da Biblioteca (999) é ignorada' );
contem( 'href="https://x.test/n1.jpg?large"', $gal, 'galeria: abre a foto grande' );
contem( 'src="https://x.test/n1.jpg?medium_large"', $gal, 'galeria: miniatura média' );
contem( 'data-elementor-lightbox-slideshow="tt-destino-9"', $gal, 'galeria: um grupo por destino' );

/* ---------------------------------------------------------------- formulário de cotação */
igual( 0, tt_destino_form_id(), 'sem formulário instalado: 0' );
igual( '', $sc['tt_destino_form']( array( 'titulo' => 'Solicite sua cotação' ) ), 'sem formulário: nada, nem o título' );
$GLOBALS['tt_ids']['forms']['cotacao'] = 305;
igual( 0, tt_destino_form_id(), 'formulário na lixeira: 0' );
$GLOBALS['tt_vivos'] = array( 305 );
igual( 305, tt_destino_form_id(), 'formulário instalado e vivo' );
$form = $sc['tt_destino_form']( array( 'titulo' => 'Solicite sua cotação' ) );
contem( '<h2 class="tt-destino-titulo">Solicite sua cotação</h2>', $form, 'formulário: título' );
contem( 'FORM([jet_fb_form form_id="305" submit_type="ajax"', $form, 'formulário: o de cotação do plugin' );
