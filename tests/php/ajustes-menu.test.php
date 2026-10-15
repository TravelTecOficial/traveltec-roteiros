<?php
// Menus do site: quando um item já aponta para onde deve — includes/ajustes.php
require __DIR__ . '/bootstrap.php';

function untrailingslashit( $s ) { return rtrim( (string) $s, '/' ); }
function home_url( $p = '' ) { return 'https://exemplo.test' . $p; }
function wp_parse_url( $u, $c = -1 ) { return parse_url( $u, $c ); }
function get_post( $id ) { return in_array( (int) $id, $GLOBALS['tt_posts'], true ) ? (object) array( 'ID' => $id ) : null; }
function get_option( $k, $p = false ) { return isset( $GLOBALS['tt_opcoes'][ $k ] ) ? $GLOBALS['tt_opcoes'][ $k ] : $p; }
function get_post_type_archive_link( $t ) { return 'https://exemplo.test/' . $t . '/'; }
function tt_destinos_ativo() { return $GLOBALS['tt_destinos_ligados']; }
$GLOBALS['tt_posts']            = array();
$GLOBALS['tt_opcoes']           = array();
$GLOBALS['tt_destinos_ligados'] = false;

require '/p/includes/ajustes.php';

$destinos = array( 'Destinos', 0, 'https://exemplo.test/destinos/', array( 'destinos' ), array( 'destinos' ) );
$sobre    = array( 'Sobre', 236, '', array( 'sobre' ), array( 'sobre' ) );

$link   = (object) array( 'type' => 'custom', 'object' => 'custom', 'object_id' => 0, 'url' => 'https://exemplo.test/destinos' );
$pagina = (object) array( 'type' => 'post_type', 'object' => 'page', 'object_id' => 29, 'url' => 'https://exemplo.test/destinos/' );
$outro  = (object) array( 'type' => 'custom', 'object' => 'custom', 'object_id' => 0, 'url' => 'https://exemplo.test/pacotes/' );

ok( tt_voucher_item_certo( $link, $destinos ), 'link para /destinos/ (com ou sem barra no fim) já está certo' );
ok( ! tt_voucher_item_certo( $outro, $destinos ), 'link para outro endereço precisa ser reapontado' );
// Site-modelo, 11/10/2026: o item do rodapé era a PÁGINA "Destinos", com o mesmo endereço da lista de destinos.
// Contava como certo, a página foi para a lixeira e o item ficou apontando para /?page_id=29.
ok( ! tt_voucher_item_certo( $pagina, $destinos ), 'item que é uma página no mesmo endereço da lista precisa virar link (a página sai do ar)' );

ok( tt_voucher_item_certo( (object) array( 'type' => 'post_type', 'object' => 'page', 'object_id' => 236, 'url' => '' ), $sobre ), 'alvo que é página: o item da própria página está certo' );
ok( ! tt_voucher_item_certo( (object) array( 'type' => 'post_type', 'object' => 'page', 'object_id' => 30, 'url' => '' ), $sobre ), 'alvo que é página: item de outra página precisa ser reapontado' );

/* ---------------------------------------------------------------- 2.3.2: o que está habilitado entra no menu (pedido do dono, 15/10/2026) */
$ids = array( 'docs' => array( 'lista' => 1162, 'blog-arquivo' => 1167, 'destinos-lista' => 1224, 'sobre' => 1183, 'cotacao' => 1177, 'contato' => 1171 ) );
igual( array(), array_keys( tt_voucher_alvos_menu( $ids ) ), 'nada instalado (posts sumidos): nada a pôr no menu' );
$GLOBALS['tt_posts']                   = array( 1162, 1167, 1224, 1183, 1177, 1171, 1020 );
$GLOBALS['tt_opcoes']['page_for_posts'] = 1020;
$alvos = tt_voucher_alvos_menu( $ids );
igual( array( 'experiencias', 'roteiros', 'sobre', 'cotacao', 'contato' ), array_keys( $alvos ), 'partes instaladas entram mesmo sem conteúdo publicado, na ordem do menu; Destinos só com os destinos ligados' );
igual( array( 'Experiências', 1020, '' ), array_slice( $alvos['experiencias'], 0, 3 ), 'Experiências aponta para a página do blog' );
igual( 'https://exemplo.test/roteiros/', $alvos['roteiros'][2], 'Roteiros aponta para a lista /roteiros/' );
$GLOBALS['tt_destinos_ligados'] = true;
$alvos = tt_voucher_alvos_menu( $ids );
igual( array( 'destinos', 'experiencias', 'roteiros', 'sobre', 'cotacao', 'contato' ), array_keys( $alvos ), 'com os destinos ligados, Destinos entra antes de Experiências' );
unset( $GLOBALS['tt_opcoes']['page_for_posts'] );
ok( ! isset( tt_voucher_alvos_menu( $ids )['experiencias'] ), 'sem página do blog definida, Experiências não entra' );

igual( array( 'home', 'destinos', 'experiencias', 'roteiros', 'sobre', 'cotacao', 'contato' ), tt_voucher_menu_ordem(), 'ordem padrão do menu' );
igual( 2, tt_voucher_menu_posicao( array( 'home' => 1, 'experiencias' => 2, 'sobre' => 3 ), 'destinos', 3 ), 'Destinos entra logo depois de Home' );
igual( 3, tt_voucher_menu_posicao( array( 'home' => 1, 'destinos' => 2, 'sobre' => 3 ), 'roteiros', 3 ), 'Roteiros entra depois do último que vem antes dele' );
igual( 1, tt_voucher_menu_posicao( array( 'sobre' => 1, 'contato' => 2 ), 'destinos', 2 ), 'sem nada antes: entra antes do primeiro que vem depois' );
igual( 6, tt_voucher_menu_posicao( array( 'blog' => 5 ), 'contato', 5 ), 'menu sem nenhum papel conhecido: entra no fim' );

ok( tt_voucher_item_e_home( (object) array( 'type' => 'custom', 'url' => 'https://exemplo.test/#home' ) ), 'Home com âncora (#home) é a Home' );
ok( tt_voucher_item_e_home( (object) array( 'type' => 'custom', 'url' => '/' ) ), 'Home com "/" é a Home' );
ok( ! tt_voucher_item_e_home( (object) array( 'type' => 'custom', 'url' => 'https://exemplo.test/contato/' ) ), 'outro endereço não é a Home' );
ok( ! tt_voucher_item_e_home( (object) array( 'type' => 'custom', 'url' => 'https://outro.test/' ) ), 'raiz de outro site não é a Home' );

/* ---------------------------------------------------------------- menus_ligar de ponta a ponta (menu como o do DSelection, sem Destinos) */
$GLOBALS['tt_menu_itens'] = array();
$GLOBALS['tt_proximo_id'] = 900;
function tt_teste_item( $id, $ordem, $titulo, $tipo, $url, $object_id = 0 ) {
	return (object) array( 'ID' => $id, 'menu_order' => $ordem, 'title' => $titulo, 'menu_item_parent' => 0, 'classes' => array( '' ), 'target' => '', 'type' => $tipo, 'object' => 'custom' === $tipo ? 'custom' : 'page', 'object_id' => $object_id, 'url' => $url );
}
function tt_voucher_ids() { return $GLOBALS['tt_ids_teste']; }
function get_nav_menu_locations() { return array( 'menu-1' => 5 ); }
function wp_get_nav_menu_items( $menu, $args = array() ) { return array_map( function ( $i ) { return clone $i; }, array_values( $GLOBALS['tt_menu_itens'] ) ); }
function update_option( $k, $v ) { $GLOBALS['tt_opcoes'][ $k ] = $v; return true; }
function is_wp_error( $x ) { return false; }
function get_post_field( $campo, $id ) { $nomes = array( 1020 => 'experiencias', 1183 => 'sobre', 1177 => 'cotacao-de-viagens', 1171 => 'contato' ); return isset( $nomes[ $id ] ) ? $nomes[ $id ] : ''; }
function remove_accents( $s ) { return strtr( $s, array( 'ê' => 'e', 'ç' => 'c', 'ã' => 'a', 'ó' => 'o' ) ); }
function wp_update_nav_menu_item( $menu, $id, $args ) {
	if ( ! $id ) {
		$id = $GLOBALS['tt_proximo_id']++;
		$GLOBALS['tt_menu_itens'][ $id ] = tt_teste_item( $id, 0, '', 'custom', '' );
	}
	$i             = $GLOBALS['tt_menu_itens'][ $id ];
	$i->menu_order = (int) $args['menu-item-position'];
	$i->title      = $args['menu-item-title'];
	$i->type       = isset( $args['menu-item-type'] ) ? $args['menu-item-type'] : $i->type;
	$i->url        = isset( $args['menu-item-url'] ) ? $args['menu-item-url'] : $i->url;
	$i->object_id  = isset( $args['menu-item-object-id'] ) ? $args['menu-item-object-id'] : $i->object_id;
	return $id;
}
function tt_teste_menu() {
	$itens = array_values( $GLOBALS['tt_menu_itens'] );
	usort( $itens, function ( $a, $b ) { return $a->menu_order - $b->menu_order; } );
	return array_map( function ( $i ) { return $i->menu_order . ':' . $i->title; }, $itens );
}

$GLOBALS['tt_ids_teste']               = array( 'docs' => $ids['docs'], 'menus' => array() );
$GLOBALS['tt_opcoes']['page_for_posts'] = 1020;
$GLOBALS['tt_destinos_ligados']        = true;
$GLOBALS['tt_menu_itens']              = array(
	210  => tt_teste_item( 210, 1, 'Home', 'custom', 'https://exemplo.test/#home' ),
	1060 => tt_teste_item( 1060, 2, 'Experiências', 'post_type', '', 1020 ),
	1127 => tt_teste_item( 1127, 3, 'Roteiros', 'custom', 'https://exemplo.test/roteiros/' ),
	211  => tt_teste_item( 211, 4, 'Sobre nós', 'post_type', '', 1183 ),
	1187 => tt_teste_item( 1187, 5, 'Cotação', 'post_type', '', 1177 ),
	1188 => tt_teste_item( 1188, 6, 'Contato', 'post_type', '', 1171 ),
);
tt_voucher_menus_ligar();
igual( array( '1:Home', '2:Destinos', '3:Experiências', '4:Roteiros', '5:Sobre nós', '6:Cotação', '7:Contato' ), tt_teste_menu(), 'Destinos entra logo depois de Home e os outros descem uma posição, sem mudar nomes' );
tt_voucher_menus_ligar();
igual( 7, count( $GLOBALS['tt_menu_itens'] ), 'rodar de novo não duplica nada' );

$GLOBALS['tt_menu_itens'] = array( 210 => tt_teste_item( 210, 1, 'Início', 'custom', '/' ), 50 => tt_teste_item( 50, 2, 'Blog do Fulano', 'custom', 'https://outro.test/' ) );
tt_voucher_menus_ligar();
igual( array( '1:Início', '2:Destinos', '3:Experiências', '4:Roteiros', '5:Sobre', '6:Cotação', '7:Contato', '8:Blog do Fulano' ), tt_teste_menu(), 'menu quase vazio: as partes entram na ordem padrão logo depois da Home; item de fora vai para o fim' );
