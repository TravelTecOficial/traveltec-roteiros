<?php
// Estrutura do plugin: sintaxe de todos os arquivos, funções repetidas, requires, versão e ligações da 2.1.0.
require __DIR__ . '/bootstrap.php';

$principal = file_get_contents( '/p/traveltec-roteiros.php' );
$admin     = file_get_contents( '/p/includes/admin.php' );
$arquivos  = array_merge( array( '/p/traveltec-roteiros.php' ), glob( '/p/includes/*.php' ) );

/* ---------------------------------------------------------------- sintaxe e funções repetidas */
$funcoes = array();
foreach ( $arquivos as $arq ) {
	$codigo = file_get_contents( $arq );
	try {
		token_get_all( $codigo, TOKEN_PARSE );
		ok( true, 'sintaxe: ' . basename( $arq ) );
	} catch ( ParseError $e ) {
		ok( false, 'sintaxe: ' . basename( $arq ) . ' linha ' . $e->getLine() . ': ' . $e->getMessage() );
		continue;
	}
	$t = token_get_all( $codigo );
	for ( $i = 0, $n = count( $t ); $i < $n; $i++ ) {
		if ( is_array( $t[ $i ] ) && T_FUNCTION === $t[ $i ][0] ) {
			$j = $i + 1;
			while ( isset( $t[ $j ] ) && is_array( $t[ $j ] ) && T_WHITESPACE === $t[ $j ][0] ) {
				$j++;
			}
			if ( isset( $t[ $j ] ) && is_array( $t[ $j ] ) && T_STRING === $t[ $j ][0] ) {
				$funcoes[ $t[ $j ][1] ][] = basename( $arq );
			}
		}
	}
}
$repetidas = array_keys( array_filter( $funcoes, function ( $onde ) { return count( $onde ) > 1; } ) );
igual( array(), $repetidas, 'nenhuma função declarada em dois arquivos' );

/* ---------------------------------------------------------------- requires do plugin principal */
preg_match_all( "/require_once TT_ROTEIROS_DIR \. '([^']+)'/", $principal, $m );
foreach ( $m[1] as $rel ) {
	ok( file_exists( '/p/' . $rel ), 'require existe: ' . $rel );
}
foreach ( array( 'includes/roteiro-dados.php', 'includes/roteiro-editor.php', 'includes/menu.php' ) as $novo ) {
	ok( in_array( $novo, $m[1], true ), 'ligado no plugin principal: ' . $novo );
}
ok( array_search( 'includes/roteiro-dados.php', $m[1], true ) < array_search( 'includes/roteiro-editor.php', $m[1], true ), 'roteiro-dados vem antes de roteiro-editor' );
ok( array_search( 'includes/opcoes.php', $m[1], true ) < array_search( 'includes/roteiro-editor.php', $m[1], true ), 'opcoes vem antes de roteiro-editor (a coluna Preço usa tt_voucher_preco_roteiros)' );

/* ---------------------------------------------------------------- versão */
preg_match( '/^\s*\*\s*Version:\s*(\S+)/m', $principal, $cab );
preg_match( "/define\( 'TT_ROTEIROS_VERSION', '([^']+)' \)/", $principal, $const );
igual( $cab[1], $const[1], 'versão do cabeçalho = constante' );
ok( version_compare( $const[1], '2.1.0', '>=' ), 'versão é 2.1.0 ou mais' );

/* ---------------------------------------------------------------- caixa antiga fora, menu com permissão certa */
ok( false === strpos( $principal, "'add_meta_boxes'" ), 'o plugin principal não registra mais a caixa antiga' );
ok( false === strpos( $principal, 'save_post_roteiros' ), 'o plugin principal não salva mais os campos (agora é o roteiro-editor)' );
contem( "add_menu_page( 'Voucher Tec', 'Voucher Tec', 'edit_posts', 'voucher-tec'", $admin, 'menu de topo visível para quem edita roteiros' );
ok( false === strpos( $admin, "'tt-roteiros-modelos'" ), 'admin.php não registra mais o submenu antigo (o endereço antigo é tratado em menu.php)' );
ok( (bool) preg_match( '/function tt_voucher_tela\(\) \{\s+if \( ! current_user_can\( \'manage_options\' \) \) \{\s+wp_die/', $admin ), 'a tela de Configuração exige manage_options' );

/* ---------------------------------------------------------------- arquivos da tela */
ok( file_exists( '/p/assets/roteiro-editor.js' ), 'assets/roteiro-editor.js existe' );
ok( file_exists( '/p/assets/roteiro-editor.css' ), 'assets/roteiro-editor.css existe' );

/* ---------------------------------------------------------------- destinos (2.2.0) */
foreach ( array( 'includes/destino-dados.php', 'includes/destinos.php' ) as $novo ) {
	ok( in_array( $novo, $m[1], true ), 'ligado no plugin principal: ' . $novo );
}
ok( array_search( 'includes/roteiro-dados.php', $m[1], true ) < array_search( 'includes/destino-dados.php', $m[1], true ), 'roteiro-dados vem antes de destino-dados (funções de texto)' );
ok( array_search( 'includes/destino-dados.php', $m[1], true ) < array_search( 'includes/destinos.php', $m[1], true ), 'destino-dados vem antes de destinos' );
ok( array_search( 'includes/instalador.php', $m[1], true ) < array_search( 'includes/destinos.php', $m[1], true ), 'instalador vem antes de destinos (tt_voucher_ids)' );
ok( file_exists( '/p/assets/destinos.css' ) && file_exists( '/p/assets/destino.js' ), 'arquivos do site para o destino existem' );
