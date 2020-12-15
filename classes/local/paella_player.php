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

namespace mod_opencastepisode\local;

defined('MOODLE_INTERNAL') || die();

class paella_player {

    public function view($opencastid) {
        global $PAGE;

        echo "<div id=\"playerContainer\" style=\"display:block;width:100%\"></div>";

        $PAGE->requires->js_call_amd('mod_opencastepisode/config');
        $PAGE->requires->js_call_amd('mod_opencastepisode/paella');
        $res = new \stdClass();
        $res->w = 0;
        $res->h = 0;

        $api = apibridge::get_instance();
        $response = $api->get_episode_json($opencastid);

        $PAGE->requires->js_call_amd('mod_opencastepisode/opencast_player', 'init',
                array('playerContainer', $response->episode));

    }
}