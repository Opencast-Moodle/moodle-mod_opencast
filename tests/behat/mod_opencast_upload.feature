@mod @mod_opencast @mod_opencast_upload
Feature: Easy And Advanced Upload feature via drag and drop in Opencast Video Provider Activity
  In order to upload videos easily or advanced to Opencast
  As a teacher
  I want to use the easy upload feature with drag and drop from the Opencast Video Provider activity

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                | idnumber |
      | teacher1 | Teacher   | 1        | teacher1@example.com | T1       |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role                   |
      | teacher1 | C1     | editingteacher         |
    And I make sure the default settings for opencast plugins are set
    And the following config values are set as admin:
      | config                        | value                                                             | plugin         |
      | apiurl_1                      | https://stable.opencast.org                                       | tool_opencast  |
      | apipassword_1                 | opencast                                                          | tool_opencast  |
      | apiusername_1                 | admin                                                             | tool_opencast  |
      | apiversion_1                  | v1.10.0                                                           | tool_opencast  |
      | ocinstances                   | [{"id":1,"name":"Default","isvisible":true,"isdefault":true}]     | tool_opencast  |
      | limituploadjobs_1             | 0                                                                 | block_opencast |
      | group_creation_1              | 0                                                                 | block_opencast |
      | group_name_1                  | Moodle_course_[COURSEID]                                          | block_opencast |
      | series_name_1                 | Course_Series_[COURSEID]                                          | block_opencast |
      | enablechunkupload_1           | 0                                                                 | block_opencast |
      | uploadworkflow_1              | schedule-and-upload                                               | block_opencast |
      | enableuploadwfconfigpanel_1   | 1                                                                 | block_opencast |
      | alloweduploadwfconfigs_1      | straightToPublishing                                              | block_opencast |
      | enableuploadtranscription_1   | 1                                                                 | block_opencast |
      | transcriptionlanguages_1      | [{"key":"de","value":"German"},{"key":"en","value":"English"}]    | block_opencast |

    And the following "permission overrides" exist:
      | capability                    | permission  | role           | contextlevel | reference |
      | block/opencast:addvideo       | Allow       | editingteacher | Course       | C1        |

  @javascript
  Scenario: Simple Upload page must follow the admin configurations to offer different options
    Given I log in as "admin"
    And the following config values are set as admin:
      | config                        | value           | plugin         |
      | upload_inline_visibility_1    | 1               | mod_opencast   |
      | upload_inline_processing_1    | 1               | mod_opencast   |
      | upload_enable_advanced_mode_1 | 1               | mod_opencast   |
    And I am on "Course 1" course homepage with editing mode on
    When I drag and drop video "mod/opencast/tests/fixtures/test.mp4" file into course General section
    And I reload the page
    Then I should see "Upload video: test.mp4" in the "General" "section"
    When I follow "Upload video: test.mp4 "
    And I wait until the page is ready
    Then I should see "Upload your video to Opencast"
    And I should see "Title"
    And I should see "Visibility"
    And I should see "Advanced Mode"
    And the following config values are set as admin:
      | config                        | value           | plugin         |
      | upload_inline_visibility_1    | 0               | mod_opencast   |
      | upload_inline_processing_1    | 0               | mod_opencast   |
      | upload_enable_advanced_mode_1 | 0               | mod_opencast   |
    When I reload the page
    Then I should not see "Visibility"
    And I should not see "straightToPublishing"
    And I should not see "Advanced Mode"

  @javascript
  Scenario: Advanced Upload page must follow the admin configurations to offer different options
    Given I log in as "admin"
    And the following config values are set as admin:
      | config                         | value           | plugin         |
      | upload_enable_advanced_mode_1  | 1               | mod_opencast   |
      | upload_metadata_activate_1     | 1               | mod_opencast   |
      | upload_metadata_list_1         | subjects        | mod_opencast   |
      | upload_presentation_activate_1 | 1               | mod_opencast   |
      | upload_visibility_activate_1   | 1               | mod_opencast   |
      | upload_processing_activate_1   | 1               | mod_opencast   |
      | upload_subtitle_activate_1     | 1               | mod_opencast   |
    And I am on "Course 1" course homepage with editing mode on
    When I drag and drop video "mod/opencast/tests/fixtures/test.mp4" file into course General section
    And I reload the page
    Then I should see "Upload video: test.mp4" in the "General" "section"
    When I follow "Upload video: test.mp4 "
    And I wait until the page is ready
    Then I should see "Upload your video to Opencast"
    And I should see "Advanced Mode"
    When I follow "Go to advanced mode page"
    Then I should see "Upload your video to Opencast"
    And I should see "Video Metadata"
    When I click on "#metadata-tab" "css_element"
    Then I should see "Title"
    And I should see "Subjects"
    And the following config values are set as admin:
      | config                        | value           | plugin         |
      | upload_metadata_list_1        |                 | mod_opencast   |
    When I reload the page
    Then I should not see "Subjects"
    And I should see "Presentation Upload"
    When I click on "#presentation-tab" "css_element"
    Then I should see "Presentation file"
    And I should see "Video Visibility"
    When I click on "#visibility-tab" "css_element"
    Then I should see "Visibility"
    And I should see "Processing Settings"
    When I click on "#processing-tab" "css_element"
    Then I should see "Straight to publishing"
    When I click on "#subtitle-tab" "css_element"
    And I should see "Subtitles Upload"
    Then I should see "English"
    And I should see "German"
    And the following config values are set as admin:
      | config                        | value           | plugin         |
      | upload_subtitle_langlist_1    | en              | mod_opencast   |
    When I reload the page
    When I click on "#subtitle-tab" "css_element"
    Then I should not see "German"
    And the following config values are set as admin:
      | config                        | value           | plugin         |
      | upload_metadata_activate_1     | 0               | mod_opencast   |
      | upload_presentation_activate_1 | 0               | mod_opencast   |
      | upload_visibility_activate_1   | 0               | mod_opencast   |
      | upload_processing_activate_1   | 0               | mod_opencast   |
      | upload_subtitle_activate_1     | 0               | mod_opencast   |
    When I reload the page
    Then I should see "No advanced options available. Contact your administrator."

  @javascript
  Scenario: Easy upload video to Opencast via drag and drop in Opencast Video Provider activity
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I drag and drop video "mod/opencast/tests/fixtures/test.mp4" file into course General section
    And I reload the page
    Then I should see "Upload video: test.mp4" in the "General" "section"
    When I follow "Upload video: test.mp4 "
    And I wait until the page is ready
    Then I should see "Upload your video to Opencast"
    And I should see "test.mp4"
    When I click on "Upload now!" "button"
    Then I should see "Video upload successful"

  @javascript @_file_upload
  Scenario: Advanced upload video to Opencast via drag and drop in Opencast Video Provider activity
    Given I log in as "teacher1"
    And the following config values are set as admin:
      | config                         | value           | plugin         |
      | upload_subtitle_activate_1     | 1               | mod_opencast   |
      | upload_subtitle_langlist_1     | en              | mod_opencast   |
    And I am on "Course 1" course homepage with editing mode on
    When I drag and drop video "mod/opencast/tests/fixtures/test.mp4" file into course General section
    And I reload the page
    Then I should see "Upload video: test.mp4" in the "General" "section"
    When I follow "Upload video: test.mp4 "
    And I wait until the page is ready
    Then I should see "Upload your video to Opencast"
    And I should see "Advanced Mode"
    When I follow "Go to advanced mode page"
    Then I should see "Upload your video to Opencast"
    And I click on "#metadata-tab" "css_element"
    And I set the field "Title" to "Test Video - Advanced Upload"
    And I click on "#presentation-tab" "css_element"
    And I upload "mod/opencast/tests/fixtures/test.mp4" file to "Presentation file" filemanager as:
      | Save as | Presentation.mp4 |
    And I click on "#visibility-tab" "css_element"
    And I set the field "Visibility" to "Prevent any student from accessing the video"
    And I click on "#processing-tab" "css_element"
    And the "Straight to publishing" checkbox should be checked
    And I click on "#subtitle-tab" "css_element"
    And I click on "English" "checkbox"
    And I upload "mod/opencast/tests/fixtures/en.vtt" subtitle file for "en"
    When I click on "Upload now!" "button"
    Then I should see "Video upload successful"
