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
