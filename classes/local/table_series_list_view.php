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
 * Table of series
 * @package    mod_opencast
 * @copyright  2021 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_opencast\local;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/tablelib.php');

/**
 * Table of series
 * @package    mod_opencast
 * @copyright  2021 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class table_series_list_view extends \flexible_table {

    public function __construct() {
        parent::__construct('mod-opencast-series-list-view');
    }

    public function set_data($episodes) {
        $this->define_columns(['title', 'duration', 'date', 'video-icons']);
        $this->define_headers([
            get_string('title', 'mod_opencast'),
            get_string('duration', 'mod_opencast'),
            get_string('date', 'mod_opencast'),
            ''
        ]);
        $this->setup();
        foreach ($episodes as $episode) {
            $this->add_data([
                $this->format_title($episode),
                $episode->duration,
                $episode->date,
                $this->format_video_icons($episode),
            ]);
        }
    }

    private function format_video_icons($episode) {
        global $OUTPUT;

        $output = '';
        if ($episode->haspresenter) {
            $output .= $OUTPUT->pix_icon('i/user', '');
        }
        if ($episode->haspresentation) {
            $output .= $OUTPUT->pix_icon('i/tv', '', 'mod_opencast');
        }
        return $output;
    }

    private function format_title($episode): string {
        return \html_writer::link($episode->link, $episode->title);
    }

}
