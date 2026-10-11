<?php
// Dados do destino — includes/destino-dados.php (funções puras)
require __DIR__ . '/bootstrap.php';
require '/p/includes/roteiro-dados.php';
require '/p/includes/destino-dados.php';

/* ---------------------------------------------------------------- campos e abas */
igual( array( 'texto_de_abertura', 'o_que_fazer', 'quando_ir', 'galeria' ), array_keys( tt_destinos_campos() ), 'campos do destino' );
igual( array( 'resumo', 'fazer', 'quando', 'fotos' ), array_keys( tt_destino_abas() ), 'abas do destino' );
$abas = tt_destino_abas();
ok( tt_roteiro_aba_preenchida( $abas['resumo'][1], array( 'resumo' => 'Ilha', 'texto_de_abertura' => '' ) ), 'aba Resumo conta o resumo curto' );
ok( ! tt_roteiro_aba_preenchida( $abas['fotos'][1], array( 'galeria' => '' ) ), 'aba Fotos vazia sem IDs' );
ok( tt_roteiro_aba_preenchida( $abas['fotos'][1], array( 'galeria' => '12,13' ) ), 'aba Fotos preenchida com IDs' );

/* ---------------------------------------------------------------- lista "um por linha" */
igual( array( 'Passeio de barco', 'Trilha do Atalaia' ), tt_destino_lista_ler( "Passeio de barco\r\n\r\n  Trilha do Atalaia  \n" ), 'lista: quebra por linha, tira espaços e linhas vazias' );
igual( array( 'Mergulho', 'Pôr do sol' ), tt_destino_lista_ler( '<ul><li>Mergulho</li><li><strong>Pôr do sol</strong></li></ul>' ), 'lista: HTML colado vira itens de texto' );
igual( array( 'Praia', 'Forte' ), tt_destino_lista_ler( "- Praia\n• Forte" ), 'lista: tira o marcador digitado à mão' );
igual( array( 'A', 'B resto' ), tt_destino_lista_ler( array( ' A ', '', array( 'x' ), "B\nresto" ) ), 'lista: aceita array; ignora vazio e não escalar; item fica numa linha só' );
igual( array(), tt_destino_lista_ler( '' ), 'lista vazia' );
igual( "A\nB resto", tt_destino_lista_montar( array( ' A ', '', "B\nresto" ) ), 'montar: uma linha por item' );
igual( '', tt_destino_lista_montar( array() ), 'montar: sem itens, texto vazio' );

/* ---------------------------------------------------------------- galeria (IDs) */
igual( array( 12, 13, 7 ), tt_destino_galeria_ids( '12, 13;7' ), 'galeria: separa por vírgula, ponto e vírgula ou espaço' );
igual( array( 12, 7 ), tt_destino_galeria_ids( '12,abc,0,-3,12,7,1.5' ), 'galeria: ignora texto, zero, negativo, decimal e repetido' );
igual( array( 5, 9 ), tt_destino_galeria_ids( array( 5, '9', null, array( 'id' => 5 ) ) ), 'galeria: aceita array de números' );
igual( array( 127, 128 ), tt_destino_galeria_ids( '[{"id":127,"url":"a.jpg","alt":""},{"id":"128","url":"b.jpg"},{"url":"sem-id.jpg"}]' ), 'galeria: aceita o JSON da tela (objetos com id)' );
igual( array(), tt_destino_galeria_ids( '[quebrado' ), 'galeria: JSON quebrado vira lista vazia' );
igual( array(), tt_destino_galeria_ids( '' ), 'galeria vazia' );
igual( '127,128', tt_destino_galeria_montar( array( 127, 128, 127 ) ), 'montar galeria: IDs separados por vírgula' );

/* ---------------------------------------------------------------- limpar por tipo de campo */
igual( "A\nB", tt_destino_limpar( 'o_que_fazer', '<li>A</li><li>B</li>' ), 'limpar: lista' );
igual( '3,4', tt_destino_limpar( 'galeria', array( 3, 4 ) ), 'limpar: galeria' );
igual( '<p>Texto</p>', tt_destino_limpar( 'quando_ir', '<p>Texto</p>' ), 'limpar: HTML passa pelo wp_kses_post' );
igual( '', tt_destino_limpar( 'nao_existe', 'x' ), 'limpar: chave desconhecida não grava nada' );

/* ---------------------------------------------------------------- o que gravar a partir do formulário */
igual( array(), tt_destino_valores_a_gravar( null ), 'gravar: sem dados, nada' );
igual(
	array( 'texto_de_abertura' => '<p>Oi</p>', 'o_que_fazer' => "A\nB", 'quando_ir' => '<p>Verão</p>', 'galeria' => '1,2' ), // na ordem de tt_destinos_campos()
	tt_destino_valores_a_gravar( array( 'texto_de_abertura' => '<p>Oi</p>', 'quando_ir' => '<p>Verão</p>', 'o_que_fazer' => array( 'A', '', 'B' ), 'galeria' => '[{"id":1},{"id":2}]', 'intruso' => 'x' ) ),
	'gravar: os quatro campos, sem chaves estranhas'
);
igual( array( 'o_que_fazer' => '' ), tt_destino_valores_a_gravar( array( 'o_que_fazer_presente' => '1' ) ), 'gravar: lista com todos os itens removidos grava vazio' );
igual( array( 'quando_ir' => '' ), tt_destino_valores_a_gravar( array( 'quando_ir' => array( 'x' ) ) ), 'gravar: valor não escalar vira vazio, não "Array"' );

/* ---------------------------------------------------------------- HTML para o site */
igual( '', tt_destino_texto_html( '', 'Quando ir' ), 'texto vazio: nada, nem o título' );
igual( '', tt_destino_texto_html( '<p>nao informado</p>', 'Quando ir' ), 'texto "nao informado" conta como vazio' );
igual( '<h2 class="tt-destino-titulo">Quando ir</h2><div class="tt-destino-texto"><p>Verão</p></div>', tt_destino_texto_html( '<p>Verão</p>', 'Quando ir' ), 'texto com título' );
igual( '<div class="tt-destino-texto"><p>Abertura</p></div>', tt_destino_texto_html( '<p>Abertura</p>' ), 'texto sem título' );
igual( '', tt_destino_lista_html( " \n ", 'O que fazer' ), 'lista vazia: nada' );
igual( '<h2 class="tt-destino-titulo">O que fazer</h2><ul class="tt-destino-lista"><li>A &amp; B</li><li>C</li></ul>', tt_destino_lista_html( "A & B\nC", 'O que fazer' ), 'lista: itens escapados' );
igual( '', tt_destino_galeria_html( array(), 'g1', 'Galeria' ), 'galeria vazia: nada' );
$g = tt_destino_galeria_html( array( array( 'grande' => 'https://x.test/a.jpg', 'mini' => 'https://x.test/a-768.jpg', 'alt' => 'Baía "do" Sancho' ), array( 'grande' => '', 'mini' => 'x' ) ), 'tt-destino-9', 'Galeria' );
contem( '<h2 class="tt-destino-titulo">Galeria</h2><div class="tt-destino-galeria">', $g, 'galeria: título e grade' );
contem( '<a href="https://x.test/a.jpg" data-elementor-open-lightbox="yes" data-elementor-lightbox-slideshow="tt-destino-9">', $g, 'galeria: link abre a foto grande na ampliação do Elementor' );
contem( '<img src="https://x.test/a-768.jpg" alt="Baía &quot;do&quot; Sancho" loading="lazy">', $g, 'galeria: miniatura com alt escapado' );
igual( 1, substr_count( $g, '<img' ), 'galeria: foto sem endereço é ignorada' );
