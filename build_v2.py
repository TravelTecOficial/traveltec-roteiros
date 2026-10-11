"""Gera traveltec-roteiros/templates/v2/*.json a partir do site de referência (Rede Turística).

Fonte: src/rede/*.json — exportados da Rede pela REST API (meta completo de cada documento).
Saída: um arquivo por documento, pronto para o instalador do plugin.

O que muda em relação à Rede:
- dados da agência viram marcadores resolvidos na hora de exibir: %tt:campo% (texto) e
  https://tt.token/<campo> (URL inteira — sobrevive ao esc_url do Elementor);
- IDs viram marcadores resolvidos na instalação: {{FORM:x}}, {{PAGE:x}}, {{DOC:x}}, {{MENU:x}},
  {{IMG:arquivo}} e {{IMGID:arquivo}};
- tons de laranja antigos viram a cor primária (#E98300); a paleta e as fontes continuam com os
  valores da Rede e o plugin troca tudo de uma vez ao aplicar os dados do cliente;
- o CSS do cabeçalho transparente passa a depender da classe body.tt-topo-transparente.

Os três modelos de destinos vêm do site-modelo (src/destinos) — ver a seção `destinos` abaixo.

    python build_v2.py
"""
import copy
import json
import re
from pathlib import Path

RAIZ = Path(__file__).parent
SRC = RAIZ / "src" / "rede"
OUT = RAIZ / "traveltec-roteiros" / "templates" / "v2"
IMAGENS = json.loads((RAIZ / "src" / "imagens-rede.json").read_text(encoding="utf-8"))

# (chave, arquivo em src/rede, tipo, título, slug, condições)
DOCS = [
    ("card", "card", "elementor_library", "Roteiros — card", None, []),
    ("lista", "lista", "elementor_library", "Roteiros — lista (/roteiros/)", None, ["include/archive/roteiros_archive"]),
    ("single", "single", "elementor_library", "Roteiros — página do roteiro", None, ["include/singular/roteiros"]),
    ("menu-popup", "menu-popup", "elementor_library", "Menu (celular)", None, ["include/general"]),
    ("header", "header", "elementor_library", "Cabeçalho", None, ["include/general"]),
    ("footer", "footer", "elementor_library", "Rodapé", None, ["include/general"]),
    ("blog-arquivo", "blog-arquivo", "elementor_library", "Blog — arquivo", None, ["include/archive"]),
    ("blog-post", "blog-post", "elementor_library", "Blog — post", None, ["include/singular/post"]),
    ("sobre", "sobre", "page", "Sobre", "sobre", []),
    ("contato", "contato", "page", "Contato", "contato", []),
    ("cotacao", "cotacao", "page", "Cotação de viagens", "cotacao-de-viagens", []),
    ("obrigado-cotacao", "obrigado-cotacao", "page", "Obrigado pela cotação", "obrigado-cotacao", []),
    ("obrigado-contato", "obrigado-contato", "page", "Obrigado pelo contato", "obrigado-contato", []),
    ("obrigado-newsletter", "obrigado-newsletter", "page", "Obrigado por assinar", "obrigado-newsletters", []),
]
FORMS = [("cotacao", "form-cotacao", "Cotação de viagens"), ("contato", "form-contato", "Contato"),
         ("newsletter", "form-newsletter", "Newsletter")]

PRIMARIA = "#E98300"
# tons de laranja antigos espalhados pelo rodapé/cabeçalho — unificados na primária
LARANJAS_ANTIGOS = ["#FC980B", "#FF9805", "#FF9F06", "#FF9704", "#F09308", "#E09705"]

MAPA_Q = "Rua+Jos%C3%A9+Martins+Borges%2C+117%2C+S%C3%A3o+Paulo%2C+SP%2C+02348-080"
ENDERECO = "Rua José Martins Borges, 117 – Jardim Leonor Mendes de Barros, São Paulo/SP – CEP 02348-080"

# Trocas de texto, na ordem. Valores com % são marcadores resolvidos na exibição.
TROCAS = [
    ("https://www.instagram.com/rede_turistica/", "https://tt.token/instagram_url"),
    ("https://www.facebook.com/profile.php?id=61567180525605", "https://tt.token/facebook_url"),
    ("https://www.youtube.com/@RedeTuristica", "https://tt.token/youtube_url"),
    ("Estamos por aqui todos os dias, 24 horas. Escolha", "Estamos por aqui para te atender. Escolha"),
    ("Atendimento todos os dias, 24 horas.", "Atendimento: %tt:horario_funcionamento%."),
    ("Atendimento todos os dias, 24 horas", "Atendimento: %tt:horario_funcionamento%"),
    ("Todos os dias, 24 horas", "%tt:horario_funcionamento%"),
    ("https://wa.me/5511912350808", "https://wa.me/%tt:whatsapp_digitos%"),
    ("WhatsApp (11) 91235-0808", "WhatsApp %tt:whatsapp%"),
    ("(11) 91235-0808", "%tt:whatsapp%"),
    ("tel:+551112350808", "tel:+%tt:telefone_digitos%"),
    ("+55 (11) 1235-0808", "%tt:telefone%"),
    ("mailto:contato@redeturistica.com.br", "mailto:%tt:email_atendimento%"),
    ("contato@redeturistica.com.br", "%tt:email_atendimento%"),
    (ENDERECO, "%tt:endereco%"),
    (MAPA_Q, "%tt:mapa_q%"),
    ("Rede%20Tur%C3%ADstica", "%tt:nome_url%"),
    ("cores e fontes da Rede Turística para o blog padrão (24/09/2026)", "cores e fontes do site (padrão Voucher Tec)"),
    ("Rede Turística", "%tt:nome%"),
    ("Rede Turistica", "%tt:nome%"),
    ("https://redeturistica.com.br/", "/"),
    ("https://redeturistica.com.br", ""),
    # links internos: cada site tem o seu endereço (/cotacao-de-viagem/, /experiencias/...) — resolvido na exibição
    ("/cotacao-de-viagens/", "https://tt.token/url_cotacao"),
    ('href="/roteiros/"', 'href="https://tt.token/url_roteiros"'),
    ('href="/blog/"', 'href="https://tt.token/url_blog"'),
    # o link do título do card herda do título (senão pega o tamanho de título global do Kit do site)
    (".blog-grade .elementor-post__title a{color:var(--blog-titulo)}",
     ".blog-grade .elementor-post__title a{color:var(--blog-titulo);font-family:inherit;font-size:inherit;font-weight:inherit;line-height:inherit;letter-spacing:inherit}"),
    # post com o cabeçalho transparente por cima da capa: o título desce a altura do cabeçalho
    (".blog-capa{padding-top:calc(var(--blog-nav-h) + 28px)}",
     ".blog-capa{padding-top:calc(var(--blog-nav-h) + 28px)}\n"
     "body.tt-topo-transparente .blog-capa{--blog-nav-h:96px}\n"
     "@media (max-width:1024px){body.tt-topo-transparente .blog-capa{--blog-nav-h:80px}}"),
]

REDES = [  # rede, ícone Font Awesome, rótulo
    ("instagram_url", "fab fa-instagram", "Instagram"),
    ("facebook_url", "fab fa-facebook", "Facebook"),
    ("youtube_url", "fab fa-youtube", "YouTube"),
    ("linkedin_url", "fab fa-linkedin", "LinkedIn"),
    ("tiktok_url", "fab fa-tiktok", "TikTok"),
]
SVG = {
    "instagram_url": '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor"/></svg>',
    "facebook_url": '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M14 8h3V4h-3a4 4 0 0 0-4 4v2H7v4h3v8h4v-8h3l1-4h-4V8.5c0-.3.2-.5.5-.5Z"/></svg>',
    "youtube_url": '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M22 8.2a3 3 0 0 0-2.1-2.1C18 5.6 12 5.6 12 5.6s-6 0-7.9.5A3 3 0 0 0 2 8.2 31 31 0 0 0 1.6 12 31 31 0 0 0 2 15.8a3 3 0 0 0 2.1 2.1c1.9.5 7.9.5 7.9.5s6 0 7.9-.5a3 3 0 0 0 2.1-2.1 31 31 0 0 0 .4-3.8 31 31 0 0 0-.4-3.8ZM10 15V9l5.2 3Z"/></svg>',
    "linkedin_url": '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M5 3.5a2 2 0 1 1 0 4 2 2 0 0 1 0-4ZM3.3 9h3.4v11.5H3.3V9Zm5.6 0h3.3v1.6c.5-.9 1.7-1.9 3.5-1.9 3.7 0 4.4 2.4 4.4 5.6v6.2h-3.4v-5.5c0-1.3 0-3-1.9-3s-2.2 1.4-2.2 2.9v5.6H8.9V9Z"/></svg>',
    "tiktok_url": '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M16.6 3c.4 2.2 1.8 3.6 4 3.8v3.3a7.5 7.5 0 0 1-4-1.2v6.3A6.2 6.2 0 1 1 10.4 9v3.4a2.9 2.9 0 1 0 2.9 2.9V3h3.3Z"/></svg>',
}


def redes_html():
    links = "".join(f'<a href="https://tt.token/{k}" target="_blank" rel="noopener" aria-label="{r}">{SVG[k]}</a>' for k, _, r in REDES)
    return f'<div class="rt-obg-redes"><span>Siga a %tt:nome%</span>{links}</div>'


def lista_social(antiga):
    base = antiga[0] if antiga else {}
    nova = []
    for i, (k, icone, _) in enumerate(REDES):
        item = copy.deepcopy(base)
        item["_id"] = f"ttso{i}"
        item["social_icon"] = {"value": icone, "library": "fa-brands"}
        item["link"] = {"url": f"https://tt.token/{k}", "is_external": "on", "nofollow": "", "custom_attributes": ""}
        nova.append(item)
    return nova


def trocar_texto(s):
    # imagens antes das trocas de domínio (senão a URL já perdeu o https://redeturistica...)
    for url, arq in IMAGENS.items():
        s = s.replace(url, "{{IMG:%s}}" % arq)
    # dentro das tags dinâmicas os textos vêm codificados: o marcador vai codificado também
    s = s.replace("Rede%20Tur%C3%ADstica", "%25tt%3Anome%25") if "elementor-tag" in s else s
    for de, para in TROCAS:
        s = s.replace(de, para)
    for cor in LARANJAS_ANTIGOS:
        s = re.sub(re.escape(cor), PRIMARIA, s, flags=re.I)
    s = re.sub(r"rgba\(\s*233\s*,\s*131\s*,\s*0\s*,", "rgba(233,131,0,", s)
    s = re.sub(r'<div class="rt-obg-redes">.*?</div>', lambda m: redes_html(), s, flags=re.S)
    s = s.replace(":is(body.home,body.single-roteiros,body.page-id-7)", "body.tt-topo-transparente")
    # o botão de menu do cabeçalho (celular) abre o popup do menu
    s = s.replace("%22popup%22%3A%221078%22", "%22popup%22%3A%22{{DOC:menu-popup}}%22")
    return s


def tratar(no, chave):
    """Percorre o JSON do Elementor e aplica as trocas estruturais."""
    if isinstance(no, list):
        return [tratar(x, chave) for x in no]
    if not isinstance(no, dict):
        return trocar_texto(no) if isinstance(no, str) else no
    st = no.get("settings")
    if isinstance(st, dict):
        wt = no.get("widgetType")
        if "social_icon_list" in st:
            st["social_icon_list"] = lista_social(st["social_icon_list"])
        if wt == "image" and isinstance(st.get("image"), dict) and "logotipo-1" in str(st["image"].get("url")):
            no["widgetType"] = "theme-site-logo"  # logo da agência: vem do logo do site
            st.pop("image", None)
            st.setdefault("__dynamic__", {})["image"] = '[elementor-tag id="" name="site-logo" settings="%7B%7D"]'

        if wt == "nav-menu" and "menu" in st:
            st["menu"] = "{{MENU:principal}}" if chave in ("header", "menu-popup") else "{{MENU:rodape}}"
        if wt == "jet-form-builder-form" and "form_id" in st:
            st["form_id"] = {"1025": "{{FORM:cotacao}}", "256": "{{FORM:contato}}", "943": "{{FORM:newsletter}}"}[str(st["form_id"])]
        if wt == "loop-grid" and str(st.get("template_id")) == "5357":
            st["template_id"] = "{{DOC:card}}"
        for k, v in list(st.items()):
            if isinstance(v, dict) and "url" in v and "id" in v and isinstance(v.get("url"), str):
                arq = IMAGENS.get(v["url"])
                if arq:
                    v["url"] = "{{IMG:%s}}" % arq
                    v["id"] = "{{IMGID:%s}}" % arq
    for k, v in list(no.items()):
        if k == "settings" and isinstance(v, dict):
            for sk, sv in list(v.items()):
                v[sk] = tratar(sv, chave)
        else:  # elements, itens de listas (icon_list, links) e page_settings
            no[k] = tratar(v, chave)
    if isinstance(st, dict) and no.get("elType") == "widget":
        for k in ("shortcode",):
            if isinstance(st.get(k), str):
                st[k] = (st[k].replace('form_id="1025"', 'form_id="{{FORM:cotacao}}"')
                         .replace('form_id="256"', 'form_id="{{FORM:contato}}"')
                         .replace('form_id="943"', 'form_id="{{FORM:newsletter}}"'))
    return no


def textos_sobre(data):
    """Textos que são da história da Rede viram os campos do Cliente Ideal."""
    def rec(nos):
        for e in nos:
            st = e.get("settings", {})
            if e.get("id") == "sbhp":
                st["editor"] = "%tt:history_html%"
            if e.get("id") == "sbfxp":
                st["editor"] = "<p>%tt:description%</p>"
            rec(e.get("elements", []))
    rec(data)


# ---------------------------------------------------------------------------------------------- destinos
# Fonte: site-modelo "Sua Agência de Viagens" (src/destinos/*.json). O Kit de lá tem cores e fontes globais
# próprias (neutro100, titulo2...) que não existem nos outros sites: aqui cada global vira valor fixo na
# paleta de referência do plugin, que o instalador troca pelas cores e fontes do site (tt_voucher_trocar_paleta).
SRC_DESTINOS = RAIZ / "src" / "destinos"
DESTINOS = [  # (chave, arquivo em src/destinos, título, tipo do modelo, condições)
    ("destino-card", "card", "Destinos — card", "loop-item", []),
    ("destinos-lista", "lista", "Destinos — lista (/destinos/)", "archive", ["include/archive/destinos_archive"]),
    ("destino-single", "single", "Destinos — página do destino", "single-page", ["include/singular/destinos"]),
]
COR_GLOBAL = {
    "primary": PRIMARIA, "secondary": "#FFF1E0", "accent": "#C56F00", "text": "#3C3D3D",
    "neutro100": "#FFFFFF", "neutro300": "#FFB700", "neutro600": "#62738A", "neutro900": "#3C3D3D",
}
TIPO_GLOBAL = {  # id: (fonte, peso, tamanho no computador, tablet, celular, entrelinha)
    "primary": ("Montserrat", "700", 56, 44, 34, 1.15),
    "titulo2": ("Montserrat", "700", 40, 34, 28, 1.2),
    "secondary": ("Montserrat", "700", 28, 24, 22, 1.3),
    "titulo4": ("Montserrat", "700", 22, 21, 20, 1.35),
    "accent": ("Montserrat", "600", 16, 16, 16, 1.2),
    "text": ("Roboto", "400", 17, 16, 16, 1.7),
}
BANNER_DESTINOS = "banner-destinos.jpg"


def resolver_globais(no):
    """Troca cada referência a cor/fonte global do Kit por valor fixo. Global desconhecido: erro (KeyError)."""
    if isinstance(no, list):
        return [resolver_globais(x) for x in no]
    if not isinstance(no, dict):
        return no
    st = no.get("settings")
    if isinstance(st, dict) and isinstance(st.get("__globals__"), dict):
        for campo, ref in st["__globals__"].items():
            m = re.match(r"globals/(colors|typography)\?id=(\w+)$", ref or "")
            if not m:
                continue
            tipo, gid = m.groups()
            if tipo == "colors":
                st[campo] = COR_GLOBAL[gid]
                continue
            fonte, peso, pc, tablet, celular, linha = TIPO_GLOBAL[gid]
            pre = campo[: -len("typography")]  # "typography_typography" -> "typography_"
            st[campo] = "custom"
            st[pre + "font_family"] = fonte
            st[pre + "font_weight"] = peso
            for sufixo, tam in (("", pc), ("_tablet", tablet), ("_mobile", celular)):
                st[pre + "font_size" + sufixo] = {"unit": "px", "size": tam, "sizes": []}
            st[pre + "line_height"] = {"unit": "em", "size": linha, "sizes": []}
        del st["__globals__"]
    return {k: resolver_globais(v) for k, v in no.items()}


def percorrer(nos):
    for e in nos:
        yield e
        yield from percorrer(e.get("elements", []))


def widget(id_, tipo, settings):
    return {"id": id_, "elType": "widget", "widgetType": tipo, "settings": settings, "elements": []}


def coluna(id_, elementos):
    return {"id": id_, "elType": "container", "isInner": True, "elements": elementos, "settings": {
        "content_width": "full", "flex_direction": "column",
        "width": {"unit": "%", "size": 50, "sizes": []}, "width_mobile": {"unit": "%", "size": 100, "sizes": []}}}


def botao_destino():
    """Botão da página de exemplo (Fernando de Noronha), com o nome do destino no texto e link para o formulário."""
    src = json.loads((SRC_DESTINOS / "botao.json").read_text(encoding="utf-8"))
    botao = next(e for e in percorrer(json.loads(src["meta"]["_elementor_data"])) if e.get("widgetType") == "button")
    st = resolver_globais(copy.deepcopy(botao))["settings"]
    st["text"] = "Quero uma cotação"
    st["link"] = {"url": "#tt-destino-cotacao", "is_external": "", "nofollow": "", "custom_attributes": ""}
    st["__dynamic__"] = {"text": '[elementor-tag id="ttd0009" name="post-title" settings="%7B%22before%22%3A%22Quero%20uma%20cota%C3%A7%C3%A3o%20para%20%22%7D"]'}
    return widget("ttd0008", "button", st)


def corpo_destino():
    """Corpo da página do destino: shortcodes do plugin (seção sem conteúdo não aparece)."""
    return [
        widget("ttd0001", "shortcode", {"shortcode": '[tt_destino_texto campo="texto_de_abertura"]'}),
        {"id": "ttd0002", "elType": "container", "isInner": True, "settings": {
            "content_width": "full", "flex_direction": "row", "flex_direction_mobile": "column",
            "flex_gap": {"unit": "px", "size": 48, "column": "48", "row": "32", "isLinked": False}},
         "elements": [
            coluna("ttd0003", [widget("ttd0004", "shortcode", {"shortcode": '[tt_destino_lista titulo="O que fazer"]'})]),
            coluna("ttd0005", [widget("ttd0006", "shortcode", {"shortcode": '[tt_destino_texto campo="quando_ir" titulo="Quando ir"]'})]),
         ]},
        widget("ttd0007", "shortcode", {"shortcode": '[tt_destino_galeria titulo="Galeria"]'}),
        botao_destino(),
    ]


def montar_destino(chave, data):
    data = resolver_globais(data)
    for e in percorrer(data):
        st = e.get("settings") if isinstance(e.get("settings"), dict) else {}
        wt = e.get("widgetType")
        if chave == "destinos-lista":
            if wt == "loop-grid":
                st["template_id"] = "{{DOC:destino-card}}"
                # mesma consulta da lista de roteiros. No site-modelo a grade usava a consulta própria "tt_destinos"
                # (páginas-filhas de Destinos): com ela a lista de destinos sai vazia.
                st["post_query_post_type"] = "destinos"
                st["post_query_post_status"] = ["publish"]
                st.pop("post_query_query_id", None)
            fundo = st.get("background_image")
            if isinstance(fundo, dict) and "banner-destinos" in str(fundo.get("url")):
                st["background_image"] = {"url": "{{IMG:%s}}" % BANNER_DESTINOS, "id": "{{IMGID:%s}}" % BANNER_DESTINOS, "size": "", "alt": "", "source": "library"}
            if wt == "button" and "tt_whatsapp_link" in str(st.get("__dynamic__")):
                st.pop("__dynamic__", None)
                st["link"] = {"url": "https://wa.me/%tt:whatsapp_digitos%", "is_external": "on", "nofollow": "", "custom_attributes": ""}
        if chave == "destino-card":
            e["elements"] = [x for x in e.get("elements", []) if x.get("widgetType") != "post-info"]
        if chave == "destino-single":
            filhos = e.get("elements", [])
            if any(x.get("widgetType") == "theme-post-content" for x in filhos):
                e["elements"] = corpo_destino()
                # o contêiner de origem só tinha o conteúdo do post, sem espaço entre filhos
                st["flex_direction"] = "column"
                st["flex_gap"] = {"unit": "px", "size": 40, "column": "40", "row": "40", "isLinked": True}
            formulario = [x for x in filhos if x.get("widgetType") == "shortcode" and "jet_fb_form" in str(x.get("settings", {}).get("shortcode"))]
            if formulario:
                st["_element_id"] = "tt-destino-cotacao"
                e["elements"] = [widget("ttd0010", "shortcode", {"shortcode": '[tt_destino_form titulo="Solicite sua cotação para este destino"]'})]
    return tratar(data, chave)  # trocas de texto comuns (links internos, nome, WhatsApp...)


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    indice = {"docs": [], "forms": [], "imagens": sorted(set(IMAGENS.values()))}
    for chave, arq, tipo, titulo, slug, cond in DOCS:
        src = json.loads((SRC / f"{arq}.json").read_text(encoding="utf-8"))
        meta = src["meta"]
        data = json.loads(meta["_elementor_data"])
        data = tratar(data, chave)
        if chave == "sobre":
            textos_sobre(data)
        ps = meta.get("_elementor_page_settings") or {}
        ps = tratar(ps if isinstance(ps, dict) else {}, chave)
        doc = {
            "chave": chave, "tipo": tipo, "titulo": titulo, "slug": slug, "condicoes": cond,
            "template_type": meta.get("_elementor_template_type"),
            "page_template": "elementor_header_footer" if tipo == "page" else "",
            "topo_transparente": chave == "sobre",
            "data": data, "page_settings": ps,
        }
        txt = json.dumps(doc, ensure_ascii=False, indent=1)
        restos = re.findall(r"redeturistica|91235|1235-0808|Rede Tur", txt)
        (OUT / f"{chave}.json").write_text(txt, encoding="utf-8")
        indice["docs"].append({"chave": chave, "tipo": tipo, "titulo": titulo, "slug": slug})
        print(f"{chave:22s} {len(txt) // 1024:4d} KB  restos da Rede: {len(restos)}")
    for chave, arq, titulo, tipo_modelo, cond in DESTINOS:
        src = json.loads((SRC_DESTINOS / f"{arq}.json").read_text(encoding="utf-8"))
        meta = src["meta"]
        data = montar_destino(chave, json.loads(meta["_elementor_data"]))
        ps = meta.get("_elementor_page_settings") or {}
        ps = tratar(resolver_globais(ps) if isinstance(ps, dict) else {}, chave)
        doc = {
            "chave": chave, "tipo": "elementor_library", "titulo": titulo, "slug": None, "condicoes": cond,
            "template_type": tipo_modelo, "page_template": "", "topo_transparente": False,
            "data": data, "page_settings": ps,
        }
        txt = json.dumps(doc, ensure_ascii=False, indent=1)
        restos = re.findall(r"yellowgreen|hostingersite|globals/|__globals__|tt_whatsapp_link|form_id=\\\"99", txt)
        if restos:
            raise SystemExit(f"{chave}: sobrou do site-modelo: {sorted(set(restos))}")
        (OUT / f"{chave}.json").write_text(txt, encoding="utf-8")
        indice["docs"].append({"chave": chave, "tipo": "elementor_library", "titulo": titulo, "slug": None})
        print(f"{chave:22s} {len(txt) // 1024:4d} KB  (destinos)")
    for chave, arq, titulo in FORMS:
        src = json.loads((SRC / f"{arq}.json").read_text(encoding="utf-8"))
        m = src["meta"]
        form = {"chave": chave, "titulo": titulo, "content": trocar_texto(src["content"]),
                "args": m.get("_jf_args"), "messages": m.get("_jf_messages"), "validation": m.get("_jf_validation")}
        (OUT / f"form-{chave}.json").write_text(json.dumps(form, ensure_ascii=False, indent=1), encoding="utf-8")
        indice["forms"].append(chave)
        print(f"form-{chave:17s} ok")
    usadas = set()
    for arq in OUT.glob("*.json"):
        usadas |= set(re.findall(r"\{\{IMG:([^}]+)\}\}", arq.read_text(encoding="utf-8")))
    indice["imagens"] = sorted(usadas)  # só as que algum modelo usa
    (OUT / "_indice.json").write_text(json.dumps(indice, ensure_ascii=False, indent=1), encoding="utf-8")


if __name__ == "__main__":
    main()
