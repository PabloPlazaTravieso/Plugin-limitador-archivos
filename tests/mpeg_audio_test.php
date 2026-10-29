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
 * Tests for the MP3 frame detector.
 *
 * @package    antivirus_mediapolicy
 * @copyright  2026 Awakelab
 * @covers     \antivirus_mediapolicy\mpeg_audio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class mpeg_audio_test extends \basic_testcase {
    /** @var string ID3v2.3 header declaring a 64 byte tag. */
    const ID3 = "ID3\x03\x00\x00\x00\x00\x00\x40";

    /** @var string Bytes between the tag and the audio, as in some real MP3s. */
    const JUNK = "\x1d\x17\x70\x29\x92\x51\x79\x27";

    /**
     * MPEG 1 Layer III frames, 128 kbps, 44.1 kHz (417 bytes each).
     *
     * @param int $count
     * @return string
     */
    public static function frames(int $count): string {
        return str_repeat("\xFF\xFB\x90\x64" . str_repeat("\x00", 413), $count);
    }

    /**
     * Writes the content to a temp file and runs the detector.
     *
     * @param string $content
     * @return bool
     */
    protected function detect(string $content): bool {
        $path = make_request_directory() . '/test.mp3';
        file_put_contents($path, $content);
        return mpeg_audio::is_tagged_mp3($path);
    }

    /**
     * Cases for test_is_tagged_mp3.
     *
     * @return array
     */
    public static function content_provider(): array {
        $tag = self::ID3 . str_repeat("\x00", 64);
        $mp4 = pack('N', 24) . 'ftypisom' . pack('N', 512) . 'isomiso2' . str_repeat("\x00", 2000);
        return [
            'frames right after the tag' => [$tag . self::frames(5), true],
            'junk between tag and frames' => [$tag . str_repeat(self::JUNK, 48) . self::frames(5), true],
            'too few frames' => [$tag . self::frames(2), false],
            'no ID3 tag' => [self::frames(5), false],
            'tag followed by text' => [$tag . str_repeat('Not audio at all. ', 500), false],
            'tag followed by mp4' => [$tag . $mp4, false],
            'invalid tag size' => ["ID3\x03\x00\x00\x00\x00\x00\x80" . self::frames(5), false],
            'empty file' => ['', false],
        ];
    }

    /**
     * Detection of tagged MP3s.
     *
     * @dataProvider content_provider
     * @param string $content
     * @param bool $expected
     */
    public function test_is_tagged_mp3(string $content, bool $expected): void {
        $this->assertSame($expected, $this->detect($content));
    }

    public function test_missing_file(): void {
        $this->assertFalse(mpeg_audio::is_tagged_mp3(make_request_directory() . '/missing.mp3'));
    }
}
