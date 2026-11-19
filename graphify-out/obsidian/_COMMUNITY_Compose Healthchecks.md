---
type: community
cohesion: 0.43
members: 7
---

# Compose Healthchecks

**Cohesion:** 0.43 - moderately connected
**Members:** 7 nodes

## Members
- [[healthcheck]] - code - compose.yaml
- [[healthcheck_1]] - code - compose.yaml
- [[interval]] - code - compose.yaml
- [[retries]] - code - compose.yaml
- [[start_period]] - code - compose.yaml
- [[test_1]] - code - compose.yaml
- [[timeout]] - code - compose.yaml

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Compose_Healthchecks
SORT file.name ASC
```

## Connections to other communities
- 1 edge to [[_COMMUNITY_Compose Redis Service]]
- 1 edge to [[_COMMUNITY_Compose Redis Overrides]]

## Top bridge nodes
- [[healthcheck_1]] - degree 6, connects to 1 community
- [[healthcheck]] - degree 5, connects to 1 community