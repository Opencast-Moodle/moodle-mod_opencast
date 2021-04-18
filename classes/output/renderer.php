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
defined('MOODLE_INTERNAL') || die;

use html_writer;
use moodle_url;
use plugin_renderer_base;
use stdClass;

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
}
