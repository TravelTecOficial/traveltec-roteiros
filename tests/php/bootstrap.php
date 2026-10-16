<?php
// Base dos testes PHP: constantes e stubs mínimos do WordPress + verificadores. Não é carregada pelo plugin.
define( 'ABSPATH', '/' );
define( 'TT_ROTEIROS_URL', 'https://exemplo.test/wp-content/plugins/traveltec-roteiros/' );
define( 'TT_ROTEIROS_VERSION', '2.3.6-teste' );

$GLOBALS['tt_falhas'] = 0;
$GLOBALS['tt_meta']   = array();   // [post_id][chave] => valor
$GLOBALS['tt_acoes']  = array();   // [gancho] => [callbacks]
$GLOBALS['tt_menu']   = array();   // chamadas de add_menu_page/add_submenu_page/remove_menu_page

function ok( $cond, $msg ) {
	echo ( $cond ? 'OK    ' : 'FALHA ' ) . $msg . "\n";
	if ( ! $cond ) {
		$GLOBALS['tt_falhas']++;
	}
}

function igual( $esperado, $atual, $msg ) {
	$bom = $esperado === $atual;
	echo ( $bom ? 'OK    ' : 'FALHA ' ) . $msg . "\n";
	if ( ! $bom ) {
		echo '      esperado: ' . var_export( $esperado, true ) . "\n      atual:    " . var_export( $atual, true ) . "\n";
		$GLOBALS['tt_falhas']++;
	}
}

function contem( $trecho, $texto, $msg ) {
	ok( false !== strpos( (string) $texto, $trecho ), $msg );
}

// ---- stubs do WordPress (só o que o código do plugin usa nestas partes)
function wp_strip_all_tags( $s ) { return trim( strip_tags( (string) $s ) ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_textarea( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function wp_json_encode( $d, $f = 0 ) { return json_encode( $d, $f ); }
function wp_kses_post( $s ) { return $s; }
function get_post_meta( $id, $chave, $unico = false ) { return isset( $GLOBALS['tt_meta'][ $id ][ $chave ] ) ? $GLOBALS['tt_meta'][ $id ][ $chave ] : ''; }
function update_post_meta( $id, $chave, $valor ) { $GLOBALS['tt_meta'][ $id ][ $chave ] = $valor; return true; }
function add_action( $gancho, $cb, $prio = 10, $args = 1 ) { $GLOBALS['tt_acoes'][ $gancho ][] = $cb; }
function add_filter( $gancho, $cb, $prio = 10, $args = 1 ) { $GLOBALS['tt_acoes'][ $gancho ][] = $cb; }
function selected( $a, $b, $eco = true ) { $r = (string) $a === (string) $b ? ' selected="selected"' : ''; if ( $eco ) { echo $r; } return $r; }
function wp_nonce_field( $acao, $nome ) { echo '<input type="hidden" name="' . $nome . '" value="nonce">'; }
function admin_url( $p = '' ) { return 'https://exemplo.test/wp-admin/' . $p; }
function tt_roteiros_campos() {
	return array(
		'subtitulo' => array( 'Subtítulo', 'text' ), 'texto_de_apresentacao' => array( 'Texto de apresentação', 'html' ),
		'preco_de_referencia' => array( 'Preço de referência', 'text' ), 'galeria' => array( 'Galeria (HTML)', 'html' ),
		'programacao' => array( 'Programação', 'html' ), 'hospedagem' => array( 'Hospedagem', 'html' ),
		'inclui' => array( 'Inclui', 'html' ), 'nao_inclui' => array( 'Não inclui', 'html' ),
		'pagamento' => array( 'Pagamento', 'html' ), 'cancelamento' => array( 'Cancelamento', 'html' ), 'documentacao' => array( 'Documentação', 'html' ),
	);
}
