@local_lernhive_copy @javascript
Feature: LernHive Copy flow through Launcher
  In order to duplicate a course from the guided LernHive flow
  As a course creator
  I need to reach Copy via Launcher and land on copy progress after submit

  Scenario: Course creator opens Copy via Launcher and queues a copy
    Given the following "categories" exist:
      | name             | parent | idnumber |
      | Copy target area | 0      | lh-copy  |
    And the following "courses" exist:
      | fullname          | shortname |
      | Source course 101 | SRC101    |
    And the following "users" exist:
      | username     | firstname | lastname | email                     |
      | coursemaker1 | Course    | Maker    | coursemaker1@example.com  |
    And the following "system role assigns" exist:
      | user        | role        |
      | coursemaker1 | coursecreator |
      | coursemaker1 | manager      |
    And I log in as "coursemaker1"
    When I am on "/local/lernhive_launcher/index.php"
    And I follow "ContentHub"
    And I click on "Open" "link" in the ".lh-plugin-card[data-card-key='copy']" "css_element"
    And I set the field "Source course" to "Source course 101"
    And I set the field "New course full name" to "Copied Course 101"
    And I set the field "New course short name" to "COPIED101"
    And I set the field "Target category" to "Copy target area"
    And I press "Start copy"
    Then I should see "Copy queued."
    And I should see "Copy progress"
