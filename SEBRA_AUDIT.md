# SeBra — Auditoría técnica y transferencia de contexto

Fecha: 15 de noviembre de 2026 (America/Santiago). Raíz inspeccionada: `/home/rick/Proyectos/SeBra`.

## 1. Alcance, método y límites

Esta es una auditoría estática del árbol disponible. La única escritura autorizada y realizada para este entregable es `SEBRA_AUDIT.md`. No se instalaron dependencias, ejecutaron migraciones, iniciaron contenedores, modificaron funcionalidades ni realizaron consultas a proveedores de IA. No se leyó `.env`, ni se incluyen credenciales o valores sensibles. Las recomendaciones no autorizan su implementación.

Etiquetas utilizadas:

- **VERIFICADO:** observable en archivos actuales, metadatos de dependencias o resultados de inspección; no equivale a una prueba de ejecución satisfactoria.
- **DOCUMENTADO:** afirmación de documentación, artefactos anteriores o requisitos entregados.
- **INFERIDO:** interpretación técnica razonable cuya consecuencia no fue reproducida.
- **PENDIENTE:** requiere ejecución, acceso adicional o una decisión del propietario.
- **PROPUESTO:** diseño o trabajo futuro.

**VERIFICADO:** `php -v` y `composer -V` fallan porque los ejecutables no están disponibles en este shell. Node informa `v24.22.1`. Existen `vendor/` y `node_modules/`; se compararon manifiestos y metadatos instalados. Docker está presente, pero no hay acceso al socket del daemon. No se necesitó elevar permisos para completar el análisis estático.

**VERIFICADO:** `git status` y `git log` no reconocen un repositorio utilizable en este entorno; la presencia del directorio `.git/` no permite recuperar commits. `rg` no está disponible; se usaron búsquedas equivalentes y lectura con Python. No se puede atribuir autoría, rama, commit ni estado limpio del working tree.

**VERIFICADO:** existe `graphify-out/graph.json`, consultado con `GRAPHIFY_QUERY_LOG_DISABLE=1 graphify query 'GraphInference Spatie HasRoles Teams' --budget 1200`, sin regenerarlo. Contiene 952 nodos y 1.359 enlaces. La respuesta fue acotada por presupuesto y se contrastó con los archivos reales. No hay nodos con `source_file` correspondiente a `app/Models/Project.php`, `ProjectNote.php`, `app/Http/Controllers/ProjectController.php` o `app/Jobs/ImportNewRelicDocs.php`. El grafo es una ayuda histórica incompleta, no la autoridad del estado actual. No se generaron nuevos artefactos Graphify.

**PENDIENTE:** rutas efectivamente registradas en ejecución, esquema y registros de la base desplegada, servicios activos, compatibilidad real de modelos de IA, entrega de correos, pruebas y compilación. No se ejecutaron PHPUnit, Playwright ni Vite. Una caché de resultados o un manifiesto compilado no demuestra que el código actual pase las comprobaciones.

## 2. Resumen ejecutivo

**VERIFICADO:** SeBra ya supera el esqueleto inicial de Laravel: ofrece autenticación Jetstream/Fortify, equipos personales y colaborativos, proyectos pertenecientes a equipos, notas Markdown, importación de grafos JSON, visualización interactiva con Three.js, consultas contextualizadas mediante Graphify y proveedores de IA, y un job especializado en importar documentación oficial de New Relic. Evidencia principal: `routes/web.php`, `routes/api.php`, `app/Http/Controllers/`, `app/Support/`, `app/Jobs/ImportNewRelicDocs.php` y `resources/js/graph*.js`.

**VERIFICADO:** no existe una entidad independiente Second Brain, ni separación Brain → Project, documentos versionados, embeddings, índice vectorial, agentes especializados persistentes o conversaciones durables en SQL. La unidad funcional de conocimiento actual es `Project`, que reúne objetivo, grafo y notas. La recuperación actual es búsqueda/recorrido del grafo con contexto de unas pocas notas, no RAG semántico mediante embeddings.

**VERIFICADO:** Jetstream y Spatie están implementados simultáneamente, pero cumplen responsabilidades distintas. Jetstream determina membresía y roles por equipo; Spatie tiene `permission.teams = false` y restringe las funciones del grafo del sistema mediante el rol global `full admin`. No se encontró un bypass global de policies para ese rol. Un administrador global no adquiere por ello acceso a todos los proyectos.

**INFERIDO:** conviene evolucionar este monolito Laravel de forma incremental. Los riesgos prioritarios son permisos de escritura demasiado amplios para un futuro rol lector, persistencia automática del contenido generado, configuración de depuración, passkeys incompletas, exposición de contexto a proveedores externos y falta de validación operacional reproducible. Ninguno justifica reconstruir desde cero.

## 3. Estado tecnológico y dependencias

### 3.1 Backend

**VERIFICADO:** las restricciones vienen de `composer.json`; las versiones instaladas se leen de `vendor/composer/installed.json`. `composer.lock` contiene 105 paquetes de producción y 38 de desarrollo. PHP requerido en el manifiesto: `^8.3`; imagen declarada en `Dockerfile`: `php:8.5-apache-bookworm`. **DOCUMENTADO:** `CLAUDE.md` describe PHP 8.5. **PENDIENTE:** versión efectiva del proceso PHP desplegado.

| Dependencia directa | Restricción | Versión en metadatos instalados |
|---|---|---|
| laravel/framework | ^13.17 | v13.42.0 |
| laravel/jetstream | ^5.5 | v5.5.3 |
| laravel/sanctum | ^4.0 | v4.4.0 |
| laravel/tinker | ^3.0 | v3.1.0 |
| livewire/livewire | ^3.6.4 | v3.8.11 |
| predis/predis | ^3.7 | v3.7.1 |
| spatie/laravel-permission | ^8.5 | 8.5.0 |
| fakerphp/faker (dev) | ^1.23 | v1.24.1 |
| laravel/boost (dev) | ^2.10 | v2.10.4 |
| laravel/pail (dev) | ^1.2.5 | v1.3.0 |
| laravel/pao (dev) | ^1.0.6 | v1.1.5 |
| laravel/pint (dev) | ^1.27 | v1.32.3 |
| mockery/mockery (dev) | ^1.6 | 1.6.15 |
| nunomaduro/collision (dev) | ^8.6 | v8.9.6 |
| phpunit/phpunit (dev) | ^12.5.12 | 12.5.44 |

**VERIFICADO:** dependencias transitivas relevantes en `composer.lock`: Fortify `v1.42.0` y Laravel Passkeys `v0.2.1`. No se usa un SDK de IA en la capa de aplicación: `GraphInference` utiliza el cliente HTTP de Laravel. Boost ya aparece instalado en metadatos y existen `boost.json` y directrices en `CLAUDE.md`, mientras `AGENTS.md` conserva el bootstrap inicial. Esto demuestra divergencia documental, no la necesidad de reinstalarlo durante esta auditoría.

### 3.2 Frontend

**VERIFICADO:** versiones de `package-lock.json` coincidentes con los respectivos `node_modules/*/package.json`:

| Paquete | Restricción | Versión |
|---|---|---|
| three | ^0.187.0 | 0.187.0 |
| @playwright/test (dev) | ^1.65.1 | 1.65.1 |
| @tailwindcss/forms (dev) | ^0.5.7 | 0.5.11 |
| @tailwindcss/typography (dev) | ^0.5.10 | 0.5.20 |
| @tailwindcss/vite (dev) | ^4.0.0 | 4.3.5 |
| autoprefixer (dev) | ^10.4.16 | 10.6.2 |
| concurrently (dev) | ^10.0.3 | 10.0.7 |
| laravel-vite-plugin (dev) | ^3.1 | 3.2.1 |
| postcss (dev) | ^8.4.32 | 8.5.32 |
| tailwindcss (dev) | ^3.4.0 | 3.4.19 |
| vite (dev) | ^8.0.0 | 8.3.8 |
| @laravel/multiplex (opcional) | ^0.4.1 | 0.4.5 |

**VERIFICADO:** el lock NPM tiene 222 entradas de paquetes excluyendo la raíz, incluidas variantes opcionales; no todas necesariamente se instalan en cada plataforma. Scripts: `npm run dev` y `npm run build`. `vite.config.js` carga el plugin Laravel; `postcss.config.js` usa Tailwind y Autoprefixer. `@tailwindcss/vite` v4 está declarado pero no se utiliza en Vite; el pipeline visible usa Tailwind v3. **PROPUESTO:** retirar esa dependencia si se confirma innecesaria; no migrar a v4 solo por su presencia.

### 3.3 Infraestructura declarada

**VERIFICADO:** `compose.yaml` define `web`, `project-import-worker`, `graph-watcher` y Redis 8 Alpine con AOF. La web se publica en loopback; SQLite reside en un volumen externo llamado `laravel-sqlite_app`. El código se monta desde el host. `Dockerfile` instala Graphify `0.9.109` en `/opt/graphify` y comprueba PDO SQLite. `start-laravel.sh` prepara permisos y ejecuta migraciones automáticamente al iniciar: por eso iniciar la web no sería una comprobación de solo lectura.

**VERIFICADO:** el watcher observa sustituciones/escrituras de `graphify-out/graph.json` y ejecuta `graph:notify`; no reconstruye el grafo. La orden `graph:notify` publica su SHA-256 en Redis. El worker escucha la conexión `project-imports`, con timeout 7.200 segundos; `config/queue.php` fija `retry_after = 7500`, `after_commit = true`. El job tiene un intento y unicidad por proyecto durante 10.800 segundos.

**PENDIENTE:** disponibilidad del volumen externo, permisos efectivos, backups SQLite/Redis, capacidad de Apache para SSE concurrentes, observabilidad, recuperación de jobs y despliegue independiente del árbol de desarrollo.

## 4. Estructura y arquitectura actual

**VERIFICADO:** organización visible:

```text
app/
  Actions/Fortify/       registro, perfil, contraseña y recuperación
  Actions/Jetstream/     creación/gestión de equipos, invitaciones y borrado
  Http/Controllers/     ProjectController, SphereGraphController, SphereColorController
  Jobs/                 ImportNewRelicDocs
  Models/               User, Team, Membership, TeamInvitation, Project, ProjectNote
  Policies/             TeamPolicy, ProjectPolicy
  Providers/            App, Fortify y Jetstream ServiceProvider
  Support/              GraphInference, ProjectGraph, ActiveCommunities
  View/Components/      AppLayout, GuestLayout
bootstrap/              configuración moderna Laravel, providers y caché
config/                 autenticación, permisos, servicios, DB, colas, etc.
database/               migraciones, seeders y factories
resources/views/        pantallas Blade y formularios Jetstream/Livewire
resources/js/           visualización, controles, simulación y eventos
routes/                 web.php, api.php y console.php
scripts/                adaptador Python para consultas Graphify
tests/                  Unit y Feature
public/build/           artefactos frontend existentes
graphify-out/           grafo, informe, HTML, caché, archivo anterior y bóvedas
storage/                almacenamiento, framework y logs
vendor/, node_modules/  dependencias presentes
```

**VERIFICADO:** arquitectura MVC de Laravel con Active Record Eloquent, actions conectadas a contratos de Fortify/Jetstream, policies, servicios auxiliares concretos y un job. No hay clases propias en `app/Livewire/`: el stack Livewire corresponde sobre todo a componentes Jetstream del paquete, con vistas locales (`resources/views/teams/`, `profile/`, `auth/`). Los flujos de conocimiento usan controladores, Blade y JavaScript con fetch/EventSource. No hay evidencia de una arquitectura de microservicios o de un framework de agentes.

**VERIFICADO:** responsabilidades:

- `ProjectController`: lista/crea proyectos, busca notas, almacena notas e importa JSON.
- `SphereGraphController`: transforma grafos para pantalla, interpreta comandos sencillos, consulta Python, invoca IA y publica progreso.
- `SphereColorController`: operaciones de visualización global y streaming SSE con revalidación de acceso.
- `ProjectGraph`: validación de grafos, incorporación de notas como nodos, extracción de contexto y notificaciones.
- `GraphInference`: selección del proveedor, payloads HTTP, límites de respuesta y errores normalizados.
- `ActiveCommunities`: coordinación atómica en Redis mediante Lua, selección por consulta y expiración de 15 segundos.
- `ImportNewRelicDocs`: ingesta especializada y sincronización por hash.

**INFERIDO:** `SphereGraphController` concentra demasiadas responsabilidades para ampliar conectores, agentes y revisión editorial. Una extracción gradual de comandos e ingesta sería suficiente; no hacen falta repositorios genéricos ni capas adicionales para cada modelo.

### Separación de conceptos

| Concepto | Estado comprobado | Implicación |
|---|---|---|
| Usuario | VERIFICADO: `User` | identidad global |
| Espacio/tenant | VERIFICADO: `Team` | frontera lógica de pertenencia |
| Second Brain | VERIFICADO: sin modelo/ruta propios | no confundirlo con Project sin decidir semántica |
| Proyecto | VERIFICADO: `Project` bajo Team | contenedor actual del conocimiento |
| Documentación | VERIFICADO: `ProjectNote` | texto, fuente y última revisión externa |
| Recurso/fuente genérica | VERIFICADO: sin entidad independiente | `source` es una cadena en la nota |
| Agente IA | VERIFICADO: sin entidad ni ciclo de vida propios | llamadas HTTP y comandos por regex |

## 5. Inventario funcional

“Implementada” significa que existe el flujo en código; su operación en el despliegue queda pendiente salvo indicación expresa.

| Funcionalidad | Estado | Evidencia y límite |
|---|---|---|
| Registro/login/logout/reset/perfil | VERIFICADO: implementada | `app/Actions/Fortify/`, vistas auth/profile, provider |
| 2FA TOTP y recuperación | VERIFICADO: implementada | feature Fortify, trait User, vistas y pruebas |
| Verificación de email obligatoria | VERIFICADO: pendiente/deshabilitada | feature comentada y User no implementa MustVerifyEmail |
| Passkeys | VERIFICADO: parcial, incompatibilidad visible | feature y migración presentes; User carece de contrato requerido |
| Equipos personales/colaborativos | VERIFICADO: implementada | HasTeams, actions, policies, invitaciones y navegación |
| Spatie | VERIFICADO: parcial respecto a permisos granulares | rol global full admin y aliases; teams desactivado |
| Proyectos por equipo | VERIFICADO: implementada | creación y listado paginado de 12, policy de membresía |
| Actualizar/eliminar proyectos | VERIFICADO: pendiente | sin rutas propias para ello |
| Notas Markdown manuales | VERIFICADO: backend implementado, UI parcial | POST notes existe; no hay formulario visible en pantalla actual |
| Notas mediante instrucciones IA | VERIFICADO: implementada | regex de askProject, JSON validado, guardado y deduplicación |
| Buscar/leer notas | VERIFICADO: implementada | LIKE en título/cuerpo, 30 coincidencias, sin lista inicial |
| Editar/borrar/versionar notas | VERIFICADO: pendiente | sin rutas ni UI propias |
| Importar grafo JSON | VERIFICADO: backend implementado, UI parcial | límite 4 MiB, endpoints coherentes, solo owner/admin |
| Grafo 3D y progreso en vivo | VERIFICADO: implementada | Three.js, SSE, controles de ratón/teclado y reduced motion |
| Grafo interno de SeBra | VERIFICADO: implementada, datos desactualizados | archivo global exclusivo de full admin |
| Preguntas al grafo + respuesta IA | VERIFICADO: implementada con límites | Python externo, BFS, reformulación y contexto de notas |
| RAG vectorial/semántico | VERIFICADO: pendiente | no embeddings, chunks ni índice vectorial en código de app |
| Importación New Relic | VERIFICADO: implementada y especializada | GitHub oficial, job, hashes y permisos revalidados |
| Importación web/PDF/repos genérica | VERIFICADO: pendiente | sin conector general |
| Conversación contextual durable | VERIFICADO: parcial | últimos eventos Redis; sin mensajes SQL ni historial enviado al modelo |
| Consolidación supervisada | VERIFICADO: pendiente | contenido IA se publica directamente como nota |
| LLM Wiki | DOCUMENTADO: objetivo; VERIFICADO: sin módulo propio | requisito entregado, sin implementación encontrada |
| Agentes investigadores/herramientas | VERIFICADO: pendiente | regex ejecuta acciones acotadas; no runtime de agentes |
| Editor de código/texto avanzado | VERIFICADO: pendiente | lector Markdown y campo de pregunta, sin editor versionado |
| Mini-SaaS comercial | DOCUMENTADO: visión | sin suscripciones, facturación o cuotas de consumo propias |

## 6. Modelo de datos

**VERIFICADO:** lo siguiente describe migraciones, no tablas efectivamente migradas en la instancia desplegada.

| Tabla/grupo | Columnas y restricciones relevantes | Fuente |
|---|---|---|
| users | id, name, email único, password, email_verified_at, remember_token, current_team_id nullable, profile_photo_path, timestamps; columnas 2FA añadidas | `0001_01_01_000000_create_users_table.php`, `2026_11_13_164846_add_two_factor_columns_to_users_table.php` |
| teams | id, user_id indexado (owner), name, personal_team, timestamps | `2020_05_21_100000_create_teams_table.php` |
| team_user | id, team_id, user_id, role nullable, timestamps; unique(team_id,user_id) | `2020_05_21_200000_create_team_user_table.php` |
| team_invitations | team_id FK cascade, email, role, timestamps; unique(team_id,email) | `2020_05_21_300000_create_team_invitations_table.php` |
| projects | team_id FK cascade, name, objective text, graph JSON nullable, timestamps | `2026_11_15_130000_create_projects_table.php` |
| project_notes | project_id FK cascade, title(120), markdown text, timestamps; source nullable, revision(64), unique(project_id,source) | `2026_11_15_140000_create_project_notes_table.php`, `2026_11_15_150000_add_sources_to_project_notes.php` |
| roles/permissions | name + guard_name únicos; pivotes model_has_roles, model_has_permissions, role_has_permissions | `2026_11_13_164940_create_permission_tables.php` |
| personal_access_tokens | relación polimórfica, token único, abilities y vencimiento | `2026_11_13_164902_create_personal_access_tokens_table.php` |
| passkeys | user_id FK cascade, nombre, credential_id único, credential JSON, last_used_at | `2026_11_13_164847_create_passkeys_table.php` |
| password_reset_tokens/sessions | recuperación por email y sesiones con last_activity indexado | migración inicial users |
| cache/cache_locks | valor, propietario del lock y expiración | `0001_01_01_000001_create_cache_table.php` |
| jobs/job_batches/failed_jobs | payload, intentos, reservas, resultados y fallos | `0001_01_01_000002_create_jobs_table.php` |

Todos los nombres de migraciones anteriores son relativos a `database/migrations/`.

**VERIFICADO:** relaciones Eloquent: User hereda `currentTeam`, `ownedTeams`, `teams` de HasTeams; Team hereda owner/users/teamInvitations y añade `projects`; Project tiene `team` y `notes`; TeamInvitation tiene `team`; Membership extiende el pivot Jetstream. `ProjectNote` no declara relación inversa `project()`. `Project.graph` se castea a array y se oculta al serializar. User oculta password, remember token y secretos 2FA, y usa cast de contraseña hashed.

**VERIFICADO:** las migraciones de teams, team_user, users.current_team_id y sessions.user_id usan identificadores sin declarar todas sus FK; la integridad no es homogénea. Las FK de projects y notes sí incluyen cascada. Las tablas polimórficas de permisos no pueden garantizar por FK la existencia de todos sus modelos.

**VERIFICADO:** migraciones de datos adicionales: `2026_11_14_235959_create_personal_teams_for_existing_users.php` crea equipos faltantes por lotes; `2026_11_15_000000_add_current_team_id_to_existing_users_table.php` añade la columna solo si falta; `2026_11_15_120000_create_full_admin_role.php` crea el rol, no asigna usuarios. Sus down no revierten completamente los cambios, según comentarios explícitos.

**VERIFICADO:** `FullAdminSeeder` usa una identidad fija codificada y exige `FULL_ADMIN_PASSWORD` solo al crear la cuenta; reutiliza la cuenta existente y le asigna el rol. `DatabaseSeeder` crea un usuario de ejemplo y no invoca FullAdminSeeder. **PENDIENTE:** qué seeders se ejecutaron. No se inspeccionaron cuentas reales.

**INFERIDO:** `revision` representa únicamente el hash de la última fuente importada; no constituye historial editorial. El upsert reemplaza el cuerpo anterior. Borrar un equipo puede borrar sus proyectos y notas por cascada; no hay recuperación documental propia.

## 7. Rutas, interfaces y flujo de consulta

### 7.1 Superficie propia

**VERIFICADO:** rutas declaradas en `routes/web.php`:

| Método/ruta | Acción | Protección |
|---|---|---|
| GET / | welcome | pública |
| GET /dashboard | ProjectController@index | auth:web, AuthenticateSession, verified |
| POST /projects | store | anteriores + policy view del equipo actual |
| GET /projects/sebra-system | vista del sistema | anteriores + full admin,web |
| GET /api/sphere/events | events | web + full admin |
| GET /api/sphere/graph | __invoke | web + full admin |
| POST /api/graph/query | ask | web + full admin + 20/min |
| GET /projects/{project} | show | web + can:view,project |
| GET /projects/{project}/graph | projectGraph | web + can:view,project |
| GET /projects/{project}/events | projectEvents | web + can:view,project |
| POST /projects/{project}/ask | askProject | web + can:view,project + 20/min |
| POST /projects/{project}/notes | storeNote | web + can:view,project |
| POST /projects/{project}/graph | importGraph | web + can:view + can:updateGraph |

**VERIFICADO:** `routes/api.php`, bajo prefijo API, declara GET `/user` con `auth:sanctum`; POST `/sphere/color`, `/sphere/direction`, `/sphere/community` con Sanctum + full admin y 60/min; POST `/graph/questions`, `/graph/ask` con Sanctum + full admin y 20/min. `questions` devuelve recuperación sin síntesis; `ask` añade IA. No se aplican middlewares de abilities Sanctum a estas rutas. Un token autenticado de una cuenta administradora no tiene aquí separación de capacidades por token.

**VERIFICADO:** Fortify y Jetstream añaden rutas desde `vendor/laravel/fortify/routes/routes.php` y `vendor/laravel/jetstream/routes/livewire.php`; su configuración habilita identidad, equipos, invitaciones y borrado de cuenta. API tokens, fotos y términos Jetstream están deshabilitados aunque existan vistas y tests. `bootstrap/app.php` registra además `/up`, aliases Spatie y errores JSON para `api/*`/expectsJson. **PENDIENTE:** inventario exacto de rutas activas mediante `route:list` en un entorno autorizado.

### 7.2 Recuperación y generación

**VERIFICADO:** `askProject` valida una pregunta de hasta 500 caracteres. Primero clasifica por regex:

1. Instrucción de descargar/importar/actualizar documentación New Relic: exige `updateGraph`, despacha job y responde 202.
2. Instrucción de crear/agregar/generar nota o nodo: solicita JSON de título/cuerpo a IA, valida longitudes y crea la nota, sin etapa de aprobación. Deduplica usando hash del título y cuerpo normalizados en `source = ai:<digest>` y unicidad por proyecto.
3. Pregunta normal: construye un grafo del proyecto en directorio temporal con permisos 0700; lo borra en finally. Si no hay nodos responde 422.

**VERIFICADO:** `searchGraph` invoca Symfony Process mediante lista de argumentos, Python en ruta fija y `scripts/graphify_query.py`; timeout 12 segundos. El adaptador observa `_bfs` privado para publicar comunidades por stderr. Se deshabilita el log de consultas Graphify. Los códigos 3/4 representan ambigüedad/sin resultados. Puede reformular hasta cinco términos usando IA y repetir la recuperación. Hay atajos específicos para roles y creación de usuarios en el grafo del sistema.

**VERIFICADO:** las notas añadidas se representan por título, comunidad y ruta sintética `sebra-notes/{id}.md`; no se extraen automáticamente relaciones de sus cuerpos. Para la síntesis, `noteContext` encuentra IDs en la salida textual de Graphify y recupera hasta cuatro notas del mismo proyecto, truncadas a 3.000 caracteres cada una. La búsqueda SQL de la interfaz sí consulta título y cuerpo; es un mecanismo distinto de la recuperación del grafo.

**INFERIDO:** una nota con el término relevante solo en su cuerpo puede aparecer en la búsqueda SQL y no recuperarse adecuadamente por Graphify. Tampoco debe asumirse que una nota importada se convierte en un grafo semántico rico. El parser de contexto depende del formato textual `NODE ... [src=... loc=...]` y del comportamiento privado de Graphify.

**VERIFICADO:** `runQuery` publica started/progress/answering/complete/error en streams Redis por proyecto o globales; envía pregunta, contexto y respuesta del proveedor. No pasa una conversación anterior al modelo. El cliente actual usa una sola pregunta y un panel de respuesta; Redis conserva hasta 60 eventos de consulta por stream y SSE los repite al conectar.

**VERIFICADO:** `resources/views/projects/show.blade.php`, `notes.blade.php` y `dashboard.blade.php` muestran objetivos, búsqueda, notas y grafo. Markdown normal elimina HTML y enlaces inseguros; MDX de New Relic se muestra escapado en pre. `resources/js/graph.js` muestra respuestas con `textContent`, envía CSRF y actualiza el grafo con eventos. Los módulos visuales implementan zoom, giro, selección/comunidades y disposición de recursos WebGL. No se verificó visualmente en navegador.

## 8. Seguridad, permisos y aislamiento

### 8.1 Controles existentes

**VERIFICADO:** las rutas de proyecto exigen membresía del equipo propietario mediante `ProjectPolicy::view`, incluida la descarga del grafo y SSE. El listado usa el equipo actual y autoriza verlo; la creación fija el team desde esa relación, sin aceptar team_id del cliente. El acceso directo a otro equipo al que el usuario sí pertenece es permitido aunque no sea su equipo actual: el equipo actual es contexto de navegación, no una frontera adicional.

**VERIFICADO:** `updateGraph` exige pertenencia y ser owner o admin Jetstream. `TeamPolicy` reserva gestión del equipo y miembros al owner; admin Jetstream no equivale a propietario. Los roles registrados son admin/editor; no existe lector definido. Las rutas de notas y consultas solo requieren view y por ello cualquier miembro puede crear notas, generar contenido y consumir IA, incluso con un rol nulo. No se usan permisos granulares Spatie para estas operaciones.

**VERIFICADO:** los streams se nombran por `Project::streamPrefix()` con ID de proyecto. SSE recarga usuario/proyecto y vuelve a evaluar la policy al conectarse y después de cada XREAD (bloqueo hasta 15 segundos). La revocación no es instantánea, pero hay revalidación explícita. El importador revalida usuario y updateGraph antes de iniciar y entre lotes.

**VERIFICADO:** login y 2FA tienen límites de cinco intentos/minuto por sus claves; passkeys diez. Hay validación de longitudes, upload de grafo limitado, IDs únicos y referencias de enlaces/hiperaristas comprobadas. Symfony Process no concatena la pregunta en un shell. El importador restringe repositorio, tipos de ruta, revisiones SHA, no sigue redirecciones y valida hashes Git de cuerpos descargados.

### 8.2 Riesgos y problemas priorizados

| Prioridad | Evidencia y clasificación | Impacto / siguiente comprobación |
|---|---|---|
| Alta antes de exposición pública | VERIFICADO: `compose.yaml` combina APP_ENV production con APP_DEBUG true | INFERIDO: errores pueden revelar configuración o detalles internos. Loopback reduce exposición directa; revisar reverse proxy y entorno real. |
| Alta | VERIFICADO: view habilita POST notes y generación IA | INFERIDO: añadir un lector sin cambiar policies permitiría escritura y gasto. Separar view/createNote/generate/import. |
| Alta | VERIFICADO: `GraphInference` envía contexto al proveedor global configurado | PROPUESTO: política de datos por espacio, consentimiento y opción local; PENDIENTE: proveedor real y condiciones de datos. |
| Alta funcional | VERIFICADO: passkeys habilitadas, pero User no implementa `Laravel\Passkeys\Contracts\PasskeyUser`; `vendor/laravel/passkeys/src/Actions/StorePasskey.php` lo exige | INFERIDO: registrar passkeys fallará por contrato; no hay UI propia ni pruebas del flujo. Corregir o deshabilitar explícitamente. |
| Media-alta | VERIFICADO: nota IA se guarda directamente | INFERIDO: alucinación o instrucción adversaria entra al corpus sin revisión; establecer draft/review/publish. |
| Media-alta | VERIFICADO: Sanctum + rol sin abilities por endpoint | INFERIDO: token de admin con abilities limitadas puede alcanzar operaciones según rol. Probar y definir política de tokens. |
| Media | VERIFICADO: SSE reproduce preguntas y respuestas recientes a cualquier miembro autorizado del proyecto | INFERIDO: chat compartido implícito; definir si preguntas son privadas o colaborativas, retención y borrado. |
| Media | VERIFICADO: streams de proyecto no tienen TTL de retención; AOF Redis está declarado | INFERIDO: preguntas/respuestas pueden persistir tras borrado SQL del proyecto. Añadir política explícita de purga. |
| Media | VERIFICADO: emailVerification deshabilitada y contrato ausente | No asumir que el middleware verified impone correo confirmado. Decidir política para invitaciones y alta pública. |
| Media | VERIFICADO: enlaces `ProjectNote::sourceUrl()` apuntan a rama develop, no revisión almacenada | INFERIDO: cita navegable puede cambiar respecto al contenido importado; almacenar commit de adquisición además de hash de blob. |
| Media | VERIFICADO: upsert sobreescribe notas importadas y no elimina fuentes retiradas del remoto | INFERIDO: pérdida de histórico y documentos obsoletos. Separar sincronización de versiones editoriales. |
| Media | VERIFICADO: preguntas IA síncronas HTTP, SSE duradero y lectura de grafos completos | INFERIDO: presión sobre workers Apache, RAM, SQLite y coste por usuario; medir concurrencia y fijar cuotas. |
| Media | VERIFICADO: regex interpreta intención en pregunta y dispara acciones | INFERIDO: ambigüedad de UX; separar propuesta y ejecución explícita de acciones conforme crezca el sistema. |
| Media | VERIFICADO: `ProjectGraph::parse` valida estructura, pero conserva atributos adicionales | INFERIDO: archivos importados pueden introducir contexto/instrucciones o metadatos ajenos. Usar whitelist y procedencia; no hay fuga entre tenants demostrada. |
| Media | VERIFICADO: aislamiento depende de policies y relaciones, sin scope global tenant | INFERIDO: nuevas consultas/jobs podrían omitir la frontera. Exigir pruebas negativas en cada superficie nueva. |
| Media | VERIFICADO: dependencia de APIs privadas Graphify y rutas fijas Python | INFERIDO: actualización o despliegue fuera del contenedor rompe recuperación/progreso. Test de contrato con versión fijada. |
| Media | VERIFICADO: grafo global omite componentes recientes | INFERIDO: IA del sistema puede describir arquitectura pasada; incorporar frescura y revisión de cobertura. |
| Media | VERIFICADO: seed de full admin usa identidad fija y promociona una cuenta existente si coincide | PROPUESTO: bootstrap administrativo controlado; verificar identidad antes de promoción y documentar ejecución. |
| Baja-media | VERIFICADO: AGENTS/CLAUDE/README no describen uniformemente el estado | INFERIDO: futuras IAs pueden reinstalar o confundir plantillas con funciones reales. Mantener contexto y procedimientos consistentes. |

**PENDIENTE:** no se probaron explotación de XSS, prompt injection, IDOR, carreras de deduplicación, abuso de cuotas, revocación de sesiones/tokens, restauración de backups ni cascadas reales. No hay evidencia suficiente para afirmar una fuga de datos existente entre equipos. La defensa por prompts de `GraphInference` ayuda a separar contexto e instrucciones, pero no garantiza resistencia a prompt injection.

## 9. Integraciones y procesamiento asíncrono

**VERIFICADO:** `GraphInference::PROVIDERS` admite OpenAI, Anthropic, OpenRouter, DeepSeek, Ollama y Groq. Configuración única global mediante `config/services.php`: `INFERENCE_PROVIDER`, `INFERENCE_MODEL`, `INFERENCE_API_KEY`. Ollama permite clave ausente; los demás la requieren. Hay timeout de conexión 5 segundos, total 35; formatos distintos para Anthropic y Ollama y normalización de errores de autorización, crédito, límites y respuesta vacía. Los modelos por defecto están codificados en el helper; su existencia o disponibilidad actual no fue validada.

**VERIFICADO:** no hay fallback entre proveedores, selección por tenant, registro durable de tokens/costes, embeddings, herramientas arbitrarias delegadas al modelo ni streaming de tokens. SSE transmite fases/resultados de aplicación, no tokens de generación. `searchTerms`, `answer` y `draftNote` son operaciones del mismo helper.

**VERIFICADO:** `ImportNewRelicDocs` consulta commit develop y árbol completo de `newrelic/docs-website`, rechaza árboles truncados, selecciona MD/MDX en `src/content/docs`, descarga por commit con concurrencia 12 y lotes de 24, compara hash Git con la revisión conocida y hace upsert por proyecto/source. Publica progreso cada 240 documentos y al finalizar. Reintentar el pedido omite documentos ya sincronizados. No es crawling web general ni usa credenciales GitHub en este código.

**VERIFICADO:** los eventos TeamCreated/Updated/Deleted y eventos de acciones Jetstream proceden del paquete; no se encontraron listeners, eventos o notificaciones propios de negocio adicionales. No hay tareas programadas propias de ingesta en `routes/console.php`. La mensajería de equipos usa Mail; Postmark/Resend/SES/Slack figuran como configuración estándar en `config/services.php`, sin demostrar integración funcional específica de SeBra con todos esos servicios.

## 10. Pruebas existentes y validación pendiente

**VERIFICADO:** 25 archivos Feature y dos Unit/TestCase según sus ubicaciones (un archivo Unit de ejemplo y `tests/TestCase.php` como base); 58 métodos cuyo nombre empieza por `test_` en el árbol de tests. El data provider de IA expande un método a seis proveedores. No se debe interpretar ese recuento como 58 tests ejecutados ni aprobados.

| Área | Evidencia |
|---|---|
| Identidad y perfil | AuthenticationTest, RegistrationTest, PasswordResetTest, PasswordConfirmationTest, UpdatePasswordTest, ProfileInformationTest, BrowserSessionsTest, TwoFactorAuthenticationSettingsTest, DeleteAccountTest, EmailVerificationTest |
| Equipos | CreateTeamTest, UpdateTeamNameTest, InviteTeamMemberTest, RemoveTeamMemberTest, UpdateTeamMemberRoleTest, LeaveTeamTest, DeleteTeamTest |
| Tokens | CreateApiTokenTest, DeleteApiTokenTest, ApiTokenPermissionsTest |
| Permisos/grafo global | DashboardGraphTest: sesiones, denegaciones sin full admin, vista autorizada y seeder idempotente |
| Proyectos/tenant | TeamProjectsTest: creación, grafos separados, denegación outsider, importación admin/owner y revocación |
| Notas/ingesta | ProjectNotesTest: Markdown seguro, contexto por proyecto, IA deduplicada, permisos del importador, hash/reuso de fuentes y búsqueda aislada |
| Proveedores IA | GraphInferenceTest con HTTP fake y prohibición de solicitudes inesperadas para seis backends |
| Base | Feature/ExampleTest.php y Unit/ExampleTest.php |

Los nombres de clase anteriores se ubican en `tests/Feature/` salvo los indicados.

**VERIFICADO:** hay tests que se omiten si API tokens, verificación de email u otras features están desactivadas; su presencia no demuestra que dichas funciones estén habilitadas. Los tests propios mockean Redis/HTTP/Queue según el escenario. `phpunit.xml` configura SQLite en memoria, caché/sesión array, correo array y cola sync; no cubre por sí solo la topología Docker con Redis y un worker separado. No se encontraron archivos de pruebas Playwright ni workflows de CI en `.github/workflows/` aunque Playwright esté instalado.

**PROPUESTO:** en una sesión con autorización de ejecución, validar primero la suite existente en base aislada; después un smoke test integrado de Graphify 0.9.109 + Redis + SSE + cola y proveedor simulado. Añadir casos negativos para rol lector, abilities de tokens, revocación durante SSE/ingesta, respuestas IA inválidas, fallos/timeouts, separación de contexto de dos proyectos y contaminación de documentos. Para RAG, crear un corpus pequeño con preguntas/respuestas esperadas, citas verificables y preguntas sin evidencia. No ejecutar esas tareas contra datos productivos.

## 11. Historial recuperable y transición desde NVM

**PENDIENTE:** esta conversación no contiene las sesiones originales de desarrollo ni las tareas iniciales de NVM. Git no es legible como repositorio utilizable en el entorno actual. No puede reconstruirse cómo se pasó de NVM a SeBra, qué órdenes se ejecutaron, qué decisiones aceptó el usuario ni qué fallos se resolvieron. No se atribuyen commits ni hechos conversacionales inexistentes. No se inspeccionaron otros chats ni historiales del shell.

**DOCUMENTADO:** `README.md` sigue siendo esencialmente la presentación genérica de Laravel; `AGENTS.md` conserva instrucciones de instalación; `CLAUDE.md` contiene directrices Boost y convenciones. Los artefactos Graphify incluyen nodos sobre prácticas y habilidades; esas recomendaciones no prueban funcionalidad implementada.

**INFERIDO a partir de artefactos, no cronología confirmada:**

1. Base Laravel con Jetstream/Livewire/Fortify y Spatie; migraciones de 2FA, passkeys, tokens y permisos con nombres del 13 de noviembre.
2. Incorporación de Graphify, su informe/bóvedas y visualización de comunidades; existe `graphify-out/2026-11-14/` como archivo anterior.
3. Infraestructura Docker con SQLite, Redis y watcher, consulta Graphify mediante Python e inferencia multibackend.
4. Consolidación de equipos existentes y rol full admin, según migraciones fechadas 14–15 de noviembre.
5. Proyectos, notas, fuentes y job New Relic, visibles en migraciones y tests actuales pero ausentes de la cobertura del grafo inspeccionado.

**PENDIENTE:** los timestamps de archivos y los nombres futuros de migración no establecen fecha real de ejecución ni orden de implementación. Esta secuencia explica capas observables; no sustituye un historial de commits. Las dificultades actuales demostrables son la divergencia documental, el grafo incompleto y la incompatibilidad de passkeys; no deben presentarse como incidentes históricos ya reportados.

## 12. Brechas frente a la visión de SeBra

**DOCUMENTADO:** la visión entregada propone espacios personales/profesionales/colaborativos, múltiples Second Brains, proyectos y documentación, IA local/remota, búsqueda semántica, agentes, consolidación supervisada y un portafolio técnico. Es una dirección de producto, no una declaración del estado implementado.

| Objetivo | Reutilizable VERIFICADO | Brecha VERIFICADA | Evolución PROPUESTA |
|---|---|---|---|
| Espacios | Team y membresías | sin tipos de espacio ni políticas propias | mapear Team a espacio; añadir tipo solo con conducta concreta |
| Múltiples Brains por espacio | proyectos por Team | Brain no existe | decidir unidad de conocimiento antes de introducir jerarquía |
| Creación por prompt | name/objective + draftNote | no plan estructural ni aprobación | generar propuesta de propósito/secciones y confirmar |
| Fuentes documentales | notes/source/revision, importador New Relic | sin catálogo de fuentes, estado ni conectores genéricos | Source, ingestión trazable y políticas por conector |
| Generación documental | helper IA y Markdown | sin borradores, secciones ni versiones | drafts con fuentes y promoción supervisada |
| RAG semántico | recuperación Graphify + contexto | sin chunks/embeddings/búsqueda vectorial | recuperación híbrida con filtros tenant/brain |
| Conversaciones | panel + streams | sin sesiones durables ni memoria | conversaciones/mensajes y referencias recuperadas |
| Edición/consolidación | lector Markdown | sin edición, revisión ni publicación | editor sencillo y versiones inmutables |
| Permisos granulares | Jetstream + Spatie + policies | no permisos por acción/recurso | matriz mínima owner/admin/editor/viewer |
| Agentes | servicios IA y job | sin agentes especializados ni herramientas gobernadas | empezar por jobs acotados con autorización explícita |
| Código/LLM Wiki | archivos/documentos y grafos | sin editor ni módulo Wiki | integrar tras estabilizar fuentes y versionado |
| Portafolio profesional | arquitectura real y tests propios | sin CI/demostración operativa completa | fixture demo, ADRs, evaluación RAG y evidencia de pruebas |

**PROPUESTO:** conservar Laravel, Jetstream, Eloquent, policies, la capa HTTP IA y el visualizador. SQLite puede seguir en desarrollo/MVP de baja concurrencia; decidir almacenamiento vectorial y eventual motor SQL conforme a mediciones y operación. No hay justificación comprobada para reescribir o separar servicios ahora.

## 13. Roadmap incremental de MVP

Todas las fases siguientes son **PROPUESTO**. No se implementaron en esta auditoría. Las estimaciones de esfuerzo dependen de decisiones y equipo; se evita asignar fechas sin base.

### Fase 0 — Fijar contratos y una base reproducible

Definir Team como frontera organizativa, elegir la relación Brain/Project y aprobar matriz de permisos. Resolver debug, passkeys y bootstrap administrativo. Mantener entornos de prueba aislados, revisar borrado/retención y ejecutar la suite existente. Documentar despliegue sin suponer que el arranque es inocuo: actualmente migra.

Criterio de salida: identidad y pertenencia comprobadas; un outsider no lista, consulta ni recibe eventos de otro espacio; comportamiento de tokens definido y entorno reproducible.

### Fase 1 — Crear un Brain mediante prompt y definir estructura (pasos 1 y 2)

Reutilizar dashboard, validación name/objective, Teams y GraphInference. El usuario introduce propósito y restricciones; la IA devuelve un esquema validado (nombre, propósito, secciones), mostrado como propuesta editable antes de persistir. Si se decide que Brain equivale al Project actual, documentar ese contrato; si son conceptos distintos, introducir `second_brains(team_id, ...)` y relacionar proyectos explícitamente. No añadir ambas capas por anticipación.

Criterio de salida: prompt → propuesta revisable → contenedor asociado al espacio correcto; ningún texto de IA crea roles o cambia team_id.

### Fase 2 — Incorporar fuentes (paso 3)

Reutilizar el patrón del importador y ProjectNote; agregar entidad Source con procedencia, revisión, estado, usuario y errores. Empezar con texto/Markdown y el conector New Relic existente. Separar adquisición de publicación; conservar original y revisiones. Añadir archivos/PDF/URL solo con validación de tamaño, permisos, formato y protección de red específica.

Criterio de salida: importación idempotente, reintentable y trazable, con autorización revalidada y estados visibles; citas apuntan a revisión estable.

### Fase 3 — Generar documentos como borradores (paso 4)

Reutilizar `draftNote` y el lector Markdown, pero cambiar el futuro flujo a draft/review/published. Guardar proveedor/modelo, fuentes y autor; validar salida estructurada. Usar jobs para trabajo largo y límites por espacio; no ejecutar herramientas arbitrarias del modelo.

Criterio de salida: generación produce borrador editable con referencias; errores no publican conocimiento ni dejan estado engañoso.

### Fase 4 — Consultar mediante RAG (paso 5)

Crear chunks versionados vinculados a Source/Document y su contenedor; definir embedding/modelo/dimensión y un índice aislado por espacio y Brain. Buscar primero dentro del alcance autorizado, combinar texto/vector y opcionalmente enlaces Graphify, construir contexto con presupuesto y registrar evidencias. El grafo visual continúa siendo una proyección, no una fuente de verdad alternativa.

Criterio de salida: respuestas con citas resolubles a versiones, abstención cuando falta evidencia y pruebas con contenido similar de dos tenants que no se mezcla. Medir recuperación, latencia y coste antes de elegir infraestructura más compleja.

### Fase 5 — Editar y consolidar (paso 6)

Editor Markdown sencillo, versiones inmutables, control de concurrencia y revisión/publicación explícitas. Reindexar solo versiones aprobadas según política. Conservar diferencias entre documento importado, borrador IA y conocimiento consolidado. Historial de conversación durable con retención elegida y referencias a recuperaciones.

Criterio de salida: editar, comparar y restaurar versión; un borrador adversario o descartado no contamina el índice publicado.

### Fase 6 — Completar permisos y evidencia de portafolio (paso 7)

Los controles mínimos empiezan en Fase 0, no se posponen a esta etapa. Completar administración de roles, pruebas por acción y recurso, auditoría de operaciones sensibles y métricas de ingesta/IA. Agregar CI, demo reproducible con datos no sensibles, diagrama, decisiones arquitectónicas y evaluaciones RAG. Solo después extender agentes de investigación y conectores.

Criterio de salida: owner/admin/editor/viewer se comportan conforme a matriz; revocación se verifica en HTTP, colas, SSE y recuperación; una demo explica límites además de capacidades.

## 14. Cinco decisiones arquitectónicas prioritarias

1. **PROPUESTO — Definir qué es un Second Brain.** Resolver si el Project actual será su representación inicial o si el dominio exige Team → Brain → Project. Team sigue como frontera del espacio. Evitar dos entidades con iguales responsabilidades; documentar migración y vocabulario de UI antes de cambiar tablas.
2. **PROPUESTO — Separar autorización global de autorización del espacio.** Mantener full admin para administración del sistema y Jetstream para pertenencia; decidir si los permisos de acciones viven en policies/roles Jetstream o Spatie con alcance explícito. No activar `permission.teams` sin migración, contexto y pruebas. Prohibir que view implique escritura o gasto por accidente.
3. **PROPUESTO — Hacer del contenido versionado y su procedencia la fuente de verdad.** Documentos/fuentes/revisiones en SQL o almacenamiento gestionado; grafos e índices como proyecciones reconstruibles. Separar importación, borrador IA y publicación humana; registrar autor y revisión citada.
4. **PROPUESTO — Adoptar recuperación híbrida con filtros de seguridad previos.** Conservar Graphify para estructura y añadir embeddings solo a contenido autorizado/versionado. Elegir backend por corpus, mediciones y operaciones; probar recuperación y aislamiento antes de permitir agentes o nuevas herramientas.
5. **PROPUESTO — Convertir IA e ingesta en operaciones gobernadas.** Contrato de proveedor, trabajos durables cuando corresponda, cuotas, trazas sin secretos, política de datos/retención y herramientas con permisos explícitos. Separar solicitudes de consulta de comandos que escriben o importan; priorizar operación verificable sobre autonomía del agente.

## 15. Instrucciones de transferencia para la siguiente IA

- **VERIFICADO:** el código actual tiene más funcionalidades que el grafo y el README. Leer primero `routes/web.php`, `routes/api.php`, ambos modelos Project, sus policies, controllers, `app/Support/` y el job; contrastar cualquier respuesta Graphify.
- **VERIFICADO:** el sistema actual usa proyectos como contenedores, roles Jetstream por equipo y Spatie global. No afirmar que hay multi-tenancy Spatie, RAG vectorial, revisión editorial o agentes completos.
- **PENDIENTE:** obtener acceso al historial original para reconstruir NVM y decisiones pasadas; no inventar esa transición.
- **PENDIENTE:** comprobar ejecución del stack, migraciones aplicadas y suite en entorno aislado antes de afirmar disponibilidad o calidad productiva.
- **DOCUMENTADO:** mantener Laravel y la evolución incremental; los requisitos futuros son objetivos, no código existente.
- **DOCUMENTADO:** autorización de esta sesión limitada a inspección y creación de este informe. No ejecutar el roadmap, instalaciones ni migraciones sin una nueva solicitud que lo autorice.

## Apéndice A — Inventario completo Composer bloqueado

**VERIFICADO:** generado leyendo `composer.lock`. Los metadatos instalados de dependencias directas se contrastaron arriba; este inventario completo describe resolución bloqueada y no certifica integridad binaria de vendor.


### Producción

| Paquete | Versión |
|---|---|
| bacon/bacon-qr-code | v3.1.1 |
| brick/math | 1.0.0 |
| carbonphp/carbon-doctrine-types | 3.2.1 |
| dasprid/enum | 1.0.7 |
| dflydev/dot-access-data | v3.0.3 |
| doctrine/deprecations | 1.2.0 |
| doctrine/inflector | 2.1.1 |
| doctrine/lexer | 3.0.3 |
| dragonmantank/cron-expression | v3.6.1 |
| egulias/email-validator | 4.0.4 |
| fruitcake/php-cors | v1.4.0 |
| graham-campbell/result-type | v1.2.0 |
| guzzlehttp/guzzle | 8.5.0 |
| guzzlehttp/promises | 3.1.0 |
| guzzlehttp/psr7 | 3.4.0 |
| guzzlehttp/uri-template | v2.0.1 |
| laravel/fortify | v1.42.0 |
| laravel/framework | v13.42.0 |
| laravel/jetstream | v5.5.3 |
| laravel/passkeys | v0.2.1 |
| laravel/prompts | v0.3.27 |
| laravel/sanctum | v4.4.0 |
| laravel/serializable-closure | v2.2.0 |
| laravel/tinker | v3.1.0 |
| league/commonmark | 2.10.7 |
| league/config | v1.2.0 |
| league/flysystem | 3.36.0 |
| league/flysystem-local | 3.35.3 |
| league/mime-type-detection | 1.17.0 |
| league/uri | 7.10.0 |
| league/uri-interfaces | 7.10.0 |
| livewire/livewire | v3.8.11 |
| mobiledetect/mobiledetectlib | 4.11.0 |
| monolog/monolog | 3.12.1 |
| nesbot/carbon | 3.15.1 |
| nette/schema | v1.4.0 |
| nette/utils | v4.1.6 |
| nikic/php-parser | v5.9.0 |
| nunomaduro/termwind | v2.4.1 |
| paragonie/constant_time_encoding | v3.1.4 |
| phpdocumentor/reflection-common | 2.2.1 |
| phpdocumentor/reflection-docblock | 6.0.3 |
| phpdocumentor/type-resolver | 2.1.0 |
| phpoption/phpoption | 1.10.0 |
| phpstan/phpdoc-parser | 2.4.1 |
| pragmarx/google2fa | v9.1.0 |
| predis/predis | v3.7.1 |
| psr/clock | 1.0.0 |
| psr/container | 2.0.2 |
| psr/event-dispatcher | 1.0.0 |
| psr/http-client | 1.0.3 |
| psr/http-factory | 1.1.0 |
| psr/http-message | 2.0 |
| psr/log | 3.0.2 |
| psr/simple-cache | 3.0.0 |
| psy/psysh | v0.12.24 |
| ramsey/collection | 2.1.1 |
| ramsey/uuid | 4.9.4 |
| spatie/laravel-package-tools | 1.93.3 |
| spatie/laravel-permission | 8.5.0 |
| spomky-labs/cbor-php | 3.4.2 |
| spomky-labs/pki-framework | 1.6.3 |
| symfony/clock | v8.1.9 |
| symfony/console | v8.1.11 |
| symfony/css-selector | v8.1.10 |
| symfony/deprecation-contracts | v3.8.1 |
| symfony/error-handler | v8.1.10 |
| symfony/event-dispatcher | v8.1.5 |
| symfony/event-dispatcher-contracts | v3.8.1 |
| symfony/finder | v8.1.11 |
| symfony/http-foundation | v8.1.11 |
| symfony/http-kernel | v8.1.11 |
| symfony/mailer | v8.1.10 |
| symfony/mime | v8.1.10 |
| symfony/polyfill-ctype | v1.37.0 |
| symfony/polyfill-intl-grapheme | v1.43.3 |
| symfony/polyfill-intl-idn | v1.43.0 |
| symfony/polyfill-intl-normalizer | v1.43.0 |
| symfony/polyfill-mbstring | v1.43.3 |
| symfony/polyfill-php80 | v1.43.0 |
| symfony/polyfill-php81 | v1.38.1 |
| symfony/polyfill-php82 | v1.43.0 |
| symfony/polyfill-php84 | v1.43.0 |
| symfony/polyfill-php85 | v1.43.0 |
| symfony/polyfill-php86 | v1.43.0 |
| symfony/polyfill-time | v1.42.0 |
| symfony/polyfill-uuid | v1.43.0 |
| symfony/process | v8.1.10 |
| symfony/property-access | v8.1.11 |
| symfony/property-info | v8.1.11 |
| symfony/routing | v8.1.11 |
| symfony/serializer | v8.1.11 |
| symfony/service-contracts | v3.8.1 |
| symfony/string | v8.1.10 |
| symfony/translation | v8.1.11 |
| symfony/translation-contracts | v3.8.1 |
| symfony/type-info | v8.1.11 |
| symfony/uid | v8.1.9 |
| symfony/var-dumper | v8.1.10 |
| tijsverkoyen/css-to-inline-styles | v2.4.0 |
| vlucas/phpdotenv | v5.8.0 |
| voku/portable-ascii | 2.1.1 |
| web-auth/cose-lib | 4.8.2 |
| web-auth/webauthn-lib | 5.3.12 |
| webmozart/assert | 2.5.0 |

### Desarrollo

| Paquete | Versión |
|---|---|
| composer/semver | 3.6.0 |
| fakerphp/faker | v1.24.1 |
| filp/whoops | 2.19.3 |
| hamcrest/hamcrest-php | v3.0.0 |
| laravel/agent-detector | v2.0.5 |
| laravel/boost | v2.10.4 |
| laravel/mcp | v1.2.0 |
| laravel/pail | v1.3.0 |
| laravel/pao | v1.1.5 |
| laravel/pint | v1.32.3 |
| laravel/roster | v1.0.0 |
| mockery/mockery | 1.6.15 |
| myclabs/deep-copy | 1.14.0 |
| nunomaduro/collision | v8.9.6 |
| phar-io/manifest | 2.0.4 |
| phar-io/version | 3.2.1 |
| phpunit/php-code-coverage | 12.5.8 |
| phpunit/php-file-iterator | 6.0.2 |
| phpunit/php-invoker | 6.0.0 |
| phpunit/php-text-template | 5.0.1 |
| phpunit/php-timer | 8.0.0 |
| phpunit/phpunit | 12.5.44 |
| sebastian/cli-parser | 4.2.1 |
| sebastian/comparator | 7.1.8 |
| sebastian/complexity | 5.0.0 |
| sebastian/diff | 7.0.1 |
| sebastian/environment | 8.1.2 |
| sebastian/exporter | 7.0.3 |
| sebastian/global-state | 8.0.3 |
| sebastian/lines-of-code | 4.0.1 |
| sebastian/object-enumerator | 7.0.0 |
| sebastian/object-reflector | 5.0.0 |
| sebastian/recursion-context | 7.0.1 |
| sebastian/type | 6.0.4 |
| sebastian/version | 6.0.2 |
| staabm/side-effects-detector | 1.0.5 |
| symfony/yaml | v8.1.11 |
| theseer/tokenizer | 2.0.1 |

## Apéndice B — Resolución transitiva NPM

**VERIFICADO:** `package-lock.json` contiene el inventario completo de 222 entradas de paquetes, variantes opcionales incluidas. La tabla principal recoge todas las dependencias directas y sus versiones instaladas. Para reproducir el árbol, usar el lock existente; no inferir versiones transitivas a partir de rangos de `package.json`. No se ejecutó instalación ni auditoría de vulnerabilidades de terceros.
