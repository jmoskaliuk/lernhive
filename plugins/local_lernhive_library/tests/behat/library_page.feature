@local_lernhive_library
Feature: LernHive Library entry page
  In order to browse managed library content
  As a user with library access
  I need a stable catalog page with a clear empty state in Release 1

  Scenario: Site administrator reaches the Library via the admin tree
    Given I log in as "admin"
    When I navigate to "Plugins > Local plugins > LernHive Library > Open LernHive Library" in site administration
    Then I should see "LernHive Library"
    And I should see "The library catalog is empty."

