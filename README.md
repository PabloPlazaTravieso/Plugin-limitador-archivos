# Plugin limitador de archivos (antivirus_mediapolicy)

Plugin antivirus para Moodle 4.4 que restringe los archivos que se pueden subir, en **cualquier vía de subida** (filepicker, TinyMCE/Atto, tareas, foros, SCORM, app móvil/web services...).

| Tipo | Regla |
|------|-------|
| Vídeo | Rechazado siempre (cualquier formato y tamaño) |
| Audio | Solo MP3 (`audio/mpeg`), máximo 20 MB |
| Resto | Aceptado, máximo 30 MB |

El tipo se detecta sobre el **contenido real** (`finfo`) y también por la extensión (`mimeinfo`). Los tres límites se configuran en los ajustes del plugin.

Cada rechazo genera el evento `\antivirus_mediapolicy\event\file_rejected` (usuario, nombre de archivo, tipo detectado, tamaño y motivo), visible en *Informes → Registros*. El evento se registra en el contexto de la página desde la que se sube (curso, actividad...) o en el de sistema si no hay página (app móvil, servicios web).

Los rechazos se consultan en *Administración del sitio → Informes → Subidas rechazadas* (fecha, usuario, archivo, tipo detectado, tamaño, motivo y ubicación, con descarga en CSV). Los datos salen del registro estándar, así que se conservan mientras se conserven los registros.

Los rechazos **no** envían avisos de "archivo infectado" a los administradores: el plugin no genera ese mensaje (sí lo siguen enviando otros antivirus instalados, como ClamAV).

Si el archivo no se puede leer para analizarlo, se rechaza: el core de Moodle acepta la subida cuando un antivirus devuelve error, así que el plugin no devuelve error nunca.

## Estructura

```
.                                 → raíz del repositorio = <moodle>/lib/antivirus/mediapolicy
├── INSTALACION.md                guía de instalación para administradores
├── version.php
├── settings.php                  ajustes (MIME de audio, MB audio, MB resto)
├── classes/
│   ├── scanner.php               enganche con \core\antivirus\scanner
│   ├── policy.php                reglas puras (testeables)
│   ├── event/file_rejected.php   evento de auditoría
│   ├── table/rejected_uploads.php  tabla del informe
│   └── privacy/provider.php
├── rejected.php                  informe "Subidas rechazadas"
├── lang/en, lang/es
└── tests/                        PHPUnit (policy_test, scanner_test, rejected_uploads_test)
```

## Instalación

Guía completa para administradores (ZIP, activación, ajustes, comprobación): [INSTALACION.md](INSTALACION.md).

Desde el repositorio:

1. Clonar el repositorio en `<moodle>/lib/antivirus/mediapolicy` (o copiar su contenido en esa carpeta).
2. *Administración del sitio → Notificaciones* para instalar.
3. *Administración del sitio → Plugins → Plugins antivirus → Gestionar plugins antivirus* → activar **Política de subida de archivos multimedia**.
4. Revisar los ajustes del plugin.

## Configuración recomendada del sitio

- **Tamaño máximo de subida** (`maxbytes`) a 30 MB y `upload_max_filesize`/`post_max_size` de PHP acordes: el antivirus actúa después de recibir el archivo, así que esto evita subir archivos enormes antes de rechazarlos.
- **Cuarentena antivirus** (`antivirus_enablequarantine`): desactivarla, o cada vídeo rechazado se guardará en cuarentena.
- **Registro estándar** (`logstore_standard`) activado: el informe de subidas rechazadas lee de él (viene activado por defecto).

## Tests

```
vendor/bin/phpunit --testsuite antivirus_mediapolicy_testsuite
# o un archivo concreto
vendor/bin/phpunit lib/antivirus/mediapolicy/tests/policy_test.php
```

Pasar la carpeta `tests/` no ejecuta nada: PHPUnit busca `*Test.php` y Moodle usa `*_test.php`.

## Limitaciones

- Los paquetes ZIP (SCORM, H5P, ZIP descomprimidos en un recurso) se analizan como un único archivo ZIP, así que un vídeo dentro del paquete no se detecta. Por decisión del proyecto se acepta así en todos ellos: el plugin no bloquea un vídeo que vaya comprimido dentro de un paquete. El paquete sí está sujeto al límite general de tamaño.
- No cubre archivos ya existentes, restauraciones de copias de seguridad ni archivos creados por código de servidor.
- `core_files_upload` (servicio web) no llama al antivirus; no está en el servicio de la app móvil.
- Formatos de audio ambiguos (`.ogg`, `.webm`, `.m4a`) se rechazan por el criterio defensivo.

## Verificación

Probado en Moodle 4.4.2: archivos privados, tarea, foro, recurso Archivo, TinyMCE, editor H5P, SCORM, `repository_ajax.php` y `webservice/upload.php` (app móvil), con MP3 con y sin ID3, WAV, OGG, M4A, MP4, WebM, AVI, vídeos y WAV renombrados, y archivos por encima de los límites. El repositorio URL solo descarga imágenes y pasa por el mismo análisis.
