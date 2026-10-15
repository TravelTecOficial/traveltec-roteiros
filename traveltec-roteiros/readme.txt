=== Voucher Tec - Travel Tec ===
(slug traveltec-roteiros; até a 1.3.2 se chamava TravelTec Roteiros)
Requer: WordPress 6+, tema Hello Elementor, Elementor + Elementor Pro / PRO Elements, JetFormBuilder, PHP 7.4+.

Instalação: Plugins > Adicionar novo > Enviar plugin > traveltec-roteiros.zip > Ativar.
Em site novo, ao ativar (com Elementor Pro ativo) o site padrão é instalado sozinho. Senão: Voucher Tec > Instalar site padrão
ou POST /wp-json/voucher-tec/v1/instalar. Dados da agência, cores e fontes: Voucher Tec > Dados da agência ou
POST /wp-json/voucher-tec/v1/aplicar (linha da tabela companies do Cliente Ideal). Guia completo: docs/GUIA-MCP.md.

O que instala
- Tipo de conteúdo "Roteiros" (slug /roteiros/) com os campos do fluxo n8n "Roteiros Lazer - Publicar Pagina":
  subtitulo, texto_de_apresentacao, preco_de_referencia, galeria, programacao, hospedagem, inclui, nao_inclui,
  pagamento, cancelamento, documentacao + imagem destacada. A API aceita o objeto "acf" no POST /wp-json/wp/v2/roteiros.
- Modelo Elementor "página do roteiro" (Single, condição: todos os roteiros).
- Modelo "card" (Loop Item) e o modelo de Arquivo que responde em /roteiros/ com a grade.

1.1.0: a lista passou a ser /roteiros/ (arquivo do tipo de conteúdo). Antes era a página /galeria-de-roteiros/,
que continua existindo em quem instalou a 1.0.0 e pode ser apagada.
Cores e fontes vêm do Kit do Elementor do site.

1.1.1: a página do roteiro passa a exibir o preço de referência, logo abaixo do subtítulo.

1.2.0: a animação da galeria passou a vir num CSS do plugin (o <style> do campo é descartado na gravação).

1.3.0: atualização automática. O site consulta as releases do repositório
github.com/TravelTecOficial/traveltec-roteiros e mostra "Atualizar agora" na tela de Plugins.
Repositório privado: define( 'TT_ROTEIROS_GITHUB_TOKEN', '...' ) no wp-config.php.

1.3.1: rótulos do tipo de conteúdo (busca e lista vazia).

1.3.2: a aba do navegador em /roteiros/ deixa de ser "Roteiros Archive" — quem escrevia isso era o Rank Math.

2.0.0 (Voucher Tec): o plugin passa a instalar o site padrão inteiro, no layout da Rede Turística
- cabeçalho (transparente sobre o topo), rodapé, roteiros (card com movimento, lista, página com galeria e abas),
  blog (lista e post), Sobre, Contato, Cotação, 3 páginas de obrigado, menus e os formulários Cotação, Contato e
  Newsletter (JetFormBuilder, AJAX);
- dados da agência com os campos da tabela companies do Cliente Ideal, exibidos por marcadores (%tt:campo%);
- cores e fontes trocadas de uma vez em todas as páginas e no Kit do Elementor;
- captação de leads: UTMs e ids de clique + envio ao webhook recebe-forms (n8n) com o company_id;
- API REST voucher-tec/v1 (status, instalar, aplicar) e painel "Voucher Tec".
Sites da 1.x atualizam só o código: nada é instalado sem clicar em "Instalar site padrão".

2.0.1 (teste no clone da Rede com os dados da Mergulhando na Viagem):
- logo do rodapé ligado ao logo do site; nome da agência no copyright do rodapé;
- menus do plugin refeitos a cada instalação, com as páginas que existirem;
- Instagram/TikTok/YouTube cadastrados só com o @perfil viram URL completa;
- a secundária da marca só vira a cor dos textos se for escura (senão vai só para o Kit);
- a sinopse do Cliente Ideal (texto interno) nunca é usada no site.

2.0.2 (modelos da Rede Turística v2.0, fechada em 24/09/2026):
- post do blog no padrão da casa (capa, lateral com busca e categorias, chamada final com botão);
- logo do cabeçalho com altura limitada (logos retangulares não crescem no tablet/celular);
- primeira versão publicada no GitHub com atualização automática para os sites.

2.0.3: reinstalar uma peça depois de aplicar os dados já grava com as cores e fontes do site
(antes voltava com a paleta da referência).

2.0.4: inclui o popup "Menu (celular)" que o botão de menu do cabeçalho abre — sem ele, num site
limpo, o menu não abria no celular.

2.0.5: nos sites sem o site padrão instalado (os da 1.x), o plugin não acrescenta nada às páginas —
as fontes do Google e o CSS dos ícones vazios só entram depois de "Instalar site padrão".

2.0.6: painel em abas — Licença e instruções, Identidade, Roteiros, Experiências, Contato, Cotação, Sobre.
Cada aba mostra as suas peças (Editar no Elementor / Ver), instala só aquela parte e edita os dados que ela usa.
Instalar uma parte não mexe em mais nada: cores e fontes globais do Elementor só na instalação completa,
menus só com cabeçalho/rodapé, formulários só os da parte — dá para trocar um site no ar aos poucos.

2.0.7: salvar cores e fontes num site com o layout antigo muda só as peças do plugin; as cores e fontes
globais do Elementor (Kit) só mudam depois que o cabeçalho do plugin estiver instalado.

2.0.8: links internos dos modelos (botões de cotação, "Ver roteiros", blog) apontam para a página que existir no
site — a do plugin ou a que já havia (/cotacao-de-viagem/, /experiencias/, /fale-conosco/...). O post do blog
assume também o lugar de um "Single Post" antigo. Quem já instalou uma parte: reinstalar para ganhar os links.

2.0.9: títulos dos cards do blog no tamanho do modelo mesmo em sites com tipografia global própria (antes pegavam
o tamanho de título do Kit e ficavam enormes).

2.0.10: cabeçalho transparente também nos posts do blog (sobre a foto de capa), como nos roteiros; o fundo
aparece ao rolar a página.

2.0.11: a capa do post desce o título a altura do cabeçalho transparente (reinstalar Experiências).

2.0.12: quando a API do GitHub recusa (limite de consultas por IP, comum em hospedagem compartilhada), o site
descobre a última versão pela página de releases — a atualização aparece mesmo assim.

2.0.13: as páginas do plugin usam as fontes da aba Identidade mesmo quando o Kit do site tem outras (sites com
o layout antigo). Ex.: DSelection, em que o texto do Kit é Cinzel, só maiúsculas.

2.0.14: links dentro das páginas do plugin seguem a fonte do texto (o Kit pode ter fonte própria para links).

2.0.15: as páginas instaladas entram nos menus do site ("Quem Somos", "Fale Conosco", "Cotação"... passam a
apontar para as páginas novas; o que faltar entra no fim do menu do cabeçalho). Ao ativar ou atualizar, a
assinatura TravelTec antiga do rodapé vira uma linha discreta.

2.0.16: duas opções no painel. Aba Cotação: moeda do orçamento por pessoa no formulário (dólar, o padrão, ou
real — faixas fixas em cada moeda, sem conversão de câmbio). Aba Roteiros: preço nos roteiros — exibir o preço
de referência (padrão) ou mostrar "Consulte-nos" no card e na página do roteiro (o valor gravado não muda).
Também pela API: POST /wp-json/voucher-tec/v1/aplicar com moeda_orcamento (USD|BRL) e preco_roteiros
(exibir|consulte); GET /status devolve as escolhas em "opcoes".

2.0.17: roteiros com categoria e tags (as mesmas do blog). Aparecem na tela do roteiro e a API aceita
categories/tags em /wp/v2/roteiros — o Cliente Ideal envia as definidas na importação do roteiro.

2.1.0: menu único "Voucher Tec" com Roteiros, Adicionar roteiro e Configuração (o menu "Roteiros" solto sai). A tela de
edição do roteiro ganhou abas (Resumo, Preço, Fotos, Programação, Hospedagem, Inclui/Não inclui, Condições) com editor
visual, preço em campos (prefixo, moeda, valor, complemento) e fotos pela Biblioteca de Mídia. A lista de roteiros ganhou
as colunas Capa e Preço. O formato gravado não mudou: roteiros importados, o n8n e os modelos seguem iguais; preço e
fotos só são regravados quando você mexe neles. Categorias e Tags dos roteiros ficam no menu Voucher Tec.

2.2.0: Destinos. Novo tipo de conteúdo, em Voucher Tec › Destinos e Adicionar destino: nome, foto de capa, resumo
(para o card), texto de abertura, lista "O que fazer", "Quando ir" e fotos pela Biblioteca de Mídia. No site, a
lista fica em /destinos/ e cada destino em /destinos/<nome>/, com galeria, botão e o formulário de cotação já com o
destino preenchido. Para ligar: Voucher Tec › Configuração › Destinos › Instalar esta parte — até lá os destinos ficam só no
painel e nenhum endereço do site muda. Site que já tem uma
página no endereço /destinos/: a aba avisa — passe o conteúdo para o cadastro e mande a página para a lixeira.
Também pela API: /wp-json/wp/v2/destinos, campos em "acf" (texto_de_abertura, o_que_fazer, quando_ir, galeria,
imagem_destaque).

2.2.1: a lista /destinos/ saía vazia (a grade trazia um filtro da página antiga do site-modelo). Depois de atualizar,
reinstale a parte em Voucher Tec › Configuração › Destinos.

2.2.2: item de menu que era uma página no mesmo endereço da lista (ex.: página "Destinos" em /destinos/) passa a ser
reapontado para a lista — antes ficava como estava e quebrava quando a página saía do ar.

2.3.0: Destinos v2 — página completa. Campos novos (todos opcionais; destino antigo continua igual): nacional ou
internacional, subchamada, perfis (até 3), duração ideal, clima das 4 estações, como chegar, documentação, moeda,
idioma e fuso (só internacional), pontos turísticos e passeios (cards com foto), gastronomia, onde ficar, perguntas
frequentes e "atualizado em". Tela do destino em abas: Resumo, Informações práticas, O que fazer, Onde ficar, FAQ e
Fotos. Página do destino refeita: topo com nome, subchamada e botão "Solicite sua cotação"; Visão geral; Informações
práticas; O que fazer; Onde ficar; Galeria; FAQ (também marcado para o Google, FAQPage); "Atualizado em"; formulário
de cotação (sem ele, botão do WhatsApp do site). Seção sem dado não aparece. O card da lista mostra a subchamada.
API: /wp-json/wp/v2/destinos, "acf" com os campos novos; listas vão e voltam como array. Depois de atualizar,
reinstale a parte em Voucher Tec › Configuração › Destinos (troca a página e o card).

2.3.1: /destinos/ podia abrir com o modelo do blog ("Experiências") em vez da lista de destinos: o Elementor
perdia a condição da lista ao instalar a aba Destinos. Ao atualizar, o site se corrige na primeira visita ao painel.

2.3.2: /destinos/ com o mesmo topo das listas de roteiros e do blog (etiqueta, título e frase, fundo branco; sai a foto
e a chamada final). Títulos das seções da página do destino no tamanho do site (estavam maiores que o nome do destino).
Menu do site: toda parte instalada entra sozinha (Destinos, Experiências, Roteiros, Sobre, Cotação, Contato), no lugar
certo da ordem, mesmo antes de ter conteúdo. Depois de atualizar: reinstale a parte Destinos (troca a lista) e abra o
painel uma vez (o menu se completa).

2.3.3: formulário de cotação dentro da página do destino: o título dele não fica mais gigante e os subtítulos
(Datas da viagem, Passageiros...) deixam de sair brancos no fundo claro.
