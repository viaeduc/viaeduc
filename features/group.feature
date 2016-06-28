Feature: I can hang with my contacts in groups

    Scenario: I can post and see my post
        Given group "LE BREVET DES COLLÈGES" has no post
          And I am connected as "admin" on "/groups/my_groups"
         Then I should see "MES GROUPES"
         When I click on "xpath=//h2[contains(., 'LE BREVET DES COLLÈGES')]"
         Then I should see "Page Groupe : LE BREVET DES COLLÈGES"
         When I fill:
            | Ecrivez votre message... | THIS IS A TEST |
          And I click on "css=#simple-post-form button[type='submit']"
         When I refresh
         Then I should see "THIS IS A TEST"
