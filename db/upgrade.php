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
 * Upgrade steps.
 *
 * @package    antivirus_mediapolicy
 * @copyright  2026 Awakelab
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrades the plugin.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_antivirus_mediapolicy_upgrade($oldversion) {
    if ($oldversion < 2026110300) {
        // The MIME type list setting is replaced by format checkboxes: keep what was allowed.
        $mimes = get_config('antivirus_mediapolicy', 'allowedaudiomimetypes');
        if ($mimes !== false) {
            $formats = \antivirus_mediapolicy\audio_formats::from_mimetypes(explode(',', $mimes));
            set_config('allowedaudioformats', implode(',', $formats), 'antivirus_mediapolicy');
            unset_config('allowedaudiomimetypes', 'antivirus_mediapolicy');
        }
        upgrade_plugin_savepoint(true, 2026110300, 'antivirus', 'mediapolicy');
    }
    return true;
}
