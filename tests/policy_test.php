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
 * Unit tests for the pure policy rules.
 *
 * @package    antivirus_mediapolicy
 * @copyright  2026 Awakelab
 * @covers     \antivirus_mediapolicy\policy
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class policy_test extends \basic_testcase {
    /** One megabyte. */
    const MB = 1048576;

    /**
     * Cases: content mime, extension mime, size, expected reason (null = accepted).
     *
     * @return array
     */
    public static function evaluate_provider(): array {
        return [
            'mp4 video' => ['video/mp4', 'video/mp4', 1 * self::MB, 'rejected_video'],
            'tiny video' => ['video/webm', 'video/webm', 10, 'rejected_video'],
            'video renamed to pdf' => ['video/mp4', 'application/pdf', 1 * self::MB, 'rejected_video'],
            'unknown content with video extension' => ['application/octet-stream', 'video/quicktime', 10, 'rejected_video'],
            'mp3 ok' => ['audio/mpeg', 'audio/mp3', 5 * self::MB, null],
            'mp3 at limit' => ['audio/mpeg', 'audio/mp3', 20 * self::MB, null],
            'mp3 too big' => ['audio/mpeg', 'audio/mp3', 20 * self::MB + 1, 'rejected_audio'],
            'wav' => ['audio/x-wav', 'audio/wav', 1 * self::MB, 'rejected_audio'],
            'wav renamed to mp3' => ['audio/x-wav', 'audio/mp3', 1 * self::MB, 'rejected_audio'],
            'mp3 renamed to txt' => ['audio/mpeg', 'text/plain', 1 * self::MB, 'rejected_audio'],
            'pdf ok' => ['application/pdf', 'application/pdf', 29 * self::MB, null],
            'pdf at limit' => ['application/pdf', 'application/pdf', 30 * self::MB, null],
            'pdf too big' => ['application/pdf', 'application/pdf', 30 * self::MB + 1, 'rejected_size'],
            'image ok' => ['image/png', 'image/png', 2 * self::MB, null],
        ];
    }

    /**
     * Test the rules with default limits.
     *
     * @dataProvider evaluate_provider
     * @param string $contentmime
     * @param string $extmime
     * @param int $size
     * @param string|null $expected
     */
    public function test_evaluate(string $contentmime, string $extmime, int $size, ?string $expected): void {
        $result = policy::evaluate($contentmime, $extmime, $size, ['audio/mpeg'], 20 * self::MB, 30 * self::MB);
        $this->assertSame($expected, $result['reason'] ?? null);
    }

    /**
     * Messages carry the configured limit.
     */
    public function test_placeholders(): void {
        $result = policy::evaluate(
            'application/pdf',
            'application/pdf',
            11 * self::MB,
            ['audio/mpeg'],
            5 * self::MB,
            10 * self::MB
        );
        $this->assertSame(['maxmb' => 10], $result['a']);
    }
}
