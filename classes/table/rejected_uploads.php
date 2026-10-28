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

namespace antivirus_mediapolicy\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Rejected uploads, read from the file_rejected events of the standard log.
 *
 * @package    antivirus_mediapolicy
 * @copyright  2026 Awakelab
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rejected_uploads extends \table_sql {
    /**
     * Sets up columns and SQL.
     *
     * @param string $uniqueid
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $columns = ['timecreated', 'fullname', 'filename', 'contentmime', 'filesize', 'reason', 'context'];
        $this->define_columns($columns);
        $this->define_headers(array_map(fn($c) => get_string('col_' . $c, 'antivirus_mediapolicy'), $columns));
        $this->sortable(true, 'timecreated', SORT_DESC);
        $this->no_sorting('filename');
        $this->no_sorting('contentmime');
        $this->no_sorting('filesize');
        $this->no_sorting('reason');
        $this->no_sorting('context');
        $this->set_attribute('class', 'generaltable antivirus_mediapolicy_rejected');

        $userfields = \core_user\fields::for_name()->get_sql('u', false, '', '', false);
        $this->set_sql(
            "l.id, l.timecreated, l.userid, l.contextid, l.other, {$userfields->selects}",
            '{logstore_standard_log} l LEFT JOIN {user} u ON u.id = l.userid',
            'l.eventname = :eventname',
            ['eventname' => '\\' . \antivirus_mediapolicy\event\file_rejected::class] + $userfields->params
        );
    }

    /**
     * Decodes the event data once per row.
     *
     * @param \stdClass $row
     * @return array
     */
    protected function other(\stdClass $row): array {
        if (!isset($row->decoded)) {
            $other = $row->other ?? '';
            if ($other === 'N;' || preg_match('~^.:~', $other)) {
                $row->decoded = unserialize($other, ['allowed_classes' => false]);
            } else {
                $row->decoded = json_decode($other, true);
            }
            $row->decoded = is_array($row->decoded) ? $row->decoded : [];
        }
        return $row->decoded;
    }

    /**
     * Date of the attempt.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_timecreated($row) {
        $format = $this->is_downloading() ? '%Y-%m-%d %H:%M:%S' : get_string('strftimedatetimeshort', 'langconfig');
        return userdate($row->timecreated, $format);
    }

    /**
     * User who tried the upload.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_fullname($row) {
        $name = fullname($row);
        if ($this->is_downloading() || empty($row->userid)) {
            return $name;
        }
        return \html_writer::link(new \moodle_url('/user/profile.php', ['id' => $row->userid]), $name);
    }

    /**
     * Original file name.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_filename($row) {
        $name = $this->other($row)['filename'] ?? '';
        return $this->is_downloading() ? $name : s($name);
    }

    /**
     * Type detected from the content (and from the extension when it differs).
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_contentmime($row) {
        $other = $this->other($row);
        $content = $other['contentmime'] ?? '';
        $extension = $other['extensionmime'] ?? '';
        $type = ($content === '' || $content === $extension) ? $extension : "{$content} ({$extension})";
        return $this->is_downloading() ? $type : s($type);
    }

    /**
     * File size.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_filesize($row) {
        $size = (int) ($this->other($row)['filesize'] ?? 0);
        return $this->is_downloading() ? (string) $size : display_size($size);
    }

    /**
     * Reason of the rejection.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_reason($row) {
        $reason = $this->other($row)['reason'] ?? '';
        $key = 'reason_' . $reason;
        return get_string_manager()->string_exists($key, 'antivirus_mediapolicy')
            ? get_string($key, 'antivirus_mediapolicy') : s($reason);
    }

    /**
     * Where the upload was attempted (course, activity...).
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_context($row) {
        $context = \context::instance_by_id($row->contextid, IGNORE_MISSING);
        if (!$context) {
            return '';
        }
        $name = $context->get_context_name(true, true);
        if ($this->is_downloading()) {
            return $name;
        }
        return \html_writer::link($context->get_url(), $name);
    }
}
