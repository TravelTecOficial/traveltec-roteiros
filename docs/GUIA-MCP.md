# Guia para o MCP — montar um site de agência com o Voucher Tec

Roteiro para o agente (Claude + MCPs) transformar um WordPress em preparação no site de uma
agência, a partir dos dados do Cliente Ideal. O plugin é o **Voucher Tec - Travel Tec**
(slug `traveltec-roteiros`, a partir da 2.0.0).

## 0. Pré-requisitos do site

- Domínio provisório (área de teste), nunca o domínio final.
- Tema **Hello Elementor** ativo; **Elementor** + **Elementor Pro / PRO Elements**; **JetFormBuilder**.
- Usuário administrador com **Application Password** (Basic auth nas chamadas REST).
- Requisições com `User-Agent` de navegador (o Cloudflare barra o do Python) e `?_cb=<timestamp>` nas leituras.

## 1. Instalar o plugin

Plugins › Adicionar novo › Enviar plugin › `traveltec-roteiros.zip` (anexo da última release em
`github.com/TravelTecOficial/traveltec-roteiros`) › Ativar.

- Em site novo (sem instalação anterior) e com Elementor Pro ativo, **ativar já instala o site padrão**.
- Caso contrário: `POST /wp-json/voucher-tec/v1/instalar` (ou painel **Voucher Tec › Instalar site padrão**).

```http
POST /wp-json/voucher-tec/v1/instalar
{ "substituir": true }
```

`substituir: true` usa o layout do plugin também em páginas que já existem com o mesmo endereço
(`/sobre/`, `/contato/`, `/cotacao-de-viagens/`...). Sem ele, essas páginas voltam em `conflitos`.
`modulos: ["contato","obrigado-contato"]` instala só as chaves pedidas.

O que é instalado (chaves):

| Chave | O quê |
|---|---|
| `header`, `footer`, `menu-popup` | Cabeçalho (transparente sobre o topo das páginas com a classe `tt-topo-transparente`), rodapé e o popup do menu no celular |
| `card`, `lista`, `single` | Roteiros: card com movimento, lista em `/roteiros/`, página do roteiro (hero, preço, galeria, abas) |
| `blog-arquivo`, `blog-post` | Lista do blog (todos os arquivos) e post |
| `sobre`, `contato`, `cotacao` | Páginas `/sobre/`, `/contato/`, `/cotacao-de-viagens/` |
| `obrigado-cotacao`, `obrigado-contato`, `obrigado-newsletter` | Páginas de obrigado |
| formulários `cotacao`, `contato`, `newsletter` | JetFormBuilder, envio AJAX, redirecionam para as páginas de obrigado |
| menus `principal`, `rodape` | Home, Roteiros, Blog, Cotação, Sobre, Contato / Página inicial, Roteiros, Contato, Política |

Modelos de outro cabeçalho/rodapé que disputem a mesma condição perdem a condição (guardada em
`tt_voucher_condicoes_anteriores`) — o relatório avisa em `avisos`.

## 2. Ler o cliente no Cliente Ideal

MCP `clienteideal-leitura` (somente leitura):

```sql
select id, name, nome_fantasia, celular_atendimento, email_atendimento, logradouro, numero, bairro,
       cidade, uf, cep, horario_funcionamento, instagram_url, facebook_url, linkedin_url, tiktok_url,
       logo_url, cor_primaria, cor_secundaria, cor_terciaria, fonte_titulos, fonte_textos,
       description, history, sinopse, site_oficial
from companies where id = '<company_id>';
```

## 3. Aplicar os dados (uma chamada)

```http
POST /wp-json/voucher-tec/v1/aplicar
Content-Type: application/json

{ ...a linha inteira de companies..., "telefone": "", "youtube_url": "", "webhook_url": "" }
```

Faz de uma vez:
- grava os dados da agência (campos com o mesmo nome da tabela `companies`);
- baixa `logo_url` e define como logo do site (cabeçalho e rodapé usam o logo do site);
- título do site = `nome_fantasia` (ou `name`); descrição = `description` (a `sinopse` é texto interno da IA e nunca vai para o site);
- **cores e fontes**: `cor_primaria`, `cor_secundaria`, `cor_terciaria`, `fonte_titulos`, `fonte_textos`
  trocadas em todas as páginas e modelos do plugin e no Kit do Elementor (a primária gera a versão
  escura e a clara sozinha);
- e-mail de aviso dos formulários = `email_atendimento`;
- limpa o CSS do Elementor e o cache do LiteSpeed.

Campo ausente ou `null` fica como está. Os extras que não existem em `companies`:
`telefone` (fixo, opcional — sem ele usa o celular), `youtube_url`, `webhook_url`
(padrão `https://exec.traveltec.com.br/webhook/recebe-forms`).

**`id` é obrigatório para a captação**: é o `company_id` que vai em todo lead. Sem ele o script não envia ao n8n.

## 4. Conferir

```http
GET /wp-json/voucher-tec/v1/status
```

Devolve versão, dependências, IDs de tudo o que foi instalado (com link de edição), dados, paleta
atual e a lista de marcadores. Abrir no navegador (domínio provisório): `/`, `/roteiros/`, um roteiro,
`/blog/`, `/sobre/`, `/contato/`, `/cotacao-de-viagens/`, `/obrigado-cotacao/` — em desktop e 375px.

## 5. Ajustes finos pelo MCP (EMCP / Elementor MCP)

Os documentos são Elementor clássico (containers + widgets). IDs em `status.documentos`.

**Marcadores — não apagar, são os dados da agência:**

| Marcador | Onde aparece | Valor |
|---|---|---|
| `%tt:nome%` | textos | nome fantasia |
| `%tt:whatsapp%`, `%tt:telefone%` | textos | (11) 99999-8888 |
| `%tt:whatsapp_digitos%`, `%tt:telefone_digitos%` | `wa.me/…`, `tel:+…` | 5511999998888 |
| `%tt:email_atendimento%` | textos, `mailto:` | e-mail |
| `%tt:endereco%`, `%tt:mapa_q%` | contato, rodapé, mapa | endereço montado / consulta do Google Maps |
| `%tt:horario_funcionamento%` | contato, rodapé | horário |
| `%tt:description%`, `%tt:history_html%` | Sobre | textos do Cliente Ideal |
| `%tt:nome_url%` | mensagens de WhatsApp | nome codificado para URL |
| `https://tt.token/instagram_url` (e `facebook_url`, `youtube_url`, `linkedin_url`, `tiktok_url`) | ícones sociais | URL; vazio esconde o ícone |
| `[tt dado="campo"]` | qualquer texto | shortcode com o mesmo valor |

Para trocar um dado, use `aplicar` — não edite o texto no widget.

**Cores**: os widgets e o CSS usam a paleta atual (`status.estilo`). Para mudar a marca, use `aplicar`
com `cor_*`; editar cor à mão num widget vale só para ele e sai da troca automática.

**Cabeçalho transparente**: vale nos roteiros, na home e em páginas com o meta
`_tt_topo_transparente = 1` (a Sobre já vem marcada). Se a home do site não tiver uma imagem grande
no topo, desligue com a option `tt_voucher_home_transparente = 0`.

**Textos que pedem revisão** (genéricos do modelo): Sobre (razões, faixa de destaque, chamada final),
Contato (títulos), Cotação (lateral), páginas de obrigado. Fotos da Sobre vêm do plugin (Unsplash) —
trocar pelas da agência quando houver.

## 6. Captação de leads

Ligada automaticamente quando os formulários são instalados. Em todas as páginas o script
`assets/captacao.js` guarda UTMs e ids de clique (90 dias) e, depois do envio com sucesso de cada
formulário, faz `POST` ao webhook com `company_id`, `source` (`site-cotacao`, `site-contato`,
`site-newsletter`), dados do lead, todos os campos (`campo_*` e `campos_json`), UTMs, `gclid`,
`gbraid`, `wbraid`, `fbclid`, `msclkid`, `ttclid`, cookies de anúncio, página e dispositivo.
Para testar: enviar cada formulário com `?utm_source=teste&gclid=TESTE123` e ver a execução no n8n
(workflow `recebe-forms`, `exec.traveltec.com.br`).

## 7. Não fazer

- Não reinstalar (`/instalar`) depois dos ajustes: volta páginas e modelos ao padrão.
- Não instalar o site padrão num site já no ar (ex.: Rede Turística, que é o site de referência).
- Não usar o MCP `wp-teste`.
