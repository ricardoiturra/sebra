# Graph Report - SeBra  (2026-11-15)

## Corpus Check
- 58 files · ~48,004 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 952 nodes · 1359 edges · 147 communities (50 shown, 97 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 46 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Core Database Migrations
- Testing Assertions Guidance
- Frontend Build Dependencies
- Graph Visualizer Modules
- Jetstream Features and Tests
- Tailwind and Agent Docs
- Team Invitation Tests
- Caching and Queue Jobs
- Fortify User Actions
- Team Invitation Action
- Team Model and Policy
- Password Reset Tests
- Routing and Controller Guidance
- Sphere Visualizer Controllers
- Team Creation Tests
- Laravel Cloud Deployment
- Convention Inference Skill
- Advanced Query Guidance
- Collection Guidance
- Configuration Guidance
- Events and Notifications Guidance
- Fortify Password Actions
- User and Team Deletion
- Sphere Graph API
- Layouts and Routes
- Service Providers
- Fortify Service Provider
- User Factory
- Team Member Actions
- Graph Inference Service
- App Bootstrap and Middleware
- Team Lifecycle Actions
- Docker Compose Services
- Remove Team Member Action
- Compose Redis Service
- Compose Web Service
- Composer Package Metadata
- Production Dependencies
- Development Dependencies
- Composer Scripts
- Graphify Query Tooling
- Browser Session and Team Name Tests
- Eloquent Query Guidance
- Profile Update Action
- Team Member Manager View
- Compose Volumes and Networks
- Email Verification Tests
- Architecture Best Practices
- Blade Component Guidance
- Caching Guidance
- Graph Inference Helper
- Compose Healthchecks
- Composer Plugin Config
- Password Update Tests
- Error Handling Guidance
- HTTP Client Guidance
- Style and Naming Guidance
- Two Factor Settings Tests
- Compose Build Args
- Compose Redis Overrides
- Composer Autoload
- API Token Manager View
- Database Seeder
- Delete Team Tests
- Profile Information Tests
- Authentication Tests
- Example Test
- Sphere Color Controller
- Team Membership Model
- Test Autoload
- Composer Extras
- confirmLogout
- confirmPassword
- confirmUserDeletion
- deleteProfilePhoto
- .mcp
- Controller
- deleteTeam
- Privacy Policy
- start-laravel
- watch-graph
- robots.txt Allow All Crawlers
- SeBra Brand Mark

## God Nodes (most connected - your core abstractions)
1. `User` - 70 edges
2. `Team` - 35 edges
3. `TestCase` - 30 edges
4. `Laravel Best Practices Skill` - 23 edges
5. `Best-Practices Rule Index` - 19 edges
6. `SphereColorController` - 12 edges
7. `SphereGraphController` - 12 edges
8. `drawGraph()` - 12 edges
9. `Security Best Practices` - 11 edges
10. `Queue and Job Best Practices` - 11 edges

## Surprising Connections (you probably didn't know these)
- `{closure#2}()` --references--> `User`  [EXTRACTED]
  database/factories/UserFactory.php → app/Models/User.php
- `Tailwind CSS Development` --references--> `search-docs Documentation Search`  [INFERRED]
  .claude/skills/tailwindcss-development/SKILL.md → CLAUDE.md
- `Testing Best Practices` --references--> `search-docs Documentation Search`  [INFERRED]
  .claude/skills/testing-best-practices/SKILL.md → CLAUDE.md
- `Prefer Framework Assertions Over Hand-Built Checks` --semantically_similar_to--> `Named Response Assertions`  [INFERRED] [semantically similar]
  .claude/skills/testing-best-practices/rules/finding-features.md → .claude/skills/testing-best-practices/rules/assertions.md
- `Http::preventStrayRequests()` --semantically_similar_to--> `Global Fakes in Base setUp()`  [INFERRED] [semantically similar]
  .claude/skills/testing-best-practices/rules/isolation.md → .claude/skills/testing-best-practices/rules/performance.md

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **Index Design Guidance Across Rule Files** — _claude_skills_laravel_best_practices_rules_advanced_queries_composite_index_design, _claude_skills_laravel_best_practices_rules_db_performance_query_indexes, _claude_skills_laravel_best_practices_rules_migrations_migration_index_design [EXTRACTED 0.85]
- **Laravel Cloud Deployment Flow** — _claude_skills_deploying_to_cloud_skill_cloud_cli, _claude_skills_deploying_to_cloud_skill_deployment_monitoring, _claude_skills_deploying_to_cloud_skill_secrets_manager, _claude_skills_deploying_to_cloud_reference_checklists_full_environment_setup [EXTRACTED 0.85]
- **N+1 Query Prevention Set** — _claude_skills_laravel_best_practices_rules_db_performance_eager_loading, _claude_skills_laravel_best_practices_rules_db_performance_prevent_lazy_loading, _claude_skills_laravel_best_practices_rules_db_performance_with_count [EXTRACTED 1.00]
- **Scheduler Safety Controls** — _claude_skills_laravel_best_practices_rules_scheduling_without_overlapping, _claude_skills_laravel_best_practices_rules_scheduling_on_one_server, _claude_skills_laravel_best_practices_rules_scheduling_run_in_background, _claude_skills_laravel_best_practices_rules_scheduling_environments [EXTRACTED 1.00]
- **Testing Best Practices Rule Suite** — _claude_skills_testing_best_practices_skill_testing_best_practices, _claude_skills_testing_best_practices_rules_assertions_rule, _claude_skills_testing_best_practices_rules_endpoint_tests_rule, _claude_skills_testing_best_practices_rules_finding_features_rule, _claude_skills_testing_best_practices_rules_isolation_rule, _claude_skills_testing_best_practices_rules_naming_rule, _claude_skills_testing_best_practices_rules_performance_rule, _claude_skills_testing_best_practices_rules_review_rule, _claude_skills_testing_best_practices_rules_security_rule, _claude_skills_testing_best_practices_rules_test_data_rule [EXTRACTED 1.00]
- **Laravel Boost Onboarding Flow** — agents_laravel_boost_bootstrap, agents_php_composer_prerequisites, agents_laravel_boost_install, readme_agentic_development, claude_laravel_boost_guidelines [INFERRED 0.85]
- **Test Determinism and Isolation Practices** — _claude_skills_testing_best_practices_rules_isolation_framework_fakes, _claude_skills_testing_best_practices_rules_isolation_time_and_randomness_control, _claude_skills_testing_best_practices_rules_isolation_outbound_http_prevent_stray_requests, _claude_skills_testing_best_practices_rules_isolation_lazily_refresh_database, _claude_skills_testing_best_practices_rules_performance_global_fakes [INFERRED 0.85]

## Communities (147 total, 97 thin omitted)

### Community 0 - "Core Database Migrations"
Cohesion: 0.06
Nodes (21): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}() (+13 more)

### Community 1 - "Testing Assertions Guidance"
Cohesion: 0.07
Nodes (28): assertSame() over assertEquals(), Named Response Assertions, Assertions Rule, Endpoint Tests Rule, Finding Framework Features Rule, Framework Fakes, LazilyRefreshDatabase, Http::preventStrayRequests() (+20 more)

### Community 2 - "Frontend Build Dependencies"
Cohesion: 0.08
Nodes (32): dependencies, three@^0.187.0, devDependencies, autoprefixer@^10.4.16, concurrently@^10.0.3, laravel-vite-plugin@^3.1, @playwright/test@^1.65.1, postcss@^8.4.32 (+24 more)

### Community 3 - "Graph Visualizer Modules"
Cohesion: 0.10
Nodes (20): three, graphMount, connectGraph(), applyHighlight(), applyLiveSelection(), refresh(), select(), connectGraphControls() (+12 more)

### Community 4 - "Jetstream Features and Tests"
Cohesion: 0.13
Nodes (6): ApiTokenPermissionsTest, CreateApiTokenTest, DeleteAccountTest, DeleteApiTokenTest, ExampleTest, TestCase

### Community 5 - "Tailwind and Agent Docs"
Cohesion: 0.12
Nodes (15): Replaced Tailwind v4 Utilities, Tailwind CSS Development, Tailwind v4 @import Syntax, Testing Best Practices, Laravel Boost Bootstrap Instructions, Laravel Boost Installation, PHP and Composer Prerequisites, Committed .ai/rules Project Rules (+7 more)

### Community 6 - "Team Invitation Tests"
Cohesion: 0.13
Nodes (4): InviteTeamMemberTest, LeaveTeamTest, RemoveTeamMemberTest, UpdateTeamMemberRoleTest

### Community 7 - "Caching and Queue Jobs"
Cohesion: 0.12
Nodes (16): Atomic Locks for Race Conditions, Cache::add() Atomic Conditional Write, Queue and Job Best Practices, Laravel Horizon for Redis Queues, Back Off Transient Failures, Bus::batch() Job Batches, Terminal Failure Handling, RateLimited Queue Middleware (+8 more)

### Community 8 - "Fortify User Actions"
Cohesion: 0.17
Nodes (3): {closure#2}(), CreateNewUser, User

### Community 9 - "Team Invitation Action"
Cohesion: 0.16
Nodes (3): {closure#1}(), InviteTeamMember, TeamInvitation

### Community 12 - "Routing and Controller Guidance"
Cohesion: 0.14
Nodes (12): Routing and Controller Best Practices, Organize Controllers Around Resources, Resource Routes, Implicit Route Model Binding, Scoped Nested Bindings, Authorize Protected Actions, Mass Assignment Control, Validation and Forms Best Practices (+4 more)

### Community 14 - "Team Creation Tests"
Cohesion: 0.18
Nodes (3): CreateTeamTest, DashboardGraphTest, PasswordConfirmationTest

### Community 15 - "Laravel Cloud Deployment"
Cohesion: 0.17
Nodes (10): Checklists for Multi-Step Cloud Operations, Custom Domain Setup Checklist, Full Environment Setup Checklist, Resend Email Setup Checklist, Deploying with Laravel Cloud, Laravel Cloud CLI, Laravel Cloud Platform, Cloud Managed Queues (+2 more)

### Community 16 - "Convention Inference Skill"
Cohesion: 0.17
Nodes (7): Convention Detection Checklist, 49 Laravel Convention Dimensions, Dimension Verdict Taxonomy, Infer Conventions Skill, Confirm Before Recording, Rule Glob Mapping, Action Class for Focused Business Operations

### Community 17 - "Advanced Query Guidance"
Cohesion: 0.15
Nodes (11): Advanced Query Best Practices, Composite Index Design for Query, Conditional Aggregates for Combined Counts, Correlated Subquery for Relationship Values, Correlated Subquery for has-many Ordering, whereHas() vs IN Subquery, Migration Best Practices, Deliberate Foreign-Key Constraints (+3 more)

### Community 18 - "Collection Guidance"
Cohesion: 0.15
Nodes (13): Collection Best Practices, #[CollectedBy] Custom Collection, cursor() vs lazy() Tradeoff, Higher-Order Collection Messages, lazyById() for Concurrent Updates, toQuery() for Bulk Collection Operations, Database Performance Best Practices, Chunking Large Data Sets (+5 more)

### Community 19 - "Configuration Guidance"
Cohesion: 0.17
Nodes (12): Configuration Best Practices, App::environment() Environment Checks, Encrypted Environment File, env() Only in Configuration Files, Name Repeated Domain Values, Security Best Practices, CSRF Protection, Encrypted Casts and Hidden Attributes (+4 more)

### Community 20 - "Events and Notifications Guidance"
Cohesion: 0.17
Nodes (13): Events and Notifications Best Practices, Event Discovery and Caching, HasLocalePreference on Notifiables, viaQueues() Per-Channel Queues, On-Demand Notifications, Queue Slow Notifications, ShouldDispatchAfterCommit, Mail Best Practices (+5 more)

### Community 21 - "Fortify Password Actions"
Cohesion: 0.18
Nodes (3): PasswordValidationRules, ResetUserPassword, UpdateUserPassword

### Community 22 - "User and Team Deletion"
Cohesion: 0.26
Nodes (4): DeleteTeam, {closure#1}(), {closure#2}(), DeleteUser

### Community 24 - "Layouts and Routes"
Cohesion: 0.24
Nodes (4): AppLayout, GuestLayout, GET /(), GET /dashboard()

### Community 25 - "Service Providers"
Cohesion: 0.20
Nodes (3): AppServiceProvider, FortifyServiceProvider, JetstreamServiceProvider

### Community 26 - "Fortify Service Provider"
Cohesion: 0.23
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), GET /user()

### Community 27 - "User Factory"
Cohesion: 0.23
Nodes (3): TeamFactory, {closure#2}(), UserFactory

### Community 30 - "App Bootstrap and Middleware"
Cohesion: 0.22
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 32 - "Docker Compose Services"
Cohesion: 0.27
Nodes (10): APP_DEBUG, APP_ENV, APP_NAME, APP_URL, DB_CONNECTION, DB_DATABASE, REDIS_CLIENT, REDIS_HOST (+2 more)

### Community 34 - "Compose Redis Service"
Cohesion: 0.22
Nodes (9): redis, condition, web, depends_on, extra_hosts, image, ports, restart (+1 more)

### Community 35 - "Compose Web Service"
Cohesion: 0.22
Nodes (9): web, command, depends_on, image, restart, user, volumes, graph-watcher (+1 more)

### Community 36 - "Composer Package Metadata"
Cohesion: 0.22
Nodes (8): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type

### Community 37 - "Production Dependencies"
Cohesion: 0.22
Nodes (9): require, laravel/framework, laravel/jetstream, laravel/sanctum, laravel/tinker, livewire/livewire, php, predis/predis (+1 more)

### Community 38 - "Development Dependencies"
Cohesion: 0.22
Nodes (9): require-dev, fakerphp/faker, laravel/boost, laravel/pail, laravel/pao, laravel/pint, mockery/mockery, nunomaduro/collision (+1 more)

### Community 39 - "Composer Scripts"
Cohesion: 0.22
Nodes (9): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+1 more)

### Community 40 - "Graphify Query Tooling"
Cohesion: 0.33
Nodes (4): observe_bfs(), neighbors(), record(), publish()

### Community 42 - "Eloquent Query Guidance"
Cohesion: 0.25
Nodes (7): Eager Loading with with(), Eloquent Best Practices, Attribute Casts and Date Casting, Local Scopes for Reusable Queries, Model-Aware Queries, Precise Relationship Types and Return Types, whereBelongsTo() Relationship Constraint

### Community 44 - "Team Member Manager View"
Cohesion: 0.25
Nodes (7): cancelTeamInvitation, confirmTeamMemberRemoval, leaveTeam, manageRole, removeTeamMember, stopManagingRole, updateRole

### Community 45 - "Compose Volumes and Networks"
Cohesion: 0.25
Nodes (7): external, name, name, services, volumes, app, sphere-events

### Community 47 - "Architecture Best Practices"
Cohesion: 0.29
Nodes (7): Architecture Best Practices, Concurrency::run() Parallel Execution, Context Facade for Request-Scoped Data, Contracts at System Boundaries, defer() for Post-Response Work, Constructor and Method Injection, Deterministic Sort Order

### Community 48 - "Blade Component Guidance"
Cohesion: 0.29
Nodes (6): Blade and View Best Practices, @aware Ancestor Component Props, Blade Components over Includes, Blade Fragments for Partial Rendering, @pushOnce for Per-Component Scripts, View Composer for Shared View Data

### Community 49 - "Caching Guidance"
Cohesion: 0.33
Nodes (7): Caching Best Practices, Cache::flexible() Stale-While-Revalidate, Cache::memo() In-Execution Memoization, Cache::remember() Cache-Aside Read, Cache Tags for Group Invalidation, Failover Cache Store, once() In-Process Memoization

### Community 51 - "Compose Healthchecks"
Cohesion: 0.43
Nodes (7): interval, retries, start_period, test, timeout, healthcheck, healthcheck

### Community 52 - "Composer Plugin Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 55 - "Error Handling Guidance"
Cohesion: 0.33
Nodes (6): Error Handling Best Practices, Exception context() Data, Where to Report and Render Exceptions, shouldRenderJsonWhen() API Policy, ShouldntReport Exception Marker, Throttle High-Volume Exception Reports

### Community 56 - "HTTP Client Guidance"
Cohesion: 0.33
Nodes (5): HTTP Client Best Practices, Explicit HTTP Error Handling, Http::fake() in Tests, Http::pool() Concurrent Requests, Explicit HTTP Timeouts

### Community 57 - "Style and Naming Guidance"
Cohesion: 0.33
Nodes (6): Convention and Style Best Practices, Clear, Idiomatic Laravel Syntax, Project Naming Conventions, Maintainable Presentation Code, Str, Arr, Number, and Uri Utilities, Comments That Explain Why

### Community 59 - "Compose Build Args"
Cohesion: 0.40
Nodes (5): APP_GID, APP_UID, args, context, build

### Community 60 - "Compose Redis Overrides"
Cohesion: 0.40
Nodes (5): command, image, restart, volumes, redis

### Community 61 - "Composer Autoload"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 65 - "API Token Manager View"
Cohesion: 0.40
Nodes (4): confirmApiTokenDeletion, deleteApiToken, manageApiTokenPermissions, updateApiToken

### Community 76 - "Test Autoload"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 77 - "Composer Extras"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

## Ambiguous Edges - Review These
- `Atomic Locks for Race Conditions` → `environments() as Operational Safeguard`  [AMBIGUOUS]
  .claude/skills/laravel-best-practices/rules/scheduling.md · relation: conceptually_related_to

## Knowledge Gaps
- **124 isolated node(s):** `confirmApiTokenDeletion`, `deleteApiToken`, `manageApiTokenPermissions`, `updateApiToken`, `confirmLogout` (+119 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 447 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **97 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `Fortify User Actions` to `Jetstream Features and Tests`, `Team Invitation Tests`, `Team Invitation Action`, `Team Model and Policy`, `Password Reset Tests`, `Team Creation Tests`, `Fortify Password Actions`, `User and Team Deletion`, `Layouts and Routes`, `User Factory`, `Team Member Actions`, `Team Lifecycle Actions`, `Remove Team Member Action`, `Browser Session and Team Name Tests`, `Profile Update Action`, `Email Verification Tests`, `Password Update Tests`, `Two Factor Settings Tests`, `Database Seeder`, `Delete Team Tests`, `Profile Information Tests`, `Authentication Tests`?**
  _High betweenness centrality (0.092) - this node is a cross-community bridge._
- **What connects `confirmApiTokenDeletion`, `deleteApiToken`, `manageApiTokenPermissions` to the rest of the system?**
  _124 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Core Database Migrations` be split into smaller, more focused modules?**
  _Cohesion score 0.05928614640048397 - nodes in this community are weakly interconnected._
- **Why does `Team` connect `Team Model and Policy` to `Remove Team Member Action`, `Delete Team Tests`, `Fortify User Actions`, `Team Invitation Action`, `User and Team Deletion`, `Layouts and Routes`, `User Factory`, `Team Member Actions`, `Team Lifecycle Actions`?**
  _High betweenness centrality (0.023) - this node is a cross-community bridge._
- **Should `Testing Assertions Guidance` be split into smaller, more focused modules?**
  _Cohesion score 0.06882591093117409 - nodes in this community are weakly interconnected._
- **Why does `Laravel Best Practices Skill` connect `Configuration Guidance` to `Caching and Queue Jobs`, `Eloquent Query Guidance`, `Routing and Controller Guidance`, `Architecture Best Practices`, `Blade Component Guidance`, `Advanced Query Guidance`, `Collection Guidance`, `Caching Guidance`, `Events and Notifications Guidance`, `Convention Inference Skill`, `Error Handling Guidance`, `HTTP Client Guidance`, `Style and Naming Guidance`?**
  _High betweenness centrality (0.015) - this node is a cross-community bridge._

### Low-confidence Hints
_AMBIGUOUS edges — the extractor was unsure. Verify before acting on these._

- **What is the exact relationship between `Atomic Locks for Race Conditions` and `environments() as Operational Safeguard`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._