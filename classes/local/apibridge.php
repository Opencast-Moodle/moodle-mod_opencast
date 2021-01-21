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

    public function get_episode_json($episodeid, $seriesid = null) {
        $result = new \stdClass();
        $result->error = 0;

        $api = new api();
        $resource = "/search/episode.json?id=$episodeid&sign=true";
        if ($seriesid) {
            $resource .= "&sid=$seriesid";
        }
        var_dump($resource);
        $response = $api->oc_get($resource);

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

    public function get_episodes_in_series($seriesid) {
        $api = new api();
        $resource = "/api/events?filter=is_part_of:$seriesid&withpublications=true&sort=start_date:DESC,title:ASC";
        $response = $api->oc_get($resource);

        if ($api->get_http_code() != 200) {
            return false;
        }

        $response = json_decode($response);
        if (!$response) {
            return false;
        }

        return $response;
    }


    public function get_series($seriesid) {
        $api = new api();
        $resource = "/api/series/$seriesid";
        $response = $api->oc_get($resource);

        if ($api->get_http_code() != 200) {
            return false;
        }

        $response = json_decode($response);
        if (!$response) {
            return false;
        }
        return $response;
    }


    /**
     * Finds out, if a opencastid specifies an episode, a series, or nothing.
     * @param string $id opencastid
     * @return int the type {@link opencasttype}
     */
    public function find_opencast_type_for_id($id) {
        $api = new api();
        $api->oc_get("/api/events/$id");
        if ($api->get_http_code() == 200) {
            return opencasttype::EPISODE;
        }

        $api->oc_get("/api/series/$id");
        if ($api->get_http_code() == 200) {
            return opencasttype::SERIES;
        }

        return opencasttype::UNDEFINED;
    }
}
