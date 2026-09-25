<?php
/**
 * Painel "Voucher Tec", em abas: Licença e instruções, Identidade (dados gerais, cores, cabeçalho,
 * rodapé e a instalação completa) e uma aba por parte do site (Roteiros, Experiências, Contato,
 * Cotação, Sobre), cada uma com a situação das peças, instalar só aquela parte e os dados que ela usa.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function tt_voucher_url_painel( $args = array() ) {
	return add_query_arg( array_merge( array( 'page' => 'voucher-tec' ), $args ), admin_url( 'admin.php' ) );
}

function tt_voucher_abas() {
	return array(
		'licenca'      => 'Licença e instruções',
		'identidade'   => 'Identidade',
		'roteiros'     => 'Roteiros',
		'experiencias' => 'Experiências',
		'contato'      => 'Contato',
		'cotacao'      => 'Cotação',
		'sobre'        => 'Sobre',
	);
}

/**
 * Peças de cada aba (chaves do instalador: documentos e formulários). As páginas de obrigado
 * levam o formulário da newsletter, por isso ele vai junto com Contato e Cotação.
 */
function tt_voucher_grupos() {
	return array(
		'identidade'   => array( 'header', 'menu-popup', 'footer', 'newsletter', 'obrigado-newsletter' ),
		'roteiros'     => array( 'card', 'lista', 'single' ),
		'experiencias' => array( 'blog-arquivo', 'blog-post' ),
		'contato'      => array( 'contato', 'obrigado-contato', 'newsletter' ),
		'cotacao'      => array( 'cotacao', 'obrigado-cotacao', 'newsletter' ),
		'sobre'        => array( 'sobre' ),
	);
}

/** Campos de dados editados em cada aba (a sinopse é texto interno do Cliente Ideal e não aparece). */
function tt_voucher_campos_da_aba( $aba ) {
	$mapa = array(
		'licenca'    => array( 'id' ),
		'identidade' => array( 'nome_fantasia', 'name', 'logo_url', 'instagram_url', 'facebook_url', 'youtube_url', 'linkedin_url', 'tiktok_url', 'site_oficial' ),
		'contato'    => array( 'celular_atendimento', 'telefone', 'email_atendimento', 'logradouro', 'numero', 'bairro', 'cidade', 'uf', 'cep', 'horario_funcionamento' ),
		'cotacao'    => array( 'webhook_url' ),
		'sobre'      => array( 'description', 'history' ),
	);
	return isset( $mapa[ $aba ] ) ? $mapa[ $aba ] : array();
}

function tt_voucher_aba_atual() {
	$aba = isset( $_GET['aba'] ) ? sanitize_key( wp_unslash( $_GET['aba'] ) ) : 'licenca'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return isset( tt_voucher_abas()[ $aba ] ) ? $aba : 'licenca';
}

function tt_voucher_aba_postada() {
	$aba = isset( $_POST['aba'] ) ? sanitize_key( wp_unslash( $_POST['aba'] ) ) : 'licenca'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce conferido antes
	return isset( tt_voucher_abas()[ $aba ] ) ? $aba : 'licenca';
}

add_action( 'admin_menu', function () {
	add_menu_page( 'Voucher Tec', 'Voucher Tec', 'manage_options', 'voucher-tec', 'tt_voucher_tela', 'dashicons-tickets-alt', 58 );
	// endereço antigo (1.x): Roteiros › Modelos do Elementor
	add_submenu_page( 'edit.php?post_type=roteiros', 'Voucher Tec', 'Voucher Tec', 'manage_options', 'tt-roteiros-modelos', function () {
		echo '<script>location.href=' . wp_json_encode( tt_voucher_url_painel() ) . ';</script>';
	} );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'toplevel_page_voucher-tec' === $hook ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script( 'wp-color-picker', 'jQuery(function($){$(".tt-cor").wpColorPicker();});' );
	}
} );

add_action( 'admin_post_tt_voucher_instalar', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Sem permissão.' );
	}
	check_admin_referer( 'tt_voucher_instalar' );
	$aba    = tt_voucher_aba_postada();
	$grupo  = isset( $_POST['grupo'] ) ? sanitize_key( wp_unslash( $_POST['grupo'] ) ) : '';
	$grupos = tt_voucher_grupos();
	$opcoes = array( 'substituir' => ! empty( $_POST['substituir'] ) );
	if ( isset( $grupos[ $grupo ] ) ) {
		$opcoes['modulos'] = $grupos[ $grupo ];
	}
	$r = tt_voucher_instalar( $opcoes );
	set_transient( 'tt_voucher_relatorio', is_wp_error( $r ) ? array( 'erro' => $r->get_error_message() ) : $r, 300 );
	wp_safe_redirect( tt_voucher_url_painel( array( 'aba' => $aba, 'feito' => 'instalar' ) ) );
	exit;
} );

add_action( 'admin_post_tt_voucher_aplicar', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Sem permissão.' );
	}
	check_admin_referer( 'tt_voucher_aplicar' );
	$entrada = isset( $_POST['tt'] ) && is_array( $_POST['tt'] ) ? wp_unslash( $_POST['tt'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitizado em tt_voucher_salvar_dados
	set_transient( 'tt_voucher_relatorio', tt_voucher_aplicar( $entrada ), 300 );
	wp_safe_redirect( tt_voucher_url_painel( array( 'aba' => tt_voucher_aba_postada(), 'feito' => 'aplicar' ) ) );
	exit;
} );

add_action( 'admin_post_tt_roteiros_verificar', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Sem permissão.' );
	}
	check_admin_referer( 'tt_roteiros_verificar' );
	delete_site_transient( TT_ROTEIROS_CACHE );
	tt_roteiros_release( true );
	delete_site_transient( 'update_plugins' );
	wp_safe_redirect( tt_voucher_url_painel( array( 'checado' => 1 ) ) );
	exit;
} );

function tt_voucher_bloco_versao() {
	$release = tt_roteiros_release();
	echo '<p><strong>Versão instalada:</strong> ' . esc_html( TT_ROTEIROS_VERSION ) . ' — ';
	if ( empty( $release['versao'] ) ) {
		echo 'não foi possível consultar o repositório agora.';
	} elseif ( version_compare( $release['versao'], TT_ROTEIROS_VERSION, '>' ) ) {
		echo '<strong>versão ' . esc_html( $release['versao'] ) . ' disponível.</strong> Atualize em <a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">Plugins</a>.';
	} else {
		echo 'é a mais recente.';
	}
	echo ' <a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=tt_roteiros_verificar' ), 'tt_roteiros_verificar' ) ) . '">Procurar atualização</a></p>';
}

/** Resultado da última instalação/aplicação, em listas legíveis. */
function tt_voucher_mostrar_relatorio() {
	$rel = get_transient( 'tt_voucher_relatorio' );
	if ( ! $rel ) {
		return;
	}
	delete_transient( 'tt_voucher_relatorio' );
	if ( ! empty( $rel['erro'] ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $rel['erro'] ) . '</p></div>';
		return;
	}
	$rotulos = array(
		'criados'         => 'Criado',
		'atualizados'     => 'Atualizado',
		'conflitos'       => 'Não mexido (já existe uma página nesse endereço — marque “usar o layout do plugin” para trocar)',
		'avisos'          => 'Aviso',
		'dados_alterados' => 'Dado alterado',
	);
	echo '<div class="notice notice-success"><p><strong>Feito.</strong></p><ul style="list-style:disc;margin-left:20px">';
	$algo = false;
	foreach ( $rotulos as $k => $rotulo ) {
		foreach ( (array) ( $rel[ $k ] ?? array() ) as $item ) {
			$algo = true;
			echo '<li>' . esc_html( $rotulo . ': ' . ( is_scalar( $item ) ? $item : wp_json_encode( $item, JSON_UNESCAPED_UNICODE ) ) ) . '</li>';
		}
	}
	if ( ! empty( $rel['logo'] ) ) {
		$algo = true;
		echo '<li>' . esc_html( 'Logo: ' . ( is_numeric( $rel['logo'] ) ? 'atualizado' : $rel['logo'] ) ) . '</li>';
	}
	if ( ! empty( $rel['estilo'] ) ) {
		$algo = true;
		echo '<li>Cores e fontes aplicadas em ' . (int) count( (array) $rel['estilo']['documentos_alterados'] ) . ' documento(s).</li>';
	}
	if ( ! $algo ) {
		echo '<li>Nada mudou.</li>';
	}
	echo '</ul></div>';
}

/** Tabela com a situação das peças de uma aba. */
function tt_voucher_tabela_pecas( $pecas, $st ) {
	$indice = tt_voucher_indice();
	$docs   = array();
	foreach ( $indice['docs'] as $d ) {
		$docs[ $d['chave'] ] = $d['titulo'];
	}
	echo '<table class="widefat striped" style="max-width:860px"><thead><tr><th>Parte</th><th>Situação</th><th></th></tr></thead><tbody>';
	foreach ( $pecas as $chave ) {
		if ( isset( $docs[ $chave ] ) ) {
			$i = $st['documentos'][ $chave ] ?? null;
			echo '<tr><td>' . esc_html( $docs[ $chave ] ) . '</td><td>' . ( $i && $i['existe'] ? 'Instalado (#' . (int) $i['id'] . ')' : '<em>Não instalado</em>' ) . '</td><td>';
			if ( $i && $i['existe'] ) {
				echo '<a href="' . esc_url( $i['editar'] ) . '">Editar no Elementor</a>' . ( $i['url'] ? ' · <a href="' . esc_url( $i['url'] ) . '" target="_blank">Ver</a>' : '' );
			}
			echo '</td></tr>';
		}
		if ( in_array( $chave, $indice['forms'], true ) ) {
			$id = $st['formularios'][ $chave ] ?? 0;
			$ok = $id && tt_voucher_vivo( $id );
			echo '<tr><td>Formulário: ' . esc_html( $chave ) . '</td><td>' . ( $ok ? 'Instalado (#' . (int) $id . ')' : '<em>Não instalado</em>' ) . '</td><td>';
			if ( $ok ) {
				echo '<a href="' . esc_url( admin_url( 'post.php?post=' . (int) $id . '&action=edit' ) ) . '">Editar formulário</a>';
			}
			echo '</td></tr>';
		}
	}
	echo '</tbody></table>';
}

/** Botão de instalar/reinstalar só a parte da aba (ou tudo, com $grupo vazio). */
function tt_voucher_form_instalar( $aba, $grupo, $st ) {
	$pecas     = $grupo ? tt_voucher_grupos()[ $grupo ] : array();
	$instalada = false;
	foreach ( $pecas as $p ) {
		if ( ! empty( $st['documentos'][ $p ]['existe'] ) ) {
			$instalada = true;
		}
	}
	if ( ! $grupo ) {
		$instalada = $st['instalado'];
		$rotulo    = $instalada ? 'Reinstalar o site padrão inteiro' : 'Instalar o site padrão inteiro';
	} else {
		$rotulo = $instalada ? 'Reinstalar esta parte' : 'Instalar esta parte';
	}
	$aviso = $grupo
		? 'Instala só as peças desta aba. O que foi editado nelas volta ao padrão. Continuar?'
		: 'Instala cabeçalho, rodapé, todas as páginas, formulários e menus, e troca as cores e fontes globais do Elementor. Só num site em preparação. Continuar?';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:14px 0 24px" onsubmit="return confirm(' . esc_attr( wp_json_encode( $aviso ) ) . ')">';
	wp_nonce_field( 'tt_voucher_instalar' );
	echo '<input type="hidden" name="action" value="tt_voucher_instalar"><input type="hidden" name="aba" value="' . esc_attr( $aba ) . '"><input type="hidden" name="grupo" value="' . esc_attr( $grupo ) . '">';
	$tem_pagina = ! $grupo || array_intersect( $pecas, array( 'contato', 'cotacao', 'sobre', 'obrigado-contato', 'obrigado-cotacao', 'obrigado-newsletter' ) );
	if ( $tem_pagina ) {
		echo '<label><input type="checkbox" name="substituir" value="1"> Usar o layout do plugin também em páginas que já existem com o mesmo endereço (/contato/, /sobre/…)</label><br><br>';
	}
	submit_button( $rotulo, $grupo ? 'secondary' : 'primary', 'submit', false );
	echo '</form>';
}

/** Formulário com os campos de dados de uma aba. */
function tt_voucher_form_dados( $aba, $st, $com_estilo = false ) {
	$campos = tt_voucher_campos_da_aba( $aba );
	if ( ! $campos && ! $com_estilo ) {
		return;
	}
	$rotulos = tt_voucher_campos();
	$d       = $st['dados'];
	$e       = $st['estilo'];
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><table class="form-table" style="max-width:900px"><tbody>';
	wp_nonce_field( 'tt_voucher_aplicar' );
	echo '<input type="hidden" name="action" value="tt_voucher_aplicar"><input type="hidden" name="aba" value="' . esc_attr( $aba ) . '">';
	if ( $com_estilo ) {
		foreach ( array( 'cor_primaria' => array( 'Cor primária', $e['primaria'] ), 'cor_secundaria' => array( 'Cor secundária', $e['secundaria'] ), 'cor_terciaria' => array( 'Cor terciária', $e['terciaria'] ) ) as $k => $c ) {
			echo '<tr><th><label>' . esc_html( $c[0] ) . '</label></th><td><input type="text" class="tt-cor" name="tt[' . esc_attr( $k ) . ']" value="' . esc_attr( $c[1] ) . '"></td></tr>';
		}
		foreach ( array( 'fonte_titulos' => 'Fonte dos títulos (Google Fonts)', 'fonte_textos' => 'Fonte dos textos (Google Fonts)' ) as $k => $r ) {
			echo '<tr><th><label>' . esc_html( $r ) . '</label></th><td><input type="text" class="regular-text" name="tt[' . esc_attr( $k ) . ']" value="' . esc_attr( $e[ $k ] ) . '"></td></tr>';
		}
	}
	foreach ( $campos as $k ) {
		echo '<tr><th><label>' . esc_html( $rotulos[ $k ] ) . '</label></th><td>';
		if ( in_array( $k, tt_voucher_campos_longos(), true ) ) {
			echo '<textarea class="large-text" rows="5" name="tt[' . esc_attr( $k ) . ']">' . esc_textarea( $d[ $k ] ) . '</textarea>';
		} else {
			echo '<input type="text" class="regular-text" name="tt[' . esc_attr( $k ) . ']" value="' . esc_attr( $d[ $k ] ) . '">';
		}
		if ( 'logo_url' === $k && get_theme_mod( 'custom_logo' ) ) {
			echo '<p>' . wp_get_attachment_image( (int) get_theme_mod( 'custom_logo' ), 'medium', false, array( 'style' => 'max-height:60px;width:auto;margin-top:8px' ) ) . '</p>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
	submit_button( 'Salvar e aplicar no site' );
	echo '</form>';
}

function tt_voucher_aviso_site_antigo( $st ) {
	if ( $st['instalacao_1x'] ) {
		echo '<div class="notice notice-warning inline"><p><strong>Site com o layout antigo (1.x).</strong> Instale uma parte por vez e confira o site antes da próxima. Não use a instalação completa num site no ar.</p></div>';
	}
}

function tt_voucher_aba_licenca( $st ) {
	$d      = $st['dados'];
	$nome   = tt_voucher_valor( 'nome' );
	$status = $d['id'] ? '<span style="color:#008a20">● Vinculado ao Cliente Ideal</span> — ' . esc_html( $nome ) . ' <code>' . esc_html( $d['id'] ) . '</code>' : '<span style="color:#b32d2e">● Sem vínculo com o Cliente Ideal</span> — os formulários não enviam leads até o ID ser preenchido.';

	echo '<h2>Licença</h2><table class="form-table" style="max-width:900px"><tbody>';
	echo '<tr><th>Plugin</th><td>Voucher Tec - Travel Tec · uso exclusivo das agências clientes Travel Tec</td></tr>';
	echo '<tr><th>Versão</th><td>';
	tt_voucher_bloco_versao();
	echo '</td></tr>';
	echo '<tr><th>Agência</th><td>' . $status . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- montado com esc_html acima
	echo '<tr><th>Plugins necessários</th><td>';
	foreach ( array( 'elementor' => 'Elementor', 'elementor_pro' => 'Elementor Pro / PRO Elements', 'jetformbuilder' => 'JetFormBuilder' ) as $k => $n ) {
		echo ( ! empty( $st['dependencias'][ $k ] ) ? '✅ ' : '❌ ' ) . esc_html( $n ) . '<br>';
	}
	echo '</td></tr>';
	echo '<tr><th>Site padrão</th><td>' . ( $st['instalado'] ? 'Instalado (modelos ' . esc_html( $st['versao_modelos'] ) . ')' : ( $st['instalacao_1x'] ? 'Layout antigo (1.x) — site padrão não instalado' : 'Não instalado' ) ) . '</td></tr>';
	echo '<tr><th>Captação de leads</th><td>' . ( $st['captacao'] ? 'Ligada' : 'Desligada (liga ao instalar os formulários)' ) . '</td></tr>';
	echo '</tbody></table>';

	echo '<h3>ID da agência no Cliente Ideal</h3>';
	tt_voucher_form_dados( 'licenca', $st );

	echo '<h2>Instruções</h2><ol style="max-width:860px;line-height:1.7">';
	echo '<li><strong>Site novo</strong> (domínio provisório): em <em>Identidade</em>, clique em <em>Instalar o site padrão inteiro</em>. Em site novo isso já acontece ao ativar o plugin.</li>';
	echo '<li><strong>Dados da agência</strong>: preencha o ID acima e os dados de cada aba — ou deixe o MCP aplicar tudo do Cliente Ideal de uma vez (<code>POST /wp-json/voucher-tec/v1/aplicar</code>). Telefone, e-mail, endereço e redes aparecem sozinhos em todas as páginas.</li>';
	echo '<li><strong>Cores e fontes</strong>: em <em>Identidade</em>. Trocam em todas as páginas e modelos do plugin.</li>';
	echo '<li><strong>Revisar</strong> cada aba: <em>Editar no Elementor</em> abre a peça; <em>Ver</em> abre a página no site.</li>';
	echo '<li><strong>Site no ar com o layout antigo</strong>: atualizar o plugin não muda nada. Para trocar o layout, instale uma aba por vez (<em>Instalar esta parte</em>) e confira o site antes da próxima.</li>';
	echo '<li><strong>Depois de ajustar uma peça no Elementor, não a reinstale</strong>: reinstalar volta a peça ao padrão.</li>';
	echo '</ol>';
}

function tt_voucher_aba_identidade( $st ) {
	echo '<h2>Cabeçalho, rodapé e newsletter</h2>';
	tt_voucher_aviso_site_antigo( $st );
	tt_voucher_tabela_pecas( tt_voucher_grupos()['identidade'], $st );
	tt_voucher_form_instalar( 'identidade', 'identidade', $st );
	echo '<h2>Marca, cores e redes sociais</h2><p class="description">Os mesmos campos da tabela <code>companies</code> do Cliente Ideal. Redes em branco somem do site.</p>';
	tt_voucher_form_dados( 'identidade', $st, true );
	echo '<hr style="margin:30px 0"><h2>Instalação completa</h2><p class="description" style="max-width:860px">Tudo de uma vez: cabeçalho, rodapé, roteiros, blog, Sobre, Contato, Cotação, páginas de obrigado, formulários, menus e as cores e fontes globais do Elementor. Só para site em preparação.</p>';
	tt_voucher_form_instalar( 'identidade', '', $st );
}

function tt_voucher_aba_roteiros( $st ) {
	$n = wp_count_posts( 'roteiros' );
	echo '<p><strong>' . (int) ( $n->publish ?? 0 ) . '</strong> roteiros publicados · <a href="' . esc_url( admin_url( 'edit.php?post_type=roteiros' ) ) . '">Ver todos</a> · <a href="' . esc_url( admin_url( 'post-new.php?post_type=roteiros' ) ) . '">Adicionar</a> · <a href="' . esc_url( home_url( '/roteiros/' ) ) . '" target="_blank">Abrir /roteiros/</a></p>';
	echo '<p class="description">Os roteiros chegam do Cliente Ideal pelo n8n (<code>/wp-json/wp/v2/roteiros</code>).</p>';
	echo '<h2>Páginas dos roteiros</h2>';
	tt_voucher_aviso_site_antigo( $st );
	tt_voucher_tabela_pecas( tt_voucher_grupos()['roteiros'], $st );
	tt_voucher_form_instalar( 'roteiros', 'roteiros', $st );
	tt_voucher_form_opcao(
		'roteiros',
		'preco_roteiros',
		'Preço nos roteiros',
		'Vale para o card (lista /roteiros/) e para a página de cada roteiro. O valor continua gravado no roteiro: dá para voltar a exibir a qualquer momento.',
		array( 'exibir' => 'Exibir o preço de referência do roteiro', 'consulte' => 'Mostrar “' . TT_VOUCHER_TEXTO_CONSULTE . '” no lugar do preço' ),
		tt_voucher_preco_roteiros()
	);
}

function tt_voucher_aba_experiencias( $st ) {
	$n    = wp_count_posts( 'post' );
	$blog = (int) get_option( 'page_for_posts' );
	echo '<p><strong>' . (int) ( $n->publish ?? 0 ) . '</strong> posts publicados · <a href="' . esc_url( admin_url( 'edit.php' ) ) . '">Ver todos</a> · <a href="' . esc_url( admin_url( 'post-new.php' ) ) . '">Escrever</a>';
	if ( $blog ) {
		echo ' · <a href="' . esc_url( get_permalink( $blog ) ) . '" target="_blank">Abrir a página do blog</a>';
	}
	echo '</p><h2>Lista e página do post</h2>';
	tt_voucher_aviso_site_antigo( $st );
	tt_voucher_tabela_pecas( tt_voucher_grupos()['experiencias'], $st );
	tt_voucher_form_instalar( 'experiencias', 'experiencias', $st );
}

function tt_voucher_aba_contato( $st ) {
	echo '<h2>Página de contato</h2>';
	tt_voucher_aviso_site_antigo( $st );
	tt_voucher_tabela_pecas( tt_voucher_grupos()['contato'], $st );
	tt_voucher_form_instalar( 'contato', 'contato', $st );
	echo '<h2>Canais de atendimento</h2><p class="description">Aparecem na página de contato, no rodapé e nos botões de WhatsApp. Hoje: '
		. esc_html( implode( ' · ', array_filter( array( tt_voucher_valor( 'whatsapp' ), tt_voucher_valor( 'email_atendimento' ), tt_voucher_valor( 'endereco' ) ) ) ) ) . '</p>';
	tt_voucher_form_dados( 'contato', $st );
}

function tt_voucher_aba_cotacao( $st ) {
	echo '<h2>Página de cotação</h2>';
	tt_voucher_aviso_site_antigo( $st );
	tt_voucher_tabela_pecas( tt_voucher_grupos()['cotacao'], $st );
	tt_voucher_form_instalar( 'cotacao', 'cotacao', $st );
	tt_voucher_form_opcao(
		'cotacao',
		'moeda_orcamento',
		'Orçamento do formulário',
		'Moeda das faixas de orçamento por pessoa que o cliente escolhe na cotação. São faixas fixas em cada moeda, sem conversão de câmbio; dá para ajustá-las depois em Editar formulário.',
		array( 'USD' => 'Dólar americano (US$ 2.000 a acima de US$ 10.000)', 'BRL' => 'Real (R$ 10.000 a acima de R$ 50.000)' ),
		tt_voucher_moeda_orcamento()
	);
	echo '<h2>Captação de leads</h2><p style="max-width:860px">Depois de cada envio com sucesso (cotação, contato e newsletter), o site manda o lead ao webhook com o ID da agência, os dados do formulário, as UTMs e os ids de clique (gclid, fbclid…), guardados por 90 dias.</p>';
	echo '<p>Situação: <strong>' . ( $st['captacao'] ? 'ligada' : 'desligada' ) . '</strong> · ID da agência: <strong>' . ( $st['dados']['id'] ? esc_html( $st['dados']['id'] ) : 'não preenchido (aba Licença)' ) . '</strong></p>';
	tt_voucher_form_dados( 'cotacao', $st );
}

function tt_voucher_aba_sobre( $st ) {
	echo '<h2>Página Sobre</h2>';
	tt_voucher_aviso_site_antigo( $st );
	tt_voucher_tabela_pecas( tt_voucher_grupos()['sobre'], $st );
	tt_voucher_form_instalar( 'sobre', 'sobre', $st );
	echo '<h2>Textos da agência</h2><p class="description">A descrição curta e a história aparecem na página Sobre.</p>';
	tt_voucher_form_dados( 'sobre', $st );
}

function tt_voucher_tela() {
	$st  = tt_voucher_status();
	$aba = tt_voucher_aba_atual();
	echo '<div class="wrap"><h1>Voucher Tec — Travel Tec</h1>';
	echo '<nav class="nav-tab-wrapper" style="margin-bottom:16px">';
	foreach ( tt_voucher_abas() as $k => $titulo ) {
		echo '<a href="' . esc_url( tt_voucher_url_painel( array( 'aba' => $k ) ) ) . '" class="nav-tab' . ( $k === $aba ? ' nav-tab-active' : '' ) . '">' . esc_html( $titulo ) . '</a>';
	}
	echo '</nav>';
	tt_voucher_mostrar_relatorio();
	call_user_func( 'tt_voucher_aba_' . $aba, $st );
	echo '</div>';
}

add_action( 'admin_notices', function () {
	if ( get_transient( 'tt_roteiros_aviso' ) && current_user_can( 'manage_options' ) ) {
		delete_transient( 'tt_roteiros_aviso' );
		echo '<div class="notice notice-warning is-dismissible"><p><strong>Voucher Tec:</strong> ative o Elementor Pro (e o JetFormBuilder) e depois clique em <a href="' . esc_url( tt_voucher_url_painel( array( 'aba' => 'identidade' ) ) ) . '">Voucher Tec › Identidade › Instalar o site padrão inteiro</a>.</p></div>';
	}
} );
