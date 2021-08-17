<?php
namespace Drupal\impexium_sso\Helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\impexium_sso\Api\Model\Response\ImpexiumUser;
use Drupal\impexium_sso\Exception\ExceptionHandler;
use Drupal\impexium_sso\Exception\Types\UserDataException;
use Drupal\User\Entity\User;
use Drupal\user\UserInterface;
use Throwable;

/**
 * Class UserDataHelper
 *
 * Helper class for syncing Drupal user data with impexium user data
 *
 * @package Drupal\impexium_sso\Helper
 */
class UserDataHelper
{

  /**
   * @var ImmutableConfig
   */
  private $config;
  /**
   * @var ExceptionHandler
   */
  private $exceptionHandler;
  /**
   * @var LoggerChannelInterface
   */
  private $logger;

  /**
   * UserDataHelper constructor.
   * @param ConfigFactoryInterface $configFactory
   * @param ExceptionHandler $exceptionHandler
   * @param LoggerChannelFactory $loggerFactory
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    ExceptionHandler $exceptionHandler,
    LoggerChannelFactory $loggerFactory
  ) {
    $this->config = $configFactory->get('impexium_sso.settings');
    $this->exceptionHandler = $exceptionHandler;
    $this->logger = $loggerFactory->get('impexium_sso');
  }

  /**
   * @param ImpexiumUser $impexiumUser
   * @param UserInterface|User $drupalUser
   * @return UserInterface|User|null
   * @throws Throwable
   */
  public function updateDrupalUserDataFromImpexiumUser(ImpexiumUser $impexiumUser, User $drupalUser)
  {

    try {
      $updatedUser = $this->syncUsers($impexiumUser, $drupalUser);

      if ($updatedUser) {
        $this->logger->notice("Updated impexium user {$impexiumUser->getId()} to Drupal user {$drupalUser->id()}");
        return $updatedUser;
      }

    } catch (Throwable $t) {
      $this->logger->error("Could not update impexium user {$impexiumUser->getId()} to Drupal user {$drupalUser->id()}");
      $this->exceptionHandler->handleException($t);
    }
    return null;
  }

  /**
   * @param ImpexiumUser $impexiumUser
   * @return User|null
   * @throws Throwable
   */
  public function createDrupalUserFromImpexiumUser(ImpexiumUser $impexiumUser)
  {

    try {
      $baseUser = $this->createBaseUser($impexiumUser);

      $drupalUser = $this->syncUsers($impexiumUser, $baseUser);

      if (! $drupalUser) {
        throw new UserDataException("Could not create new Drupal user from Impexium user {$impexiumUser->getId()}");
      }

      $this->logger->notice("Created Drupal user {$drupalUser->id()} from impexium user {$impexiumUser->getId()}");

      return $drupalUser;

    } catch (Throwable $t) {
      $this->exceptionHandler->handleException($t);
    }

    return null;
  }

  /**
   * @param ImpexiumUser $impexiumUser
   * @param User $drupalUser
   * @return User|null
   * @throws UserDataException
   * @throws EntityStorageException
   */
  private function syncUsers(ImpexiumUser $impexiumUser, User $drupalUser)
  {
    $userFieldsToMap = json_decode($this->config->get('impexium_sso_user_field_json_map'), true);

    if (! $userFieldsToMap) {
      throw new UserDataException("Could not update user. No user fields to map.");
    }

    //loop through the user field map and set the values
    foreach ($userFieldsToMap as $userFieldToMap) {

      if (! $drupalUser->hasField($userFieldToMap['destination_field'])) {
        $this->logger->notice("Skipping field {$userFieldToMap['destination_field']} does not exist on Drupal user.");
        continue;
      }

      //the source may be nested
      $sourceFieldParts = explode('.', $userFieldToMap['source_field']);

      if (! $sourceFieldParts) {
        $this->logger->notice("Skipping field {$userFieldToMap['destination_field']} as source map is empty.");
        continue;
      }

      $drupalUser = $this->mapUserField($userFieldToMap, $sourceFieldParts, $impexiumUser, $drupalUser);

    }

    $drupalUser = $this->mapUserRoles($impexiumUser, $drupalUser);

    $drupalUser->save();

    return $drupalUser;
  }

  /**
   * @param $userFieldToMap
   * @param array $sourceFieldParts
   * @param ImpexiumUser $impexiumUser
   * @param User $drupalUser
   * @return User
   */
  private function mapUserField($userFieldToMap, array $sourceFieldParts, ImpexiumUser $impexiumUser, User $drupalUser)
  {
    //theres only one value in the source. set it.
    if (count($sourceFieldParts) === 1) {

      $sourceValue = $impexiumUser->get($userFieldToMap['source_field']);

      if ($sourceValue === null) {
        $this->logger->notice(
          "Skipping field {$userFieldToMap['destination_field']} 
            source field {$userFieldToMap['source_field']} is not on impexium user."
        );
      } else {
        $drupalUser->set($userFieldToMap['destination_field'], $sourceValue);
      }
      return $drupalUser;
    }

    //loop through the nesting to get the right value
    $sourceValue = null;
    foreach ($sourceFieldParts as $sourceFieldPart) {

      $sourceFieldPart = trim($sourceFieldPart);

      if ($sourceFieldPart === 'addresses') {

        $baseValue = $impexiumUser->getPrimaryAddress();

      } elseif ($sourceFieldPart === 'customFields') {

        $customFieldParts = explode('=',$sourceFieldParts[1]);
        $customField = $impexiumUser->getCustomField(trim($customFieldParts[1]));
        $sourceValue = $customField['value'] ?? '';
        $drupalUser->set($userFieldToMap['destination_field'], $sourceValue);
        return $drupalUser;

      } else {

        $baseValue = $impexiumUser->get($sourceFieldPart);

      }

      $firstSourceFieldPart = next($sourceFieldParts);

      $secondSourceFieldPart = next($sourceFieldParts);

      if ($secondSourceFieldPart === false) {
        $sourceValue = $baseValue[$firstSourceFieldPart];
        $drupalUser->set($userFieldToMap['destination_field'], $sourceValue);
        return $drupalUser;
      }

      $thirdSourceFieldPart = next($sourceFieldParts);

      if ($thirdSourceFieldPart === false) {
        $sourceValue = $baseValue[$firstSourceFieldPart][$secondSourceFieldPart];
        $drupalUser->set($userFieldToMap['destination_field'], $sourceValue);
        return $drupalUser;
      }

      $sourceValue = $baseValue[$firstSourceFieldPart][$secondSourceFieldPart][$thirdSourceFieldPart];
      $drupalUser->set($userFieldToMap['destination_field'], $sourceValue);
      return $drupalUser;
    }

    return $drupalUser;
  }

  /**
   * @param ImpexiumUser $impexiumUser
   * @param User $drupalUser
   * @return User
   * @throws UserDataException
   */
  private function mapUserRoles(ImpexiumUser $impexiumUser, User $drupalUser)
  {
    $securityRoles = $impexiumUser->getSecurityRoles();

    if (! $securityRoles) {
      throw new UserDataException("Could not update user. Missing security roles on impexium user {$impexiumUser->getOldId()}");
    }

    $userRolesToMap = json_decode($this->config->get('impexium_sso_user_json_role_map'), true);

    if (! $userRolesToMap) {
      throw new UserDataException("Could not update user. No user roles to map.");
    }

    $currentRoles = $drupalUser->getRoles(true);

    //first remove all the existing unlocked roles
    foreach ($currentRoles as $currentRole) {
      $drupalUser->removeRole($currentRole);
    }


    //now map the new ones
    foreach ($securityRoles as $securityRole) {

      if (! isset($securityRole['name'])) {
        $this->logger->notice("Skipping role assignment. Security role array does not contain a name key.");
        continue;
      }

      if ($roleId = $this->getDestinationRole($userRolesToMap, $securityRole['name'])) {
        $drupalUser->addRole($roleId);
      } else {
        $this->logger->notice("Skipping security role {$securityRole} no destination role was found.");
      }
    }

    return $drupalUser;

  }

  /**
   * @param ImpexiumUser $impexiumUser
   * @return EntityInterface|User
   * @throws UserDataException
   */
  private function createBaseUser(ImpexiumUser $impexiumUser)
  {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $newUser = \Drupal\user\Entity\User::create();

    if (! isset($impexiumUser->getUser()['loginEmail'])
      || ! $impexiumUser->getUser()['loginEmail']) {
      throw new UserDataException("Could not create user. Missing impexium user login email.");
    }

    // Mandatory.
    $newUser->setPassword($this->generatePassword());
    $newUser->enforceIsNew();
    $newUser->setEmail($impexiumUser->getUser()['loginEmail']);
    $newUser->setUsername($impexiumUser->getUser()['loginEmail']);
    $newUser->activate();

    // Optional.
    $newUser->set('langcode', $language);
    $newUser->set('preferred_langcode', $language);
    $newUser->set('preferred_admin_langcode', $language);

    return $newUser;
  }

  /**
   * @return string
   */
  private function generatePassword()
  {
    $alphabet = "!&$#@)(abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
    $pass = array();
    $alphaLength = strlen($alphabet) - 1;
    for ($i = 0; $i < 15; $i++) {
      $n = rand(0, $alphaLength);
      $pass[] = $alphabet[$n];
    }
    return implode($pass);
  }

  /**
   * @param $userRolesToMap
   * @param $securityRole
   * @return mixed|null
   */
  private function getDestinationRole($userRolesToMap, $securityRole)
  {
    foreach ($userRolesToMap as $userRoleToMap) {
      if ($userRoleToMap['source_field'] === $securityRole) {
        return $userRoleToMap['destination_field'];
      }
    }
    return null;
  }

}
