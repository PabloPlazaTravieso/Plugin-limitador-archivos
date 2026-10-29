# Guía de instalación: Política de subida de archivos multimedia

Plugin `antivirus_mediapolicy`, versión 1.0.1, para Moodle 4.4 o superior con PHP 8.1 o superior.

## Qué hace

Se aplica a cualquier subida de archivos de la plataforma: selector de archivos, editor de texto, tareas, foros, recursos, H5P, app móvil, etc.

| Tipo | Regla |
|------|-------|
| Vídeo | Rechazado siempre, de cualquier formato y tamaño |
| Audio | Solo MP3, máximo 20 MB |
| Resto | Aceptado, máximo 30 MB |

- El tipo se detecta por el contenido real del archivo, no solo por la extensión: un vídeo renombrado a `.pdf` se rechaza.
- El usuario ve un mensaje claro, por ejemplo "No se permiten archivos de vídeo (clip.mp4)".
- Los rechazos no envían avisos a los administradores. Se consultan en un informe.
- Las reglas se aplican a todos los usuarios, también a profesores y administradores.

## 1. Instalar

Instalación desde la web, con el archivo `antivirus_mediapolicy-1.0.1.zip`:

1. Ve a *Administración del sitio → Plugins → Instalar plugins* (*Install plugins*).
2. Sube el ZIP. Si Moodle no detecta el tipo, elige **Plugin antivirus**.
3. Pulsa *Instalar plugin desde el archivo ZIP* y sigue los pasos hasta *Actualizar base de datos de Moodle ahora*.

Si la instalación desde la web está desactivada en el sitio (`$CFG->disableupdateautodeploy`), haz esto en su lugar:

1. Descomprime el ZIP en el servidor, dentro de `<moodle>/lib/antivirus/`. Tiene que quedar la carpeta `<moodle>/lib/antivirus/mediapolicy/`.
2. Entra en *Administración del sitio → Notificaciones* para completar la instalación.

## 2. Activar

1. Ve a *Administración del sitio → Plugins → Plugins antivirus → Gestionar plugins antivirus* (*Manage antivirus plugins*).
2. Activa **Política de subida de archivos multimedia**. Para eso, pulsa el icono del ojo.
3. En esa misma página, deja **Activar cuarentena** (*Enable quarantine*) **desactivada**. Si está activada, Moodle guarda una copia de cada archivo rechazado.

Hasta que no se activa, el plugin no hace nada.

## 3. Revisar los ajustes

Los ajustes están en *Administración del sitio → Plugins → Plugins antivirus → Política de subida de archivos multimedia*:

| Ajuste | Valor por defecto |
|--------|-------------------|
| Tipos MIME de audio permitidos | `audio/mpeg` (MP3) |
| Tamaño máximo de audio (MB) | 20 |
| Tamaño máximo del resto de archivos (MB) | 30 |

## 4. Recomendado: límite de subida del sitio

El plugin revisa cada archivo cuando ya se ha subido. Para que no se lleguen a subir archivos enormes que luego se rechazan, conviene limitar también la subida:

- **En Moodle:** *Administración del sitio → Seguridad → Configuración de seguridad del sitio → Tamaño máximo de archivo subido* (`maxbytes`) a **30 MB**.
- **En PHP:** si hay acceso al servidor, ajusta `upload_max_filesize` y `post_max_size` en `php.ini` en consonancia.

Si no se hace, el plugin rechaza igualmente los archivos de más de 30 MB, pero después de subirlos.

## 5. Comprobar que funciona

1. Desde cualquier curso, intenta subir un vídeo, por ejemplo un `.mp4`, en un recurso *Archivo*. Debe salir "No se permiten archivos de vídeo".
2. Sube un PDF pequeño. Debe subirse sin problema.
3. Ve a *Administración del sitio → Informes → Subidas rechazadas*. Debe aparecer el intento del paso 1 con la fecha, el usuario, el archivo, el tipo detectado, el tamaño, el motivo y el curso.

El informe se puede descargar en CSV. Lee los datos de los registros estándar del sitio, que vienen activados por defecto, así que conserva los intentos durante el tiempo que se conserven los registros.

## Limitaciones conocidas

- **Paquetes comprimidos:** SCORM, H5P y ZIP se revisan como un único archivo. Un vídeo dentro del paquete se acepta, por decisión del proyecto. El paquete sí está sujeto al límite de 30 MB.
- **Archivos ya existentes:** no se revisan los archivos que ya estaban en la plataforma, los que entran al restaurar copias de seguridad ni los que crean procesos internos del servidor.
- **Servicio web `core_files_upload`:** no pasa por el antivirus. La app móvil no lo usa; solo aparece en servicios web a medida.
- **Repositorio URL:** solo descarga imágenes, que también pasan por el plugin.

## Actualizar o desinstalar

- **Actualizar:** sube el ZIP de la versión nueva por *Instalar plugins*, o reemplaza la carpeta en el servidor, y entra en *Notificaciones*.
- **Desactivar temporalmente:** desactívalo en *Gestionar plugins antivirus*.
- **Desinstalar:** ve a *Administración del sitio → Plugins → Vista general de plugins*, busca `antivirus_mediapolicy` y pulsa *Desinstalar*.
