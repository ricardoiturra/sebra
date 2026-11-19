---
type: community
cohesion: 0.27
members: 10
---

# Docker Compose Services

**Cohesion:** 0.27 - loosely connected
**Members:** 10 nodes

## Members
- [[APP_DEBUG]] - code - compose.yaml
- [[APP_ENV]] - code - compose.yaml
- [[APP_NAME]] - code - compose.yaml
- [[APP_URL]] - code - compose.yaml
- [[DB_CONNECTION]] - code - compose.yaml
- [[DB_DATABASE]] - code - compose.yaml
- [[REDIS_CLIENT]] - code - compose.yaml
- [[REDIS_HOST]] - code - compose.yaml
- [[environment]] - code - compose.yaml
- [[environment_1]] - code - compose.yaml

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Docker_Compose_Services
SORT file.name ASC
```

## Connections to other communities
- 1 edge to [[_COMMUNITY_Compose Web Service]]
- 1 edge to [[_COMMUNITY_Compose Redis Service]]

## Top bridge nodes
- [[environment_1]] - degree 9, connects to 1 community
- [[environment]] - degree 5, connects to 1 community