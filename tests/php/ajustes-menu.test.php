<?php
// Menus do site: quando um item já aponta para onde deve — includes/ajustes.php
require __DIR__ . '/bootstrap.php';

function untrailingslashit( $s ) { return rtrim( (string) $s, '/' ); }

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
