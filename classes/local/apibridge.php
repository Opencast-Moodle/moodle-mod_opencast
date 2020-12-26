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
 * API-bridge for mod_opencast. Contain all the function, which uses the external API.
 *
 * @package    mod_opencast
 * @copyright  2020 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_opencast\local;

use tool_opencast\local\api;

defined('MOODLE_INTERNAL') || die();

class apibridge {

    /**
     * Get an instance of an object of this class. Create as a singleton.
     *
     * @staticvar \apibridge $apibridge
     *
     * @param boolean $forcenewinstance true, when a new instance should be created.
     *
     * @return apibridge
     */
    public static function get_instance($forcenewinstance = false) {
        static $apibridge;

        if (isset($apibridge) && !$forcenewinstance) {
            return $apibridge;
        }

        $apibridge = new apibridge();

        return $apibridge;
    }

    public function get_episode_json($episodeid) {
        $result = new \stdClass();
        $result->error = 0;

        $api = new api();
        $response = $api->oc_get("/search/episode.json?id=$episodeid&sign=true");

        if ($api->get_http_code() != 200) {
            $result->error = $api->get_http_code();
            return $result;
        }

        $response = json_decode($response);
        if (!$response || !property_exists($response->{'search-results'}, 'result')) {
            return $result;
        }

        $result->episode = $response->{'search-results'}->result;
        return $result;
    }
}