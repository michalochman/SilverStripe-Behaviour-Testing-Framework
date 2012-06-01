# features/login.feature
Feature: Login
  In order to use the admin infrastructure
  I need proper authorisation

  Scenario: /admin/ redirect for not logged in user
    Given I am on "/admin/"
    Then I should be redirected to "/Security/login"
    And the response status code should be 200

