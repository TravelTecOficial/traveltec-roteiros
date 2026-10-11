<?php
// Caixa "Dados do roteiro", salvamento e colunas da lista — includes/roteiro-editor.php
require __DIR__ . '/bootstrap.php';

function wp_unslash( $v ) { return $v; }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function wp_verify_nonce( $n, $acao ) { return 'nonce-ok' === $n; }
function current_user_can( $cap, $id = 0 ) { return ! empty( $GLOBALS['tt_pode'] ); }
function get_the_post_thumbnail( $id, $tam ) { return ! empty( $GLOBALS['tt_capa'][ $id ] ) ? '<img src="capa-' . $id . '.jpg">' : ''; }
function get_current_screen() { return isset( $GLOBALS['tt_tela'] ) ? $GLOBALS['tt_tela'] : null; }
function wp_enqueue_media() { $GLOBALS['tt_fila'][] = 'media'; }
function wp_enqueue_editor() { $GLOBALS['tt_fila'][] = 'editor'; }
function wp_enqueue_style( $h ) { $GLOBALS['tt_fila'][] = 'css'; }
function wp_enqueue_script( $h ) { $GLOBALS['tt_fila'][] = 'js'; }
function tt_voucher_preco_roteiros() { return isset( $GLOBALS['tt_preco_modo'] ) ? $GLOBALS['tt_preco_modo'] : 'exibir'; }

require '/p/includes/roteiro-dados.php';
require '/p/includes/roteiro-editor.php';

$GLOBALS['tt_meta'][7] = array(
	'subtitulo'             => 'Dubai e "Abu Dhabi"',
	'texto_de_apresentacao' => '<p>Uma imersão de 7 dias.</p>',
	'preco_de_referencia'   => '<p>a partir de USD 1.044, por pessoa em duplo</p>',
	'galeria'               => '<div class="rt-galeria"><div class="rt-galeria-track"><img src="https://x.test/a.jpg" alt="" /><img src="https://x.test/b.jpg" alt="" /><img src="https://x.test/a.jpg" alt="" /><img src="https://x.test/b.jpg" alt="" /></div></div>',
	'programacao'           => '<ol><li>Dia 1</li></ol>',
	'hospedagem'            => '',
	'inclui'                => '<ul><li>Hotel</li></ul>',
	'nao_inclui'            => '',
	'pagamento'             => '<p>nao informado</p>',
	'cancelamento'          => '',
	'documentacao'          => '',
);

/* ---------------------------------------------------------------- caixa */
ob_start();
tt_roteiro_desenhar_caixa( (object) array( 'ID' => 7 ) );
$html = ob_get_clean();

contem( 'name="tt_roteiros_nonce"', $html, 'caixa: tem o nonce' );
igual( 7, preg_match_all( '/role="tab"/', $html ), 'caixa: 7 abas' );
igual( 7, preg_match_all( '/role="tabpanel"/', $html ), 'caixa: 7 painéis' );
igual( 6, preg_match_all( '/data-tt-painel="[a-z]+" hidden/', $html ), 'caixa: só o primeiro painel visível' );
contem( 'class="tt-rot__aba is-ativa" id="tt-rot-aba-resumo"', $html, 'caixa: a primeira aba abre ativa' );
igual( 8, preg_match_all( '/data-tt-editor/', $html ), 'caixa: 8 textos longos com editor visual' );
contem( 'value="Dubai e &quot;Abu Dhabi&quot;"', $html, 'caixa: subtítulo com aspas é escapado' );
contem( '&lt;p&gt;Uma imersão de 7 dias.&lt;/p&gt;', $html, 'caixa: HTML do texto vai escapado dentro da textarea' );
foreach ( array( 'resumo', 'preco', 'fotos', 'programacao', 'inclui' ) as $aba ) {
	ok( (bool) preg_match( '/id="tt-rot-aba-' . $aba . '"[^>]*>[^<]*<span class="tt-rot__ponto is-cheio"/', $html ), "caixa: aba $aba com bolinha cheia" );
}
foreach ( array( 'hospedagem', 'condicoes' ) as $aba ) {
	ok( (bool) preg_match( '/id="tt-rot-aba-' . $aba . '"[^>]*>[^<]*<span class="tt-rot__ponto"/', $html ), "caixa: aba $aba com bolinha vazia" );
}
// preço em campos
contem( 'name="tt_preco[valor]" inputmode="decimal" value="1.044"', $html, 'preço: valor preenchido' );
contem( 'name="tt_preco[complemento]" value="por pessoa em duplo"', $html, 'preço: complemento preenchido' );
contem( '<option value="USD" selected="selected">', $html, 'preço: moeda USD marcada' );
contem( 'name="tt_preco[modo]" value="campos"', $html, 'preço: modo campos' );
contem( 'name="tt_preco[alterado]" value="0"', $html, 'preço: começa sem "alterado"' );
// fotos
contem( 'name="tt_galeria[alterado]" value="0"', $html, 'fotos: começa sem "alterado"' );
contem( 'data-urls="[{&quot;url&quot;:&quot;https:\/\/x.test\/a.jpg&quot;,&quot;alt&quot;:&quot;&quot;},{&quot;url&quot;:&quot;https:\/\/x.test\/b.jpg&quot;,&quot;alt&quot;:&quot;&quot;}]"', $html, 'fotos: a faixa duplicada chega ao JS como 2 fotos' );

// preço fora do padrão abre em texto livre
$GLOBALS['tt_meta'][8] = array_merge( $GLOBALS['tt_meta'][7], array( 'preco_de_referencia' => '<p>Sob consulta</p>', 'galeria' => '' ) );
ob_start();
tt_roteiro_desenhar_caixa( (object) array( 'ID' => 8 ) );
$livre = ob_get_clean();
contem( 'name="tt_preco[modo]" value="livre"', $livre, 'preço livre: modo livre' );
contem( 'data-tt-modo-campos hidden', $livre, 'preço livre: campos escondidos' );
contem( 'name="tt_preco[livre]" value="Sob consulta"', $livre, 'preço livre: texto preenchido' );
contem( 'data-urls="[]"', $livre, 'fotos: roteiro sem galeria abre vazio' );

// roteiro novo (sem nenhum meta)
ob_start();
tt_roteiro_desenhar_caixa( (object) array( 'ID' => 99 ) );
$novo = ob_get_clean();
igual( 0, preg_match_all( '/tt-rot__ponto is-cheio/', $novo ), 'roteiro novo: nenhuma bolinha cheia' );
contem( 'name="tt_preco[prefixo]" value="A partir de"', $novo, 'roteiro novo: prefixo padrão' );

/* ---------------------------------------------------------------- salvar */
$_POST = array();
igual( array(), tt_roteiro_salvar( 7 ), 'salvar: sem nonce não grava' );
$_POST = array( 'tt_roteiros_nonce' => 'errado', 'tt_roteiros' => array( 'subtitulo' => 'X' ) );
igual( array(), tt_roteiro_salvar( 7 ), 'salvar: nonce inválido não grava' );
$_POST = array( 'tt_roteiros_nonce' => 'nonce-ok', 'tt_roteiros' => array( 'subtitulo' => 'X' ) );
$GLOBALS['tt_pode'] = false;
igual( array(), tt_roteiro_salvar( 7 ), 'salvar: sem permissão não grava' );
$GLOBALS['tt_pode'] = true;
$_POST = array(
	'tt_roteiros_nonce' => 'nonce-ok',
	'tt_roteiros'       => array( 'subtitulo' => 'Novo subtítulo', 'programacao' => '<ol><li>Dia 1 — Chegada</li></ol>' ),
	'tt_preco'          => array( 'alterado' => '0', 'modo' => 'campos', 'valor' => '1' ),
	'tt_galeria'        => array( 'alterado' => '0', 'urls' => '[]' ),
);
igual( array( 'subtitulo', 'programacao' ), tt_roteiro_salvar( 7 ), 'salvar: só os campos simples quando preço e fotos não foram tocados' );
igual( '<p>a partir de USD 1.044, por pessoa em duplo</p>', get_post_meta( 7, 'preco_de_referencia', true ), 'salvar: preço importado intacto' );
contem( 'a.jpg', get_post_meta( 7, 'galeria', true ), 'salvar: galeria importada intacta' );
$_POST['tt_preco'] = array( 'alterado' => '1', 'modo' => 'campos', 'prefixo' => 'A partir de', 'moeda' => 'EUR', 'valor' => '2.000', 'complemento' => 'por pessoa' );
$_POST['tt_galeria'] = array( 'alterado' => '1', 'urls' => '[{"url":"https://x.test/c.jpg","alt":"Praia"}]' );
tt_roteiro_salvar( 7 );
igual( '<p>A partir de EUR 2.000, por pessoa</p>', get_post_meta( 7, 'preco_de_referencia', true ), 'salvar: preço alterado é regravado' );
igual( 2, substr_count( get_post_meta( 7, 'galeria', true ), '<img ' ), 'salvar: galeria alterada é regravada (duplicada)' );

/* ---------------------------------------------------------------- colunas da lista */
igual( array( 'cb', 'tt_capa', 'title', 'tt_preco', 'date' ), array_keys( tt_roteiro_colunas( array( 'cb' => '', 'title' => 'Título', 'date' => 'Data' ) ) ), 'lista: Capa antes do título, Preço depois' );
$GLOBALS['tt_capa'][7] = true;
ob_start(); tt_roteiro_coluna_valor( 'tt_capa', 7 ); $c = ob_get_clean();
contem( '<img src="capa-7.jpg">', $c, 'lista: mostra a capa' );
ob_start(); tt_roteiro_coluna_valor( 'tt_capa', 8 ); $c = ob_get_clean();
contem( '—', $c, 'lista: sem capa mostra traço' );
ob_start(); tt_roteiro_coluna_valor( 'tt_preco', 7 ); $c = ob_get_clean();
igual( 'A partir de EUR 2.000, por pessoa', $c, 'lista: preço em texto simples' );
$GLOBALS['tt_preco_modo'] = 'consulte';
ob_start(); tt_roteiro_coluna_valor( 'tt_preco', 7 ); $c = ob_get_clean();
contem( 'oculto no site', $c, 'lista: selo quando "Consulte-nos" está ligado' );
ob_start(); tt_roteiro_coluna_valor( 'tt_preco', 99 ); $c = ob_get_clean();
igual( '—', $c, 'lista: sem preço mostra traço, sem selo' );

/* ---------------------------------------------------------------- arquivos carregados por tela */
$fila = function ( $gancho, $tipo ) {
	$GLOBALS['tt_fila'] = array();
	$GLOBALS['tt_tela'] = null === $tipo ? null : (object) array( 'post_type' => $tipo );
	tt_roteiro_arquivos( $gancho );
	return $GLOBALS['tt_fila'];
};
igual( array( 'media', 'editor', 'css', 'js' ), $fila( 'post.php', 'roteiros' ), 'arquivos: editar roteiro carrega mídia, editor, CSS e JS' );
igual( array( 'media', 'editor', 'css', 'js' ), $fila( 'post-new.php', 'roteiros' ), 'arquivos: roteiro novo idem' );
igual( array( 'css' ), $fila( 'edit.php', 'roteiros' ), 'arquivos: a lista só carrega o CSS' );
igual( array(), $fila( 'post.php', 'post' ), 'arquivos: outros tipos de conteúdo não carregam nada' );
igual( array(), $fila( 'edit.php', 'page' ), 'arquivos: outras listas também não' );
igual( array(), $fila( 'post.php', null ), 'arquivos: sem tela, nada' );
igual( array(), $fila( 'options-general.php', 'roteiros' ), 'arquivos: outras telas do admin não' );

/* ---------------------------------------------------------------- site com grupo de campos do ACF: a caixa do plugin não carrega */
ok( ! tt_roteiro_acf_ativo(), 'ACF ausente: a caixa do plugin vale' );
if ( true ) {
	function acf_get_field_groups( $args ) { return array( array( 'key' => 'group_1' ) ); }
}
ok( (bool) tt_roteiro_acf_ativo(), 'ACF com grupo para roteiros: detectado' );
igual( array(), $fila( 'post.php', 'roteiros' ), 'ACF ativo: editar roteiro não carrega os arquivos da caixa' );
igual( array( 'css' ), $fila( 'edit.php', 'roteiros' ), 'ACF ativo: a lista continua com Capa e Preço (só CSS)' );
