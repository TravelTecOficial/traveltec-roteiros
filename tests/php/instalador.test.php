<?php
// Instalação num site com layout próprio (AV Turismo, 15/10/2026) — includes/instalador.php
require __DIR__ . '/bootstrap.php';

define( 'OBJECT', 'OBJECT' );
$GLOBALS['tt_paginas'] = array(); // slug => [ id, status ]
$GLOBALS['tt_novos']   = 900;
function get_page_by_path( $slug, $o = null, $t = 'page' ) {
	return isset( $GLOBALS['tt_paginas'][ $slug ] ) ? (object) array( 'ID' => $GLOBALS['tt_paginas'][ $slug ][0], 'post_name' => $slug ) : null;
}
function get_post_status( $id ) {
	foreach ( $GLOBALS['tt_paginas'] as $p ) {
		if ( $p[0] === (int) $id ) {
			return $p[1];
		}
	}
	return 'publish';
}
function get_post( $id ) { return $id ? (object) array( 'ID' => $id ) : null; }
function wp_insert_post( $a ) { return ++$GLOBALS['tt_novos']; }
function is_wp_error( $x ) { return false; }
function get_option( $k, $p = false ) { return $p; }

require '/p/includes/instalador.php';

/* ---------------------------------------------------------------- condições que disputam o mesmo lugar */
ok( tt_voucher_condicao_disputa( 'include/general', 'include/general' ), 'cabeçalho do site inteiro disputa com o do plugin' );
ok( tt_voucher_condicao_disputa( 'include/general', 'include/singular/page' ), 'cabeçalho do site só nas páginas também conta (o site tem cabeçalho próprio)' );
ok( tt_voucher_condicao_disputa( 'include/singular/post', 'include/singular' ), 'single "todos os singulares" do site cobre os posts' );
ok( tt_voucher_condicao_disputa( 'include/singular/post', 'include/singular/post/in_category/3' ), 'single de uma categoria de posts conta' );
ok( tt_voucher_condicao_disputa( 'include/archive', 'include/archive/post_archive' ), 'arquivo de posts do site (DSelection #1030) conta' );
ok( ! tt_voucher_condicao_disputa( 'include/singular/post', 'include/singular/page' ), 'single de páginas não disputa com o de posts' );
ok( ! tt_voucher_condicao_disputa( 'include/archive', 'include/archive/roteiros_archive' ), 'lista de roteiros (do plugin, inclusive a da 1.x) não conta' );
ok( ! tt_voucher_condicao_disputa( 'include/singular/post', 'include/singular/destinos' ), 'página de destino não conta' );
ok( ! tt_voucher_condicao_disputa( 'include/general', 'exclude/singular/page' ), 'exclusão não ocupa lugar' );

$header = array( 'template_type' => 'header', 'condicoes' => array( 'include/general' ) );
$post   = array( 'template_type' => 'single', 'condicoes' => array( 'include/singular/post' ) );
// AV Turismo: Header 48, Footer 50, Blog 68 (arquivo) e 70 (post).
$av = array(
	48 => array( 'header', array( 'include/general' ) ),
	50 => array( 'footer', array( 'include/general' ) ),
	68 => array( 'archive', array( 'include/archive' ) ),
	70 => array( 'single-post', array( 'include/singular/post' ) ),
);
igual( 48, tt_voucher_ocupante( $header, $av ), 'AV Turismo: o cabeçalho #48 ocupa o lugar — o do plugin não entra' );
igual( 70, tt_voucher_ocupante( $post, $av ), 'AV Turismo: o "Single Post" #70 ocupa o lugar do post do blog' );
igual( 0, tt_voucher_ocupante( $header, array( 50 => $av[50], 68 => $av[68] ) ), 'rodapé e arquivo não ocupam o lugar do cabeçalho' );
igual( 0, tt_voucher_ocupante( $header, array( 48 => array( 'header', array() ) ) ), 'cabeçalho do site sem condição: lugar livre' );

igual( array( 'header', 'footer', 'menu-popup', 'blog-arquivo', 'blog-post' ), tt_voucher_pecas_do_site(), 'peças do layout do site' );

/* ---------------------------------------------------------------- páginas genéricas */
$rel = array( 'criados' => array(), 'conflitos' => array(), 'avisos' => array() );
$ids = tt_voucher_ids();
$GLOBALS['tt_paginas'] = array( 'quem-somos' => array( 12, 'publish' ), 'fale-conosco' => array( 13, 'draft' ) );

igual( 0, tt_voucher_garantir_pagina( array( 'chave' => 'sobre', 'slug' => 'sobre', 'titulo' => 'Sobre' ), $ids, $rel, false ), 'Sobre não é criada: o site tem /quem-somos/' );
igual( 12, $ids['equivalentes']['sobre'], 'a página do site fica anotada como equivalente' );
ok( empty( $ids['docs']['sobre'] ), 'e não vira documento do plugin (o conteúdo dela não é trocado)' );
ok( 0 < tt_voucher_garantir_pagina( array( 'chave' => 'contato', 'slug' => 'contato', 'titulo' => 'Contato' ), $ids, $rel, false ), 'rascunho /fale-conosco/ não conta: Contato é criada' );
igual( 0, tt_voucher_garantir_pagina( array( 'chave' => 'obrigado-cotacao', 'slug' => 'obrigado-cotacao', 'titulo' => 'Obrigado' ), $ids, $rel, false ), 'obrigado da cotação não é criado sem a Cotação do plugin' );
ok( 0 < tt_voucher_garantir_pagina( array( 'chave' => 'obrigado-contato', 'slug' => 'obrigado-contato', 'titulo' => 'Obrigado' ), $ids, $rel, false ), 'obrigado do contato é criado com a página Contato do plugin' );
ok( 0 < tt_voucher_garantir_pagina( array( 'chave' => 'sobre', 'slug' => 'sobre', 'titulo' => 'Sobre' ), $ids, $rel, true ), 'com substituir, Sobre é criada mesmo com /quem-somos/' );
