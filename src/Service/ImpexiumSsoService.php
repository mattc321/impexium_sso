<?php
namespace Drupal\impexium_sso\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\impexium_sso\Api\Client;
use Drupal\impexium_sso\Api\Model\Response\ImpexiumUser;
use Drupal\impexium_sso\Api\Model\Response\ResponseModelInterface;
use Drupal\impexium_sso\Exception\ExceptionHandler;
use Drupal\impexium_sso\Exception\Types\UserDataException;
use Drupal\user\UserInterface;
use Throwable;

class ImpexiumSsoService
{
  /**
   * @var Client
   */
  private $client;
  /**
   * @var ExceptionHandler
   */
  private $exceptionHandler;
  /**
   * @var ImmutableConfig
   */
  private $config;

  /**
   * ImpexiumSsoService constructor.
   * @param Client $client
   * @param ExceptionHandler $exceptionHandler
   * @param ConfigFactoryInterface $configFactory
   */
  public function __construct(
    Client $client,
    ExceptionHandler $exceptionHandler,
    ConfigFactoryInterface $configFactory
  ) {
    $this->client = $client;
    $this->exceptionHandler = $exceptionHandler;
    $this->config = $configFactory->get('impexium_sso.settings');

  }

  /**
   * @param string $ssoId
   * @return ResponseModelInterface|ImpexiumUser|null
   * @throws Throwable
   */
  public function getImpexiumUser(string $ssoId)
  {
    try {

      return $this->client->getUserDataBySsoId($ssoId);

    } catch (Throwable $t) {
      $this->exceptionHandler->handleException($t);
    }
  }

  /**
   * @param string $userId
   * @param ImpexiumUser $impexiumUser
   * @return UserInterface|null
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   * @throws UserDataException
   */
  public function getMatchingDrupalUserFromImpexiumUser(string $userId, ImpexiumUser $impexiumUser)
  {

    $impexiumUserData = $impexiumUser->getUser();
    if (! isset($impexiumUserData['loginEmail'])) {
      throw new UserDataException('Impexium User record is missing required loginEmail field.');
    }

    $userStorage = \Drupal::entityTypeManager()->getStorage('user');

    $query = $userStorage->getQuery();
    $or = $query->orConditionGroup();
    $or->condition('field_impexium_user_id', $userId);
    $or->condition('name', $impexiumUserData['loginEmail']);
    $or->condition('mail', $impexiumUserData['loginEmail']);

    $uids = $query
      ->condition($or)
      ->accessCheck(false)
      ->execute();

    $users = $userStorage->loadMultiple($uids);

    if (! $users || count($users) <= 0) {
      return null;
    }

    return $users[array_key_first($users)];

  }

  /**
   * @return ImmutableConfig
   */
  public function getConfig(): ImmutableConfig
  {
    return $this->config;
  }

  /**
   * @return Client
   */
  public function getClient(): Client
  {
    return $this->client;
  }

}
