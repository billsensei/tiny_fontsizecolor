@editor @editor_tiny @tiny @tiny_fontsizecolor
Feature: Change the font size and colour in TinyMCE
  To style my text - I need a font size and a font colour menu.

  Background:
    Given I log in as "admin"
    And I open my profile in edit mode
    And I set the field "Description" to "<p>Hello</p>"
    And I select the "p" element in position "0" of the "Description" TinyMCE editor

  @javascript
  Scenario: The toolbar has the font size and font colour buttons
    Then "Font size" button should exist in the "Description" TinyMCE editor
    And "Font colour" button should exist in the "Description" TinyMCE editor

  @javascript
  Scenario: Apply a font size from the toolbar and see it ticked
    When I click on the "Font size" button for the "Description" TinyMCE editor
    And I click on "//*[@role='menuitemcheckbox'][@aria-label='12 pt']" "xpath_element"
    Then the field "Description" matches value "<p><span style=\"font-size: 12pt;\">Hello</span></p>"
    And I click on the "Font size" button for the "Description" TinyMCE editor
    And "//*[@role='menuitemcheckbox'][@aria-label='12 pt'][@aria-checked='true']" "xpath_element" should exist
    And "//*[@role='menuitemcheckbox'][@aria-label='11 pt'][@aria-checked='true']" "xpath_element" should not exist

  @javascript
  Scenario: Return to the default size
    Given I click on the "Font size" button for the "Description" TinyMCE editor
    And I click on "//*[@role='menuitemcheckbox'][@aria-label='12 pt']" "xpath_element"
    When I click on the "Font size" button for the "Description" TinyMCE editor
    And I click on "Default size" "menuitem"
    Then the field "Description" matches value "<p>Hello</p>"

  @javascript
  Scenario: Apply a custom font size
    When I click on the "Font size" button for the "Description" TinyMCE editor
    And I click on "Custom size..." "menuitem"
    And I set the field "Font size in points (6 to 72)" to "30"
    And I click on "Save changes" "button" in the ".tox-dialog" "css_element"
    Then the field "Description" matches value "<p><span style=\"font-size: 30pt;\">Hello</span></p>"

  @javascript
  Scenario: A custom font size outside the range is refused
    When I click on the "Font size" button for the "Description" TinyMCE editor
    And I click on "Custom size..." "menuitem"
    And I set the field "Font size in points (6 to 72)" to "99"
    And I click on "Save changes" "button" in the ".tox-dialog" "css_element"
    Then I should see "Enter a whole number between 6 and 72."
    And the field "Description" matches value "<p>Hello</p>"

  @javascript
  Scenario: Apply a font colour from the Format menu and remove it again
    When I click on the "Format > Font colour > Red" menu item for the "Description" TinyMCE editor
    Then the field "Description" matches value "<p><span style=\"color: #e03e2d;\">Hello</span></p>"
    And I click on the "Font colour" button for the "Description" TinyMCE editor
    And I click on "Remove colour" "menuitem"
    And the field "Description" matches value "<p>Hello</p>"
