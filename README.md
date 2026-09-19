# TravelTec Roteiros

Plugin WordPress que instala o sistema de roteiros nos sites dos clientes: o tipo de
conteúdo **Roteiros** com os campos que o fluxo n8n envia, o recebimento pela API REST
e os três modelos do Elementor (lista em `/roteiros/`, card e página do roteiro).

Não depende de ACF nem de CPT UI. Exige **Elementor + Elementor Pro** (ou PRO Elements).
Cores e fontes vêm do Kit do Elementor de cada site, então o visual sai com a marca do cliente.

## Instalar num site

1. Plugins › Adicionar novo › Enviar plugin › `traveltec-roteiros.zip` › Ativar.
2. Roteiros › Modelos do Elementor › **Instalar modelos**.

O passo 2 é o que regrava os links permanentes; sem ele `/roteiros/` dá 404.

## Atualizar

A partir da 1.3.0 o site consulta as releases deste repositório e mostra
"Atualizar agora" na tela de Plugins, como qualquer plugin do repositório oficial.
Para conferir na hora: Roteiros › Modelos do Elementor › **Procurar atualização**.

Se o repositório for privado, cada site precisa de um token de leitura no `wp-config.php`:

```php
define( 'TT_ROTEIROS_GITHUB_TOKEN', 'ghp_...' );
```

Atualizar o plugin troca o código, não os modelos do Elementor — isso apagaria o que o
cliente tiver editado neles. Quando uma versão muda os modelos, o painel avisa e o
botão *Reinstalar modelos* aplica o layout novo.

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
| `traveltec-roteiros/templates/*.json` | os modelos do Elementor, prontos para gravar |
| `src/*.json` | modelos originais extraídos do site de referência |
| `build_templates.py` | gera `templates/` a partir de `src/` |
| `empacotar.py` | gera `dist/traveltec-roteiros.zip` |
