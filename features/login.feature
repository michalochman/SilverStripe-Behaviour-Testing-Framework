# features/login.feature
Feature: Log in
  As an site owner
  I want to access to the CMS to be secure
  So that only my team can make content changes

  Scenario: Bad login
    Given I log in with "bad@example.com" and "badpassword"
    Then I will see a bad log-in message

  Scenario: Valid login
    Given I am logged in
    Then I should see the CMS

  @isolated
  Scenario: /admin/ redirect for not logged in user
    # scenario is insulated, so we probably don't need the following check
    Given I am not logged in
    # disable automatic redirection so we can use the profiler
    When I go to "/admin/" without redirection
    Then I should be redirected to "/Security/login"
    And I should see a log-in form