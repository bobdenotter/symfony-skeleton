Feature: Create record

    Scenario: As an Admin I want to create a record
        Given I am logged in as "admin"

        When I visit the "new_record" page with parameters:
            | contentType | page |
        Then I wait for "title_field" element to appear

        When I fill the "title_field" field with "New record title"
        And I click the "status_select" element
        And I click the "status_published" element
        And I click the "save_button" element
        Then I wait for "title_field" element to appear
        Then there is element "title_field" with text "New record title"
