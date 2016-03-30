@rest
Feature: Providing statistics


Background:
  Given I am the registered user named "Goutte"


Scenario: Get initial statistics
   When I get /stats
   Then the request should be accepted
    And the response should include :
"""
users_count: 1
items_count: 0
"""


Scenario: Get updated statistics
  Given I gave 42 items
    And there is a user named "Sherlock"
   When I get /stats
   Then the request should be accepted
    And the response should include :
"""
users_count: 2
items_count: 42
"""