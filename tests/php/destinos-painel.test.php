<?php
// Aba Destinos da Configuração, grupo de peças e ordem de gravação — admin.php / instalador.php
require __DIR__ . '/bootstrap.php';

// No Windows o git pode gravar os arquivos com CRLF: os testes abaixo comparam trechos com quebra de linha.
$ler   = function ( $arq ) {
	return str_replace( "\r\n", "\n", file_get_contents( '/p/includes/' . $arq ) );
};
$admin = $ler( 'admin.php' );
$inst  = $ler( 'instalador.php' );
$aj    = $ler( 'ajustes.php' );

/* ---------------------------------------------------------------- aba e grupo (lidos do código, sem carregar o WordPress) */
ok( (bool) preg_match( "/'roteiros'\s*=>\s*'Roteiros',\s*'destinos'\s*=>\s*'Destinos',/", $admin ), 'aba Destinos logo depois de Roteiros' );
contem( "'destinos'     => array( 'destino-card', 'destinos-lista', 'destino-single' ),", $admin, 'grupo de peças dos destinos' );
contem( 'function tt_voucher_aba_destinos( $st )', $admin, 'a aba tem a sua função' );
contem( 'function tt_voucher_pagina_em_conflito( $caminho )', $admin, 'aviso de página no mesmo endereço' );
contem( 'tt_destino_form_id()', $admin, 'a aba avisa quando falta o formulário de cotação' );
contem( 'tt_destinos_registrar_tipo();', $inst, 'a instalação registra o tipo antes de refazer os endereços' );
contem( "wp_count_posts( 'destinos' )", $aj, 'menu do site: Destinos só com destino publicado' );
contem( 'tt_destinos_registrar_tipo( true );', $inst, 'instalar a aba Destinos liga os endereços do tipo antes de refazer as regras' );
contem( "array( 'Destinos', home_url( '/destinos/' ), 0 )", $inst, 'menus do próprio plugin: Destinos entra' );
contem( 'tt_destinos_ativo()', $admin, 'a aba avisa enquanto os destinos não estão ligados no site' );

/* ---------------------------------------------------------------- ordem de gravação: cards antes das listas */
preg_match( '/function tt_voucher_ordem_docs\(.*?\n}\n/s', $inst, $f );
ok( ! empty( $f ), 'tt_voucher_ordem_docs existe' );
eval( $f[0] ); // função pura
$docs = array(
	'lista'          => array( 'template_type' => 'archive' ),
	'card'           => array( 'template_type' => 'loop-item' ),
	'destinos-lista' => array( 'template_type' => 'archive' ),
	'contato'        => array( 'template_type' => 'wp-page' ),
	'destino-card'   => array( 'template_type' => 'loop-item' ),
	'faltou'         => null,
);
igual( array( 'card', 'destino-card', 'lista', 'destinos-lista', 'contato' ), tt_voucher_ordem_docs( $docs ), 'cards (loop-item) primeiro; o resto na ordem do índice; modelo que não carregou fica de fora' );
igual( array(), tt_voucher_ordem_docs( array() ), 'sem documentos, lista vazia' );

/* ---------------------------------------------------------------- 2.3.1: condição da lista de destinos no cache do Elementor */
// DSelection, 15/10/2026: /destinos/ abria com o modelo do blog. O cache de condições foi refeito na instalação com o
// tipo ainda "desligado" (sem show_in_nav_menus), e o Elementor descartou include/archive/destinos_archive.
contem( 'function tt_voucher_regenerar_condicoes(', $inst, 'refazer o cache de condições virou função' );
$pos_tipo  = strpos( $inst, 'tt_destinos_registrar_tipo( true );' );
$pos_cache = strpos( $inst, 'tt_voucher_regenerar_condicoes( $rel );' );
ok( false !== $pos_tipo && false !== $pos_cache && $pos_tipo < $pos_cache, 'instalação: liga o tipo Destinos antes de refazer o cache de condições' );
contem( "update_option( 'tt_voucher_regenerar_condicoes', 1 );", $inst, 'instalação: pede outro refazer do cache na próxima visita (o Elementor pode ter lido os tipos antes)' );
contem( "add_action( 'wp_loaded', 'tt_voucher_regenerar_condicoes_pendente' );", $inst, 'o pedido pendente é atendido no wp_loaded' );
ok( (bool) preg_match( '/function tt_voucher_ajustes_da_versao\(\) \{.*?tt_destinos_ativo\(\).*?tt_voucher_regenerar_condicoes\(\);.*?\n\}/s', $aj ), 'atualizar o plugin: site com destinos ligados refaz o cache (conserta quem já instalou)' );
