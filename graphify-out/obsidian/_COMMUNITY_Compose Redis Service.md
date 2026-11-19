---
type: community
cohesion: 0.22
members: 9
---

# Compose Redis Service

**Cohesion:** 0.22 - loosely connected
**Members:** 9 nodes

## Members
- [[condition]] - code - compose.yaml
- [[depends_on]] - code - compose.yaml
- [[extra_hosts]] - code - compose.yaml
- [[image]] - code - compose.yaml
- [[ports]] - code - compose.yaml
- [[redis]] - code - compose.yaml
- [[restart]] - code - compose.yaml
- [[volumes]] - code - compose.yaml
- [[web]] - code - compose.yaml

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Compose_Redis_Service
SORT file.name ASC
```

## Connections to other communities
- 1 edge to [[_COMMUNITY_Docker Compose Services]]
- 1 edge to [[_COMMUNITY_Compose Volumes and Networks]]
- 1 edge to [[_COMMUNITY_Compose Healthchecks]]
- 1 edge to [[_COMMUNITY_Compose Build Args]]

## Top bridge nodes
- [[web]] - degree 10, connects to 4 communities