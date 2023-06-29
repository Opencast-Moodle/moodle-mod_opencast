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

$string['allvideos'] = 'All videos';
$string['allowdownload'] = 'Allow students to download the video(s)';

$string['date'] = 'Date';
$string['downloadvideo'] = 'Download video';
$string['duration'] = 'Duration';

$string['episode'] = 'Opencast episode';
$string['errorfetchingvideo'] = 'There was a problem fetching the video.';

$string['gridview'] = 'View as grid';

$string['listview'] = 'View as list';

$string['manualocid'] = 'Directly enter the Opencast ID of the series/episode';
$string['modulename'] = 'Video (Opencast)';
$string['modulenameplural'] = 'Videos (Opencast)';

$string['ocinstance'] = 'Opencast instance';
$string['opencastname'] = 'Opencast Video Provider: {$a}';
$string['opencast:addinstance'] = 'Add a new Video (Opencast) instance';
$string['opencastid'] = 'Opencast ID';
$string['opencastidnotrecognized'] = 'This ID is neither recognized as a series nor a video.';

$string['pluginname'] = 'Opencast Video Provider';
$string['pluginadministration'] = 'Opencast Video Provider administration';
$string['privacy:metadata'] = 'Opencast Activities are just a way to show Opencast videos inside moodle. They do not store any user related data.';

$string['settings:api-channel'] = 'Opencast Channel';
$string['settings:download_header'] = 'Student Download Configuration';
$string['settings:download-channel'] = 'Opencast Download Channel';
$string['settings:download-channel_desc'] = 'Opencast publication channel from which the videos are served when downloading them.';
$string['settings:download-default'] = 'Allow download by default';
$string['settings:download-default_desc'] = 'If activated, the checkbox for allowing downloads in activity forms is checked by default.';
$string['settings:configurl'] = 'URL to Paella config.json';
$string['settings:configurl_desc'] = 'URL of the config.json used by Paella Player. Can either be a absolute URL or a URL relative to the wwwroot.';
$string['settings:global_download'] = 'Force student download';
$string['settings:global_download_desc'] = 'Allow globally that students can download videos. Teachers cannot overwrite this setting.';
$string['settings:themeurl'] = 'URL to Paella theme.json';
$string['settings:themeurl_desc'] = 'URL of the theme.json used by Paella Player. Can either be a absolute URL or a URL relative to the wwwroot.';

$string['series'] = 'Opencast series';
$string['seriesisempty'] = 'This series is currently empty.';

$string['title'] = 'Title';
// Strings for upload feature.
$string['dnduploadvideofile'] = 'Upload video file to Opencast';
$string['uploadtitledisplay'] = 'Upload video:';
$string['uploaddefaultintrodisplay'] = 'This is an opencast activity module for uploading a video.';
$string['uploadlandinginfo'] = 'You are about to upload video to Opencast, please make sure the required information are entered.';
$string['uploadnotallowed'] = 'Performing this action is not allowed';
$string['uploadformtitle'] = 'Upload the video to Opencast';
$string['uploadform_general_header'] = 'General Settings';
$string['uploadform_ocinstancesselect'] = 'Opencast Instanance';
$string['uploadform_seriessselect'] = 'Series';
$string['uploadform_metadata_header'] = 'Required Event Metadata';
$string['uploadform_flavor_label'] = 'Use the video for the flavor of:';
$string['uploadform_flavor_presenter'] = 'Presenter Video';
$string['uploadform_flavor_presentation'] = 'Presentation Video';
$string['uploadform_uploadexplaination'] = 'This is a partial and short form to upload video, in order to add more metadata and other informations please use the Opencast Video plugin.';
$string['uploadform_requirednotice'] = '<b>{$a}Required</b>: all inputs in this form are mandetory to fill out.';
$string['uploadmissingfile'] = 'Because of missing file, this module is no longer valid and is now deleted, please try adding another one.';
$string['uploadsaved'] = 'Video upload successful. The video is scheduled to be transferred to Opencast now, for more info please go to <a target="_blank" href="{$a}">Opencast Videos</a>';
$string['defaultuploadedvideotitle'] = 'Uploaded video';
$string['uploadinprogress'] = 'Uploading video ({$a}) is in progress, please try again later.';
$string['uploadedvideoisbeingprocesses'] = 'This video ({$a}) is already uploaded and is being processed by Opencast, please wait!';
$string['uploadjobmissing'] = 'There was an error fetching upload data for this video, please try uploading a new one. Due to insufficient data this module is deleted.';
