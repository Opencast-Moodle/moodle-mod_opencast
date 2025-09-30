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
 * Steps definitions related with the mod_opencast plugin.
 *
 * @package    mod_opencast
 * @copyright  2025 Farbod Zamani Boroujeni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use tool_opencast\setting_default_manager;
use tool_opencast\seriesmapping;
use Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Exception\DriverException as DriverException,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

/**
 * Steps definitions related with the mod_opencast plugin.
 *
 * @package    mod_opencast
 * @copyright  2025 Farbod Zamani Boroujeni
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_opencast extends behat_base {
    /**
     * Drag and drop a file from fixtures into a course section.
     *
     * Example: When I upload file "example.txt" into course "General" section
     *
     * @When /^I drag and drop video "(.*)" file into course General section$/
     * @param string $filepath the file to upload (must exist in tests/fixtures)
     */
    public function i_drag_and_drop_video_file_into_course_general_section($filepath) {
        global $CFG, $DB;

        // Get the course by its name.
        $courses = core_course_category::search_courses(['search' => 'Course 1']);
        $courseobj = reset($courses);
        $course = $DB->get_record('course', ['id' => $courseobj->id], '*', MUST_EXIST);

        // Create a series mapping for the course.
        $mapping = new seriesmapping();
        $mapping->set('courseid', $course->id);
        $mapping->set('series', '1234-1234-1234-1234-1234');
        $mapping->set('isdefault', '1');
        $mapping->set('ocinstanceid', 1);
        $mapping->create();

        // Get the role of teachers.
        $role = $DB->get_record('role', ['shortname' => 'editingteacher']);
        // Get the course context.
        $coursecontext = context_course::instance($course->id);
        // Get the teachers based on their role in the course context.
        $teachers = get_role_users($role->id, $coursecontext);

        // Use the first teacher we find as the user.
        $user = reset($teachers);

        // The section we want is the first one known as General.
        $section = 0;

        // Preparing the file upload which mimics the drag and drop upload.
        $context = \context_user::instance($user->id);
        $draftitemid = file_get_unused_draft_itemid();
        $record = new \stdClass();
        $record->filearea = 'draft';
        $record->component = 'user';
        $record->filepath = '/';
        $record->itemid   = $draftitemid;
        $record->license  = $CFG->sitedefaultlicense;
        $record->author   = fullname($user);
        $record->filename = 'test.mp4';
        $record->contextid = $context->id;
        $record->userid    = $user->id;

        // Upload the file as draft.
        $fs = get_file_storage();
        $filefullpath = $CFG->dirroot . '/' . $filepath;
        $storedfile = $fs->create_file_from_pathname($record, $filefullpath);

        $filesize = filesize($filefullpath);

        // Log the event of adding a draft file.
        $logevent = \core\event\draft_file_added::create([
                'objectid' => $storedfile->get_id(),
                'context' => $context,
                'other' => [
                        'itemid' => $record->itemid,
                        'filename' => $record->filename,
                        'filesize' => $filesize,
                        'filepath' => $record->filepath,
                        'contenthash' => $storedfile->get_contenthash(),
                ],
        ]);
        $logevent->trigger();

        // Add the opencast activity module to the course.
        require_once($CFG->dirroot.'/course/modlib.php');
        list($module, $context, $cw, $cm, $data) = prepare_new_moduleinfo_data($course, 'opencast', $section);

        $data->coursemodule = $data->id = add_course_module($data);
        $moduledata = new \stdClass();
        $moduledata->type = 'Files';
        $moduledata->course = $course;
        $moduledata->draftitemid = $draftitemid;
        $moduledata->coursemodule = $data->id;
        $moduledata->displayname = 'test.mp4';
        $instanceid = plugin_callback('mod', 'opencast', 'dndupload', 'handle', [$moduledata], 'invalidfunction');
        $visible = get_fast_modinfo($course)->get_section_info(0)->visible;
        $DB->set_field('course_modules', 'instance', $instanceid, ['id' => $instanceid]);
        \course_modinfo::purge_course_module_cache($course->id, $data->coursemodule);
        rebuild_course_cache($course->id, true, true);
        $sectionid = course_add_cm_to_section($course, $data->coursemodule, $section);
        set_coursemodule_visible($data->coursemodule, $visible);
        $info = get_fast_modinfo($course);
        $mod = $info->get_cm($data->id);
        $event = \core\event\course_module_created::create_from_cm($mod);
        $event->trigger();
    }

    /**
     * Behat step to upload a subtitle file to a subtitle.
     * @When /^I upload "(.*)" subtitle file for "(.*)"$/
     * @param string $filepath path to the file to upload
     * @param string $subtitleid the transcription language id
     */
    public function i_upload_subtitle_file_for($filepath, $subtitleid) {
        global $CFG;
        $data = new TableNode([]);
        $exception = new ExpectationException('Filepicker element for subtitle "' . $subtitleid . '" can not be found',
            $this->getSession());
        $filemanagernode = $this->find(
            'xpath',
            "//div[contains(@class, 'mod-opencast-subtitle-filepicker-{$subtitleid}')] " .
            "//*[ @data-fieldtype='filemanager' or @data-fieldtype='filepicker' ]",
            $exception
        );

        $this->execute('behat_general::i_click_on_in_the', [
            'div.fp-btn-add a, input.fp-btn-choose', 'css_element',
            $filemanagernode, 'NodeElement',
        ]);

        $this->execute('behat_general::i_click_on_in_the', [
            'Upload a file', 'link',
            'File picker', 'dialogue',
        ]);

        // Ensure all the form is ready.
        $noformexception = new ExpectationException('The upload file form is not ready', $this->getSession());
        $this->find(
                'xpath',
                "//div[contains(concat(' ', normalize-space(@class), ' '), ' container ')]" .
                "[contains(concat(' ', normalize-space(@class), ' '), ' repository_upload ')]" .
                "/descendant::div[contains(concat(' ', normalize-space(@class), ' '), ' file-picker ')]" .
                "/descendant::div[contains(concat(' ', normalize-space(@class), ' '), ' fp-content ')]" .
                "/descendant::div[contains(concat(' ', normalize-space(@class), ' '), ' fp-upload-form ')]" .
                "/descendant::form",
                $noformexception
        );
        // After this we have the elements we want to interact with.

        // Form elements to interact with.
        $file = $this->find_file('repo_upload_file');

        // Attaching specified file to the node.
        // Replace 'admin/' if it is in start of path with $CFG->admin .
        if (substr($filepath, 0, 6) === 'admin/') {
            $filepath = $CFG->dirroot . DIRECTORY_SEPARATOR . $CFG->admin .
                    DIRECTORY_SEPARATOR . substr($filepath, 6);
        }
        $filepath = str_replace('/', DIRECTORY_SEPARATOR, $filepath);
        if (!is_readable($filepath)) {
            $filepath = $CFG->dirroot . DIRECTORY_SEPARATOR . $filepath;
            if (!is_readable($filepath)) {
                throw new ExpectationException('The file to be uploaded does not exist.', $this->getSession());
            }
        }
        $file->attachFile($filepath);

        // Fill the form in Upload window.
        $datahash = $data->getRowsHash();

        // The action depends on the field type.
        foreach ($datahash as $locator => $value) {

            $field = behat_field_manager::get_form_field_from_label($locator, $this);

            // Delegates to the field class.
            $field->set_value($value);
        }

        // Submit the file.
        $submit = $this->find_button(get_string('upload', 'repository'));
        $submit->press();

        // We wait for all the JS to finish as it is performing an action.
        $this->getSession()->wait(self::get_timeout(), self::PAGE_READY_JS);
    }

    /**
     * Setup default settings for the tool
     * @Given /^I make sure the default settings for opencast plugins are set$/
     */
    public function i_make_sure_the_default_settings_for_opencast_plugins_are_set() {
        setting_default_manager::init_regirstered_defaults(1);
    }

    /**
     * Checks whether the checkbox is checked, throws exception if it is not checked.
     *
     * @Then /^the "(?P<element_string>(?:[^"]|\\")*)" checkbox should be checked$/
     * @throws ExpectationException Thrown by behat_base::find and isChecked
     * @param string $element Element we look on
     */
    public function the_element_checkbox_should_be_checked($element) {
        $element = $this->find("checkbox", $element);
        if (!$element) {
            throw new ExpectationException('Checkbox not found with the provided XPath.', $this->getSession());
        }

        if (!$element->isChecked()) {
            throw new ExpectationException('The checkbox is not checked.', $this->getSession());
        }
    }

    /**
     * Checks whether the checkbox is not checked, throws error if it is checked.
     *
     * @Then /^the "(?P<element_string>(?:[^"]|\\")*)" checkbox should not be checked$/
     * @throws ExpectationException Thrown by behat_base::find and isChecked
     * @param string $element Element we look on
     */
    public function the_element_checkbox_should_not_be_checked($element) {
        $element = $this->find("checkbox", $element);
        if (!$element) {
            throw new ExpectationException('Checkbox not found with the provided XPath.', $this->getSession());
        }

        if ($element->isChecked()) {
            throw new ExpectationException('The checkbox is checked.', $this->getSession());
        }
    }
}
