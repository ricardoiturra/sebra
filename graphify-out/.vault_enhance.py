"""Enhance a graphify Obsidian vault: community colors, app defaults, home note.

Usage: python .vault_enhance.py [vault_dir]   (default: graphify-out/obsidian)
"""

import json
import re
import sys
from collections import Counter
from datetime import date
from pathlib import Path

PALETTE = [
    0x4C8BF5, 0xF5A04C, 0x4CF5A0, 0xF54C8B, 0xA04CF5, 0xF5D64C,
    0x4CF5E0, 0xF57A4C, 0x8BF54C, 0x8B4CF5, 0x4C6BF5, 0xF5C04C,
    0x4CF56B, 0xD64CF5,
]


def frontmatter(text: str) -> dict[str, str]:
    if not text.startswith("---"):
        return {}
    end = text.find("\n---", 3)
    if end == -1:
        return {}
    fields: dict[str, str] = {}
    for line in text[3:end].splitlines():
        m = re.match(r"^([a-z_]+):\s*\"?(.*?)\"?\s*$", line)
        if m:
            fields[m.group(1)] = m.group(2)
    return fields


def main() -> None:
    vault = Path(sys.argv[1] if len(sys.argv) > 1 else "graphify-out/obsidian")
    graph = json.loads(Path("graphify-out/graph.json").read_text(encoding="utf-8"))
    report = Path("graphify-out/GRAPH_REPORT.md").read_text(encoding="utf-8")

    members = Counter(
        n.get("community_name") for n in graph["nodes"] if n.get("community_name")
    )

    tag_by_label: dict[str, str] = {}
    for note in vault.glob("*.md"):
        fm = frontmatter(note.read_text(encoding="utf-8", errors="replace"))
        community = fm.get("community")
        if not community or community in tag_by_label:
            continue
        for tag in re.findall(r"community/[^\s\"']+", note.read_text(encoding="utf-8", errors="replace")):
            tag_by_label[community] = tag
            break

    ranked = [c for c, _ in members.most_common() if c in tag_by_label][: len(PALETTE)]
    color_groups = [
        {"query": f"tag:#{tag_by_label[c]}", "color": {"a": 1, "rgb": PALETTE[i]}}
        for i, c in enumerate(ranked)
    ]

    obsidian_dir = vault / ".obsidian"
    obsidian_dir.mkdir(exist_ok=True)
    (obsidian_dir / "graph.json").write_text(
        json.dumps(
            {
                "collapse-filter": False,
                "search": "",
                "showTags": False,
                "showAttachments": False,
                "hideUnresolved": False,
                "showOrphans": False,
                "collapse-color-groups": False,
                "colorGroups": color_groups,
                "collapse-display": True,
                "showArrow": True,
                "textFadeMultiplier": 0.35,
                "nodeSizeMultiplier": 1.1,
                "lineSizeMultiplier": 0.9,
                "collapse-forces": True,
                "centerStrength": 0.35,
                "repelStrength": 12,
                "linkStrength": 0.8,
                "linkDistance": 140,
                "scale": 0.9,
                "close": True,
            },
            indent=2,
        ),
        encoding="utf-8",
    )
    (obsidian_dir / "app.json").write_text(
        json.dumps(
            {
                "alwaysUpdateLinks": True,
                "newLinkFormat": "shortest",
                "useMarkdownLinks": False,
                "showUnsupportedFiles": True,
                "attachmentFolderPath": "./",
                "foldHeading": True,
                "foldIndent": True,
            },
            indent=2,
        ),
        encoding="utf-8",
    )

    god_lines = []
    for line in report.splitlines():
        m = re.match(r"^(\d+)\. `([^`]+)` - (\d+) edges", line)
        if m:
            god_lines.append(f"{m.group(1)}. [[{m.group(2)}]] — {m.group(3)} aristas")

    community_lines = [
        f"- [[_COMMUNITY_{name}|{name}]] — {count} nodos"
        for name, count in members.most_common(15)
    ]

    home = f"""---
type: dashboard
tags:
  - graphify/dashboard
---

# SeBra — mapa del grafo

Grafo generado por graphify el {date.today().isoformat()} sobre `SeBra`.

- **{len(graph['nodes'])}** nodos · **{len(graph['links'])}** aristas · **{len(members)}** comunidades
- Color por comunidad ya configurado: abre *Graph view* (`Ctrl/Cmd+G`).
- Lienzo manual: [[graph.canvas|Canvas]] (arrastra para reorganizar).

## Comunidades principales

{chr(10).join(community_lines)}

## Nodos más conectados

{chr(10).join(god_lines)}

## Cómo navegar

- `Ctrl/Cmd+O`: saltar a cualquier nota; cada nota es un nodo del grafo.
- *Graph view* + filtro `tag:#graphify/rationale` para ver solo decisiones de diseño.
- *Graph view* + filtro `tag:#graphify/code` para ver solo código y dejar fuera la documentación.
- Frontmatter de cada nota: `source_file`, `type`, `community`.
- Las consultas `dataview` de las notas `_COMMUNITY_*` necesitan el plugin Dataview; sin él, la lista de miembros estática sigue funcionando.
"""

    (vault / "_INICIO.md").write_text(home, encoding="utf-8")
    print(f"vault: {vault}")
    print(f"color groups: {len(color_groups)} · communities: {len(members)}")


if __name__ == "__main__":
    main()
