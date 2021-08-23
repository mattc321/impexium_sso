A Drupal Module offering a Single Sign On solution integration using Impexium

- After login into Impexium systems
- Redirect to drupal site sso endpoint with user id and sso params
- Verify SSO token against api
- Query existing users by supplied user id
- If user exists, update its fields with impexium user data
  - authenticate to Drupal
- If user does not exist, create a new user with impexium user data
  - authenticate to drupal.

https://www.impexium.com/

# Install
- Install with composer by adding to repository to composer.json. Example:

         "repositories": {
                 "0": {
                     "type": "composer",
                     "url": "https://packages.drupal.org/8"
                 },
                 "matt/impexium_sso": {
                     "type": "vcs",
                     "url": "https://github.com/mattc321/impexium_sso.git"
                 }
             },
             
- Then you can install using 

         composer require matt/impexium_sso
         
- Enable the module

         drush pm-enable impexium_sso
         
- Configure the API credentials and setting on the configuration page

# Developers

Services:
- "impexium_sso.api_service" provided by \Drupal\impexium_sso\Service\ImpexiumSsoService
- "impexium_sso.client" provided by \Drupal\impexium_sso\Api\Client
