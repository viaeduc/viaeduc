Feature: Security

    Scenario: If my credentials are invalid, I'm properly refused
        Given I am on "/"
          And I fill:
            | id=_username | foo |
            | id=_password | bar |
          And I click on "Connexion"
         Then I should see "Identifiants invalides"

    Scenario: I can access the application with an account
        Given I am on "/"
          And I fill:
            | id=_username   | admin |
            | id=_password | admin |
          And I click on "Connexion"
         Then I should see "AUJOURD'HUI DANS VOTRE RÉSEAU"
