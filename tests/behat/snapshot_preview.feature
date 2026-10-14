@local @local_siteconfigdiff @javascript
Feature: Preview configuration differences before any application
  As a Moodle administrator
  I can compare sanitized configuration snapshots without automatically writing settings.

  Scenario: An administrator creates a snapshot and views a read-only comparison
    Given I log in as "admin"
    And I open the site configuration differences dashboard
    When I set the following fields to these values:
      | Snapshot name | Behat source |
    And I press "Capture current site snapshot"
    Then I should see "Behat source"
    When I press "Compare snapshots"
    Then I should see "Differences are read-only until an authorized administrator"
    And I should not see "Apply the reviewed changes"

  Scenario: An explicitly authorized administrator previews and approves a selected change
    Given I log in as "admin"
    And I open an explicitly authorized configuration preview fixture
    Then I should see "Site language"
    When I click on "core:lang" "checkbox"
    And I press "Create application preview"
    Then I should see "Application plan"
    And I should see "pt_br"
    When I press "Apply the reviewed changes"
    Then I should see "Applied"
    And I should see "Prepare reversal preview"
