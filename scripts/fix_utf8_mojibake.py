#!/usr/bin/env python3
"""Corrige texto UTF-8 doble/triple codificado (mojibake) en PHP, Blade y recursos."""
from __future__ import annotations

import sys
from pathlib import Path

# Directorios relativos a la raíz del proyecto API (sin vendor, storage, etc.)
SCAN_DIRS = ("app", "bootstrap", "config", "database", "lang", "public", "resources", "routes", "tests")
SKIP_DIR_PARTS = frozenset({"vendor", "node_modules", "storage", "bootstrap/cache"})
EXTENSIONS = frozenset({".php", ".md", ".json", ".xml", ".yml", ".yaml", ".neon"})


def fix_c3_83_quote_sequences(b: bytes) -> bytes:
    """Corrige Ó/É rotos como secuencia UTF-8 mal fusionada (Ã + comillas/‰)."""
    b = b.replace(b"\xc3\x83\xe2\x80\x9c", b"\xc3\x93")  # Ó
    b = b.replace(b"\xc3\x83\xe2\x80\xb0", b"\xc3\x89")  # É (p. ej. MÉTRICAS)
    return b


def fix_double_utf8_bytes(b: bytes) -> bytes:
    i = 0
    out = bytearray()
    while i < len(b):
        if i + 4 <= len(b):
            try:
                chunk = b[i : i + 4].decode("utf-8")
                if len(chunk) == 2:
                    fixed = chunk.encode("latin-1").decode("utf-8")
                    if len(fixed) == 1:
                        out.extend(fixed.encode("utf-8"))
                        i += 4
                        continue
            except (UnicodeDecodeError, UnicodeEncodeError, UnicodeError):
                pass
        if i + 6 <= len(b):
            try:
                chunk = b[i : i + 6].decode("utf-8")
                if len(chunk) == 3:
                    fixed = chunk.encode("latin-1").decode("utf-8")
                    if len(fixed) == 1:
                        out.extend(fixed.encode("utf-8"))
                        i += 6
                        continue
            except (UnicodeDecodeError, UnicodeEncodeError, UnicodeError):
                pass
        out.append(b[i])
        i += 1
    return bytes(out)


# Sustituciones tras el paso binario (emojis y símbolos rotos en comentarios).
_UNICODE_REPLACEMENTS: tuple[tuple[str, str], ...] = (
    ("SECCI\u00c3\u201cN:", "SECCIÓN:"),
    ("PROVEEDOR COM\u00c3\u0161N", "PROVEEDOR COMÚN"),
    ("\u00c3\u201crdenes", "Órdenes"),
    ("\u00f0\u0178\u201d\u201d", "\U0001f514"),
    ("\u00f0\u0178\u201c\u00a1", "\U0001f4e1"),
    ("\u00f0\u0178\u201c\u00b1", "\U0001f4f1"),
    ("\u00f0\u0178\u201c\u0160", "\U0001f4ca"),
    ("\u00f0\u0178\u201c\u2039", "\U0001f4cb"),  # 📋
    ("\u00f0\u0178\u2020\u2022", "\U0001f195"),  # 🆕
    ("\u00f0\u0178\u2018\u00a5", "\U0001f465"),  # 👥
    ("\u00f0\u0178\u201d\u2019", "\U0001f512"),
    ("\u00f0\u0178\u0161\u20ac", "\U0001f680"),
    # routes/gerente.php: comentarios con emoji tras doble codificación
    ("\u00f0\u0178\u201d\u008d", "\U0001f50d"),  # 🔍
    ("\u00f0\u0178\u201c\u009d", "\U0001f4dd"),  # 📝
    ("\u00e2\u0161\u00a0\ufe0f", "\u26a0\ufe0f"),
    ("\u00e2\u0153\u2026", "\u2705"),
    ("\u00e2\u0153\u201c", "\u2713"),
    ("\u00e2\u2020\u2019", "\u2192"),
    ("\u00e2\u20ac\u00a6", "\u2026"),
    ("\u00e2\u0153\u008f\ufe0f", "\u270f\ufe0f"),  # ✏️
    ("\u00e2\u009d\u0152", "\u274c"),  # ❌
)


def apply_unicode_replacements(text: str) -> str:
    for old, new in _UNICODE_REPLACEMENTS:
        text = text.replace(old, new)
    return text


def fix_file(path: Path) -> bool:
    raw = path.read_bytes()
    b = fix_c3_83_quote_sequences(raw)
    prev = None
    while b != prev:
        prev = b
        b = fix_double_utf8_bytes(b)
    text = b.decode("utf-8")
    text2 = apply_unicode_replacements(text)
    b2 = text2.encode("utf-8")
    if b2 == raw:
        return False
    path.write_bytes(b2)
    return True


def iter_source_files(root: Path):
    for sub in SCAN_DIRS:
        d = root / sub
        if not d.is_dir():
            continue
        for p in d.rglob("*"):
            if not p.is_file():
                continue
            if any(part in SKIP_DIR_PARTS for part in p.parts):
                continue
            if p.name.endswith(".blade.php"):
                yield p
            elif p.suffix in EXTENSIONS:
                yield p


def main() -> None:
    root = Path(__file__).resolve().parents[1]
    changed: list[Path] = []
    for p in iter_source_files(root):
        try:
            if fix_file(p):
                changed.append(p)
        except OSError as e:
            print(f"skip {p}: {e}", file=sys.stderr)
    for p in changed:
        print(p.relative_to(root))


if __name__ == "__main__":
    main()
