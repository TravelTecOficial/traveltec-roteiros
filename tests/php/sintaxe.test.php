<?php
// Sintaxe de todos os arquivos PHP do plugin (os que não têm teste próprio: admin, api, opcoes...).
require __DIR__ . '/bootstrap.php';

foreach ( array_merge( array( '/p/traveltec-roteiros.php' ), glob( '/p/includes/*.php' ) ) as $arq ) {
	try {
		token_get_all( file_get_contents( $arq ), TOKEN_PARSE );
		ok( true, 'sintaxe: ' . basename( $arq ) );
	} catch ( ParseError $e ) {
		ok( false, 'sintaxe: ' . basename( $arq ) . ' — ' . $e->getMessage() . ' (linha ' . $e->getLine() . ')' );
	}
}
