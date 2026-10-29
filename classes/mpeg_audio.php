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
 * Recognises MP3 files that finfo reports as application/octet-stream.
 *
 * Some real MP3s have bytes between the ID3v2 tag and the first audio frame, so libmagic
 * does not find the frame and gives up. This looks for several consecutive MPEG audio
 * Layer III frames shortly after the tag.
 *
 * @package    antivirus_mediapolicy
 * @copyright  2026 Awakelab
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mpeg_audio {
    /** @var int How far after the ID3 tag the first frame is searched for. */
    const SEARCH_BYTES = 65536;

    /** @var int Consecutive valid frames required. */
    const FRAMES = 3;

    /** @var int[][] Layer III bitrates in kbps, by MPEG version 1 / 2 and 2.5. */
    const BITRATES = [
        1 => [0, 32, 40, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320],
        2 => [0, 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112, 128, 144, 160],
    ];

    /** @var int[][] Sample rates in Hz, by version bits (3 = MPEG 1, 2 = MPEG 2, 0 = MPEG 2.5). */
    const SAMPLERATES = [
        3 => [44100, 48000, 32000],
        2 => [22050, 24000, 16000],
        0 => [11025, 12000, 8000],
    ];

    /**
     * Whether the file is an ID3-tagged MP3 with real audio frames after the tag.
     *
     * @param string $path Full path to the file.
     * @return bool
     */
    public static function is_tagged_mp3(string $path): bool {
        $handle = @fopen($path, 'rb');
        if (!$handle) {
            return false;
        }
        $header = fread($handle, 10);
        $tagend = self::id3_end($header);
        if ($tagend === null) {
            fclose($handle);
            return false;
        }
        fseek($handle, $tagend);
        $data = (string) fread($handle, self::SEARCH_BYTES + self::FRAMES * 2881);
        fclose($handle);
        return self::has_frames($data);
    }

    /**
     * Offset where the audio starts after an ID3v2 tag, or null if there is no tag.
     *
     * @param string $header First 10 bytes of the file.
     * @return int|null
     */
    protected static function id3_end(string $header): ?int {
        if (strlen($header) < 10 || substr($header, 0, 3) !== 'ID3') {
            return null;
        }
        $major = ord($header[3]);
        if ($major < 2 || $major > 4) {
            return null;
        }
        $size = 0;
        for ($i = 6; $i < 10; $i++) {
            $byte = ord($header[$i]);
            if ($byte & 0x80) {
                return null;
            }
            $size = ($size << 7) | $byte;
        }
        $footer = ($major === 4 && (ord($header[5]) & 0x10)) ? 10 : 0;
        return 10 + $size + $footer;
    }

    /**
     * Whether the data contains FRAMES consecutive Layer III frames starting in the first SEARCH_BYTES.
     *
     * @param string $data Bytes following the ID3 tag.
     * @return bool
     */
    protected static function has_frames(string $data): bool {
        $limit = min(strlen($data) - 4, self::SEARCH_BYTES);
        for ($pos = 0; $pos <= $limit; $pos++) {
            if ($data[$pos] !== "\xFF") {
                continue;
            }
            $first = self::frame($data, $pos);
            if ($first === null) {
                continue;
            }
            $next = $pos + $first['length'];
            $found = 1;
            while ($found < self::FRAMES) {
                $frame = self::frame($data, $next);
                if ($frame === null || $frame['version'] !== $first['version'] || $frame['rate'] !== $first['rate']) {
                    break;
                }
                $next += $frame['length'];
                $found++;
            }
            if ($found === self::FRAMES) {
                return true;
            }
        }
        return false;
    }

    /**
     * Parses a Layer III frame header.
     *
     * @param string $data
     * @param int $pos
     * @return array|null ['version', 'rate', 'length'] or null if not a valid header.
     */
    protected static function frame(string $data, int $pos): ?array {
        if ($pos + 4 > strlen($data)) {
            return null;
        }
        $b1 = ord($data[$pos + 1]);
        $b2 = ord($data[$pos + 2]);
        if (ord($data[$pos]) !== 0xFF || ($b1 & 0xE0) !== 0xE0) {
            return null;
        }
        $version = ($b1 >> 3) & 3;
        $layer = ($b1 >> 1) & 3;
        $bitrateindex = ($b2 >> 4) & 15;
        $rateindex = ($b2 >> 2) & 3;
        if ($version === 1 || $layer !== 1 || $bitrateindex === 0 || $bitrateindex === 15 || $rateindex === 3) {
            return null;
        }
        $bitrate = self::BITRATES[$version === 3 ? 1 : 2][$bitrateindex] * 1000;
        $rate = self::SAMPLERATES[$version][$rateindex];
        $factor = $version === 3 ? 144 : 72;
        $length = intdiv($factor * $bitrate, $rate) + (($b2 >> 1) & 1);
        return ['version' => $version, 'rate' => $rate, 'length' => $length];
    }
}
