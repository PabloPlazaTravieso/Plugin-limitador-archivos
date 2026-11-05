<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Spanish strings.
 *
 * @package    antivirus_mediapolicy
 * @copyright  2026 Awakelab
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allowedaudioformats'] = 'Formatos de audio permitidos';
$string['allowedaudioformats_desc'] = 'Marca los formatos de audio que se pueden subir. Se comprueba el contenido real del archivo, no solo la extensión. Si no marcas ninguno, no se admite ningún audio. Un vídeo nunca se acepta como audio, aunque tenga una de estas extensiones.';
$string['allowvideo'] = 'Permitir archivos de vídeo';
$string['allowvideo_desc'] = 'Si no está marcada, se rechaza cualquier archivo de vídeo, de cualquier formato y tamaño, aunque se le cambie la extensión. Si se marca, los vídeos se aceptan con el límite de tamaño del resto de archivos.';
$string['allowvideo_label'] = 'Sí, permitir subir vídeos';
$string['audiomaxmb'] = 'Tamaño máximo de audio (MB)';
$string['audiomaxmb_desc'] = 'Se rechazan los archivos de audio que superen este tamaño.';
$string['col_contentmime'] = 'Tipo detectado';
$string['col_context'] = 'Ubicación';
$string['col_filename'] = 'Archivo';
$string['col_filesize'] = 'Tamaño';
$string['col_fullname'] = 'Usuario';
$string['col_reason'] = 'Motivo';
$string['col_timecreated'] = 'Fecha';
$string['eventfilerejected'] = 'Archivo rechazado por la política multimedia';
$string['formatlist'] = '{$a->list} o {$a->last}';
$string['othermaxmb'] = 'Tamaño máximo del resto de archivos (MB)';
$string['othermaxmb_desc'] = 'Se aplica a cualquier archivo que no sea audio ni vídeo (documentos, imágenes...).';
$string['pluginname'] = 'Política de subida de archivos multimedia';
$string['privacy:metadata'] = 'El plugin Política de subida de archivos multimedia no almacena datos personales.';
$string['reason_rejected_audio'] = 'Formato de audio no permitido o demasiado grande';
$string['reason_rejected_audio_none'] = 'Audio no permitido';
$string['reason_rejected_size'] = 'Demasiado grande';
$string['reason_rejected_unreadable'] = 'No se pudo comprobar';
$string['reason_rejected_video'] = 'Vídeo';
$string['rejected_audio'] = 'El audio debe ser {$a->formats} de máximo {$a->maxmb} MB ({$a->item}).';
$string['rejected_audio_none'] = 'No se permiten archivos de audio ({$a->item}).';
$string['rejected_size'] = 'El archivo {$a->item} supera el límite de {$a->maxmb} MB.';
$string['rejected_unreadable'] = 'No se ha podido comprobar el archivo {$a->item} y se ha rechazado.';
$string['rejected_video'] = 'No se permiten archivos de vídeo ({$a->item}).';
$string['rejecteduploads'] = 'Subidas rechazadas';
$string['rejecteduploads_desc'] = 'Subidas bloqueadas por la política de archivos multimedia. Los datos salen de los registros del sitio, así que se conservan mientras se conserven los registros.';
