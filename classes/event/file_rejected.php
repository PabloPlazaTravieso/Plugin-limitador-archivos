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

namespace antivirus_mediapolicy\event;

/**
 * Triggered when an upload is rejected by the media policy.
 *
 * Other data: filename, contentmime, extensionmime, filesize, reason.
 *
 * @package    antivirus_mediapolicy
 * @copyright  2026 Awakelab
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class file_rejected extends \core\event\base {
    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventfilerejected', 'antivirus_mediapolicy');
    }

    /**
     * Event description for the logs.
     *
     * @return string
     */
    public function get_description() {
        $o = $this->other;
        return "The user with id '{$this->userid}' tried to upload '{$o['filename']}' " .
            "(content type '{$o['contentmime']}', extension type '{$o['extensionmime']}', " .
            "{$o['filesize']} bytes). Rejected: {$o['reason']}.";
    }

    /**
     * Validates the custom data.
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();
        foreach (['filename', 'contentmime', 'extensionmime', 'filesize', 'reason'] as $key) {
            if (!isset($this->other[$key])) {
                throw new \coding_exception("The '{$key}' value must be set in other.");
            }
        }
    }

    /**
     * No ids to map in other on restore.
     *
     * @return bool
     */
    public static function get_other_mapping() {
        return false;
    }
}
