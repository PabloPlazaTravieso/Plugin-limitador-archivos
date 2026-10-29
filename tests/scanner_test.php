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

/**
 * Integration tests for the scanner, using small generated files detected by finfo.
 *
 * @package    antivirus_mediapolicy
 * @copyright  2026 Awakelab
 * @covers     \antivirus_mediapolicy\scanner
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class scanner_test extends \advanced_testcase {
    /** @var string Temp dir for generated files. */
    protected $dir;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $this->dir = make_request_directory();
    }

    /**
     * Writes a file into the temp dir.
     *
     * @param string $name
     * @param string $content
     * @return string Full path.
     */
    protected function make_file(string $name, string $content): string {
        $path = $this->dir . '/' . $name;
        file_put_contents($path, $content);
        return $path;
    }

    /**
     * Minimal MP3: ID3v2 header followed by MPEG frame headers.
     *
     * @return string
     */
    protected function mp3_content(): string {
        return "ID3\x03\x00\x00\x00\x00\x00\x00" . str_repeat("\xFF\xFB\x90\x64" . str_repeat("\x00", 413), 10);
    }

    /**
     * Minimal WAV header.
     *
     * @return string
     */
    protected function wav_content(): string {
        return 'RIFF' . pack('V', 36) . 'WAVEfmt ' . pack('VvvVVvv', 16, 1, 1, 8000, 8000, 1, 8)
            . 'data' . pack('V', 0);
    }

    /**
     * Minimal MP4 header.
     *
     * @return string
     */
    protected function mp4_content(): string {
        return pack('N', 24) . 'ftypisom' . pack('N', 512) . 'isomiso2' . str_repeat("\x00", 100);
    }

    public function test_text_file_accepted(): void {
        $scanner = new scanner();
        $path = $this->make_file('notes.txt', 'Hello');
        $this->assertSame(scanner::SCAN_RESULT_OK, $scanner->scan_file($path, 'notes.txt'));
    }

    public function test_mp3_accepted(): void {
        $scanner = new scanner();
        $path = $this->make_file('song.mp3', $this->mp3_content());
        $this->assertSame(scanner::SCAN_RESULT_OK, $scanner->scan_file($path, 'song.mp3'));
    }

    public function test_mp3_not_recognised_by_finfo_accepted(): void {
        // Bytes between the ID3 tag and the audio, as in some real MP3s: finfo gives up.
        $content = "ID3\x03\x00\x00\x00\x00\x00\x40" . str_repeat("\x00", 64)
            . str_repeat("\x1d\x17\x70\x29\x92\x51\x79\x27", 48)
            . str_repeat("\xFF\xFB\x90\x64" . str_repeat("\x00", 413), 10);
        $path = $this->make_file('song.mp3', $content);
        $this->assertSame('application/octet-stream', (new \finfo(FILEINFO_MIME_TYPE))->file($path));

        $scanner = new scanner();
        $this->assertSame(scanner::SCAN_RESULT_OK, $scanner->scan_file($path, 'song.mp3'));
        // Only for .mp3 names: the same content with another extension is not treated as audio.
        $this->assertSame(scanner::SCAN_RESULT_OK, $scanner->scan_file($path, 'song.bin'));
        set_config('audiomaxmb', 0, 'antivirus_mediapolicy');
        $this->assertSame(scanner::SCAN_RESULT_FOUND, $scanner->scan_file($path, 'song.mp3'));
    }

    public function test_video_rejected_and_logged(): void {
        $sink = $this->redirectEvents();
        $scanner = new scanner();
        $path = $this->make_file('clip.mp4', $this->mp4_content());

        $this->assertSame(scanner::SCAN_RESULT_FOUND, $scanner->scan_file($path, 'clip.mp4'));
        $this->assertSame('rejected_video', $scanner->get_virus_found_message()['string']);

        $events = array_values(array_filter(
            $sink->get_events(),
            fn($e) => $e instanceof event\file_rejected
        ));
        $this->assertCount(1, $events);
        $this->assertSame('clip.mp4', $events[0]->other['filename']);
        $this->assertSame('rejected_video', $events[0]->other['reason']);
        // No page context set: the event falls back to system.
        $this->assertEquals(\context_system::instance(), $events[0]->get_context());
    }

    public function test_event_uses_page_context(): void {
        global $PAGE;
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);
        $PAGE->set_context($coursecontext);

        $sink = $this->redirectEvents();
        $scanner = new scanner();
        $path = $this->make_file('clip.mp4', $this->mp4_content());
        $scanner->scan_file($path, 'clip.mp4');

        $events = array_values(array_filter(
            $sink->get_events(),
            fn($e) => $e instanceof event\file_rejected
        ));
        $this->assertCount(1, $events);
        $this->assertEquals($coursecontext, $events[0]->get_context());
    }

    public function test_unreadable_file_rejected(): void {
        $sink = $this->redirectEvents();
        $scanner = new scanner();

        $result = $scanner->scan_file($this->dir . '/missing.pdf', 'missing.pdf');

        $this->assertSame(scanner::SCAN_RESULT_FOUND, $result);
        $this->assertSame('rejected_unreadable', $scanner->get_virus_found_message()['string']);
        $events = array_values(array_filter(
            $sink->get_events(),
            fn($e) => $e instanceof event\file_rejected
        ));
        $this->assertCount(1, $events);
        $this->assertSame('rejected_unreadable', $events[0]->other['reason']);
        $this->assertSame(0, $events[0]->other['filesize']);
    }

    public function test_rejection_does_not_notify_admins(): void {
        set_config('antiviruses', 'mediapolicy');
        // IPv6 so the incident details skip the online IP lookup.
        $_SERVER['REMOTE_ADDR'] = '::1';
        $messages = $this->redirectMessages();
        $emails = $this->redirectEmails();
        $path = $this->make_file('clip.mp4', $this->mp4_content());

        try {
            \core\antivirus\manager::scan_file($path, 'clip.mp4', false);
            $this->fail('The upload should have been rejected.');
        } catch (\core\antivirus\scanner_exception $e) {
            $this->assertSame('rejected_video', $e->errorcode);
        }
        $this->assertSame(0, $messages->count());
        $this->assertSame(0, $emails->count());
    }

    public function test_video_disguised_as_pdf_rejected(): void {
        $scanner = new scanner();
        $path = $this->make_file('report.pdf', $this->mp4_content());
        $this->assertSame(scanner::SCAN_RESULT_FOUND, $scanner->scan_file($path, 'report.pdf'));
    }

    public function test_wav_renamed_to_mp3_rejected(): void {
        $scanner = new scanner();
        $path = $this->make_file('fake.mp3', $this->wav_content());
        $this->assertSame(scanner::SCAN_RESULT_FOUND, $scanner->scan_file($path, 'fake.mp3'));
        $this->assertSame('rejected_audio', $scanner->get_virus_found_message()['string']);
    }

    public function test_size_limit_uses_settings(): void {
        set_config('othermaxmb', 1, 'antivirus_mediapolicy');
        $scanner = new scanner();
        $path = $this->make_file('big.txt', str_repeat('a', 1048577));

        $this->assertSame(scanner::SCAN_RESULT_FOUND, $scanner->scan_file($path, 'big.txt'));
        $message = $scanner->get_virus_found_message();
        $this->assertSame('rejected_size', $message['string']);
        $this->assertSame(1, $message['placeholders']['maxmb']);
    }

    public function test_audio_limit_uses_settings(): void {
        set_config('audiomaxmb', 0, 'antivirus_mediapolicy');
        $scanner = new scanner();
        $path = $this->make_file('song.mp3', $this->mp3_content());
        $this->assertSame(scanner::SCAN_RESULT_FOUND, $scanner->scan_file($path, 'song.mp3'));
    }
}
