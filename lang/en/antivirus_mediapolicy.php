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
 * English strings.
 *
 * @package    antivirus_mediapolicy
 * @copyright  2026 Awakelab
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allowedaudiomimetypes'] = 'Allowed audio MIME types';
$string['allowedaudiomimetypes_desc'] = 'Comma separated list, checked against the real file content (e.g. audio/mpeg for MP3).';
$string['audiomaxmb'] = 'Maximum audio size (MB)';
$string['audiomaxmb_desc'] = 'Audio files larger than this are rejected.';
$string['eventfilerejected'] = 'File rejected by media policy';
$string['othermaxmb'] = 'Maximum size for other files (MB)';
$string['othermaxmb_desc'] = 'Applies to any file that is not audio or video (documents, images...).';
$string['pluginname'] = 'Media upload policy';
$string['privacy:metadata'] = 'The Media upload policy plugin does not store any personal data.';
$string['rejected_audio'] = 'Audio must be MP3 and at most {$a->maxmb} MB ({$a->item}).';
$string['rejected_size'] = 'The file {$a->item} exceeds the {$a->maxmb} MB limit.';
$string['rejected_unreadable'] = 'The file {$a->item} could not be checked and has been rejected.';
$string['rejected_video'] = 'Video files are not allowed ({$a->item}).';
