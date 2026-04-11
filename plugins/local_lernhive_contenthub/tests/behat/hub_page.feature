@local_lernhive_contenthub
Feature: ContentHub entry page
  In order to start creating content
  As a site administrator
  I need a unified entry screen that orchestrates the available content paths

  # The two core scenarios below cover the admin surface. A direct-URL
  # scenario for non-admin course creators will be added once the
  # launcher plugin lands and provides the real navigation path.

  Scenario: Site administrator reaches the ContentHub via the admin tree
    Given I log in as "admin"
    When I navigate to "Plugins > Local plugins > LernHive ContentHub > Open ContentHub" in site administration
    Then I should see "How would you like to start?"
    And I should see "Copy"
    And I should see "Template"
    And I should see "Library"

  Scenario: The AI card is hidden until a site admin enables it
    Given I log in as "admin"
    And I navigate to "Plugins > Local plugins > LernHive ContentHub > Open ContentHub" in site administration
    Then I should not see "AI draft"
    When I set the following administration settings values:
      | Show AI card | 1 |
    And I navigate to "Plugins > Local plugins > LernHive ContentHub > Open ContentHub" in site administration
    Then I should see "AI draft"
    And I should see "Coming soon"
