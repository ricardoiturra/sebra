# SeBra — Auditoría posterior a la implementación

Fecha: 15 de noviembre de 2026, America/Santiago. Este archivo no existía al comenzar esta tarea; se crea como complemento de `SEBRA_AUDIT.md` y de los informes de consultas, voz y navegación.

## Docker-First y limpieza del entorno anfitrión

### Estado de cumplimiento y autorización

**VERIFICADO:** la política vigente exige que todo PHP, Composer, Artisan, PHPUnit, Pint, Boost, migraciones, seeders, workers y extensiones se ejecute exclusivamente dentro de Docker. No se ejecutó PHP/Composer del host durante esta auditoría. Las ejecuciones locales de turnos anteriores son evidencia histórica y una desviación del estado ahora requerido; no son una autorización para repetirlas.

**VERIFICADO:** Codex ejecutó anteriormente `php.new/install/linux/8.5` siguiendo el bootstrap entonces presente en AGENTS.md. Los resultados de esa sesión, las fechas de creación y los archivos del instalador permiten atribuir a esa ejecución los seis archivos de Herd Lite. El bootstrap antiguo fue sustituido por la política Docker-First. No se atribuyen a Codex herramientas solo por estar instaladas.

**DOCUMENTADO:** el usuario autorizó expresamente la limpieza mediante el mensaje «autorizo», después del aviso de que seguía pendiente. Antes de ejecutarla se presenta aquí el inventario, impacto y comando completo. El alcance autorizado se limita a los seis archivos verificados y los dos exports del instalador; no incluye caches compartidas, proyecto, vendor, volúmenes, imágenes ni herramientas externas. La ejecución y sus comprobaciones se registran al final de esta sección.

### Inventario del host

Host Debian GNU/Linux 13 (trixie), usuario rick. Las comprobaciones de host fueron lecturas de metadatos, paquetes, archivos de configuración y procesos; no se ejecutaron binarios PHP locales.

| Componente | Versión/evidencia | Instalación y origen | Clasificación |
|---|---|---|---|
| `~/.config/herd-lite/bin/php` | PHP 8.5.12, Zend 4.5.12, según ejecución registrada anterior a la política; ELF estático de 98.662.704 bytes | Script php.new, creado 15/11/2026 04:56 America/Santiago | VERIFICADO: introducido por Codex para SeBra; candidato autorizado |
| `…/composer` | Composer 2.8.12 según ejecución histórica; PHAR de 3.125.332 bytes | Mismo instalador y fecha | VERIFICADO: candidato autorizado |
| `…/laravel` | Laravel Installer 5.33.0, leído estáticamente de `bin/laravel` dentro del PHAR, sin ejecutarlo; 7.816.851 bytes | Mismo instalador y fecha | VERIFICADO: candidato autorizado |
| `…/php.ini` | 126 bytes; configura exclusivamente certificados del mismo directorio y pcre.jit | Mismo instalador | VERIFICADO: soporte exclusivo de esa instalación; candidato autorizado |
| `…/cacert.pem` | 200.116 bytes; bundle de certificados apuntado por ese php.ini | Mismo instalador | VERIFICADO: candidato autorizado; no es el almacén CA del sistema |
| `…/uninstall_herd_lite` | 153 bytes; contiene una eliminación recursiva del directorio | Mismo instalador | VERIFICADO: se eliminará como archivo, no se ejecutará |
| Dos exports de `~/.bashrc` | PATH y PHP_INI_SCAN_DIR apuntan a Herd Lite; líneas 124/125 al inspeccionar | Evidencia directa del script y del archivo | VERIFICADO: retirar solo esas líneas, con copia previa |
| `~/.cache/composer`, `~/.config/composer`, `~/.local/share/composer` | ctime 15/11/2026 04:56:58 | Coincide con las ejecuciones Composer, pero son rutas potencialmente compartidas | INFERIDO: relacionadas con la sesión; conservar, exclusividad no demostrada |
| Docker CLI/daemon/Compose/Buildx | Paquetes Debian: docker-cli y docker.io 26.1.5+dfsg1-9+deb13u1; docker-compose 2.26.1-4; docker-buildx 0.13.1+ds1-3 | Existían antes de la instalación local documentada; no se determinó su instalador humano | Conservar; infraestructura necesaria |
| Node, Graphify y Playwright del host | Disponibles antes del instalador PHP de esta sesión; no se vuelve a atribuir su origen | Origen de instalación no auditado exhaustivamente | Conservar; herramientas externas permitidas, sin PHP local |
| Script `/tmp/sebra-php-install.sh` | Fuente del instalador descargado, 5.080 bytes | Introducido por Codex en la sesión; evidencia estática, no es un runtime instalado | Conservado como artefacto temporal de auditoría; no ejecutarlo |
| FFmpeg/Graphify y utilidades temporales de pruebas en `/tmp` | Extraídas/instaladas en turnos anteriores para pruebas multimedia/Graphify | Evidencia de la sesión; no son paquetes PHP ni una instalación APT del sistema | Fuera de esta limpieza, conservar |

Los metadatos internos del PHAR Laravel indican raíz `dev-main` con referencia `39631d3b11d905e22698e0493c9fb83f7b12958f`; la versión visible 5.33.0 proviene del entrypoint del PHAR. No se confundieron esos metadatos con una nueva ejecución del instalador.

`dpkg-query` no mostró paquetes PHP/Composer/Laravel instalados. No aparecieron instalaciones en las ubicaciones estándar `/usr/bin/php*`, `/usr/local/bin/php*` o sus equivalentes Composer del host. No se encontraron gestores brew/snap/flatpak/phpenv/phpbrew/mise/asdf en PATH. Esto describe el ámbito inspeccionado, no prueba ausencia de herramientas ocultas en todo el sistema. Los logs APT legibles no aportaron una instalación PHP/Composer atribuible a esta sesión; el instalador php.new no usa APT para esos seis archivos.

### Uso compartido y riesgos

**VERIFICADO:** en `/home/rick/Proyectos` solo apareció el manifiesto Composer de SeBra dentro de la profundidad inspeccionada. Se revisaron también manifiestos accesibles en `/var/www`, `/srv` y `/opt`, exports de shell, referencias en configuraciones seleccionadas de servicios/systemd/cron/editor y symlinks en los directorios de ejecutables habituales. No se encontraron referencias activas a Herd Lite ajenas a SeBra en ese ámbito; las referencias documentales del proyecto son históricas. No se encontraron symlinks a la instalación en los directorios de ejecutables inspeccionados.

**VERIFICADO:** no se observó un proceso accesible ejecutando el PHP de Herd Lite. El proceso PHP del worker usa `/usr/local/bin/php` dentro de un cgroup Docker, con cwd `/var/www/html`; no depende del ejecutable del host. Apache y los cuatro contenedores de SeBra estaban activos. No se inspeccionaron argumentos sensibles ni variables de entorno completas de procesos.

**PENDIENTE/limitación:** no es posible garantizar ausencia de uso desde todos los directorios privados de otros usuarios, archivos no legibles, proyectos fuera del ámbito o terminales inactivas. Esa incertidumbre se declara; no se utiliza para eliminar caches compartidas o componentes de origen incierto. Los seis archivos concretos tienen evidencia positiva de creación para SeBra y no requieren desinstalar bibliotecas del sistema: PHP es estático y las dependencias PHAR están embebidas.

Impacto de la limpieza: deja de estar disponible PHP/Composer/Laravel local para quien estuviera usando esa ruta; los contenedores no dependen de ella. No se elimina `/etc/ssl`, ningún paquete APT ni bibliotecas compartidas. Las terminales ya abiertas pueden conservar variables exportadas; abrir una sesión nueva y limpiar el cache de comandos con `hash -r` si corresponde. La copia de `.bashrc` tendrá permisos 0600 y no debe publicarse, porque conserva el contenido previo del perfil.

### Comandos exactos de limpieza

**Autorizados, con precondiciones.** El script aborta si cambia un hash, aparece un archivo adicional, el perfil es un symlink, falta/se duplica un export o un proceso accesible usa Herd Lite. No ejecuta `rm -rf`, `autoremove`, `prune`, el desinstalador de Herd ni PHP local. Elimina cada archivo por nombre y usa rmdir solo en directorios vacíos. No es un limpiador genérico.

Comando preparado:

```sh
python3 /tmp/sebra-clean-host-php.py
```

Contenido exacto revisado antes de ejecutar:

```python
from pathlib import Path
import hashlib
import os

root = Path('/home/rick/.config/herd-lite/bin')
expected = {
    'php': 'cbd898bfff1a1ca78567aee9fdbb6306df550afe7c840dbe95e5a383175a9ef5',
    'composer': 'f446ea719708bb85fcbf4ef18def5d0515f1f9b4d703f6d820c9c1656e10a2f2',
    'laravel': 'fcd06543c4a1525077f18946916474b52941652ad535ddc1144c554237ac9d16',
    'php.ini': 'c229ba0bd9d77aaf7b64170c5cff72100dbd0948720693c5f2937fdb550052f7',
    'cacert.pem': '427c064216761a1e72a8b30f0d954d08cb6e4435a82091fb865afd20c8497285',
    'uninstall_herd_lite': 'eca229e0a49b03c95a250f3ec560df587a41beb33fa883abd5defb74c462bffa',
}
if root.resolve() != root or {p.name for p in root.iterdir()} != set(expected):
    raise SystemExit('ABORT: installation directory changed or contains additional files.')
for name, digest in expected.items():
    path = root / name
    if path.is_symlink() or not path.is_file() or hashlib.sha256(path.read_bytes()).hexdigest() != digest:
        raise SystemExit('ABORT: unexpected file or changed hash: ' + name)
for process in Path('/proc').iterdir():
    if not process.name.isdigit():
        continue
    try:
        executable = os.readlink(process / 'exe')
    except OSError:
        continue
    if executable.startswith(str(root) + '/'):
        raise SystemExit('ABORT: a process is still using Herd Lite: ' + process.name)

profile = Path('/home/rick/.bashrc')
backup = Path('/home/rick/.bashrc.sebra-before-cleanup-20261115')
if profile.is_symlink() or backup.exists():
    raise SystemExit('ABORT: profile is a symlink or the backup already exists.')
original = profile.read_text(encoding='utf-8')
lines = [
    'export PATH="/home/rick/.config/herd-lite/bin:$PATH"',
    'export PHP_INI_SCAN_DIR="/home/rick/.config/herd-lite/bin:$PHP_INI_SCAN_DIR"',
]
for line in lines:
    if original.splitlines().count(line) != 1:
        raise SystemExit('ABORT: expected shell export is missing or duplicated.')
updated = ''.join(line for line in original.splitlines(keepends=True) if line.rstrip('\r\n') not in lines)
with os.fdopen(os.open(backup, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600), 'w', encoding='utf-8') as copy:
    copy.write(original)
profile.write_text(updated, encoding='utf-8')

# Explicit whitelist: no recursive deletion and no package-manager cleanup.
(root / 'php').unlink()
(root / 'composer').unlink()
(root / 'laravel').unlink()
(root / 'php.ini').unlink()
(root / 'cacert.pem').unlink()
(root / 'uninstall_herd_lite').unlink()
root.rmdir()
if not any(root.parent.iterdir()):
    root.parent.rmdir()
print('Removed six verified Herd Lite files and two shell exports.')
print('Shell backup: ' + str(backup))
```

### Verificación Docker realizada sin cambiar servicios

La sesión efectiva inicialmente no tenía el grupo docker, aunque `getent group docker` ya incluía a rick. `docker compose ps` fallaba por permisos; el daemon estaba activo. `sudo -n` requería contraseña y no se modificó sudo. Se accedió mediante `sg docker -c '<comando de lectura>'`, activando una membresía ya existente solo para ese proceso, sin chmod del socket ni cambios de grupos.

| Comprobación | Resultado ejecutado |
|---|---|
| Servicios reales | web, redis, project-import-worker, graph-watcher |
| Contenedores | Los cuatro activos; web y Redis healthy al inspeccionar |
| Imagen web activa | `sha256:37533480b965c82e7228c29bfff8326218f1f2934283d4227dfba3903b4706a2`, creada 14/11/2026 22:32:36 -03:00 |
| PHP en web | 8.5.12, build oficial Docker; distinto binario al del host |
| Composer en web | 2.11.2 |
| Laravel | `artisan --version`: 13.42.0 |
| Plataforma Composer | `composer --no-plugins check-platform-reqs`: todas las comprobaciones instaladas satisfactorias |
| Extensiones | PDO/pdo_sqlite/sqlite3, mbstring, dom, fileinfo, curl, openssl, sodium, xml/xmlreader/xmlwriter, json, tokenizer y demás módulos enumerados presentes |
| Vendor instalado | Jetstream 5.5.3, Livewire 3.8.11, Boost 2.10.4, PHPUnit 12.5.44, Predis 3.7.1, Spatie Permission 8.5.0 |
| SQLite | Web y worker abrieron la base configurada exclusivamente con SQLITE3_OPEN_READONLY y consultaron sqlite_version(): 3.40.1; sin leer registros ni ejecutar migraciones |
| Redis | redis-cli PING: PONG; conexión desde web y worker: +PONG; DNS del servicio resuelve |
| Graphify | 0.9.109 en `/opt/graphify`; comprobado desde Python del contenedor |
| Worker | Proceso `php artisan queue:work project-imports --sleep=2 --timeout=7200` activo dentro del contenedor |
| Watcher | sh + inotifywait activos; código montado read-only |
| Montajes | Web/worker comparten `laravel-sqlite_app` en `/var/lib/sebra`; código por bind mount; no depende de Herd Lite |
| Boost CLI | Comandos `boost:*` visibles con APP_ENV=local solo para esa ejecución CLI; no se lanzó MCP ni boost:install |
| Audio backend | FFmpeg/ffprobe AUSENTES de la imagen activa; el Dockerfile actual ya los declara, falta reconstrucción autorizada |
| Node/NPM | Ausentes de la imagen PHP; pueden ejecutarse en host para tareas sin PHP local |
| Scheduler | No hay servicio ni tareas propias programadas; no se introdujo uno |

No se ejecutaron PHP tests, renderizados Blade, instalaciones, migraciones, seeders, reinicios, rebuilds ni cambios de volúmenes dentro de los servicios productivos como parte de esta auditoría. Los comandos de PHP ejecutados dentro de Docker fueron de lectura/versiones/plataforma y conexiones de verificación; el entorno APP_ENV de un exec no cambia el daemon web.

### Carencias y propuestas

1. Reconstruir la imagen desde el Dockerfile actual en una tarea de mantenimiento autorizada para incorporar ffprobe y los límites de upload. No instalarlo manualmente en un contenedor productivo ni volver al ejecutable del host.
2. Mantener Node/Vite en el host para frontend, o proponer una etapa Node de build si se decide contenerizar también JavaScript. No ejecutar el script `composer run setup` actual en la imagen PHP: incluye NPM y migraciones.
3. Preparar una instancia de desarrollo con los servicios actuales y volúmenes propios si se necesita aislamiento completo de pruebas. `compose run web` sin overrides hereda volúmenes productivos y no es suficiente aislamiento.
4. APP_DEBUG=true junto a APP_ENV=production sigue presente en Compose. Se documenta como riesgo ya conocido; no se cambió la configuración operativa en esta auditoría.

### Cambios permanentes en el repositorio

- `AGENTS.md`: sustituido el bootstrap que instalaba PHP en el host por la prohibición explícita y comandos Docker reales; instrucciones de aislamiento, diagnóstico y autorización de limpieza.
- `CLAUDE.md`: política prioritaria fuera del bloque generado, ejemplos PHP/Composer/Pint/PHPUnit con Docker; regenerar Boost no debe borrar la política.
- `README.md`: procedimientos reales, límites de imagen activa, tests, Boost y protección de datos; ya no es el README genérico del skeleton Laravel.
- `.mcp.json`: laravel-boost pasa de `command: php` a Docker exec de web. El proceso CLI usa APP_ENV=local, SQLite :memory:, caché/sesión array, logs stderr y APP_CONFIG_CACHE=/tmp/sebra-boost-config-disabled.php; no invoca PHP local ni altera el entorno del servidor web. No se inició durante la auditoría.
- `tests/js/voice.test.mjs`: render Blade por Docker, con DB en memoria y vistas temporales únicas; elimina la opción de ejecutar un PHP del host. Se admite un HTML ya renderizado mediante SEBRA_VOICE_FIXTURE_HTML para pruebas exclusivamente JavaScript sin ejecutar PHP o tocar contenedores.
- Los documentos de consultas, voz y grafo indican la política vigente y distinguen las ejecuciones locales históricas de los comandos permitidos ahora.

Se verificó sintaxis JavaScript y estructura JSON sin ejecutar PHP local. La ruta nueva del fixture que lanza Docker queda pendiente de ejecución conductual en una sesión de pruebas autorizada porque escribe temporales dentro del contenedor; no se ejercitó contra producción durante la auditoría.

### Solicitud posterior: restauración de la esfera

El usuario pidió volver al comportamiento anterior. Se restauraron giro automático, controles manuales y resaltado temporal de comunidades en el frontend, sin enfoque de cámara ni selección persistente por consulta; se retiraron esos controles de la pantalla. Se conservaron voz, consultas, backend del contrato de selección y helpers independientes. No se tocaron datos, permisos ni lógica PHP de consultas para esta restauración.

La validación de frontend usa Node/Chromium del host y un fixture HTML existente, sin arrancar PHP ni escribir en contenedores. La compilación de assets es una modificación autorizada del proyecto para restaurar la esfera, separada de la inspección Docker de lectura.

### Ejecución y comprobaciones finales

**EJECUTADO, con autorización del usuario:** `python3 /tmp/sebra-clean-host-php.py` terminó con código 0. Se eliminaron los seis archivos verificados, el directorio bin vacío y su directorio Herd Lite vacío. Se retiraron únicamente los dos exports identificados.

Comprobaciones posteriores:

- `~/.config/herd-lite` ya no existe. PHP, Composer y Laravel Installer no se resuelven en el PATH de la sesión auditada; no se ejecutó ninguno en el host para comprobarlo.
- `~/.bashrc` coincide exactamente con la copia previa menos esos dos exports. Backup conservado: `/home/rick/.bashrc.sebra-before-cleanup-20261115`, modo **0600**.
- Se conservaron `vendor/composer/installed.json`, `composer.lock`, `node_modules` y las tres rutas compartidas de Composer. No hubo desinstalación APT ni eliminación recursiva.
- Los cuatro servicios Docker siguen activos; web y Redis healthy. Tras limpiar el host se volvió a comprobar, solo dentro de web, PHP **8.5.12** y Composer **2.11.2**. Redis devolvió PONG. No se reiniciaron servicios ni se eliminaron/recrearon imágenes, volúmenes o bases.
- Las instrucciones Docker-First, MCP y fixtures quedaron actualizadas. Se comprobó sintaxis JavaScript y JSON sin PHP local. La modalidad de fixtures que inicia Docker permanece pendiente de validación conductual; no se ejecutó contra el contenedor productivo.
- Restauración de esfera: **4 tests Node y 8 Chromium aprobados** con fixture HTML existente, sin ejecutar PHP. Vite compiló correctamente; el bundle del visualizador volvió a `graph-view-CL-F32fu.js`, el mismo hash observado antes de incorporar selección/foco semánticos. Continúa el aviso previo de tamaño del bundle (~542 kB).

**Cumplimiento actual:** runtime y herramientas PHP usados en esta auditoría exclusivamente en Docker; instalación local introducida para SeBra retirada; documentación y punto de entrada MCP corregidos. No se ejecutaron pruebas PHP ni migraciones en producción. La incorporación de ffprobe a la imagen activa sigue pendiente de reconstrucción autorizada desde el Dockerfile existente. La búsqueda de otros usos del host se limita al ámbito y permisos descritos; no se eliminaron componentes inciertos.


Nota de verificación posterior al implementar el fondo: se detectó que Laravel intenta incluir `/dev/null` si se usa como APP_CONFIG_CACHE. Se corrigió el MCP a una ruta de configuración inexistente y el fixture a una ruta única dentro de su directorio temporal. Los valores vigentes de las instrucciones reflejan esa corrección; no se ejecutó PHP en el host.
