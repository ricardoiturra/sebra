# Navegación semántica del grafo 3D

> Estado actual: por solicitud posterior del usuario, se restauró la visualización anterior de la esfera (giro automático, controles manuales y resaltado temporal por comunidades). El frontend ya no aplica enfoque ni selección persistente por consulta y se retiraron sus controles. El contrato backend `selection` y sus helpers quedan conservados, pero la interfaz de la esfera no los consume. Las secciones siguientes describen la implementación previa y sus pruebas históricas.


> Política vigente Docker-First: todo PHP, Composer, Artisan, PHPUnit y Boost se ejecuta exclusivamente dentro de Docker. Los relatos de ejecuciones locales de esta documentación son evidencia histórica, no instrucciones para repetirlas. Para comandos actuales e aislamiento, consultar `README.md` y `AGENTS.md`.


Implementación incremental, 15 de noviembre de 2026. Se mantienen Three.js, Graphify, el intérprete existente y el formulario compartido por teclado y voz. Este documento complementa `SEMANTIC_QUERIES.md` y `VOICE_INTERFACE.md`.

## Flujo y contrato

```text
Texto o micrófono → mismo /ask → interpretación → recorrido Graphify
                                 ├─ respuesta textual + speech_text
                                 └─ selection validada → selección Three.js + foco
```

El frontend no extrae IDs del texto del LLM. El adaptador `scripts/graphify_query.py` observa el resultado real de BFS y emite por stderr un mensaje `SEBRA_SELECTION` con semillas, nodos y pares de aristas. El texto normal y los mensajes `SEBRA_PROGRESS` continúan funcionando.

`GraphifySearch` devuelve ese tercer resultado estructurado. `GraphSelection` lo contrasta con los nodos y enlaces del grafo autorizado de la petición. Una sugerencia del modelo, una etiqueta repetida en la respuesta o un ID ajeno no se convierten en selección. Los pares que no existen en ese grafo se descartan. Las etiquetas de relación, dirección y confianza proceden de los enlaces almacenados, no de la síntesis.

La respuesta JSON de consulta incorpora:

```json
{
  "answer": "Explicación con sus referencias…",
  "speech_text": "Versión oral de la explicación…",
  "selection": {
    "version": 1,
    "status": "matched",
    "mode": "related",
    "node_ids": ["id-real-user", "id-real-team"],
    "seed_ids": ["id-real-user"],
    "display_node_ids": ["id-real-user", "id-real-team"],
    "focus_ids": ["id-real-user"],
    "relations": [
      {
        "id": "edge:hash-estable",
        "source": "id-real-user",
        "target": "id-real-team",
        "relation": "belongs to",
        "confidence": "EXTRACTED",
        "directed": false
      }
    ],
    "truncated": false,
    "message": null
  }
}
```

Los IDs y etiquetas anteriores son ilustrativos. `node_ids` son nodos recuperados verificables; `seed_ids` son las semillas elegidas por Graphify. La lista se limita a 200 nodos y 400 relaciones, manteniendo primero las semillas. `truncated` indica recorte de tamaño. `status=none` o `ambiguous` informa que no hay una selección confirmada; el frontend conserva la anterior. La respuesta escrita explica falta de evidencia o ambigüedad.

Los IDs de relación se calculan de forma determinista a partir de extremos, tipo, confianza y dirección. Los endpoints de datos del grafo añaden `relations` con los mismos metadatos, manteniendo `nodes`, `links` e `hyperedges` anteriores. El cliente comprueba que cada relación recibida coincide con la actual antes de resaltarla. Los enlaces idénticos en todos esos campos comparten identidad semántica; no hay un identificador persistente de fila porque los grafos JSON actuales no tienen ese modelo.

## Proyección de notas agrupadas

El grafo de consulta contiene notas individuales, pero la pantalla agrupa documentación New Relic por sección para evitar miles de puntos adicionales. `ProjectGraph` asigna explícitamente `display_id` a los nodos de notas que genera. `display_node_ids` y `focus_ids` usan esos IDs agrupados, sin que JavaScript tenga que inferirlos por nombres, rutas o comunidades.

Los IDs visuales se generan con la misma regla de colisiones en ambas proyecciones. Los campos `display_id` aportados por grafos importados se eliminan de la proyección de proyecto; solo se añaden los calculados para las notas propias. Las notas Markdown normales conservan su ID individual. Si una nota cambia o se elimina antes de actualizar la pantalla, se filtra contra los IDs visuales realmente disponibles.

## Operaciones de navegación

El contrato `GraphQuery` añade `navigation`, validada por el intérprete. Las coincidencias literales siguen teniendo prioridad; la expansión LLM continúa limitada al vocabulario del grafo. Los modos son:

- **related:** selecciona el subgrafo recuperado y sus relaciones verificadas. Ejemplos: «Muéstrame los usuarios» o «Destaca todo lo relacionado con autenticación».
- **focus:** enfoca solo las semillas confirmadas. Ejemplo: «Enfócate en los permisos».
- **path:** busca un camino entre las dos primeras semillas confirmadas usando exclusivamente las relaciones recuperadas. Se respeta la dirección si el grafo es dirigido. Si no hay camino en ese subgrafo o faltan dos entidades, se indica expresamente y no se inventa una arista.
- **dependencies:** si el grafo declara dirección, presenta las relaciones entrantes a las semillas, con sus tipos reales. Si no declara dirección, se muestran las relaciones recuperadas con una advertencia: no se afirman dependencias direccionales.

El modo orienta la visualización; no convierte automáticamente relaciones como `contains` o `belongs to` en `depends on`. Las preguntas sobre dependencias siguen dependiendo de la calidad y convenciones de las aristas de entrada. En el grafo global desactualizado pueden faltar entidades recientes como Project; una pregunta no las crea.

## Visualización e interacción

- `graph-selection.js` verifica el contrato contra los datos cargados y calcula un encuadre ortográfico acotado. No analiza lenguaje natural.
- `drawGraph` mantiene los buffers existentes, añade un atributo de opacidad por nodo y aumenta el tamaño de los seleccionados. Se atenúan los demás incluso si comparten comunidad.
- Solo los enlaces verificados por el contrato se iluminan. El panel de relaciones muestra nombres del grafo, tipo, confianza y una flecha textual cuando existe dirección. No se añadieron flechas geométricas a las líneas 3D.
- El foco orienta el conjunto y ajusta posición/zoom de cámara de inmediato; no añade una animación que contradiga `prefers-reduced-motion`. El zoom permanece dentro del rango manual existente.
- Una selección semántica congela la evolución física y el giro automático para que el encuadre sea estable. El giro, zoom y teclado manuales siguen disponibles.
- **Restaurar visualización** elimina selección, atenuación y lista de relaciones, y restaura orientación, cámara y zoom. Reanuda el comportamiento original conforme a preferencias de movimiento. No vuelve a importar datos ni reconstruye el proyecto.
- Los eventos temporales de comunidades siguen disponibles fuera de una selección persistente. No sustituyen la selección semántica mientras se espera una consulta o después de confirmarla.
- El formulario conserva el último contrato confirmado en memoria de la página para aplicarlo si el módulo Three.js termina de cargar después de la respuesta.

El render loop no espera al LLM ni a fetch. El resaltado usa atributos de buffers y conjuntos de IDs; no reconstruye geometrías por cada frame. La física no se ejecuta durante una selección persistente. No se añadieron dependencias Composer/NPM ni infraestructura adicional para esta navegación.

## Permisos y voz

Las rutas y policies existentes siguen autorizando el grafo completo antes de interpretarlo o recuperarlo. Las consultas de proyecto no consultan el grafo global. La comprobación adicional de IDs ocurre sobre esa misma instantánea autorizada. No se aceptan rutas de archivos ni IDs enviados por el modelo como autoridad.

Micrófono y teclado completan el mismo campo y envían el mismo formulario. Una respuesta puede actualizar simultáneamente el texto, la voz opcional y la selección; no hay un endpoint de navegación por voz separado ni despacho de acciones de escritura. La selección es local a la pestaña que realizó la consulta; no se propaga como selección persistente a otros usuarios del equipo mediante SSE.

## Pruebas y límites

- PHP `GraphSelectionTest`: filtrado de IDs ajenos/aristas inexistentes, rutas reales, ausencia de caminos, dirección, proyección de notas, modos del intérprete, respuesta HTTP conjunta y denegación a outsiders.
- PHP `GraphifySearchTest` y Python `graphify_query_test.py`: verifican IDs y aristas emitidos por Graphify 0.9.109 real sobre fixtures, además de los contratos de consultas existentes.
- Node `graph-selection.test.mjs`: nodos de una misma comunidad, opacidad, restauración, rechazo de resultados vacíos/relaciones alteradas, buffers conservados, encuadre acotado y giro posterior.
- Chromium `voice.test.mjs`: Three.js real con WebGL software, captura de errores de shader, consulta pendiente mientras aumentan los frames, selección/foco, control manual, ausencia de cambios ante falta de resultados y restauración. Se emula movimiento reducido. El mismo formulario sigue cubierto por las pruebas de voz existentes con proveedores simulados.

Limitaciones: BFS se observa mediante una API privada de Graphify fijada a 0.9.109; una actualización requiere repetir el test de contrato. Los caminos están limitados al subgrafo recuperado y al presupuesto de selección, no prueban ausencia de un camino en todo el corpus. Se seleccionan enlaces binarios; los hiperenlaces se mantienen visibles atenuados, sin un contrato nuevo de selección de hiperrelaciones. Los cambios de datos entre consulta y render pueden invalidar IDs; se filtran y se informa sin deducir sustitutos. El foco es inmediato, no una interpolación cinemática. La simulación existente sigue teniendo su coste original cuando no hay selección. No se evaluó rendimiento sobre corpus masivos ni GPU/micrófono físicos.

Comandos de pruebas, usando los entornos ya preparados:

```sh
# Pruebas PHP: usar el comando Docker aislado del README.
node --test tests/js/graph-selection.test.mjs
node --test tests/js/voice.test.mjs
docker compose exec -T web /opt/graphify/bin/python tests/graphify_query_test.py
node node_modules/vite/bin/vite.js build
```

`GRAPHIFY_PYTHON`, `AUDIO_FFPROBE` y `SEBRA_TEST_FFMPEG` pueden seleccionar ejecutables dentro de Docker. `SEBRA_TEST_PHP` se retiró: el fixture de navegador no admite PHP del host. Las pruebas PHP mantienen SQLite en memoria; las APIs externas están simuladas. La verificación del MVP de audio y la corrección de avisos repetitivos se documentan en `VOICE_INTERFACE.md`.

### Resultado final ejecutado

- **PHPUnit: 130 casos, 123 aprobados, 7 omitidos**, 494 aserciones. Las omisiones corresponden a las features deshabilitadas de API tokens y verificación de email; las integraciones Graphify y multimedia reales sí se ejecutaron.
- **JavaScript/Chromium: 12 pruebas aprobadas**: cuatro de selección y cámara sin GPU y ocho en Chromium, incluida integración Three.js/WebGL y el flujo compartido con voz.
- **Adaptador Python: 3 pruebas aprobadas** con Graphify 0.9.109 real.
- **Vite y Pint: aprobados.** Se usó la lista explícita de archivos PHP porque `--dirty` requiere un repositorio Git utilizable, no disponible en este entorno.
- Las cuatro rutas de audio fueron inspeccionadas con `route:list`; mantienen el control por proyecto o rol global.

No se llamó a proveedores LLM/STT/TTS reales, no se probó hardware de voz ni se inició el contenedor productivo. La compilación conserva el aviso de tamaño del bundle Three.js (~547 kB), cargado de forma independiente del formulario textual y del módulo de audio. No se introdujo infraestructura vectorial ni cambios de esquema.

### Distribución de la superficie (corrección de polos)

La esfera utiliza muestras de área uniforme (espiral de Fibonacci), asignadas por bandas para mantener próximas las comunidades. El movimiento se calcula directamente sobre la superficie esférica, con fuerzas tangenciales y normalización del radio. Se elimina la proyección de alturas limitadas que dejaba casquetes vacíos y concentraba nodos en círculos próximos a los polos. No cambia los identificadores, las relaciones ni los controles manuales.

Validación: `node --test tests/js/graph-sphere.test.mjs tests/js/graph-selection.test.mjs` (6 pruebas aprobadas) y compilación Vite aprobada. Las pruebas comprueban cobertura de ambos polos, distribución por área y estabilidad numérica durante movimiento, repulsión y cambios de proporción. La distribución conserva agrupaciones aproximadas; no representa distancias semánticas exactas.


### Fuerzas entre comunidades

Los enlaces reales generan atracción (más fuerte dentro de una comunidad); los representantes de comunidades diferentes se repelen. El representante es el nodo real con más enlaces de su comunidad, con desempate estable por orden inicial. No se añaden nodos ni enlaces sintéticos. Una separación local y velocidades limitadas evitan el colapso y los saltos; un anclaje suave conserva la distribución inicial. Los cálculos se ejecutan en los pasos de animación existentes y respetan movimiento reducido. Los eventos de comunidad conservan su transición temporal al interior de la esfera.

Pruebas ejecutadas: atracción de nodos enlazados, repulsión entre representantes, radio constante, estabilidad durante cambios de proporción, controles y flujo de voz/texto con proveedores simulados. La intensidad visual queda sujeta a ajuste con grafos de tamaños y densidades diferentes.

Todos los nodos usan la misma escala de tamaño basada en su grado de interconexión; los representantes no reciben aumentos adicionales. Por petición del usuario se retiraron el halo neón y el núcleo brillante.

La opacidad de los nodos depende de su profundidad respecto al centro de la esfera en coordenadas de cámara: 25 % en la mitad trasera y 100 % delante, con transición suave alrededor del centro. Se calcula en el shader, por lo que sigue el giro manual y automático sin actualizar atributos en CPU. Se combina con la atenuación temporal por comunidad.

### Etiquetas al acercar el zoom

A partir de un zoom de 1.6× se muestran los nombres (`label`, con ID como respaldo) de los nodos de la mitad delantera. Las etiquetas siguen las posiciones reales y la rotación, se ocultan al alejar y no interceptan arrastre ni rueda. Se dibujan en una capa Canvas 2D, con un máximo de 70 nombres simultáneos, descartando solapamientos y textos fuera del encuadre. La capa se elimina al desmontar el visualizador. No se infieren nombres a partir de respuestas del LLM.

Pruebas de `graph-labels.test.mjs`: umbral de zoom, ocultación de nodos traseros, cambio al girar y prevención de solapamientos. Limitación: en zonas densas se muestra un subconjunto de nombres para conservar legibilidad; el texto completo puede requerir girar o acercar más.

### Doble clic: detalle verificable

El doble clic cerca de un nodo delantero abre una ventana de detalle con sus atributos, fuentes, vecinos, relaciones directas y relaciones de grupo registradas. La selección usa las posiciones renderizadas, incluso durante el giro o zoom; un clic en espacio vacío no abre nada. El contenido se inserta como texto, sin interpretar HTML del grafo. Se puede cerrar con el botón o Escape; la petición pendiente se cancela y el foco vuelve al visualizador.

Endpoints de lectura `projects.node` y `sphere.node`: reciben el ID exacto como parámetro de consulta (admite barras), aplican las mismas policies/rol que el grafo, limitan peticiones y deshabilitan caché de respuesta. Los datos se recuperan del grafo autorizado actual; un ID inexistente devuelve 404. No leen archivos arbitrarios ni consultan al LLM. El detalle muestra la evidencia presente en el grafo, no el contenido completo de todos los archivos referenciados.

Verificación: PHPUnit en Docker con SQLite en memoria (9 pruebas, 65 aserciones); 8 pruebas Node de selección, etiquetas y picking; 9 pruebas Chromium de voz, controles y ventana de detalle (incluye contenido HTML tratado como texto, cierre y recuperación de foco). Compilación Vite aprobada.

### Vista 2D y pantalla completa

La barra superior derecha incluye **Vista 2D / Vista 3D** y **Maximizar / Restaurar**. La vista inicial sigue siendo 3D. El modo plano reutiliza los mismos nodos, enlaces y tamaños, con posiciones iniciales aleatorias por todo el panel y una simulación de fuerzas independiente: arrastrar desplaza la cámara, rueda y teclas +/- ajustan el zoom; no hay giro automático. Los nombres y el detalle por doble clic funcionan en ambas vistas. La atenuación por profundidad solo se aplica en 3D. Al alternar, cada modo conserva su orientación, posición y zoom mientras permanece montado.

Maximizar usa la API de pantalla completa del navegador, sin recargar ni duplicar el renderizador; el ResizeObserver ajusta el encuadre. Restaurar o Escape vuelve al tamaño normal. El detalle del nodo se monta dentro del visualizador para permanecer disponible en pantalla completa. Si el navegador rechaza la solicitud, se muestra un error recuperable y se conserva la vista normal.

Verificación ejecutada: compilación Vite y 20 pruebas Node/Chromium aprobadas, incluidas geometría plana con IDs estables, etiquetas del plano, alternancia de modos con restauración de orientación/zoom y entrada/salida real de pantalla completa. No se realizaron cambios PHP ni operaciones sobre datos para esta función.


La distribución inicial 2D es aleatoria, sin cuadrícula ni contorno de mapamundi. Los enlaces reales atraen los nodos, la repulsión general y entre representantes separa los grupos, y una fuerza central suave evita dispersión excesiva. No hay anclas que devuelvan los nodos a su posición inicial. Se conserva la distribución al alternar vistas y se adapta al tamaño del panel. La esfera mantiene su propia simulación y distribución originales. Con movimiento reducido, se conserva una vista estática.

Verificación de este cambio: pruebas de cobertura aleatoria del panel, atracción por enlaces, separación y estabilidad al redimensionar, regresiones de esfera y selección, compilación Vite y pruebas Chromium de voz, doble clic, cambio de modo y pantalla completa. La distribución final depende de la conectividad; los nodos desconectados pueden quedar hacia los bordes.

### Agrupación 2D dominada por comunidades

Cada representante (nodo real con mayor grado) atrae a todos los miembros de su comunidad, incluso cuando no tienen un enlace directo al representante. Esa afinidad es exclusivamente una fuerza de layout: no crea relaciones documentales. Los representantes se repelen y transmiten el desplazamiento a sus grupos completos; así no dejan a sus miembros atrás. Los enlaces entre comunidades ejercen una atracción menor para no fusionar los grupos. La repulsión entre nodos ordinarios es local y evita solapamientos, sin competir globalmente con la cohesión comunitaria. Los tamaños continúan dependiendo únicamente del grado de interconexión.

Pruebas ejecutadas: 12 pruebas Node (incluida reducción del radio medio de tres comunidades a menos del 40 % del inicial y separación de sus representantes), 10 pruebas Chromium y compilación Vite. Pendiente: ajuste visual con la densidad del grafo real del usuario. La simulación es independiente de la vista esférica.
