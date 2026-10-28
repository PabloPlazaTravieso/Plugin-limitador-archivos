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

namespace antivirus_mediapolicy;

use antivirus_mediapolicy\table\rejected_uploads;

/**
 * Tests for the rejected uploads report table.
 *
 * @package    antivirus_mediapolicy
 * @copyright  2026 Awakelab
 * @covers     \antivirus_mediapolicy\table\rejected_uploads
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class rejected_uploads_test extends \advanced_testcase {
    public function test_lists_rejections_from_the_log(): void {
        $this->resetAfterTest();
        // The standard log store only writes when no transaction is open.
        $this->preventResetByRollback();
        set_config('enabled_stores', 'logstore_standard', 'tool_log');
        set_config('buffersize', 0, 'logstore_standard');
        get_log_manager(true);

        $user = $this->getDataGenerator()->create_user(['firstname' => 'Ana', 'lastname' => 'Pérez']);
        $this->setUser($user);
        $dir = make_request_directory();
        file_put_contents("{$dir}/clip.mp4", pack('N', 24) . 'ftypisom' . pack('N', 512) . 'isomiso2' . str_repeat("\x00", 100));
        file_put_contents("{$dir}/notes.txt", 'Hello');

        $scanner = new scanner();
        $scanner->scan_file("{$dir}/clip.mp4", 'clip.mp4');
        $scanner->scan_file("{$dir}/notes.txt", 'notes.txt');

        $table = new rejected_uploads('test');
        $table->define_baseurl(new \moodle_url('/lib/antivirus/mediapolicy/rejected.php'));
        $table->setup();
        $table->query_db(10, false);
        $rows = array_values($table->rawdata);

        $this->assertCount(1, $rows);
        $this->assertSame('clip.mp4', $table->col_filename($rows[0]));
        $this->assertStringContainsString('video/mp4', $table->col_contentmime($rows[0]));
        $this->assertSame(get_string('reason_rejected_video', 'antivirus_mediapolicy'), $table->col_reason($rows[0]));
        $this->assertStringContainsString('Ana Pérez', $table->col_fullname($rows[0]));
    }
}
