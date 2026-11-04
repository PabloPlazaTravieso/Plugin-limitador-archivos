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
 * Audio formats an admin can allow, with the content mimetypes finfo reports for each.
 *
 * Only formats whose extension Moodle maps to audio/* are listed: the policy also requires
 * an audio extension. WMA is left out because finfo reports it as video (ASF).
 *
 * @package    antivirus_mediapolicy
 * @copyright  2026 Awakelab
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class audio_formats {
    /** @var array Format key => [label, extensions, content mimetypes]. */
    const FORMATS = [
        'mp3' => ['MP3', '.mp3', ['audio/mpeg']],
        'wav' => ['WAV', '.wav', ['audio/x-wav', 'audio/wav', 'audio/vnd.wave']],
        'ogg' => ['OGG', '.ogg, .oga', ['audio/ogg']],
        'm4a' => ['M4A', '.m4a', ['audio/x-m4a', 'audio/mp4']],
        'aac' => ['AAC', '.aac', ['audio/x-hx-aac-adts', 'audio/aac']],
        'flac' => ['FLAC', '.flac', ['audio/flac', 'audio/x-flac']],
        'aiff' => ['AIFF', '.aif, .aiff', ['audio/x-aiff', 'audio/aiff']],
    ];

    /** @var string Default value of the setting. */
    const DEFAULT = 'mp3';

    /**
     * Choices for the admin checkboxes.
     *
     * @return string[] Format key => label with extensions, e.g. "MP3 (.mp3)".
     */
    public static function choices(): array {
        $choices = [];
        foreach (self::FORMATS as $key => [$label, $extensions]) {
            $choices[$key] = "{$label} ({$extensions})";
        }
        return $choices;
    }

    /**
     * Parses the stored setting ("mp3,wav") into known format keys.
     *
     * @param string|null $value Stored value; null if never saved.
     * @return string[]
     */
    public static function parse(?string $value): array {
        $keys = array_map('trim', explode(',', strtolower($value ?? self::DEFAULT)));
        return array_values(array_intersect(array_keys(self::FORMATS), $keys));
    }

    /**
     * Content mimetypes accepted for the given formats.
     *
     * @param string[] $keys
     * @return string[]
     */
    public static function mimetypes(array $keys): array {
        $mimes = [];
        foreach ($keys as $key) {
            $mimes = array_merge($mimes, self::FORMATS[$key][2] ?? []);
        }
        return $mimes;
    }

    /**
     * Format keys covering the given mimetypes, to migrate the old MIME list setting.
     *
     * @param string[] $mimes
     * @return string[]
     */
    public static function from_mimetypes(array $mimes): array {
        $mimes = array_map(fn($m) => strtolower(trim($m)), $mimes);
        return array_keys(array_filter(self::FORMATS, fn($f) => array_intersect($f[2], $mimes)));
    }

    /**
     * Human readable list for messages, e.g. "MP3", "MP3 o WAV", "MP3, WAV o OGG".
     *
     * @param string[] $keys
     * @return string
     */
    public static function label_list(array $keys): string {
        $labels = array_map(fn($k) => self::FORMATS[$k][0], $keys);
        $last = array_pop($labels);
        if (!$labels) {
            return (string) $last;
        }
        return get_string('formatlist', 'antivirus_mediapolicy', ['list' => implode(', ', $labels), 'last' => $last]);
    }
}
