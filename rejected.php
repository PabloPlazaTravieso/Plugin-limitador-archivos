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
 * Report of uploads rejected by the media policy.
 *
 * @package    antivirus_mediapolicy
 * @copyright  2026 Awakelab
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('antivirus_mediapolicy_rejected');

$download = optional_param('download', '', PARAM_ALPHA);

$table = new \antivirus_mediapolicy\table\rejected_uploads('antivirus_mediapolicy_rejected');
$table->define_baseurl($PAGE->url);
$table->is_downloading($download, 'rejected_uploads', get_string('rejecteduploads', 'antivirus_mediapolicy'));
$table->show_download_buttons_at([TABLE_P_BOTTOM]);

if (!$table->is_downloading()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('rejecteduploads', 'antivirus_mediapolicy'));
    echo $OUTPUT->notification(get_string('rejecteduploads_desc', 'antivirus_mediapolicy'), 'info');
}

$table->out(50, false);

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}
