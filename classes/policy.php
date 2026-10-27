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
 * Pure upload rules, independent of Moodle so they can be unit tested in isolation.
 *
 * @package    antivirus_mediapolicy
 * @copyright  2026 Pablo Plaza <pablo.plaza@awakelab.world>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class policy {
    /**
     * Evaluates a file against the policy.
     *
     * @param string $contentmime Mimetype detected from the file content (finfo).
     * @param string $extmime Mimetype derived from the file extension (mimeinfo).
     * @param int $size File size in bytes.
     * @param string[] $allowedaudio Allowed audio content mimetypes.
     * @param int $audiomax Max audio size in bytes.
     * @param int $othermax Max size in bytes for any other file.
     * @return array|null Null if accepted, otherwise ['reason' => string id, 'a' => placeholders].
     */
    public static function evaluate(
        string $contentmime,
        string $extmime,
        int $size,
        array $allowedaudio,
        int $audiomax,
        int $othermax
    ): ?array {

        // Video: rejected if either the content or the extension says video (defensive).
        if (self::is_type($contentmime, 'video') || self::is_type($extmime, 'video')) {
            return ['reason' => 'rejected_video', 'a' => []];
        }

        if (self::is_type($contentmime, 'audio') || self::is_type($extmime, 'audio')) {
            // The real content must be an allowed format, and the extension must look like audio too.
            $formatok = in_array($contentmime, $allowedaudio, true) && self::is_type($extmime, 'audio');
            if (!$formatok || $size > $audiomax) {
                return ['reason' => 'rejected_audio', 'a' => ['maxmb' => self::to_mb($audiomax)]];
            }
            return null;
        }

        if ($size > $othermax) {
            return ['reason' => 'rejected_size', 'a' => ['maxmb' => self::to_mb($othermax)]];
        }
        return null;
    }

    /**
     * Whether a mimetype belongs to a top-level group (video, audio...).
     *
     * @param string $mime
     * @param string $group
     * @return bool
     */
    protected static function is_type(string $mime, string $group): bool {
        return str_starts_with(strtolower($mime), $group . '/');
    }

    /**
     * Bytes to rounded megabytes, for user messages.
     *
     * @param int $bytes
     * @return int
     */
    protected static function to_mb(int $bytes): int {
        return (int) round($bytes / 1048576);
    }
}
