# Plugin limitador de archivos (antivirus_mediapolicy)

Plugin antivirus para Moodle 4.4 que restringe los archivos que se pueden subir, en **cualquier vía de subida** (filepicker, TinyMCE/Atto, tareas, foros, SCORM, app móvil/web services...).

| Tipo | Regla |
|------|-------|
| Vídeo | Rechazado siempre (cualquier formato y tamaño) |
| Audio | Solo MP3 (`audio/mpeg`), máximo 20 MB |
| Resto | Aceptado, máximo 30 MB |

El tipo se detecta sobre el **contenido real** (`finfo`) y también por la extensión (`mimeinfo`). Los tres límites se configuran en los ajustes del plugin.

Cada rechazo genera el evento `\antivirus_mediapolicy\event\file_rejected` (usuario, nombre de archivo, tipo detectado, tamaño y motivo), visible en *Informes → Registros*. El evento se registra en el contexto de la página desde la que se sube (curso, actividad...) o en el de sistema si no hay página (app móvil, servicios web).

Si el archivo no se puede leer para analizarlo, se rechaza: el core de Moodle acepta la subida cuando un antivirus devuelve error, así que el plugin no devuelve error nunca.

## Estructura

```
.                                 → raíz del repositorio = <moodle>/lib/antivirus/mediapolicy
├── version.php
├── settings.php                  ajustes (MIME de audio, MB audio, MB resto)
├── classes/
│   ├── scanner.php               enganche con \core\antivirus\scanner
│   ├── policy.php                reglas puras (testeables)
│   ├── event/file_rejected.php   evento de auditoría
│   └── privacy/provider.php
├── lang/en, lang/es
└── tests/                        PHPUnit (policy_test, scanner_test)
```

## Instalación

1. Clonar el repositorio en `<moodle>/lib/antivirus/mediapolicy` (o copiar su contenido en esa carpeta).
2. *Administración del sitio → Notificaciones* para instalar.
3. *Administración del sitio → Plugins → Plugins antivirus → Gestionar plugins antivirus* → activar **Política de subida de archivos multimedia**.
4. Revisar los ajustes del plugin.

## Configuración recomendada del sitio

- **Tamaño máximo de subida** (`maxbytes`) a 30 MB y `upload_max_filesize`/`post_max_size` de PHP acordes: el antivirus actúa después de recibir el archivo, así que esto evita subir archivos enormes antes de rechazarlos.
- **Cuarentena antivirus** (`antivirus_enablequarantine`): desactivarla, o cada vídeo rechazado se guardará en cuarentena.
- **Notificaciones a administradores**: el core avisa a los admins en cada rechazo ("archivo infectado"). Desactivar ese proveedor en las preferencias de mensajes si resulta ruidoso.

## Tests

```
vendor/bin/phpunit --testsuite antivirus_mediapolicy_testsuite
# o un archivo concreto
vendor/bin/phpunit lib/antivirus/mediapolicy/tests/policy_test.php
```

Pasar la carpeta `tests/` no ejecuta nada: PHPUnit busca `*Test.php` y Moodle usa `*_test.php`.

## Pendiente de verificar

- Que `finfo` del servidor detecta como `audio/mpeg` los MP3 reales (algunos sin ID3 pueden salir como `application/octet-stream`).
- Formatos ambiguos (`.ogg`, `.webm`, `.m4a`): con el criterio defensivo se rechazan.
- Probar manualmente cada vía de subida: tarea, foro, SCORM, recurso archivo, TinyMCE (arrastrar e insertar), repositorio URL y `webservice/upload.php`.
- No cubre archivos ya existentes, restauraciones de copias de seguridad ni archivos creados por código de servidor.
- Los paquetes ZIP (SCORM, H5P, ZIP descomprimidos en un recurso) se analizan como un único archivo ZIP, así que un vídeo dentro del paquete no se detecta. En los paquetes SCORM se acepta así por decisión del proyecto.
