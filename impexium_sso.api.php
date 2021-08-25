<?php


use Drupal\impexium_sso\Api\Model\Response\ImpexiumUser;
use Drupal\impexium_sso\Exception\Types\UserDataException;
use Drupal\impexium_sso\Model\AllowLoginResponse;
use Drupal\user\UserInterface;

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
