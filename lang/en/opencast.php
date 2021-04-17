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

/**
 * Plugin strings are defined here.
 *
 * @package     mod_opencast
 * @category    string
 * @copyright   2020 Tobias Reischmann <tobias.reischmann@wi.uni-muenster.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Opencast Episode';
$string['modulename'] = 'Opencast Episode';
$string['modulenameplural'] = 'Opencast Episodes';
$string['opencastname'] = 'Opencast Episode: {$a}';
$string['pluginadministration'] = 'Opencast Episode administration';
$string['opencast:addinstance'] = 'Add a new Opencast instance';

$string['opencastid'] = 'Opencast ID';
$string['opencastidnotrecognized'] = 'This ID is neither recognized as a series nor a video.';

$string['listview'] = 'View as list';
$string['gridview'] = 'View as grid';

$string['title'] = 'Title';
$string['duration'] = 'Duration';
$string['date'] = 'Date';

$string['settings:api-channel'] = 'Opencast Channel';
$string['settings:configurl'] = 'URL to Paella config.json';
$string['settings:configurl_desc'] = 'URL of the config.json used by Paella Player. Can either be a absolute URL or a URL relative to the wwwroot.';

$string['errorfetchingvideo'] = 'There was a problem fetching the video.';
$string['seriesisempty'] = 'This series is currently empty.';
