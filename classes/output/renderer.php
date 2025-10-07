<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * mod_opencast renderer
 * @package    mod_opencast
 * @copyright  2021 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_opencast\output;

use html_writer;
use moodle_url;
use plugin_renderer_base;
use stdClass;
use MoodleQuickForm;
use mod_opencast\local\advancedupload_field;

/**
 * mod_opencast renderer
 * @package    mod_opencast
 * @copyright  2021 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Renders a toggle icon for toggling the Series List/Grid-View.
     * @param bool $listviewactive whether the listview is active.
     * @return string
     */
    public function render_listview_toggle($listviewactive) {

        $o = html_writer::start_div('mt-3 mb-1 w-100 text-right icon-size-4');

        if ($listviewactive) {
            $icon = $this->output->pix_icon('i/grid', get_string('gridview', 'mod_opencast'), 'mod_opencast');
        } else {
            $icon = $this->output->pix_icon('i/list', get_string('listview', 'mod_opencast'), 'mod_opencast');
        }

        $o .= html_writer::link(
            new moodle_url($this->page->url, ['list' => $listviewactive ? '0' : '1', 'sesskey' => sesskey()]),
            $icon
        );

        $o .= html_writer::end_div();
        return $o;
    }

    /**
     * Renders the header for the advanced upload mode form.
     *
     * Adds an explanation and a link to switch back to the simple upload mode at the top of the advanced upload form.
     *
     * @param MoodleQuickForm $mform The Moodle form object.
     * @param int $cmid The course module ID.
     * @return void
     */
    public function render_advanced_mode_header(MoodleQuickForm &$mform, int $cmid): void {
        $simplemodeurl = new moodle_url('/mod/opencast/uploadvideo.php', ['cmid' => $cmid]);
        $linkattr['href'] = $simplemodeurl->out(false);
        $simplemodelink = html_writer::start_tag('a', $linkattr);
        $simplemodelink .= html_writer::tag('span',
            get_string('uploadform_simple_page_title', 'mod_opencast'), ['class' => 'mr-1']);
        $simplemodelink .= $this->output->pix_icon('i/share', get_string('uploadform_simple_page_title', 'mod_opencast'));
        $simplemodelink .= html_writer::end_tag('a');
        $explanation = html_writer::tag('p',
            get_string('uploadform_advanced_uploadexplaination', 'mod_opencast', $simplemodelink));
        $mform->addElement('html', $explanation);
    }

    /**
     * Renders the navigation tabs for the advanced upload form.
     *
     * Generates a Bootstrap nav-tabs element for the provided tab names and adds it to the form.
     * The first tab is set as active by default.
     *
     * @param MoodleQuickForm $mform The Moodle form object.
     * @param array $tabs Array of tab names to display.
     * @return void
     */
    public function render_advanced_upload_form_tabs_navigation(MoodleQuickForm &$mform, array $tabs): void {
        $ul = html_writer::start_tag('ul',
            [
                'class' => 'nav nav-tabs mb-3',
                'id' => 'mod_opencast_advanced_upload_tabs',
                'role' => 'tablist',
            ]
        );

        foreach ($tabs as $index => $tab) {
            $liclasses = ['nav-item'];
            $aclasses = ['nav-link'];
            $ariaselected = 'false';
            // The first tab activation.
            if ($index == 0) {
                $liclasses[] = 'active';
                $aclasses[] = 'active';
                $ariaselected = 'true';
            }
            $li = html_writer::start_tag('li',
                [
                    'class' => implode(' ', $liclasses),
                ]
            );

            $a = html_writer::start_tag('a',
                [
                    'class' => implode(' ', $aclasses),
                    'id' => "{$tab}-tab",
                    'data-toggle' => "tab",
                    'data-target' => "#{$tab}",
                    'type' => "button",
                    'role' => "tab",
                    'aria-controls' => $tab,
                    'aria-selected' => $ariaselected,
                ]
            );

            $stringid = 'uploadform_tab_' . $tab;
            $span = html_writer::tag('span', get_string($stringid, 'mod_opencast'));

            $a .= $span;
            $a .= html_writer::end_tag('a');

            $li .= $a;
            $li .= html_writer::end_tag('li');

            $ul .= $li;
        }

        $ul .= html_writer::end_tag('ul');
        $mform->addElement('html', $ul);
    }

    /**
     * Starts the tab content container for the advanced upload form.
     *
     * Adds the opening HTML for the tab content section, optionally with a card style.
     *
     * @param MoodleQuickForm $mform The Moodle form object.
     * @param bool $withcard Whether to include the 'card' class for styling.
     * @return void
     */
    public function tab_content_start(MoodleQuickForm &$mform, bool $withcard = false): void {
        $classes = ['tab-content', 'mb-3'];
        if ($withcard) {
            $classes[] = 'card';
        }
        $attributes = [
            'class' => implode(' ', $classes),
        ];
        $tabcontentstart = html_writer::start_tag('div', $attributes);
        $mform->addElement('html', $tabcontentstart);
    }

    /**
     * Ends the tab content container for the advanced upload form.
     *
     * Adds the closing HTML tag for the tab content section to the form.
     *
     * @param MoodleQuickForm $mform The Moodle form object.
     * @return void
     */
    public function tab_content_end(MoodleQuickForm &$mform): void {
        $mform->addElement('html', html_writer::end_tag('div'));
    }

    /**
     * Starts a tab pane container for a specific tab in the advanced upload form.
     *
     * Adds the opening HTML for a tab pane, setting its ID, active state, and optional card styling.
     *
     * @param MoodleQuickForm $mform The Moodle form object.
     * @param string $id The unique ID for the tab pane.
     * @param bool $active Whether this tab pane should be marked as active.
     * @param bool $withcard Whether to include the 'card-body' class for styling.
     * @return void
     */
    public function tab_pane_start(MoodleQuickForm &$mform, string $id, bool $active = false, bool $withcard = false): void {
        $classes = ['tab-pane fade'];
        if ($active) {
            $classes[] = 'show active';
        }
        if ($withcard) {
            $classes[] = 'card-body';
        }
        $attributes = [
            'id' => $id,
            'class' => implode(' ', $classes),
            'role' => 'tabpanel',
            'style' => 'min-height: 250px;',
            'aria-labelledby' => "{$id}-tab",
        ];
        $tabpanestart = html_writer::start_tag('div', $attributes);
        $mform->addElement('html', $tabpanestart);
    }

    /**
     * Ends a tab pane container for a specific tab in the advanced upload form.
     *
     * Adds the closing HTML tag for the tab pane section to the form.
     *
     * @param MoodleQuickForm $mform The Moodle form object.
     * @return void
     */
    public function tab_pane_end(MoodleQuickForm &$mform): void {
        $mform->addElement('html', html_writer::end_tag('div'));
    }

    /**
     * Renders a form field for a tab in the advanced upload form.
     *
     * Adds the specified advancedupload_field to the Moodle form, sets default values, required rules,
     * advanced settings, and handles conditional display based on parent fields. Recursively renders any subfields.
     *
     * @param MoodleQuickForm $mform The Moodle form object.
     * @param advancedupload_field $field The field to render.
     * @param string|null $parentid Optional parent field ID for conditional display.
     * @return void
     */
    public function render_advanced_upload_form_tab_field(
        MoodleQuickForm &$mform, advancedupload_field $field, ?string $parentid = null
    ): void {
        global $CFG, $PAGE;
        $mainrenderer = $PAGE->get_renderer('block_opencast');
        $attributes = $field->get_attributes();
        $setadvanced = false;
        if (isset($attributes['set_advanced'])) {
            $setadvanced = true;
            unset($attributes['set_advanced']);
        }
        if ($field->get_datatype() === 'chunkupload') {
            MoodleQuickForm::registerElementType('chunkupload',
                "$CFG->dirroot/local/chunkupload/classes/chunkupload_form_element.php",
                'local_chunkupload\chunkupload_form_element');
        }
        $element = $mform->addElement(
            $field->get_datatype(),
            $field->get_id(),
            $field->get_label(),
            $field->get_params(),
            $attributes
        );
        if (!empty($field->get_description())) {
            $element->_helpbutton = $mainrenderer->render_help_icon_with_custom_text(
                $field->get_label(), $field->get_description()
            );
        }
        if (!empty($field->get_default())) {
            $mform->setDefault($field->get_id(), $field->get_default());
        }
        if ($field->is_required()) {
            $mform->addRule($field->get_id(), get_string('required'), 'required');
        }
        if ($setadvanced) {
            $mform->setAdvanced($field->get_id());
        }
        if (!empty($parentid)) {
            $mform->hideif($field->get_id(), $parentid, 'notchecked');
            $mform->disabledIf($field->get_id(), $parentid, 'notchecked');
        }

        $type = PARAM_RAW;
        switch ($field->get_datatype()) {
            case 'checkbox':
            case 'number':
                $type = PARAM_INT;
                break;
            default:
                $type = PARAM_TEXT;
                break;
        }

        $mform->setType($field->get_id(), $type);

        if ($children = $field->get_subfields()) {
            foreach ($children as $child) {
                $this->render_advanced_upload_form_tab_field($mform, $child, $field->get_id());
            }
        }
    }

    /**
     * Renders a description for a tab in the advanced upload form.
     *
     * Adds the provided description as a paragraph element to the Moodle form.
     *
     * @param MoodleQuickForm $mform The Moodle form object.
     * @param string $description The description text to display.
     * @return void
     */
    public function render_tab_description(MoodleQuickForm &$mform, string $description): void {
        $tabdescription = html_writer::tag('p', $description, ['class' => 'pb-2']);
        $mform->addElement('html', $tabdescription);
    }
}
