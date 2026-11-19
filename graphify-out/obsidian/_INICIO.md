---
type: dashboard
tags:
  - graphify/dashboard
---

# SeBra — mapa del grafo

Grafo generado por graphify el 2026-11-15 sobre `SeBra`.

- **952** nodos · **1359** aristas · **147** comunidades
- Color por comunidad ya configurado: abre *Graph view* (`Ctrl/Cmd+G`).
- Lienzo manual: [[graph.canvas|Canvas]] (arrastra para reorganizar).

## Comunidades principales

- [[_COMMUNITY_Core Database Migrations|Core Database Migrations]] — 58 nodos
- [[_COMMUNITY_Testing Assertions Guidance|Testing Assertions Guidance]] — 39 nodos
- [[_COMMUNITY_Frontend Build Dependencies|Frontend Build Dependencies]] — 36 nodos
- [[_COMMUNITY_Graph Visualizer Modules|Graph Visualizer Modules]] — 34 nodos
- [[_COMMUNITY_Jetstream Features and Tests|Jetstream Features and Tests]] — 22 nodos
- [[_COMMUNITY_Team Invitation Tests|Team Invitation Tests]] — 21 nodos
- [[_COMMUNITY_Tailwind and Agent Docs|Tailwind and Agent Docs]] — 21 nodos
- [[_COMMUNITY_Fortify User Actions|Fortify User Actions]] — 20 nodos
- [[_COMMUNITY_Caching and Queue Jobs|Caching and Queue Jobs]] — 20 nodos
- [[_COMMUNITY_Team Invitation Action|Team Invitation Action]] — 18 nodos
- [[_COMMUNITY_Team Model and Policy|Team Model and Policy]] — 17 nodos
- [[_COMMUNITY_Password Reset Tests|Password Reset Tests]] — 17 nodos
- [[_COMMUNITY_Sphere Visualizer Controllers|Sphere Visualizer Controllers]] — 14 nodos
- [[_COMMUNITY_Team Creation Tests|Team Creation Tests]] — 14 nodos
- [[_COMMUNITY_Routing and Controller Guidance|Routing and Controller Guidance]] — 14 nodos

## Nodos más conectados

1. [[User]] — 70 aristas
2. [[Team]] — 35 aristas
3. [[TestCase]] — 30 aristas
4. [[Laravel Best Practices Skill]] — 23 aristas
5. [[Best-Practices Rule Index]] — 19 aristas
6. [[SphereColorController]] — 12 aristas
7. [[SphereGraphController]] — 12 aristas
8. [[drawGraph()]] — 12 aristas
9. [[Security Best Practices]] — 11 aristas
10. [[Queue and Job Best Practices]] — 11 aristas

## Cómo navegar

- `Ctrl/Cmd+O`: saltar a cualquier nota; cada nota es un nodo del grafo.
- *Graph view* + filtro `tag:#graphify/rationale` para ver solo decisiones de diseño.
- *Graph view* + filtro `tag:#graphify/code` para ver solo código y dejar fuera la documentación.
- Frontmatter de cada nota: `source_file`, `type`, `community`.
- Las consultas `dataview` de las notas `_COMMUNITY_*` necesitan el plugin Dataview; sin él, la lista de miembros estática sigue funcionando.
