"""Gera dist/traveltec-roteiros.zip, o anexo que vai na release do GitHub.

O nome do arquivo é sempre o mesmo (o atualizador procura por ele) e a pasta
dentro do zip é sempre `traveltec-roteiros/` — é assim que o WordPress atualiza
por cima da instalação existente em vez de criar uma pasta nova.

    python empacotar.py
"""

import re
import zipfile
from pathlib import Path

RAIZ = Path(__file__).parent
PLUGIN = RAIZ / "traveltec-roteiros"
DIST = RAIZ / "dist"

# Só o que o plugin precisa em produção; o resto do repositório fica de fora.
INCLUIR = ["traveltec-roteiros.php", "readme.txt", "includes/*.php", "assets/*", "assets/img/*", "templates/v2/*.json"]


def versao():
    cabecalho = (PLUGIN / "traveltec-roteiros.php").read_text(encoding="utf-8")
    return re.search(r"^\s*\*\s*Version:\s*(.+)$", cabecalho, re.M).group(1).strip()


def main():
    v = versao()
    DIST.mkdir(exist_ok=True)
    destino = DIST / "traveltec-roteiros.zip"

    arquivos = sorted({c for padrao in INCLUIR for c in PLUGIN.glob(padrao) if c.is_file()})
    if not arquivos:
        raise SystemExit("Nada para empacotar — confira o caminho do plugin.")

    with zipfile.ZipFile(destino, "w", zipfile.ZIP_DEFLATED) as z:
        for arq in arquivos:
            z.write(arq, f"traveltec-roteiros/{arq.relative_to(PLUGIN).as_posix()}")

    print(f"{destino}  —  versão {v}, {len(arquivos)} arquivos")
    print(f"Release:  gh release create v{v} \"{destino}\" --title \"v{v}\" --notes-file NOTAS.md")


if __name__ == "__main__":
    main()
