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

// Let codechecker ignore some sniffs for this file as it is partly ordered semantically instead of alphabetically.
// phpcs:disable moodle.Files.LangFilesOrdering.UnexpectedComment
// phpcs:disable moodle.Files.LangFilesOrdering.IncorrectOrder

defined('MOODLE_INTERNAL') || die();

$string['advancedsettings'] = 'Advanced settings';
$string['allowdownload'] = 'Allow students to download the video(s)';
$string['allvideos'] = 'All videos';

$string['captions_generator_type_auto'] = 'Auto generated';
$string['captions_generator_type_manual'] = 'Manually generated';

$string['date'] = 'Date';
$string['defaultuploadedvideotitle'] = 'Uploaded video';
$string['dnduploadvideofile'] = 'Upload video file to Opencast';
$string['downloadvideo'] = 'Download video';
$string['duration'] = 'Duration';

$string['episode'] = 'Opencast episode';
$string['erroremptystreamsources'] = 'There is no video source available. Please contact your system administrator.';
$string['errorfetchingvideo'] = 'There was a problem fetching the video.';
$string['errorvideonotavailable'] = 'Unable to find the video! <br />Please contact your system administrator.';
$string['errorvideonotready'] = 'The video is either not ready yet or cannot be properly accessed from the source!<br />Please try again later.';
$string['gridview'] = 'View as grid';

$string['listview'] = 'View as list';

$string['manualocid'] = 'Directly enter the Opencast ID of the series/episode';
$string['modulename'] = 'Video (Opencast)';
$string['modulename_help'] = '<p>The Video (Opencast) module is used to display videos or series from a connected Opencast platform.</p><p>In most cases, it is easier not to create the activity directly but to do it via the block "Opencast Videos" instead.</p>';
$string['modulenameplural'] = 'Videos (Opencast)';

$string['ocinstance'] = 'Opencast instance';
$string['opencast:addinstance'] = 'Add a new Video (Opencast) instance';
$string['opencastid'] = 'Opencast ID';
$string['opencastidnotrecognized'] = 'This ID is neither recognized as a series nor a video.';
$string['opencastname'] = 'Opencast Video Provider: {$a}';
$string['opencastnotreachable'] = 'Opencast is currently not reachable, please try again later.';

$string['pluginadministration'] = 'Opencast Video Provider administration';
$string['pluginname'] = 'Opencast Video Provider';
$string['privacy:metadata'] = 'Opencast Activities are just a way to show Opencast videos inside moodle. They do not store any user related data.';

$string['series'] = 'Opencast series';
$string['seriesisempty'] = 'This series is currently empty.';
$string['settings:api-channel'] = 'Opencast Channel';
$string['settings:configurl'] = 'URL to Paella config.json';
$string['settings:configurl_desc'] = 'URL of the config.json used by Paella Player. Can either be a absolute URL or a URL relative to the wwwroot.';
$string['settings:download-channel'] = 'Opencast Download Channel';
$string['settings:download-channel_desc'] = 'Opencast publication channel from which the videos are served when downloading them.';
$string['settings:download-default'] = 'Allow download by default';
$string['settings:download-default_desc'] = 'If activated, the checkbox for allowing downloads in activity forms is checked by default.';
$string['settings:download_header'] = 'Student Download Settings';
$string['settings:general_header'] = 'General Settings';
$string['settings:global_download'] = 'Force student download';
$string['settings:global_download_desc'] = 'Allow globally that students can download videos. Teachers cannot overwrite this setting.';
$string['settings:themeurl'] = 'URL to Paella theme.json';
$string['settings:themeurl_desc'] = 'URL of the theme.json used by Paella Player. Can either be a absolute URL or a URL relative to the wwwroot.';

$string['settings:upload_inline_processing'] = 'Enable Inline Processing';
$string['settings:upload_inline_processing_desc'] = 'With this option, the processing settings from the upload workflow configuration panel will be offered to the user in the upload form.';
$string['settings:upload_inline_visibility'] = 'Enable Inline Visibility';
$string['settings:upload_inline_visibility_desc'] = 'With this option, an inline visibility toggle will be displayed on the upload page.';
$string['settings:upload_enable_advanced_mode'] = 'Enable Advanced Mode';
$string['settings:upload_enable_advanced_mode_desc'] = 'With this option, advanced upload link will be displayed to the user in the upload form.';
$string['settings:upload_header'] = 'Upload Settings';
$string['settings:upload_main'] = 'Main';
$string['settings:upload_main_heading'] = 'Main upload tab configuration';
$string['settings:upload_main_desc'] = 'In this section you are able to configure the setting for the very first upload page.';
$string['settings:upload_metadata'] = 'Metadata';
$string['settings:upload_metadata_activate'] = 'Activate Metadata tab in Advanced Mode';
$string['settings:upload_metadata_activate_desc'] = 'With this option, the metadata tab will be activated and offered in Advanced mode for further configuration.';
$string['settings:upload_metadata_heading'] = 'Metadata tab configuration ';
$string['settings:upload_metadata_desc'] = 'In this section you are able to configure the settings for the metadata upload page.';
$string['settings:upload_metadata_list'] = 'Selected metadata fields';
$string['settings:upload_metadata_list_desc'] = 'With this selection option, you can filter which event metadata fields to be displayed to the user alongside the title and those fields labels as required.';
$string['settings:upload_presentation'] = 'Presentation';
$string['settings:upload_presentation_activate'] = 'Activate Presentation Upload tab in Advanced Mode';
$string['settings:upload_presentation_activate_desc'] = 'With this option, the presentation upload tab will be activated and offered in Advanced mode for in order for uploading a presentation video alongside the main video.';
$string['settings:upload_presentation_heading'] = 'Presentation tab configuration ';
$string['settings:upload_presentation_desc'] = 'In this section you are able to configure the settings for the presentation upload page.';
$string['settings:upload_visibility'] = 'Visibility';
$string['settings:upload_visibility_activate'] = 'Activate Visibility tab in Advanced Mode';
$string['settings:upload_visibility_activate_desc'] = 'With this option, the visibility settings tab will be activated and offered in Advanced mode for further configuration.';
$string['settings:upload_visibility_heading'] = 'Visibility tab configuration ';
$string['settings:upload_visibility_desc'] = 'In this section you are able to configure the settings for the visibility upload page.';
$string['settings:upload_visibility_default'] = 'Default visibility';
$string['settings:upload_visibility_default_desc'] = 'Choose the default visibility setting for new uploads.';
$string['settings:upload_subtitle'] = 'Subtitle';
$string['settings:upload_subtitle_activate'] = 'Activate Subtitle tab in Advanced Mode';
$string['settings:upload_subtitle_activate_desc'] = 'With this option, the subtitle upload tab will be activated and offered in Advanced mode for further configuration.';
$string['settings:upload_subtitle_activate_desc_disabled'] = 'In order to offer subtitle uploads, the corresponding settings in "Opencast Videos" plugin must be configured first.';
$string['settings:upload_subtitle_heading'] = 'Subtitle tab configuration ';
$string['settings:upload_subtitle_desc'] = 'In this section you are able to configure the settings for the subtitle upload page.';
$string['settings:upload_subtitle_langlist'] = 'Subtitle allowed languages';
$string['settings:upload_subtitle_langlist_desc'] = 'With this selection option, you can filter which subtitle languages to be displayed to the user, if nothing is selected all languages will be offered.';
$string['settings:upload_processing'] = 'Processing';
$string['settings:upload_processing_activate'] = 'Activate Processing tab in Advanced Mode';
$string['settings:upload_processing_activate_desc'] = 'With this option, the processing upload tab will be activated and offered in Advanced mode for further configuration.';
$string['settings:upload_processing_activate_desc_disabled'] = 'This option is deactivated, due to it not being configured in the main plugin.';
$string['settings:upload_processing_heading'] = 'Processing tab configuration ';
$string['settings:upload_processing_desc'] = 'In this section you are able to configure the settings for the processing upload page.';

$string['sortseriesby'] = 'Order videos by';
$string['sortseriesby_help'] = 'Only affects series';
$string['title'] = 'Title';
$string['uploaddate'] = 'Upload date';
$string['uploaddefaultintrodisplay'] = 'This is an opencast activity module for uploading a video.';
$string['uploadedvideoisbeingprocesses'] = 'This video ({$a}) is already uploaded and is being processed by Opencast, please wait!';
$string['uploadform_advanced_mode_desc'] = 'If you are looking for more upload options: {$a}';
$string['uploadform_advanced_mode_link_text'] = 'Go to advanced mode page';
$string['uploadform_advanced_mode_title'] = 'Advanced Mode';
$string['uploadform_flavor_label'] = 'Use the video for the flavor of:';
$string['uploadform_inplace_edit_checkbox_false_hint'] = 'Not accepted';
$string['uploadform_inplace_edit_checkbox_hint'] = 'Make changes';
$string['uploadform_inplace_edit_checkbox_label'] = 'Edit';
$string['uploadform_inplace_edit_checkbox_true_hint'] = 'Accepted';
$string['uploadform_inplace_edit_text_hint'] = 'Make changes';
$string['uploadform_inplace_edit_text_label'] = 'Edit';
$string['uploadform_inplace_edit_select_hint'] = 'Make changes';
$string['uploadform_inplace_edit_select_label'] = 'Edit';
$string['uploadform_inplace_edit_autocomplete_hint'] = 'Make changes';
$string['uploadform_inplace_edit_autocomplete_label'] = 'Edit';
$string['uploadform_inplace_edit_visibility_hint'] = 'Make changes';
$string['uploadform_inplace_edit_visibility_label'] = 'Edit';
$string['uploadform_inplace_edit_visibility_visible'] = 'Make it visible!';
$string['uploadform_inplace_edit_visibility_hidden'] = 'Make it hidden!';
$string['uploadform_ocinstancesselect'] = 'Opencast Instance';
$string['uploadform_visibility'] = 'Visibility';
$string['uploadform_seriessselect'] = 'Series';
$string['uploadform_submit'] = 'Upload now!';
$string['uploadform_subtitle_field_enabled'] = 'Enable upload field';
$string['uploadform_simple_uploadexplaination'] = 'Review and adjust the essential upload settings before submitting your video.';
$string['uploadform_advanced_uploadexplaination'] = 'Use this page to configure detailed upload options across multiple tabs before uploading your video.<br> Or you can go back to the {$a}';
$string['uploadform_simple_header'] = 'Upload your video to Opencast';
$string['uploadform_simple_page_title'] = 'Quick Video Upload';
$string['uploadinprogress'] = 'Uploading video ({$a}) is in progress, please try again later.';
$string['uploadjobmissing'] = 'There was an error fetching upload data for this video, please try uploading a new one. Due to insufficient data this module is deleted.';
$string['uploadlandinginfo'] = 'You are about to upload video to Opencast, please make sure the required information are entered.';
$string['uploadmissingfile'] = 'Because of missing file, this module is no longer valid and is now deleted, please try adding another one.';
$string['uploadmismatchedocinstanceids'] = 'The selected Opencast instance does not match the expected data. Please check your selection and try again.';
$string['uploadnotallowed'] = 'Performing this action is not allowed';
$string['uploadsaved'] = 'Video upload successful. The video is scheduled to be transferred to Opencast now, for more info please go to <a target="_blank" href="{$a}">Opencast Videos</a>';
$string['uploadtitledisplay'] = 'Upload video:';
$string['videotitle'] = 'Video title';
$string['uploadform_tab_metadata'] = 'Video Metadata';
$string['uploadform_tab_presentation'] = 'Presentation Upload';
$string['uploadform_tab_visibility'] = 'Video Visibility';
$string['uploadform_tab_processing'] = 'Processing Settings';
$string['uploadform_tab_subtitle'] = 'Subtitles Upload';
$string['uploadform_presentation_upload'] = 'Presentation file';
$string['uploaddeactivatedadvancedmode'] = 'Advanced mode is deactivated for this Opencast instance, please contact your system administrator.';
