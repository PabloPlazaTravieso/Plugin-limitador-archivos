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
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allowedaudiomimetypes'] = 'Tipos MIME de audio permitidos';
$string['allowedaudiomimetypes_desc'] = 'Lista separada por comas, comprobada sobre el contenido real del archivo (p. ej. audio/mpeg para MP3).';
$string['audiomaxmb'] = 'Tamaño máximo de audio (MB)';
$string['audiomaxmb_desc'] = 'Se rechazan los archivos de audio que superen este tamaño.';
$string['eventfilerejected'] = 'Archivo rechazado por la política multimedia';
$string['othermaxmb'] = 'Tamaño máximo del resto de archivos (MB)';
$string['othermaxmb_desc'] = 'Se aplica a cualquier archivo que no sea audio ni vídeo (documentos, imágenes...).';
$string['pluginname'] = 'Política de subida de archivos multimedia';
$string['privacy:metadata'] = 'El plugin Política de subida de archivos multimedia no almacena datos personales.';
$string['rejected_audio'] = 'El audio debe ser MP3 de máximo {$a->maxmb} MB ({$a->item}).';
$string['rejected_size'] = 'El archivo {$a->item} supera el límite de {$a->maxmb} MB.';
$string['rejected_video'] = 'No se permiten archivos de vídeo ({$a->item}).';
