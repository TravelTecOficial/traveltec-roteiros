"""Baixa da Rede Turística (site de referência) as imagens usadas nos modelos e grava versões
comprimidas em traveltec-roteiros/assets/img/. O plugin as envia para a Biblioteca de Mídia ao instalar.
Gera src/imagens-rede.json (URL original -> arquivo), usado pelo build_v2.py.

    python baixar_imagens.py
"""
import io
import json
import os
import re
import urllib.request
from pathlib import Path

from PIL import Image

RAIZ = Path(__file__).parent
DEST = RAIZ / "traveltec-roteiros" / "assets" / "img"
H = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/126"}
UPLOAD = re.compile(r"https://redeturistica\.com\.br/wp-content/uploads/[^\"'\s)\\]+")


def nome_arquivo(url):
    if "logotipo-2" in url:
        return "traveltec-assinatura.png"
    nome = url.rsplit("/", 1)[1]
    nome = re.sub(r"-(scaled|\d+x\d+)(?=\.)", "", nome)
    return nome.replace(".jpeg", ".jpg")


def main():
    DEST.mkdir(parents=True, exist_ok=True)
    urls = set()
    for arq in (RAIZ / "src" / "rede").glob("*.json"):
        texto = re.sub(r"\\+/", "/", arq.read_text(encoding="utf-8"))
        urls |= set(UPLOAD.findall(texto))
    mapa = {}
    for url in sorted(urls):
        if "logotipo-1" in url:  # logo da Rede: no cliente vira o logo do site
            continue
        if "/2026/" not in url and "logotipo-2" not in url:
            continue
        nome = nome_arquivo(url)
        saida = DEST / nome
        if not saida.exists():
            dados = urllib.request.urlopen(urllib.request.Request(url, headers=H), timeout=60).read()
            if nome.endswith(".jpg"):
                im = Image.open(io.BytesIO(dados)).convert("RGB")
                im.thumbnail((1920, 1920))
                im.save(saida, "JPEG", quality=78, optimize=True, progressive=True)
            else:
                saida.write_bytes(dados)
        mapa[url] = nome
        print(f"{nome:60s} {os.path.getsize(saida) // 1024} KB")
    (RAIZ / "src" / "imagens-rede.json").write_text(json.dumps(mapa, indent=1), encoding="utf-8")


if __name__ == "__main__":
    main()
