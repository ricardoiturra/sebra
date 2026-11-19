# Graph Report - SeBra  (2026-11-14)

## Corpus Check
- 194 files · ~44,378 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 23 file(s) not represented in the graph (top: (none) 19, .example 1, .xml 1)

## Summary
- 881 nodes · 1220 edges · 140 communities (47 shown, 93 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 38 edges (avg confidence: 0.79)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- User
- Core Database Migrations
- SphereColorController
- environment
- Deploying with Laravel Cloud
- Testing Assertions Guidance
- Frontend Build Dependencies
- Caching and Queue Jobs
- Tailwind and Agent Docs
- Convention Inference Skill
- Config Files and Jetstream Tests
- Service Providers
- Events and Notifications
- Illuminate\Foundation\Testing\RefreshDatabase
- TestCase
- Password Reset Tests
- Livewire\Livewire
- Composer Package Metadata
- Production Dependencies
- Development Dependencies
- Composer Scripts
- Eloquent Query Guidance
- Layout Components
- Email Verification Tests
- Blade Component Guidance
- Collection Guidance
- Migration Guidance
- Composer Plugin Config
- Fortify Registration Tests
- Team
- DeleteUser.php
- Error Handling Guidance
- HTTP Client Guidance
- Style and Naming Guidance
- JetstreamServiceProvider.php
- Password Update Tests
- Composer Autoload Map
- API Token Manager View
- Laravel Best Practices Skill
- Unit Test Example
- Test Autoload Map
- Composer Extras
- Logout Sessions View
- Password Confirmation View
- Delete User Form View
- Profile Information View
- MCP Server Config
- Policy and Terms Pages
- Laravel Start Script
- Robots Policy
- SeBra Brand Mark
- ResetUserPassword.php
- InviteTeamMember.php
- UserFactory.php
- AddTeamMember.php
- FortifyServiceProvider.php
- bootstrap/app.php
- RemoveTeamMember.php
- graph-watcher
- Routing and Controller Best Practices
- Illuminate\Support\Facades\Validator
- team-member-manager.blade.php
- services
- web
- SphereGraphController.php
- healthcheck
- Validation and Forms Best Practices
- InviteTeamMemberTest.php
- TeamInvitation.php
- args
- redis
- DatabaseSeeder.php
- web.php
- DeleteAccountTest.php
- ProfileInformationTest.php
- Membership.php
- deleteTeam
- watch-graph.sh

## God Nodes (most connected - your core abstractions)
1. `User` - 69 edges
2. `TestCase` - 44 edges
3. `Team` - 35 edges
4. `Laravel Best Practices Skill` - 23 edges
5. `Best-Practices Rule Index` - 19 edges
6. `Security Best Practices` - 11 edges
7. `Queue and Job Best Practices` - 11 edges
8. `SphereColorController` - 10 edges
9. `TeamPolicy` - 10 edges
10. `Testing Rule Index` - 10 edges

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

## Communities (140 total, 93 thin omitted)

### Community 0 - "User"
Cohesion: 0.18
Nodes (3): {closure#2}(), CreateNewUser, User

### Community 1 - "Core Database Migrations"
Cohesion: 0.06
Nodes (20): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}() (+12 more)

### Community 3 - "environment"
Cohesion: 0.22
Nodes (10): APP_DEBUG, APP_ENV, APP_NAME, APP_URL, DB_CONNECTION, DB_DATABASE, REDIS_CLIENT, REDIS_HOST (+2 more)

### Community 4 - "Deploying with Laravel Cloud"
Cohesion: 0.17
Nodes (10): Checklists for Multi-Step Cloud Operations, Custom Domain Setup Checklist, Full Environment Setup Checklist, Resend Email Setup Checklist, Deploying with Laravel Cloud, Laravel Cloud CLI, Laravel Cloud Platform, Cloud Managed Queues (+2 more)

### Community 5 - "Testing Assertions Guidance"
Cohesion: 0.07
Nodes (28): assertSame() over assertEquals(), Named Response Assertions, Assertions Rule, Endpoint Tests Rule, Finding Framework Features Rule, Framework Fakes, LazilyRefreshDatabase, Http::preventStrayRequests() (+20 more)

### Community 6 - "Frontend Build Dependencies"
Cohesion: 0.06
Nodes (40): dependencies, three@^0.187.0, devDependencies, autoprefixer@^10.4.16, concurrently@^10.0.3, laravel-vite-plugin@^3.1, postcss@^8.4.32, tailwindcss@^3.4.0 (+32 more)

### Community 7 - "Caching and Queue Jobs"
Cohesion: 0.09
Nodes (23): Atomic Locks for Race Conditions, Caching Best Practices, Cache::add() Atomic Conditional Write, Cache::flexible() Stale-While-Revalidate, Cache::memo() In-Execution Memoization, Cache::remember() Cache-Aside Read, Cache Tags for Group Invalidation, Failover Cache Store (+15 more)

### Community 8 - "Tailwind and Agent Docs"
Cohesion: 0.12
Nodes (15): Replaced Tailwind v4 Utilities, Tailwind CSS Development, Tailwind v4 @import Syntax, Testing Best Practices, Laravel Boost Bootstrap Instructions, Laravel Boost Installation, PHP and Composer Prerequisites, Committed .ai/rules Project Rules (+7 more)

### Community 9 - "Convention Inference Skill"
Cohesion: 0.11
Nodes (14): Convention Detection Checklist, 49 Laravel Convention Dimensions, Dimension Verdict Taxonomy, Infer Conventions Skill, Confirm Before Recording, Rule Glob Mapping, Architecture Best Practices, Action Class for Focused Business Operations (+6 more)

### Community 10 - "Config Files and Jetstream Tests"
Cohesion: 0.13
Nodes (3): ApiTokenPermissionsTest, CreateApiTokenTest, DeleteApiTokenTest

### Community 11 - "Service Providers"
Cohesion: 0.20
Nodes (3): AppServiceProvider, FortifyServiceProvider, JetstreamServiceProvider

### Community 12 - "Events and Notifications"
Cohesion: 0.17
Nodes (13): Events and Notifications Best Practices, Event Discovery and Caching, HasLocalePreference on Notifiables, viaQueues() Per-Channel Queues, On-Demand Notifications, Queue Slow Notifications, ShouldDispatchAfterCommit, Mail Best Practices (+5 more)

### Community 13 - "Illuminate\Foundation\Testing\RefreshDatabase"
Cohesion: 0.18
Nodes (3): CreateTeamTest, DeleteTeamTest, PasswordConfirmationTest

### Community 14 - "TestCase"
Cohesion: 0.17
Nodes (4): AuthenticationTest, ExampleTest, UpdateTeamNameTest, TestCase

### Community 16 - "Livewire\Livewire"
Cohesion: 0.12
Nodes (4): BrowserSessionsTest, LeaveTeamTest, RemoveTeamMemberTest, UpdateTeamMemberRoleTest

### Community 17 - "Composer Package Metadata"
Cohesion: 0.22
Nodes (8): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type

### Community 18 - "Production Dependencies"
Cohesion: 0.22
Nodes (9): require, laravel/framework, laravel/jetstream, laravel/sanctum, laravel/tinker, livewire/livewire, php, predis/predis (+1 more)

### Community 19 - "Development Dependencies"
Cohesion: 0.22
Nodes (9): require-dev, fakerphp/faker, laravel/boost, laravel/pail, laravel/pao, laravel/pint, mockery/mockery, nunomaduro/collision (+1 more)

### Community 20 - "Composer Scripts"
Cohesion: 0.22
Nodes (9): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+1 more)

### Community 21 - "Eloquent Query Guidance"
Cohesion: 0.25
Nodes (7): Eager Loading with with(), Eloquent Best Practices, Attribute Casts and Date Casting, Local Scopes for Reusable Queries, Model-Aware Queries, Precise Relationship Types and Return Types, whereBelongsTo() Relationship Constraint

### Community 24 - "Blade Component Guidance"
Cohesion: 0.29
Nodes (6): Blade and View Best Practices, @aware Ancestor Component Props, Blade Components over Includes, Blade Fragments for Partial Rendering, @pushOnce for Per-Component Scripts, View Composer for Shared View Data

### Community 25 - "Collection Guidance"
Cohesion: 0.15
Nodes (13): Collection Best Practices, #[CollectedBy] Custom Collection, cursor() vs lazy() Tradeoff, Higher-Order Collection Messages, lazyById() for Concurrent Updates, toQuery() for Bulk Collection Operations, Database Performance Best Practices, Chunking Large Data Sets (+5 more)

### Community 26 - "Migration Guidance"
Cohesion: 0.15
Nodes (11): Advanced Query Best Practices, Composite Index Design for Query, Conditional Aggregates for Combined Counts, Correlated Subquery for Relationship Values, Correlated Subquery for has-many Ordering, whereHas() vs IN Subquery, Migration Best Practices, Deliberate Foreign-Key Constraints (+3 more)

### Community 27 - "Composer Plugin Config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 30 - "DeleteUser.php"
Cohesion: 0.16
Nodes (3): {closure#1}(), {closure#2}(), DeleteUser

### Community 31 - "Error Handling Guidance"
Cohesion: 0.33
Nodes (6): Error Handling Best Practices, Exception context() Data, Where to Report and Render Exceptions, shouldRenderJsonWhen() API Policy, ShouldntReport Exception Marker, Throttle High-Volume Exception Reports

### Community 32 - "HTTP Client Guidance"
Cohesion: 0.33
Nodes (5): HTTP Client Best Practices, Explicit HTTP Error Handling, Http::fake() in Tests, Http::pool() Concurrent Requests, Explicit HTTP Timeouts

### Community 33 - "Style and Naming Guidance"
Cohesion: 0.33
Nodes (6): Convention and Style Best Practices, Clear, Idiomatic Laravel Syntax, Project Naming Conventions, Maintainable Presentation Code, Str, Arr, Number, and Uri Utilities, Comments That Explain Why

### Community 34 - "JetstreamServiceProvider.php"
Cohesion: 0.19
Nodes (3): CreateTeam, DeleteTeam, UpdateTeamName

### Community 36 - "Composer Autoload Map"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 39 - "API Token Manager View"
Cohesion: 0.40
Nodes (4): confirmApiTokenDeletion, deleteApiToken, manageApiTokenPermissions, updateApiToken

### Community 40 - "Laravel Best Practices Skill"
Cohesion: 0.17
Nodes (12): Configuration Best Practices, App::environment() Environment Checks, Encrypted Environment File, env() Only in Configuration Files, Name Repeated Domain Values, Security Best Practices, CSRF Protection, Encrypted Casts and Hidden Attributes (+4 more)

### Community 45 - "Test Autoload Map"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 46 - "Composer Extras"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 109 - "ResetUserPassword.php"
Cohesion: 0.18
Nodes (3): PasswordValidationRules, ResetUserPassword, UpdateUserPassword

### Community 111 - "UserFactory.php"
Cohesion: 0.23
Nodes (3): TeamFactory, {closure#2}(), UserFactory

### Community 113 - "FortifyServiceProvider.php"
Cohesion: 0.25
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), GET /user()

### Community 114 - "bootstrap/app.php"
Cohesion: 0.22
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 116 - "graph-watcher"
Cohesion: 0.22
Nodes (9): web, command, depends_on, image, restart, user, volumes, graph-watcher (+1 more)

### Community 117 - "Routing and Controller Best Practices"
Cohesion: 0.25
Nodes (7): Routing and Controller Best Practices, Organize Controllers Around Resources, Resource Routes, Implicit Route Model Binding, Scoped Nested Bindings, Authorize Protected Actions, Extract Validation to Form Requests

### Community 119 - "team-member-manager.blade.php"
Cohesion: 0.25
Nodes (7): cancelTeamInvitation, confirmTeamMemberRemoval, leaveTeam, manageRole, removeTeamMember, stopManagingRole, updateRole

### Community 120 - "services"
Cohesion: 0.25
Nodes (7): external, name, name, services, volumes, app, sphere-events

### Community 121 - "web"
Cohesion: 0.25
Nodes (8): redis, condition, web, depends_on, image, ports, restart, volumes

### Community 123 - "healthcheck"
Cohesion: 0.43
Nodes (7): interval, retries, start_period, test, timeout, healthcheck, healthcheck

### Community 124 - "Validation and Forms Best Practices"
Cohesion: 0.33
Nodes (5): Mass Assignment Control, Validation and Forms Best Practices, Readable Array Rule Syntax, Conditional Validation Rules, Use Only Validated Data

### Community 127 - "args"
Cohesion: 0.40
Nodes (5): APP_GID, APP_UID, args, context, build

### Community 128 - "redis"
Cohesion: 0.40
Nodes (5): command, image, restart, volumes, redis

## Ambiguous Edges - Review These
- `Atomic Locks for Race Conditions` → `environments() as Operational Safeguard`  [AMBIGUOUS]
  .claude/skills/laravel-best-practices/rules/scheduling.md · relation: conceptually_related_to

## Knowledge Gaps
- **124 isolated node(s):** `php`, `manageApiTokenPermissions`, `confirmApiTokenDeletion`, `updateApiToken`, `deleteApiToken` (+119 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 428 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **93 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `DatabaseSeeder.php`, `DeleteAccountTest.php`, `ProfileInformationTest.php`, `Config Files and Jetstream Tests`, `Illuminate\Foundation\Testing\RefreshDatabase`, `TestCase`, `Password Reset Tests`, `Livewire\Livewire`, `Email Verification Tests`, `Fortify Registration Tests`, `Team`, `DeleteUser.php`, `JetstreamServiceProvider.php`, `Password Update Tests`, `ResetUserPassword.php`, `InviteTeamMember.php`, `UserFactory.php`, `AddTeamMember.php`, `RemoveTeamMember.php`, `Illuminate\Support\Facades\Validator`, `InviteTeamMemberTest.php`?**
  _High betweenness centrality (0.092) - this node is a cross-community bridge._
- **What connects `php`, `manageApiTokenPermissions`, `confirmApiTokenDeletion` to the rest of the system?**
  _124 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Core Database Migrations` be split into smaller, more focused modules?**
  _Cohesion score 0.06359189378057302 - nodes in this community are weakly interconnected._
- **Why does `Team` connect `Team` to `User`, `JetstreamServiceProvider.php`, `InviteTeamMember.php`, `UserFactory.php`, `AddTeamMember.php`, `RemoveTeamMember.php`, `DeleteUser.php`?**
  _High betweenness centrality (0.023) - this node is a cross-community bridge._
- **Should `Testing Assertions Guidance` be split into smaller, more focused modules?**
  _Cohesion score 0.06882591093117409 - nodes in this community are weakly interconnected._
- **Why does `TestCase` connect `TestCase` to `DeleteAccountTest.php`, `ProfileInformationTest.php`, `Password Update Tests`, `Config Files and Jetstream Tests`, `Illuminate\Foundation\Testing\RefreshDatabase`, `Password Reset Tests`, `Livewire\Livewire`, `InviteTeamMemberTest.php`, `Email Verification Tests`, `Fortify Registration Tests`, `Team`?**
  _High betweenness centrality (0.019) - this node is a cross-community bridge._

### Low-confidence Hints
_AMBIGUOUS edges — the extractor was unsure. Verify before acting on these._

- **What is the exact relationship between `Atomic Locks for Race Conditions` and `environments() as Operational Safeguard`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._