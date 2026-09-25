<?php
// Preço, fotos, indicador das abas e o que gravar — includes/roteiro-dados.php
require __DIR__ . '/bootstrap.php';
require '/p/includes/roteiro-dados.php';

/* ---------------------------------------------------------------- preço: valores reais do DSelection */
$casos = array(
	array( '<p>a partir de USD 1.044, por pessoa em duplo</p>', 'campos', 'a partir de', 'USD', '1.044', 'por pessoa em duplo', '<p>a partir de USD 1.044, por pessoa em duplo</p>' ),
	array( '<p>A partir de USD 7.109, por pessoa em duplo</p>', 'campos', 'A partir de', 'USD', '7.109', 'por pessoa em duplo', '<p>A partir de USD 7.109, por pessoa em duplo</p>' ),
	array( '<p>a partir de U$ 6.290, por pessoa em duplo</p>', 'campos', 'a partir de', 'USD', '6.290', 'por pessoa em duplo', '<p>a partir de USD 6.290, por pessoa em duplo</p>' ),
	array( '<p>A partir de USD 3.450 por pessoa em suíte dupla (referência sujeita a disponibilidade, câmbio e alteração sem aviso).</p>', 'campos', 'A partir de', 'USD', '3.450', 'por pessoa em suíte dupla (referência sujeita a disponibilidade, câmbio e alteração sem aviso).', '<p>A partir de USD 3.450, por pessoa em suíte dupla (referência sujeita a disponibilidade, câmbio e alteração sem aviso).</p>' ),
	array( '<p>a partir de EUR 2.634, por pessoa em duplo</p>', 'campos', 'a partir de', 'EUR', '2.634', 'por pessoa em duplo', '<p>a partir de EUR 2.634, por pessoa em duplo</p>' ),
	array( '<p>a partir de R$ 5.000</p>', 'campos', 'a partir de', 'BRL', '5.000', '', '<p>a partir de R$ 5.000</p>' ),
	array( '<p>a partir de € 1.200,50 por pessoa</p>', 'campos', 'a partir de', 'EUR', '1.200,50', 'por pessoa', '<p>a partir de EUR 1.200,50, por pessoa</p>' ),
);
foreach ( $casos as $c ) {
	$d = tt_roteiro_preco_ler( $c[0] );
	igual( array( $c[1], $c[2], $c[3], $c[4], $c[5] ), array( $d['modo'], $d['prefixo'], $d['moeda'], $d['valor'], $d['complemento'] ), 'preço lê: ' . strip_tags( $c[0] ) );
	igual( $c[6], tt_roteiro_preco_montar( $d ), 'preço regrava: ' . strip_tags( $c[0] ) );
}
$d = tt_roteiro_preco_ler( '<p>Sob consulta, tarifa dinâmica</p>' );
igual( array( 'livre', 'Sob consulta, tarifa dinâmica' ), array( $d['modo'], $d['livre'] ), 'preço sem moeda cai no texto livre' );
igual( '<p>Sob consulta, tarifa dinâmica</p>', tt_roteiro_preco_montar( $d ), 'texto livre regrava igual' );
$d = tt_roteiro_preco_ler( '' );
igual( array( 'campos', 'A partir de', 'USD', '' ), array( $d['modo'], $d['prefixo'], $d['moeda'], $d['valor'] ), 'preço vazio abre com os padrões' );
igual( '', tt_roteiro_preco_montar( $d ), 'preço sem valor grava vazio' );
igual( '<p>A partir de USD 1234</p>', tt_roteiro_preco_montar( array( 'modo' => 'campos', 'prefixo' => ' A partir de ', 'moeda' => 'USD', 'valor' => ' 12x34 ', 'complemento' => '' ) ), 'valor: só dígitos, ponto e vírgula' );
igual( '<p>A partir de USD 100, por &lt;b&gt;pessoa&lt;/b&gt;</p>', tt_roteiro_preco_montar( array( 'modo' => 'campos', 'prefixo' => 'A partir de', 'moeda' => 'USD', 'valor' => '100', 'complemento' => 'por <b>pessoa</b>' ) ), 'complemento com tag é escapado' );
igual( '<p>A partir de USD 100</p>', tt_roteiro_preco_montar( array( 'modo' => 'campos', 'prefixo' => 'A partir de', 'moeda' => 'XYZ', 'valor' => '100', 'complemento' => '' ) ), 'moeda desconhecida vira USD' );

/* ---------------------------------------------------------------- fotos */
$duplicada = '<div class="rt-galeria" style="padding:4px 0"><div class="rt-galeria-track" style="display:flex;gap:12px;width:max-content;flex-wrap:nowrap;--rt-duracao:40s">'
	. '<img src="https://x.test/a.jpg?w=1&amp;q=80" alt="" style="height:220px" /><img src="https://x.test/b.jpg" alt="Praia" style="height:220px" />'
	. '<img src="https://x.test/a.jpg?w=1&amp;q=80" alt="" style="height:220px" /><img src="https://x.test/b.jpg" alt="Praia" style="height:220px" /></div></div>';
$fotos = tt_roteiro_galeria_ler( $duplicada );
igual( array( 'https://x.test/a.jpg?w=1&q=80', 'https://x.test/b.jpg' ), array_column( $fotos, 'url' ), 'fotos: faixa duplicada aparece uma vez (e o &amp; volta a &)' );
igual( 'Praia', $fotos[1]['alt'], 'fotos: mantém o texto alternativo' );
igual( 3, count( tt_roteiro_galeria_ler( '<img src="a.jpg"><img src="b.jpg"><img src="c.jpg">' ) ), 'fotos: lista que não é repetição fica como está' );
igual( array(), tt_roteiro_galeria_ler( '' ), 'fotos: vazio' );
igual( array(), tt_roteiro_galeria_ler( '<p>sem imagens</p>' ), 'fotos: texto sem imagem' );
$html = tt_roteiro_galeria_montar( array( array( 'url' => 'https://x.test/a.jpg', 'alt' => '' ), array( 'url' => 'https://x.test/b.jpg', 'alt' => 'Praia' ) ) );
igual( 4, substr_count( $html, '<img ' ), 'fotos: grava a lista duas vezes (faixa que volta sem salto)' );
contem( '--rt-duracao:20s', $html, 'fotos: duração mínima de 20s' );
contem( 'class="rt-galeria"', $html, 'fotos: mesma classe do import' );
contem( 'style="height:220px;width:auto;flex:0 0 auto;border-radius:8px"', $html, 'fotos: mesmo estilo de cada foto do import' );
igual( array( 'https://x.test/a.jpg', 'https://x.test/b.jpg' ), array_column( tt_roteiro_galeria_ler( $html ), 'url' ), 'fotos: montar e ler dão a mesma lista' );
contem( '--rt-duracao:52s', tt_roteiro_galeria_montar( array_fill( 0, 13, array( 'url' => 'https://x.test/z.jpg' ) ) ), 'fotos: 4s por foto (13 fotos = 52s)' );
igual( '', tt_roteiro_galeria_montar( array() ), 'fotos: lista vazia grava vazio' );
igual( '', tt_roteiro_galeria_montar( array( array( 'url' => '' ) ) ), 'fotos: item sem url é ignorado' );

/* ---------------------------------------------------------------- indicador das abas */
ok( tt_roteiro_tem_conteudo( '<p>Um texto</p>' ), 'indicador: texto útil' );
ok( ! tt_roteiro_tem_conteudo( '' ), 'indicador: vazio' );
ok( tt_roteiro_tem_conteudo( '<div class="rt-galeria"><img src="a.jpg" alt="" /></div>' ), 'indicador: galeria só com imagens (sem texto) conta como conteúdo' );
ok( ! tt_roteiro_tem_conteudo( '<p>nao informado</p>' ), 'indicador: "nao informado" do import conta como vazio' );
ok( ! tt_roteiro_tem_conteudo( '<p> Não informado </p>' ), 'indicador: "Não informado" também' );
ok( tt_roteiro_aba_preenchida( array( 'inclui', 'nao_inclui' ), array( 'inclui' => '', 'nao_inclui' => '<ul><li>Seguro</li></ul>' ) ), 'aba cheia se algum campo tem texto' );
ok( ! tt_roteiro_aba_preenchida( array( 'pagamento' ), array( 'pagamento' => '<p>nao informado</p>' ) ), 'aba vazia se todos estão vazios' );
igual( array( 'resumo', 'preco', 'fotos', 'programacao', 'hospedagem', 'inclui', 'condicoes' ), array_keys( tt_roteiro_abas() ), 'as 7 abas, na ordem do desenho' );
$todas = array();
foreach ( tt_roteiro_abas() as $aba ) {
	$todas = array_merge( $todas, $aba[1] );
}
sort( $todas );
$chaves = array_keys( tt_roteiros_campos() );
sort( $chaves );
igual( $chaves, $todas, 'as abas cobrem os 11 campos, cada um uma vez' );

/* ---------------------------------------------------------------- o que gravar */
$v = tt_roteiro_valores_a_gravar( array( 'subtitulo' => 'Dubai', 'programacao' => '<ol><li>Dia 1</li></ol>', 'intruso' => 'x' ), array( 'alterado' => '0', 'valor' => '9' ), array( 'alterado' => '0', 'urls' => '[]' ) );
igual( array( 'subtitulo' => 'Dubai', 'programacao' => '<ol><li>Dia 1</li></ol>' ), $v, 'sem "alterado": preço e fotos não são regravados; campo desconhecido é ignorado' );
$v = tt_roteiro_valores_a_gravar( array(), array( 'alterado' => '1', 'modo' => 'campos', 'prefixo' => 'A partir de', 'moeda' => 'EUR', 'valor' => '2.000', 'complemento' => '' ), array() );
igual( array( 'preco_de_referencia' => '<p>A partir de EUR 2.000</p>' ), $v, 'com "alterado": grava o preço montado' );
$v = tt_roteiro_valores_a_gravar( array(), array(), array( 'alterado' => '1', 'urls' => '[{"url":"https://x.test/a.jpg","alt":""}]' ) );
igual( 2, substr_count( $v['galeria'], '<img ' ), 'com "alterado": grava as fotos (duplicadas)' );
$v = tt_roteiro_valores_a_gravar( array(), array(), array( 'alterado' => '1', 'urls' => 'isto não é json' ) );
igual( array( 'galeria' => '' ), $v, 'JSON quebrado das fotos vira galeria vazia, sem erro' );
igual( array(), tt_roteiro_valores_a_gravar( array(), array(), array() ), 'nada enviado, nada a gravar' );
