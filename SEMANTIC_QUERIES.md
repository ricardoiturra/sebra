# Interpretación semántica de consultas de SeBra

> Política vigente Docker-First: todo PHP, Composer, Artisan, PHPUnit y Boost se ejecuta exclusivamente dentro de Docker. Los relatos de ejecuciones locales de esta documentación son evidencia histórica, no instrucciones para repetirlas. Para comandos actuales e aislamiento, consultar `README.md` y `AGENTS.md`.


Implementación incremental: 15 de noviembre de 2026. Este documento describe el cambio posterior a `SEBRA_AUDIT.md`; el informe de auditoría conserva su carácter de fotografía histórica. No se añadieron embeddings, índices vectoriales ni dependencias de aplicación.

## Decisiones y flujo

1. **Interpretación independiente.** `app/Support/GraphQueryInterpreter.php` recibe texto y el grafo ya autorizado, sin Request ni acceso a otros proyectos. Devuelve `GraphQuery`, un contrato inmutable reutilizable desde texto, audio transcrito o API. Las intenciones son `query` (consulta), `explore` (exploración), `navigate` (navegación/relaciones) y `modify` (solicitud de modificación). Las heurísticas iniciales reconocen temas breves, preguntas, navegación y órdenes habituales; no son un parser universal del lenguaje.
2. **Recuperación económica.** Primero se buscan etiquetas y rutas literales; después se comparan tokens, incluidos componentes de CamelCase. Se conserva la capitalización exacta: `User` tiene prioridad sobre la clave `user`. No hay diccionario de sinónimos del dominio. Solo si no hay coincidencias locales se invoca `GraphInference::interpret`, que propone conceptos y términos en un JSON validado. Una palabra aislada es exploración válida aunque el proveedor la clasifique como consulta genérica.
3. **Expansión restringida al grafo.** El proveedor recibe hasta 1.200 elementos de vocabulario del grafo de esa consulta. Se validan intención, tipos, listas, longitudes y un máximo de ocho términos. Todo término desconocido se descarta. Las entidades confirmadas se derivan de los nodos reales; los conceptos candidatos no son evidencia. Se priorizan coincidencias exactas y se devuelven hasta veinte entidades candidatas confirmadas. Una etiqueta exacta repetida con la misma capitalización produce aclaración con fuentes, sin síntesis inventada. Una ruta concreta permite desambiguar.
4. **Graphify sigue recuperando.** `app/Support/GraphifySearch.php` encapsula Symfony Process, timeout y callback de progreso. `scripts/graphify_query.py` recibe por stdin únicamente intención, términos y ambigüedad; valida este contrato y llama al comando `query` de Graphify existente. La ruta del grafo la fija el servidor, nunca el proveedor. Se mantienen BFS, los eventos `SEBRA_PROGRESS`, códigos de ambigüedad/sin resultados, aislamiento temporal y limpieza. La invocación CLI anterior del adaptador también sigue funcionando. Se rechazaron opciones como `--graph` en términos para evitar que se interpreten como argumentos.
5. **Síntesis separada.** `GraphInference::answer` recibe pregunta original, interpretación y evidencia recuperada. El prompt indica que un tema aislado debe explicarse, exige distinguir evidencia de inferencias, evita deducir relaciones por mera coexistencia y pide hasta tres caminos de exploración presentes en la recuperación. `ProjectGraph::noteContext` sigue incorporando como máximo cuatro notas del proyecto autorizado. Si no hay resultados o hay ambigüedad, no se llama al generador de respuestas.

Se retiraron del controlador los atajos específicos que traducían preguntas sobre usuarios/roles a términos fijos y el suplemento de archivos del sistema para roles: la búsqueda y sus entidades se basan ahora en el vocabulario del grafo seleccionado. Esto evita confundir evidencia actual de otros archivos con entidades recuperadas del grafo. La calidad sigue limitada por la frescura del grafo.

## Contrato de respuesta

Además de los campos anteriores de consulta, la respuesta JSON incorpora `interpretation`:

```json
{
  "intent": "explore",
  "concepts": ["usuarios", "User", "gestión de cuentas"],
  "terms": ["User"],
  "entities": [
    {"id": "id-real-del-nodo", "label": "User", "source": "app/Models/User.php"}
  ],
  "ambiguous": false
}
```

Es un ejemplo de forma; los conceptos y entidades concretos dependen de la respuesta validada y del grafo autorizado. No se crean entidades por aparecer en este ejemplo. `entities` representa candidatos confirmados en el grafo, no todos los nodos recorridos por BFS ni prueba de relaciones entre ellos.

Casos de uso:

- `User`: exploración literal, sin LLM de interpretación; el endpoint de síntesis puede llamar al generador para explicar evidencia.
- `usuarios`, `users`, `accounts`: si no hay coincidencia local, el LLM puede proponer `User`, siempre que exista en el vocabulario. No se garantiza esa correspondencia para cualquier grafo.
- `¿Cómo se administran los usuarios?`: consulta con expansión cuando hace falta.
- `muéstrame la autenticación`: navegación; términos confirmados según el grafo.
- `¿Qué relación tienen User y Team?`: recuperación de ambos conceptos; únicamente las aristas recuperadas permiten explicar una relación.
- Etiqueta duplicada: lista de opciones con sus rutas y sin respuesta generada.
- Tema ausente: explicación explícita de falta de referencias, sin llamar a síntesis.

## Lectura y acciones explícitas

**Cambio intencional de compatibilidad:** `POST /projects/{project}/ask` es ahora exclusivamente de lectura. Ya no crea notas ni despacha ingestas a partir del texto de una pregunta. Los clientes que usaban esa ruta para escribir deben migrar a:

```text
POST /projects/{project}/actions
Body JSON: {"question": "Creá un nodo sobre …"}
```

Esta nueva ruta conserva los comandos existentes de nota IA y documentación New Relic; no añade herramientas nuevas. Exige la misma sesión, pertenencia al equipo y límite de consultas. La ingesta conserva además `updateGraph` (owner/admin), y la creación de notas conserva los permisos previos de miembros. Una orden desconocida devuelve 422. El intérprete no ejecuta acciones ni habilita permisos.

La pantalla actual mantiene su formulario de consulta apuntando a `/ask`; no se añadió un selector visual de acciones. Los clientes que necesiten escribir deben llamar explícitamente a `/actions`; `POST /notes` y la importación manual de grafos continúan disponibles con sus restricciones existentes.

Las rutas globales conservan el rol `full admin`. `/api/graph/questions` aplica la interpretación (incluida expansión LLM cuando no hay coincidencia local), pero devuelve la recuperación sin síntesis; las rutas `ask/query` que ya generaban respuestas continúan haciéndolo. No se envía el grafo global para interpretar una consulta de proyecto. El servidor construye el grafo temporal del proyecto y lo elimina en `finally`; el grafo se reutiliza dentro de la solicitud para evitar consultar dos veces sus notas.

Las órdenes detectadas desde lectura devuelven 422 y se cierra el estado de progreso con error. Una instrucción disfrazada que no reconozcan las heurísticas tampoco puede escribir: no existe despacho de acciones en el flujo de lectura. Los límites de acceso siguen en las rutas/policies, antes de interpretar o recuperar.

## Archivos afectados

- Nuevos: `app/Support/GraphQuery.php`, `GraphQueryInterpreter.php`, `GraphifySearch.php`.
- Modificados: `app/Support/GraphInference.php`, `app/Http/Controllers/SphereGraphController.php`, `scripts/graphify_query.py`, `routes/web.php`, `config/services.php`.
- `ProjectGraph.php` se inspeccionó y reutilizó sin cambios.
- Pruebas nuevas: `tests/Feature/GraphQueryInterpreterTest.php`, `SemanticGraphQueryTest.php`, `GraphifySearchTest.php` y `tests/graphify_query_test.py`.
- `tests/Feature/ProjectNotesTest.php` usa la nueva ruta para los dos flujos explícitos de escritura; la pregunta informativa sigue comprobándose en `/ask`.

`GRAPHIFY_PYTHON` permite seleccionar el intérprete para probar el adaptador; el valor por defecto continúa siendo `/opt/graphify/bin/python` en Docker. No permite seleccionar la ruta del grafo desde una petición.

## Preparación y aislamiento

Al inicio PHP y Composer no estaban en PATH. Según `AGENTS.md`, se ejecutó el instalador Linux PHP 8.5, quedando PHP 8.5.12 y Composer 2.8.12 en `~/.config/herd-lite/bin`. Para futuras terminales se requiere reiniciar la terminal o cargar su PATH; esta sesión utilizó la ruta explícita.

Boost ya estaba presente. Se ejecutó `composer require laravel/boost --dev --no-update`, manteniendo la restricción existente `^2.10` y sin resolver ni actualizar el lock. `boost:install` no expone el comando en testing; se ejecutó en local con SQLite en memoria y caché array. Actualizó los artefactos/directrices seleccionados para Claude (`CLAUDE.md`, `boost.json`, `.mcp.json` y habilidades locales). `AGENTS.md` se releyó y permanece con el bootstrap inicial. No se reinstalaron dependencias de aplicación ni se ejecutaron migraciones sobre la base productiva.

Las pruebas usan `tests/TestCase.php`, que fuerza SQLite `:memory:` y rechaza otra configuración, además de `phpunit.xml`. Los casos de feature sí ejecutan sus migraciones en esa base efímera mediante RefreshDatabase. Los proveedores externos se simulan con `Http::fake` y se bloquean solicitudes imprevistas. Redis y Queue se simulan en las pruebas HTTP; no se arrancó Docker ni se accedió a registros productivos.

Para comprobar la versión de producción del adaptador, se instaló Graphify 0.9.109 únicamente en `/tmp/sebra-graphify-0.9.109` con sus dependencias Python ya disponibles en el entorno local. Los tests de integración y Python emplearon esa versión y grafos de fixture temporales, sin modificar `graphify-out/graph.json`.

## Validación

Los resultados finales ejecutados se detallan a continuación. No deben confundirse los tests con dobles HTTP con validación de calidad de un modelo real.

### Ejecutado

- Suite completa PHPUnit con Graphify 0.9.109 disponible: **92 casos, 85 aprobados y 7 omitidos**, 343 aserciones en esa ejecución. Las omisiones corresponden a las features deshabilitadas de tokens API y verificación de email, no al nuevo adaptador.
- Tras reforzar la comprobación de aristas: test PHP de integración real del adaptador **1 aprobado, 6 aserciones**. Verifica nodos, fuentes, relación almacenada y comunidades de progreso sin depender de su orden.
- Tests Python del adaptador: **3 aprobados**, incluidos rechazos de payloads, intentos de cambiar comandos/rutas y recuperación real sobre fixture con nodos y relación.
- Pint en todos los archivos PHP afectados: **aprobado**. `--dirty` no puede utilizarse porque este directorio no expone un repositorio Git utilizable; se pasaron los archivos afectados explícitamente.
- `route:list --path=projects --except-vendor`: verificadas las nueve rutas propias de proyectos, incluida `/actions`.
- No se ejecutó compilación frontend porque no se modificaron recursos frontend.

Comandos reproducibles en un entorno que ya tenga PHP, dependencias y Graphify 0.9.109 (no requieren iniciar el stack):

```sh
# Pruebas PHP: usar el comando Docker aislado del README.
docker compose exec -T web /opt/graphify/bin/python tests/graphify_query_test.py
```

En esta sesión se usó un intérprete Python local con `PYTHONPATH=/tmp/sebra-graphify-0.9.109` y `GRAPHIFY_PYTHON` apuntando a ese intérprete. Sin un ejecutable Graphify configurado, únicamente el test PHP de integración se omite indicando cómo habilitarlo; los tests con proveedores simulados siguen funcionando.

### Pendiente y límites

- No se llamó a proveedores LLM reales ni se midieron precisión semántica, coste o latencia con modelos reales. Los tests verifican el contrato, el contexto enviado, la respuesta simulada y las fronteras de seguridad; un prompt de fundamentación no es una garantía formal contra alucinaciones.
- No se verificó la interfaz en navegador ni la operación con Redis/SSE/Apache reales; los eventos de aplicación se simularon en HTTP, mientras el callback de progreso Python sí se probó en proceso real.
- El vocabulario enviado al LLM está limitado a 1.200 entradas; grafos más grandes pueden requerir selección léxica adicional. No se añadieron cachés compartidas ni recuperación vectorial para evitar ampliar esta etapa.
- Las notas siguen siendo nodos recuperables por título y metadatos; este cambio no convierte sus cuerpos en un índice semántico. Se conserva el contexto acotado existente de `ProjectGraph`.
- Las fuentes y aristas solo pueden explicarse si están en la recuperación. El grafo global permanece sin regenerar y puede estar desactualizado respecto al código, como advierte la auditoría.
- La creación/importación por texto desde la pantalla de preguntas cambia de comportamiento deliberadamente: ahora exige el endpoint explícito de acciones. No se implementó una nueva UI de acciones.
