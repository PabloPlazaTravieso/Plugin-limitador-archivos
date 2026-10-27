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
 * Antivirus "scanner" that rejects files not allowed by the media policy.
 *
 * Moodle runs every enabled antivirus on any upload (filepicker, editors, web services...),
 * so this is the single hook that covers all upload paths.
 *
 * @package    antivirus_mediapolicy
 * @copyright  2026 Awakelab
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scanner extends \core\antivirus\scanner {
    /** @var array|null Reason of the last rejection, used by get_virus_found_message(). */
    protected $rejection = null;

    /**
     * Always configured: settings have sensible defaults.
     *
     * @return bool
     */
    public function is_configured() {
        return true;
    }

    /**
     * Checks a file against the policy.
     *
     * @param string $file Full path to the temporary file.
     * @param string $filename Original file name.
     * @return int Scanning result constant.
     */
    public function scan_file($file, $filename) {
        $this->rejection = null;
        $extmime = mimeinfo('type', $filename);

        // Fail closed: the core accepts the upload on SCAN_RESULT_ERROR.
        if (!is_readable($file)) {
            return $this->reject(['reason' => 'rejected_unreadable', 'a' => []], $filename, '', $extmime, 0);
        }

        $size = (int) filesize($file);
        $contentmime = (new \finfo(FILEINFO_MIME_TYPE))->file($file) ?: 'application/octet-stream';

        $config = get_config('antivirus_mediapolicy');
        $allowed = array_values(array_filter(array_map(
            'trim',
            explode(',', strtolower($config->allowedaudiomimetypes ?? 'audio/mpeg'))
        )));
        $audiomax = (int) ($config->audiomaxmb ?? 20) * 1048576;
        $othermax = (int) ($config->othermaxmb ?? 30) * 1048576;

        $result = policy::evaluate($contentmime, $extmime, $size, $allowed, $audiomax, $othermax);
        if ($result === null) {
            return self::SCAN_RESULT_OK;
        }
        return $this->reject($result, $filename, $contentmime, $extmime, $size);
    }

    /**
     * Records a rejection: message for the user, scanning notice and log event.
     *
     * @param array $result ['reason' => string id, 'a' => placeholders].
     * @param string $filename Original file name.
     * @param string $contentmime Mimetype detected from the content ('' if unknown).
     * @param string $extmime Mimetype derived from the extension.
     * @param int $size File size in bytes.
     * @return int SCAN_RESULT_FOUND.
     */
    protected function reject(array $result, string $filename, string $contentmime, string $extmime, int $size): int {
        $this->rejection = $result;
        $this->set_scanning_notice("{$result['reason']}: {$filename} ({$contentmime}, {$size} bytes)");

        event\file_rejected::create([
            'context' => $this->upload_context(),
            'other' => [
                'filename' => $filename,
                'contentmime' => $contentmime,
                'extensionmime' => $extmime,
                'filesize' => $size,
                'reason' => $result['reason'],
            ],
        ])->trigger();

        return self::SCAN_RESULT_FOUND;
    }

    /**
     * Context of the page the upload comes from (course, activity...), or system if none was set.
     *
     * Reads the page's internal context because $PAGE->context warns, or throws in AJAX
     * scripts with developer debugging, when no context has been set.
     *
     * @return \context
     */
    protected function upload_context(): \context {
        global $PAGE;
        $context = (fn() => $this->_context)->call($PAGE);
        return $context ?? \context_system::instance();
    }

    /**
     * Message shown to the user instead of the generic "virus found".
     *
     * @return array
     */
    public function get_virus_found_message() {
        if ($this->rejection === null) {
            return parent::get_virus_found_message();
        }
        return [
            'string' => $this->rejection['reason'],
            'component' => 'antivirus_mediapolicy',
            'placeholders' => $this->rejection['a'],
        ];
    }
}
