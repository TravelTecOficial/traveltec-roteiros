"""Gera traveltec-roteiros/templates/*.json a partir de src/*.json (extraídos do template Rede Turística).
Troca tags do ACF por campos nativos, tira fonte fixa (herda a do Kit) e liga cores às Global Colors do site."""
import json, re, urllib.parse, pathlib, hashlib
ROOT = pathlib.Path(__file__).parent
SRC, OUT = ROOT / "src", ROOT / "traveltec-roteiros" / "templates"

def tag(name, settings):
    return '[elementor-tag id="%s" name="%s" settings="%s"]' % (
        "tt" + hashlib.md5((name + json.dumps(settings)).encode()).hexdigest()[:5], name,
        urllib.parse.quote(json.dumps(settings, separators=(",", ":"))))

def conv_dyn(v):
    m = re.search(r'name="acf-(text|image)" settings="([^"]+)"', v)
    if not m: return v
    key = json.loads(urllib.parse.unquote(m.group(2)))["key"].split(":")[-1]
    if m.group(1) == "image":  # imagem_destaque = imagem destacada do post (o fluxo n8n grava as duas)
        return tag("post-featured-image", {"fallback": {"url": "", "id": ""}})
    return tag("post-custom-field", {"key": key, "custom_key": key})

# cor fixa -> Global Color do Kit (o que fica de fora é sobreposição/branco sobre foto)
GLOBAL_COLOR = {"#000000": "primary", "#757575": "primary", "#9D9D9D": "secondary", "#9B9B9B": "accent",
                "#E7E7E7": None, "#F0E9E9": None}


def bloco_preco():
    """Preço de referência: o modelo da Rede Turística não exibia esse campo."""
    return {
        "id": "ttpreco1", "elType": "container",
        "settings": {
            "flex_direction": "column",
            "margin": {"unit": "px", "top": "0", "right": "10", "bottom": "0", "left": "10", "isLinked": False},
            "padding": {"unit": "px", "top": "0", "right": "10", "bottom": "0", "left": "10", "isLinked": False},
        },
        "elements": [{
            "id": "ttpreco2", "elType": "widget", "widgetType": "text-editor",
            "settings": {
                "__dynamic__": {"editor": tag("post-custom-field", {"key": "preco_de_referencia", "custom_key": "preco_de_referencia"})},
                "typography_typography": "custom",
                "typography_font_weight": "600",
                "__globals__": {"text_color": "globals/colors?id=primary"},
            },
            "elements": [],
        }],
        "isInner": False,
    }

def walk(nodes):
    for e in nodes:
        st = e.get("settings")
        if isinstance(st, dict):
            for k, v in list(st.items()):
                if k == "__dynamic__":
                    st[k] = {kk: conv_dyn(vv) for kk, vv in v.items()}
                elif k == "typography_font_family" or k == "title_typography_font_family":
                    del st[k]
                elif isinstance(v, str) and v.upper() in GLOBAL_COLOR and GLOBAL_COLOR[v.upper()] \
                        and ("color" in k) and "overlay" not in k:
                    st.setdefault("__globals__", {})[k] = "globals/colors?id=" + GLOBAL_COLOR[v.upper()]
        walk(e.get("elements", []))

def ajustes(name, data):
    """Retoques que o original não tinha: card legível sobre qualquer foto e grade responsiva."""
    def rec(nodes):
        for e in nodes:
            st = e.get("settings")
            if name == "card" and isinstance(st, dict):
                if e.get("elType") == "container" and e.get("id") == "530e398":
                    st.update({"min_height": {"unit": "px", "size": 280, "sizes": []},
                               "background_overlay_background": "classic", "background_overlay_color": "#00000066",
                               "border_radius": {"unit": "px", "top": "12", "right": "12", "bottom": "12", "left": "12", "isLinked": True}})
                if e.get("widgetType") == "button":
                    st.update({"button_text_color": "#FFFFFF", "border_color": "#FFFFFFAA", "hover_color": "#000000",
                               "button_background_hover_color": "#FFFFFF"})
            if isinstance(st, dict) and e.get("widgetType") == "loop-grid":
                st.update({"columns": "3", "columns_tablet": "2", "columns_mobile": "1", "posts_per_page": 9,
                           "pagination_type": "numbers", "post_query_post_status": ["publish"],
                           "load_more_no_posts_custom_message": "", "nothing_found_message_text": "Nenhum roteiro publicado ainda.",
                           "pagination_prev_label": "Anterior", "pagination_next_label": "Próximo", "text": "Ver mais"})
            if name in ("galeria", "arquivo") and e.get("widgetType") == "text-editor" and isinstance(st, dict) and "editor" in st:
                st["editor"] = "<p>Os melhores roteiros de viagem você encontra aqui na {{NOME_SITE}}.</p>"
            rec(e.get("elements", []))
    rec(data)

for name in ("single", "card", "galeria", "arquivo"):
    # o arquivo (/roteiros/) usa a mesma receita da galeria: titulo + texto + grade
    data = json.load(open(SRC / (("galeria" if name == "arquivo" else name) + ".json"), encoding="utf8"))
    walk(data)
    ajustes(name, data)
    if name == "single":
        # entra logo depois do container do subtítulo
        alvo = next((i for i, e in enumerate(data)
                     if json.dumps(e, ensure_ascii=False).find("preco_de_referencia") < 0
                     and "subtitulo" in json.dumps(e, ensure_ascii=False)), None)
        if alvo is not None and not any("preco_de_referencia" in json.dumps(e, ensure_ascii=False) for e in data):
            data.insert(alvo + 1, bloco_preco())
            print("   + bloco de preço inserido após o container", alvo)
    txt = json.dumps(data, ensure_ascii=False)
    txt = txt.replace('"template_id": 5099', '"template_id": "{{CARD_ID}}"')
    json.dump(json.loads(txt), open(OUT / (name + ".json"), "w", encoding="utf8"), ensure_ascii=False, indent=1)
    print(name, "ok", len(txt))
