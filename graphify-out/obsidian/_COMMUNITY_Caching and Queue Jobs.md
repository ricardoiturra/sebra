---
type: community
cohesion: 0.12
members: 20
---

# Caching and Queue Jobs

**Cohesion:** 0.12 - loosely connected
**Members:** 20 nodes

## Members
- [[Atomic Locks for Race Conditions]] - concept - .claude/skills/laravel-best-practices/rules/architecture.md
- [[Back Off Transient Failures]] - concept - .claude/skills/laravel-best-practices/rules/queue-jobs.md
- [[Bound Work Inside the Task]] - rationale - .claude/skills/laravel-best-practices/rules/scheduling.md
- [[Busbatch() Job Batches]] - concept - .claude/skills/laravel-best-practices/rules/queue-jobs.md
- [[Cacheadd() Atomic Conditional Write]] - concept - .claude/skills/laravel-best-practices/rules/caching.md
- [[Laravel Horizon for Redis Queues]] - concept - .claude/skills/laravel-best-practices/rules/queue-jobs.md
- [[Queue and Job Best Practices]] - document - .claude/skills/laravel-best-practices/rules/queue-jobs.md
- [[Rate Limit Sensitive Endpoints]] - concept - .claude/skills/laravel-best-practices/rules/security.md
- [[RateLimited Queue Middleware]] - concept - .claude/skills/laravel-best-practices/rules/queue-jobs.md
- [[Schedule Groups for Shared Constraints]] - concept - .claude/skills/laravel-best-practices/rules/scheduling.md
- [[Scheduler Nodes Share an Atomic-Lock Cache Store]] - rationale - .claude/skills/laravel-best-practices/rules/scheduling.md
- [[Task Scheduling Best Practices]] - document - .claude/skills/laravel-best-practices/rules/scheduling.md
- [[Terminal Failure Handling]] - concept - .claude/skills/laravel-best-practices/rules/queue-jobs.md
- [[Unique Jobs with ShouldBeUnique]] - concept - .claude/skills/laravel-best-practices/rules/queue-jobs.md
- [[environments() as Operational Safeguard]] - rationale - .claude/skills/laravel-best-practices/rules/scheduling.md
- [[onOneServer() Single-Node Execution]] - concept - .claude/skills/laravel-best-practices/rules/scheduling.md
- [[retryUntil() Time-Based Retry Limit]] - concept - .claude/skills/laravel-best-practices/rules/queue-jobs.md
- [[retry_after Longer Than Execution Time]] - rationale - .claude/skills/laravel-best-practices/rules/queue-jobs.md
- [[runInBackground() Parallel Scheduling]] - concept - .claude/skills/laravel-best-practices/rules/scheduling.md
- [[withoutOverlapping() Mutual Exclusion]] - concept - .claude/skills/laravel-best-practices/rules/scheduling.md

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Caching_and_Queue_Jobs
SORT file.name ASC
```

## Connections to other communities
- 3 edges to [[_COMMUNITY_Configuration Guidance]]
- 1 edge to [[_COMMUNITY_Events and Notifications Guidance]]
- 1 edge to [[_COMMUNITY_Architecture Best Practices]]
- 1 edge to [[_COMMUNITY_Caching Guidance]]

## Top bridge nodes
- [[Queue and Job Best Practices]] - degree 11, connects to 2 communities
- [[Task Scheduling Best Practices]] - degree 7, connects to 1 community
- [[Atomic Locks for Race Conditions]] - degree 4, connects to 1 community
- [[Cacheadd() Atomic Conditional Write]] - degree 3, connects to 1 community
- [[Rate Limit Sensitive Endpoints]] - degree 2, connects to 1 community