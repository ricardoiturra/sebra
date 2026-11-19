# Voz por turnos en SeBra

> Política vigente Docker-First: todo PHP, Composer, Artisan, PHPUnit y Boost se ejecuta exclusivamente dentro de Docker. Los relatos de ejecuciones locales de esta documentación son evidencia histórica, no instrucciones para repetirlas. Para comandos actuales e aislamiento, consultar `README.md` y `AGENTS.md`.


Implementación: 15 de noviembre de 2026. Complementa `SEMANTIC_QUERIES.md` y la fotografía histórica de `SEBRA_AUDIT.md`. No modifica el framework, Graphify, la recuperación del grafo ni el modelo de datos.

## Uso

La pantalla de cada proyecto y la del grafo del sistema comparten controles opcionales de voz:

1. Leer el aviso del proveedor y marcar **Permitir voz según este aviso**.
2. Pulsar **Micrófono**. Nunca se inicia una grabación al abrir la página.
3. Pulsar **Detener**, o esperar el límite de 30 segundos. **Cancelar grabación** descarta la captura o cancela la transcripción en curso.
4. La transcripción aparece en el campo de consulta y puede corregirse. Por defecto no se envía sola. **Enviar transcripción automáticamente** habilita el envío mediante el mismo formulario de lectura.
5. La respuesta escrita siempre está disponible. **Reproducir / reanudar**, **Pausar lectura** y **Detener lectura** controlan su audio. **Leer respuestas automáticamente** está desactivado inicialmente.

La interfaz informa grabación, transcripción, procesamiento, preparación de voz, reproducción, pausa y errores recuperables. Usa español `es-CL`; el STT de servidor solicita `language=es`. Las preferencias de envío/lectura no se guardan entre sesiones. Desmarcar el consentimiento detiene captura y reproducción.

La versión oral elimina bloques de código, tablas, URL, rutas y líneas de fuentes; conserva identificadores cortos como User y Team cuando son parte de la explicación. No inserta avisos repetitivos como «referencia técnica en pantalla» por cada fragmento. El texto original y sus citas permanecen intactos. Las respuestas orales se limitan a 3.000 caracteres, con un único aviso final si se truncaron.

## Proveedores y alternativas evaluadas

| Opción | Ventajas | Límites y privacidad |
|---|---|---|
| Navegador STT | Sin credenciales ni carga de archivos al backend de SeBra | SpeechRecognition tiene disponibilidad limitada; puede enviar audio al servicio del navegador. No equivale a STT local garantizado. |
| Navegador TTS | Reproduce, pausa, reanuda y detiene usando voces del dispositivo | Las voces y acentos dependen del navegador/SO; algunas voces pueden ser remotas. |
| API compatible STT | Permite usar OpenAI o un servicio local compatible, independientemente del LLM del grafo | Requiere backend configurado, consentimiento, MediaRecorder compatible y ffprobe para validar el archivo. |
| API compatible TTS | Voz uniforme del proveedor y controles mediante HTMLAudio | En este MVP el endpoint debe devolver MP3; las políticas de reproducción automática pueden exigir otro clic. |

**Soportado por código:** `browser`, `compatible` y `disabled`, por separado para STT y TTS. Los valores predeterminados son `browser`. Si falta soporte, no hay cambio silencioso a un proveedor externo: se explica la indisponibilidad y el teclado sigue funcionando. Los servicios locales deben implementar el contrato compatible; no se añadió una integración nativa con un binario Whisper o Piper ni se inició un servicio nuevo.

Fuentes técnicas consultadas:

- [SpeechRecognition en MDN](https://developer.mozilla.org/en-US/docs/Web/API/SpeechRecognition).
- [MediaRecorder.isTypeSupported en MDN](https://developer.mozilla.org/en-US/docs/Web/API/MediaRecorder/isTypeSupported_static).
- [SpeechSynthesis en MDN](https://developer.mozilla.org/en-US/docs/Web/API/SpeechSynthesis).
- [Contrato de transcripción de OpenAI](https://developers.openai.com/api/reference/resources/audio/subresources/transcriptions/methods/create).
- [Contrato de síntesis de OpenAI](https://developers.openai.com/api/reference/resources/audio/subresources/speech/methods/create).

Estas referencias documentan los contratos y límites de las APIs; no implican una llamada exitosa a un proveedor real desde esta sesión.

## Configuración

`config/audio.php` y las variables nuevas en `.env.example`:

```dotenv
# Independientes: browser | compatible | disabled
AUDIO_STT_PROVIDER=browser
AUDIO_TTS_PROVIDER=browser

# Activar compatible únicamente para los servicios que se quieran usar.
AUDIO_STT_URL=https://api.openai.com/v1/audio/transcriptions
AUDIO_STT_KEY=
AUDIO_STT_MODEL=whisper-1
AUDIO_TTS_URL=https://api.openai.com/v1/audio/speech
AUDIO_TTS_KEY=
AUDIO_TTS_MODEL=tts-1
AUDIO_TTS_VOICE=alloy
AUDIO_FFPROBE=ffprobe

AUDIO_STT_DISCLOSURE="El audio se envía al servicio STT configurado en el servidor."
AUDIO_TTS_DISCLOSURE="El texto de la versión oral se envía al servicio TTS configurado en el servidor."
```

Los endpoints son configuración exclusiva del administrador, nunca parámetros de la petición. Para servicios locales se cambian URL/modelo/voz según su implementación; las claves pueden quedar vacías si el servidor local no exige autenticación. Para servicios externos se deben configurar las credenciales correspondientes y el aviso debe identificar claramente el destino real. Las claves STT/TTS son independientes de `INFERENCE_API_KEY` y nunca aparecen en Blade o JavaScript.

El contrato STT utiliza multipart con `file`, `model`, `language=es` y `response_format=json`; espera `{"text":"…"}`. El contrato TTS envía JSON con `model`, `voice`, `input` y `response_format=mp3`; espera contenido `audio/mpeg`. Los defaults de modelos son configurables, no una promesa sobre disponibilidad o calidad en una cuenta concreta.

La imagen `Dockerfile` incorpora el paquete FFmpeg, que contiene ffprobe, y límites PHP de 6 MiB para archivos y 8 MiB para POST. La configuración de aplicación acepta hasta 5 MiB de audio. Fuera de Docker se necesita ffprobe en PATH o `AUDIO_FFPROBE`; si falta, la carga STT falla de forma cerrada con 503. Un proxy inverso también debe admitir el multipart dentro de esos límites. No se arrancó ni reconstruyó el stack productivo en esta sesión; la imagen necesita reconstruirse para incorporar la dependencia del sistema. El arranque existente de SeBra ejecuta sus migraciones habituales; esta funcionalidad no añade migraciones.

## Arquitectura y reutilización

- `SpeechToText` y `TextToSpeech` en `app/Support/Audio/` son contratos separados. `CompatibleAudioProvider` implementa ambos; sus bindings están en `AppServiceProvider` y se pueden sustituir por otros adaptadores.
- `AudioController` solo valida entrada, coordina inspección/proveedor y devuelve transcripción o bytes MP3. No interpreta consultas, recupera conocimiento, invoca Graphify ni crea notas.
- `AudioUpload` verifica contenido y duración real usando un proceso ffprobe acotado. Incluye timestamps de paquetes para WebM de MediaRecorder sin duración en el contenedor.
- `SpokenText` prepara la versión oral. `SphereGraphController` la devuelve en el campo adicional `speech_text`, conservando `answer` sin alteraciones. El endpoint TTS también la aplica para no leer código/rutas enviados directamente.
- `voice-providers.js` implementa el contrato de captura (`start/stop/cancel`) y reproducción (`play/pause/stop`) para navegador y API. Detecta MediaRecorder y sus formatos antes de pedir el micrófono.
- `voice-controls.js` coordina consentimiento, estados y preferencias. El audio grabado queda en memoria del navegador hasta enviarlo o descartarlo.
- `graph-query.js` contiene el flujo textual que antes estaba dentro de `graph.js`. Se inicializa sin depender de WebGL ni del audio. Los eventos `sebra:query-start`, `sebra:query-answer` y `sebra:query-error` permiten acompañar ese flujo sin duplicarlo.
- `resources/views/components/voice-controls.blade.php` es el componente de controles, incorporado en la vista compartida `projects/show.blade.php`.

Las nuevas rutas POST son:

```text
/projects/{project}/audio/transcribe
/projects/{project}/audio/speak
/api/audio/transcribe
/api/audio/speak
```

Son rutas web con sesión y CSRF, aunque las globales lleven el prefijo `/api`. Las de proyecto exigen `can:view,project`; las globales exigen `full admin`. El modelo Project nullable en las firmas del controlador permite el binding implícito de las rutas de proyecto sin exigirlo en las globales.

La transcripción se coloca en el campo existente y se envía exclusivamente a `/ask` o a la consulta del sistema. No se conecta con `/actions`. Incluso si contiene una orden de escritura, el flujo de lectura conserva las restricciones semánticas y no la ejecuta. Los permisos y la recuperación por proyecto son los de SeBra, sin un segundo camino de consultas para la voz.

## Seguridad, límites y retención

- Consentimiento de aplicación y permiso del navegador antes de capturar; contexto seguro HTTPS o localhost para micrófono. No hay escucha permanente ni captura al cargar.
- Máximo de 30 segundos; se toleran hasta 0,5 segundos de cierre del contenedor en la validación. Máximo de 5 MiB, una sola pista de audio y formatos permitidos. Se valida MIME detectado y ffprobe, no solo nombre, Content-Type o duración declarada por el cliente. Los errores del decodificador no se devuelven al usuario.
- MediaRecorder negocia WebM/Opus, WebM, MP4 u Ogg/Opus con `isTypeSupported`; si ninguno está disponible, se mantiene el teclado. No se graba vídeo.
- ffprobe usa protocolo de archivo y una lista restringida de demuxers, con timeout de seis segundos. No interpreta órdenes del usuario ni acepta rutas proporcionadas por la petición. El cliente HTTP usa timeouts, no sigue redirecciones y no muestra cuerpos de error que puedan revelar configuración del proveedor.
- El limitador compartido de audio admite 10 peticiones/minuto y 100/día por usuario, más 500/día por equipo. STT y TTS consumen el mismo presupuesto. Autoriza el proyecto o rol global antes de contabilizar la cuota del equipo, ya que el throttle puede ejecutarse antes del binding y de la policy de la ruta. Un outsider no puede gastar la cuota de otro equipo.
- Los intentos autorizados con entrada inválida también consumen cuota. Los contadores usan el cache store de Laravel: debe ser compartido/persistente en una instalación con varios workers. No son una medición de tokens o facturación del proveedor.
- Las funciones nativas del navegador no pasan por los endpoints STT/TTS del servidor; sus límites/costes externos dependen del navegador. Las consultas al grafo mantienen su rate limiting actual.
- No hay almacenamiento permanente, catálogo ni URL pública de audio. El upload PHP se elimina en `finally`, también ante validación fallida o fallo STT. PHP elimina sus temporales normales cuando una petición se rechaza antes de entrar al controlador.
- TTS devuelve bytes con `Cache-Control: no-store, private` y `nosniff`, sin archivo de salida en disco. El navegador libera pistas, temporizadores, solicitudes y object URLs al cancelar, detener, terminar o salir de la página.
- Cancelar impide que una respuesta tardía complete la transcripción o envíe una pregunta; no puede retirar audio que ya recibió un proveedor externo, que puede terminar su trabajo. La retención de terceros depende de sus condiciones, no del borrado local de SeBra.
- Los endpoints no buscan audio o respuestas de otros proyectos ni usan cachés compartidas de contenido. La ruta del proyecto siempre es autorizada antes de contactar al proveedor.

## Pruebas y verificación

Las pruebas PHP utilizan `tests/TestCase.php`: SQLite en memoria con una comprobación explícita que impide usar otra base. HTTP, Redis/colas cuando corresponden y proveedores externos se simulan. Las migraciones de RefreshDatabase se ejecutan solo en esa base efímera.

- `AudioTest`: autenticación, membresía de equipo, rol global, consentimiento, tamaño/MIME, duración/tipo de pista, proveedor deshabilitado, ffprobe ausente, credenciales solo backend, transcripción vacía/larga, errores/conexión STT/TTS, limpieza de uploads, MP3 inválido, texto oral, cuotas por usuario/equipo y rechazo de consumo de cuota por outsiders.
- Regresión de lectura repetitiva: múltiples rutas y fragmentos técnicos no producen avisos repetidos; los símbolos breves y la explicación permanecen legibles.
- `AudioUploadTest`: ffprobe real sobre WAV válido, audio de 31 segundos, contenido inválido y WebM live sin duración de contenedor. Se omiten explícitamente si no se proporciona el ejecutable necesario.
- `tests/js/voice.test.mjs`: Chromium headless con APIs de micrófono/voz y proveedores simulados. Verifica ausencia de grabación automática, cancelación y resultados tardíos, formato MP4 negociado, liberación de pistas, transcripción editable, envío automático opt-in a lectura, fallos STT/TTS que conservan el teclado y controles de reproducción. Renderiza el componente Blade real en un fixture aislado; no conecta con el stack productivo.
- Capturas del fixture de controles revisadas en escritorio y a 390 px de ancho, sin desbordamiento horizontal. No sustituyen una prueba de micrófono/altavoz físico en el navegador del usuario.

Comandos reproducibles, con los ejecutables/dependencias ya disponibles:

```sh
# Pruebas PHP y validación multimedia: usar el comando Docker aislado del README.
# Node/Chromium pueden ejecutarse en el host; el fixture Blade se renderiza en Docker.
node --test tests/js/voice.test.mjs
node node_modules/vite/bin/vite.js build
```

En esta sesión se extrajeron FFmpeg/ffprobe 7.1.5 y sus bibliotecas faltantes únicamente en `/tmp/sebra-ffmpeg` para probar contenido real, sin instalar paquetes globales ni iniciar contenedores. Graphify 0.9.109 se utilizó desde el entorno temporal de la implementación semántica. Se cumplió el bootstrap de Boost con la restricción existente y sin actualizar Composer lock; no se añadieron paquetes Composer/NPM.

Resultados finales y límites de verificación: se consignan al cierre del documento.

Verificación ejecutada al cerrar el MVP de audio: **123 casos PHPUnit: 116 aprobados, 7 omitidos**, 464 aserciones; **7 pruebas Chromium aprobadas**; compilación Vite y Pint aprobados. Las omisiones son las features deshabilitadas de API tokens y verificación de email. Se usaron proveedores simulados y FFmpeg/ffprobe reales para el contenido multimedia. No se probaron proveedores comerciales reales, micrófono/altavoz físicos, acentos de voces instaladas ni el contenedor productivo. Vite mantiene el aviso del bundle Three.js superior a 500 kB; audio y consultas se cargan independientemente de ese bundle. La navegación semántica posterior puede ampliar estos recuentos.
