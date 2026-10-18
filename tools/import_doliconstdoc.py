#!/usr/bin/env python3
"""Importe l'inventaire GPL de DoliConstDoc et recoupe les clés avec trois versions."""

import argparse
import json
import re
import sqlite3
from collections import defaultdict
from pathlib import Path

VERSIONS = ("22.0.4", "23.0.4", "24.0.1")
EXCLUDED_DIRS = {"vendor", "modulebuilder", "test", "tests", "node_modules"}
QUOTED_KEY = re.compile(r"['\"]([A-Z][A-Z0-9_]{4,})['\"]")
PROPERTY_KEY = re.compile(r"(?:->global->|->conf->)([A-Z][A-Z0-9_]{4,})")


def source_references(source_root: Path, keys: set[str]) -> dict[str, dict[str, list[str]]]:
    references: dict[str, dict[str, list[str]]] = defaultdict(dict)
    for version in VERSIONS:
        root = source_root / f"dolibarr-{version}" / "htdocs"
        if not root.is_dir():
            raise FileNotFoundError(root)
        major = version.split(".")[0]
        for path in root.rglob("*.php"):
            if any(part in EXCLUDED_DIRS for part in path.relative_to(root).parts):
                continue
            try:
                lines = path.read_text(errors="replace").splitlines()
            except OSError:
                continue
            relative = path.relative_to(root).as_posix()
            for line in lines:
                stripped = line.lstrip()
                if stripped.startswith(("//", "#", "*", "/*")):
                    continue
                for key in (set(QUOTED_KEY.findall(line)) | set(PROPERTY_KEY.findall(line))) & keys:
                    paths = references[key].setdefault(major, [])
                    if relative not in paths and len(paths) < 3:
                        paths.append(relative)
    return references


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("source", type=Path, help="Dossier du dépôt DoliConstDoc")
    parser.add_argument("--dolibarr-sources", type=Path, default=Path("/private/tmp"))
    parser.add_argument("--output", type=Path, default=Path("data/discovery.json"))
    args = parser.parse_args()

    database = sqlite3.connect(":memory:")
    data_dir = args.source / "data"
    for name in ("schema.sql", "constants.sql"):
        database.executescript((data_dir / name).read_text())
    database.row_factory = sqlite3.Row
    rows = database.execute(
        "SELECT name, type, module, purpose, hidden_setting, confidence FROM constants ORDER BY name"
    ).fetchall()
    keys = {row["name"] for row in rows}
    references = source_references(args.dolibarr_sources, keys)
    entries = []
    for row in rows:
        key = row["name"]
        entry = {
            "key": key,
            "module": row["module"] or "core",
            "purpose_en": row["purpose"] or "",
            "type_hint": row["type"] or "string",
            "hidden": bool(row["hidden_setting"]),
            "confidence": row["confidence"] or "low",
            "references": references.get(key, {}),
        }
        entries.append(entry)
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(entries, ensure_ascii=False, separators=(",", ":")) + "\n")
    print(f"{len(entries)} constantes importées ; {sum(bool(e['references']) for e in entries)} repérées dans V22–V24.")


if __name__ == "__main__":
    main()
