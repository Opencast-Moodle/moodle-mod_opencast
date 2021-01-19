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
use core_date;
use DateTime;

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
        $this->define_columns(['title', 'duration', 'created']);
        $this->define_headers([
            get_string('title', 'mod_opencast'),
            get_string('duration', 'mod_opencast'),
            get_string('date', 'mod_opencast')
        ]);
        $this->setup();
        foreach ($episodes as $episode) {
            $timestamp = (new DateTime($episode->created, core_date::get_user_timezone_object()))->getTimestamp();
            $episode->created = userdate($timestamp, get_string('strftimedate', 'core_langconfig'));
            $this->add_data_keyed($episode);
        }
    }

}