# Voucher Tec - Travel Tec

(slug `traveltec-roteiros` — até a 1.3.2 se chamava TravelTec Roteiros)

Plugin WordPress que instala o **site padrão das agências** no layout da Rede Turística (site de
referência): roteiros (tipo de conteúdo, campos, API do n8n), cabeçalho, rodapé, blog, Sobre, Contato,
Cotação, páginas de obrigado, menus, formulários (JetFormBuilder) e a captação de leads (UTMs + webhook
`recebe-forms`). Dados, cores e fontes da agência são aplicados de uma vez, com os campos da tabela
`companies` do Cliente Ideal.

Exige Hello Elementor, **Elementor + Elementor Pro** (ou PRO Elements) e **JetFormBuilder**.
Guia para o MCP montar e configurar um site: [`docs/GUIA-MCP.md`](docs/GUIA-MCP.md).
Estado do módulo: [`docs/modulos/voucher-tec.md`](docs/modulos/voucher-tec.md).

## Instalar num site

1. Plugins › Adicionar novo › Enviar plugin › `traveltec-roteiros.zip` › Ativar.
   Em site novo com Elementor Pro ativo, o site padrão já é instalado na ativação.
2. Senão: **Voucher Tec › Instalar site padrão** (ou `POST /wp-json/voucher-tec/v1/instalar`).
3. **Voucher Tec › Dados da agência** (ou `POST /wp-json/voucher-tec/v1/aplicar` com a linha de `companies`).

## Atualizar

A partir da 1.3.0 o site consulta as releases deste repositório e mostra
"Atualizar agora" na tela de Plugins, como qualquer plugin do repositório oficial.
Para conferir na hora: **Voucher Tec › Licença e instruções › Procurar atualização** (na 1.x: Roteiros › Modelos do Elementor).

Se o repositório for privado, cada site precisa de um token de leitura no `wp-config.php`:

```php
define( 'TT_ROTEIROS_GITHUB_TOKEN', 'ghp_...' );
```

Atualizar o plugin troca o código, não as páginas e modelos do Elementor — isso apagaria o
que foi ajustado neles. Sites da 1.x (como a Rede Turística) recebem a 2.0 sem nada instalado.

## Publicar uma versão

```bash
python empacotar.py
gh release create v1.3.0 dist/traveltec-roteiros.zip --title "v1.3.0" --notes "..."
```

A release **precisa** ter o anexo chamado `traveltec-roteiros.zip` — é o nome que o
atualizador procura. O zipball automático do GitHub não serve: a pasta raiz dele vem
com o nome do repositório e da tag, e o WordPress instalaria como um plugin novo.

## Estrutura

| Caminho | O que é |
| --- | --- |
| `traveltec-roteiros/` | o plugin |
| `traveltec-roteiros/includes/atualizador.php` | atualização pelas releases do GitHub |
| `traveltec-roteiros/includes/` | dados, estilo, instalador, captação, API e painel |
| `traveltec-roteiros/templates/v2/*.json` | páginas, modelos e formulários, com marcadores |
| `traveltec-roteiros/assets/` | `captacao.js`, `galeria.css`, `img/` (fotos da Sobre e assinatura) |
| `src/rede/*.json` | exportação da Rede Turística (fonte dos modelos) |
| `build_v2.py` | gera `templates/v2/` a partir de `src/rede/` |
| `baixar_imagens.py` | copia e comprime as imagens usadas nos modelos |
| `empacotar.py` | gera `dist/traveltec-roteiros.zip` |
| `src/*.json`, `build_templates.py` | 1.x (histórico) |
