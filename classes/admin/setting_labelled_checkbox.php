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

namespace antivirus_mediapolicy\admin;

/**
 * Checkbox setting with a text next to the box instead of the "Default: No" hint.
 *
 * @package    antivirus_mediapolicy
 * @copyright  2026 Awakelab
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_labelled_checkbox extends \admin_setting_configcheckbox {
    /** @var string Text shown next to the box. */
    protected $label;

    /**
     * Constructor.
     *
     * @param string $name Setting name, 'plugin/setting'.
     * @param string $visiblename Localised name.
     * @param string $description Localised description.
     * @param string|int $defaultsetting Default value.
     * @param string $label Localised text next to the box.
     */
    public function __construct($name, $visiblename, $description, $defaultsetting, string $label) {
        parent::__construct($name, $visiblename, $description, $defaultsetting);
        $this->label = $label;
    }

    /**
     * Returns the HTML of the setting.
     *
     * @param mixed $data Current value.
     * @param string $query Admin search query.
     * @return string
     */
    public function output_html($data, $query = '') {
        $attributes = [
            'type' => 'checkbox',
            'name' => $this->get_full_name(),
            'value' => $this->yes,
            'id' => $this->get_id(),
            'class' => 'form-check-input',
        ];
        if ((string) $data === $this->yes) {
            $attributes['checked'] = 'checked';
        }
        if ($this->is_readonly()) {
            $attributes['disabled'] = 'disabled';
        }
        $element = \html_writer::div(
            \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $this->get_full_name(), 'value' => $this->no])
                . \html_writer::empty_tag('input', $attributes)
                . \html_writer::label($this->label, $this->get_id(), false, ['class' => 'form-check-label']),
            'form-check'
        );
        return format_admin_setting($this, $this->visiblename, $element, $this->description, true, '', null, $query);
    }
}
