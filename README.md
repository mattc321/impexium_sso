A Drupal Module offering a Single Sign On solution integration using Impexium

- After login into Impexium systems
- Redirect to drupal site sso endpoint with user id and sso params
- Verify SSO token against api
- Query existing users by supplied user id or loginEmail
- If user exists, update the desired fields with impexium user data
  - authenticate to Drupal
- If user does not exist, create a new user with impexium user data
  - authenticate to drupal.

https://www.impexium.com/

# Install
- Install with composer by adding the repository to composer.json. Example:

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
         
- Configure the API credentials and settings on the configuration page

# Usage

Once installed, you may enter the new endpoint into your Impexium system using the following url format to set the sso login and logout url:

    https://yourdrupalsite.com/impexium_sso

This url will take in the UserId, SSO or Action params provided by Impexium.

# Developers

REST API Services:
- "impexium_sso.api_service" provided by \Drupal\impexium_sso\Service\ImpexiumSsoService
- "impexium_sso.client" provided by \Drupal\impexium_sso\Api\Client


# Hooks

This module provides a pre authentication hook. This hook allows modules to 
instruct the SSO Service whether or not it can authenticate a user.

```
/**
 *
 * Allow participants to set the $allowLoginResponse to false.
 * This will prevent authentication.
 *
 * By default, false will always win. To override this use setForceTrue();
 *
 * @see AllowLoginResponse::setAllowLogin()
 *
 * @param UserInterface $drupalUser
 * @param ImpexiumUser $impexiumUser
 * @param AllowLoginResponse $allowLoginResponse
 */
function hook_impexium_sso_should_authenticate_user(UserInterface $drupalUser, ImpexiumUser $impexiumUser, AllowLoginResponse $allowLoginResponse)
{
  if ($drupalUser->status->value == false || $drupalUser->status->value === 0) {
    \Drupal::service('impexium_sso.exception_handler')->handleException(new UserDataException('This user is currently disabled. Cannot login.'));
    $allowLoginResponse->setAllowLogin(false);
  }
}
```
