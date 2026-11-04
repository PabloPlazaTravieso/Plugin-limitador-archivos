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
 * Tests for the allowed audio formats.
 *
 * @package    antivirus_mediapolicy
 * @copyright  2026 Awakelab
 * @covers     \antivirus_mediapolicy\audio_formats
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class audio_formats_test extends \basic_testcase {
    public function test_parse(): void {
        $this->assertSame(['mp3'], audio_formats::parse(null));
        $this->assertSame([], audio_formats::parse(''));
        $this->assertSame(['mp3', 'wav'], audio_formats::parse('wav, MP3,unknown'));
    }

    public function test_mimetypes(): void {
        $this->assertSame(['audio/mpeg'], audio_formats::mimetypes(['mp3']));
        $this->assertContains('audio/x-wav', audio_formats::mimetypes(['mp3', 'wav']));
        $this->assertSame([], audio_formats::mimetypes([]));
    }

    public function test_from_mimetypes(): void {
        $this->assertSame(['mp3'], audio_formats::from_mimetypes(['audio/mpeg']));
        $this->assertSame(['mp3', 'wav'], audio_formats::from_mimetypes([' Audio/MPEG', 'audio/x-wav', 'audio/unknown']));
        $this->assertSame([], audio_formats::from_mimetypes(['']));
    }

    public function test_label_list(): void {
        $this->assertSame('MP3', audio_formats::label_list(['mp3']));
        $this->assertSame('MP3 or WAV', audio_formats::label_list(['mp3', 'wav']));
        $this->assertSame('MP3, WAV or OGG', audio_formats::label_list(['mp3', 'wav', 'ogg']));
    }

    public function test_choices(): void {
        $choices = audio_formats::choices();
        $this->assertSame('MP3 (.mp3)', $choices['mp3']);
        $this->assertSame(array_keys(audio_formats::FORMATS), array_keys($choices));
    }

    /**
     * Every listed format has an extension Moodle maps to audio, as the policy requires.
     */
    public function test_extensions_are_audio(): void {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        foreach (audio_formats::FORMATS as [, $extensions]) {
            foreach (explode(', ', $extensions) as $ext) {
                $this->assertStringStartsWith('audio/', mimeinfo('type', 'file' . $ext), $ext);
            }
        }
    }
}
