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

namespace mod_opencast\local;

defined('MOODLE_INTERNAL') || die();

class paella_player {

    public function view($opencastid, $seriesid = null) {
        global $PAGE;

        $api = apibridge::get_instance();
        $response = $api->get_episode_json($opencastid, $seriesid);

        if (!property_exists($response, 'episode')) {
            return;
        }

        $PAGE->requires->js_call_amd('mod_opencast/config');
        $PAGE->requires->js_call_amd('mod_opencast/opencast_player', 'init',
                array($response->episode));
    }
}