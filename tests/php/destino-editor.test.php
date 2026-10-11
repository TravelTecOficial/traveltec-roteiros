<?php
// Tela de edição do destino — includes/destino-editor.php
require __DIR__ . '/bootstrap.php';

$GLOBALS['tt_pode']   = true;
$GLOBALS['tt_nonce']  = true;
$GLOBALS['tt_anexos'] = array( 127 => 'https://x.test/n1.jpg' );
function wp_verify_nonce( $n, $a ) { return $GLOBALS['tt_nonce'] && 'tt_destinos_salvar' === $a; }
function sanitize_text_field( $s ) { return trim( (string) $s ); }
function wp_unslash( $v ) { return $v; }
function current_user_can( $cap, $id = 0 ) { return $GLOBALS['tt_pode']; }
function wp_get_attachment_image_url( $id, $tam = 'thumbnail' ) { return isset( $GLOBALS['tt_anexos'][ $id ] ) ? $GLOBALS['tt_anexos'][ $id ] : false; }
function get_the_post_thumbnail( $id, $tam ) { return 5 === $id ? '<img src="capa.jpg">' : ''; }
function get_post_field( $campo, $id ) { return 5 === $id ? 'Arquipélago <b>protegido</b>' : ''; }

require '/p/includes/roteiro-dados.php';
require '/p/includes/destino-dados.php';
require '/p/includes/destino-editor.php';

/* ---------------------------------------------------------------- a caixa */
$post = (object) array( 'ID' => 5, 'post_excerpt' => 'Ilha & mar' );
$GLOBALS['tt_meta'][5] = array( 'texto_de_abertura' => '<p>Abertura</p>', 'o_que_fazer' => "Barco\nTrilha \"A\"", 'quando_ir' => '', 'galeria' => '127,999' );
ob_start();
tt_destino_desenhar_caixa( $post );
$html = ob_get_clean();

contem( 'name="tt_destinos_nonce"', $html, 'caixa: nonce' );
contem( 'class="tt-rot tt-dest" data-tt-rot', $html, 'caixa: usa a mesma raiz do editor de roteiros (o JS liga sozinho)' );
igual( 4, substr_count( $html, 'data-tt-aba="' ), 'caixa: quatro abas' );
igual( 4, substr_count( $html, 'data-tt-painel="' ), 'caixa: quatro painéis' );
igual( 3, substr_count( $html, 'tt-rot__ponto is-cheio' ), 'caixa: Resumo, O que fazer e Fotos preenchidas; Quando ir vazia' );
contem( '<textarea class="widefat" rows="3" id="tt_dest_resumo" name="excerpt" data-tt-contador="160">Ilha &amp; mar</textarea>', $html, 'resumo: grava no resumo do post (campo "excerpt" do WordPress)' );
contem( 'name="tt_destinos[texto_de_abertura]" data-tt-editor>&lt;p&gt;Abertura&lt;/p&gt;</textarea>', $html, 'abertura: editor visual' );
contem( 'data-tt-itens data-nome="tt_destinos[o_que_fazer][]"', $html, 'lista: caixa de itens' );
contem( '<input type="hidden" name="tt_destinos[o_que_fazer_presente]" value="1">', $html, 'lista: marca de presença (para gravar a lista vazia)' );
contem( 'name="tt_destinos[o_que_fazer][]" value="Trilha &quot;A&quot;"', $html, 'lista: um campo por item, escapado' );
igual( 2, substr_count( $html, 'name="tt_destinos[o_que_fazer][]"' ), 'lista: dois itens gravados, dois campos' );
contem( 'name="tt_destinos[galeria]"', $html, 'fotos: campo com os IDs' );
contem( '&quot;id&quot;:127', $html, 'fotos: foto existente entra com o ID' );
ok( false === strpos( $html, '999' ), 'fotos: ID apagado da Biblioteca não entra na tela' );
contem( 'data-titulo="Escolher fotos do destino"', $html, 'fotos: texto da janela de mídia' );

/* ---------------------------------------------------------------- salvar */
$_POST = array( 'tt_destinos' => array( 'quando_ir' => '<p>Verão</p>', 'o_que_fazer' => array( 'A', 'B' ), 'galeria' => '[{"id":127},{"id":128}]' ) );
igual( array(), tt_destino_salvar( 5 ), 'salvar: sem nonce, não grava' );
$_POST['tt_destinos_nonce'] = 'x';
$GLOBALS['tt_pode']         = false;
igual( array(), tt_destino_salvar( 5 ), 'salvar: sem permissão, não grava' );
$GLOBALS['tt_pode'] = true;
igual( array( 'o_que_fazer', 'quando_ir', 'galeria' ), tt_destino_salvar( 5 ), 'salvar: grava o que veio' );
igual( "A\nB", $GLOBALS['tt_meta'][5]['o_que_fazer'], 'salvar: lista, um por linha' );
igual( '127,128', $GLOBALS['tt_meta'][5]['galeria'], 'salvar: galeria em IDs' );
igual( '<p>Abertura</p>', $GLOBALS['tt_meta'][5]['texto_de_abertura'], 'salvar: campo que não veio fica como estava' );
$_POST['tt_destinos'] = array( 'o_que_fazer_presente' => '1' );
tt_destino_salvar( 5 );
igual( '', $GLOBALS['tt_meta'][5]['o_que_fazer'], 'salvar: todos os itens removidos esvaziam a lista' );
ok( isset( $GLOBALS['tt_acoes']['save_post_destinos'] ), 'salvar: ligado ao save_post_destinos' );

/* ---------------------------------------------------------------- colunas da lista */
igual( array( 'cb', 'tt_capa', 'title', 'tt_resumo', 'date' ), array_keys( tt_destino_colunas( array( 'cb' => '', 'title' => 'Título', 'date' => 'Data' ) ) ), 'colunas: Capa antes do título, Resumo depois' );
ob_start();
tt_destino_coluna_valor( 'tt_capa', 5 );
tt_destino_coluna_valor( 'tt_resumo', 5 );
tt_destino_coluna_valor( 'tt_resumo', 6 );
$col = ob_get_clean();
igual( '<img src="capa.jpg">Arquipélago protegido—', $col, 'colunas: capa, resumo em texto simples e traço quando vazio' );
