<?php
namespace Drupal\impexium_sso\Api;

use Drupal\impexium_sso\Api\Model\Response\AccessData;
use Drupal\impexium_sso\Api\Model\Response\AuthenticationData;
use Drupal\impexium_sso\Api\Model\Response\ImpexiumUser;
use Drupal\impexium_sso\Api\Model\Response\ResponseModelInterface;
use Drupal\impexium_sso\Exception\Types\ApiConnectionException;
use Drupal\impexium_sso\Exception\Types\EmptyResponseException;
use Drupal\impexium_sso\Exception\Types\MissingConfigurationException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\impexium_sso\Api\Response\ResponseHandler;
use GuzzleHttp\Exception\GuzzleException;

class Client
{

  /**
   * @var ImmutableConfig
   */
  private $config;
  /**
   * @var ResponseHandler
   */
  private $responseHandler;
  /**
   * @var AccessData|ResponseModelInterface
   */
  private $accessData;
  /**
   * @var AuthenticationData|ResponseModelInterface
   */
  private $authData;
  /**
   * @var ImpexiumUser|ResponseModelInterface
   */
  private $userData;

  /**
   * Client constructor.
   * @param ConfigFactoryInterface $configFactory
   * @param ResponseHandler $responseHandler
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    ResponseHandler $responseHandler
  ) {
    $this->config = $configFactory->get('impexium_sso.settings');
    $this->responseHandler = $responseHandler;
  }

  /**
   * @return ResponseModelInterface|AccessData|null
   * @throws MissingConfigurationException
   * @throws ApiConnectionException
   * @throws GuzzleException
   * @throws EmptyResponseException
   */
  public function getAccessData()
  {

    if (! $this->config->get('impexium_sso_api_app_name')
    || ! $this->config->get('impexium_sso_api_app_key')
    || ! $this->config->get('impexium_sso_api_endpoint')) {
      throw new MissingConfigurationException('Missing required configuration values');
    }

    if ($this->accessData) {
      return $this->accessData;
    }

    $client = new \GuzzleHttp\Client();

    $body = [
      'AppName' => $this->config->get('impexium_sso_api_app_name'),
      'AppKey' => $this->config->get('impexium_sso_api_app_key'),
    ];

    $response = $client->request(
      'POST',
      $this->config->get('impexium_sso_api_endpoint'),
      [
        'headers' => [
          'Content-Type' => 'application/json'
        ],
        'body' => json_encode($body)
      ]
    );

    $this->accessData = $this->responseHandler->handleResponse($response, AccessData::class);
    return $this->accessData;
  }

  /**
   * @return AuthenticationData|ResponseModelInterface
   * @throws ApiConnectionException
   * @throws EmptyResponseException
   * @throws GuzzleException
   * @throws MissingConfigurationException
   */
  public function getAuthData()
  {
    if ($this->authData) {
      return $this->authData;
    }

    $accessData = $this->getAccessData();

    $client = new \GuzzleHttp\Client();

    $body = [
      'AccessToken' => $accessData->getAccessToken(),
      'AppID' => $this->config->get('impexium_sso_api_app_id'),
      'AppPassword' => $this->config->get('impexium_sso_api_app_password'),
      'AppUserEmail' => $this->config->get('impexium_sso_api_email'),
      'AppUserPassword' => $this->config->get('impexium_sso_api_password')
    ];

    $response = $client->request(
      'POST',
      $accessData->getUri(),
      [
        'headers' => [
          'Content-Type' => 'application/json',
          'accessToken' =>  $accessData->getAccessToken()
        ],
        'body' => json_encode($body)
      ]
    );

    $this->authData = $this->responseHandler->handleResponse($response, AuthenticationData::class);
    return $this->authData;
  }

  /**
   * @param string $ssoId
   * @param bool $useCache
   * @return ImpexiumUser|ResponseModelInterface
   * @throws ApiConnectionException
   * @throws EmptyResponseException
   * @throws GuzzleException
   * @throws MissingConfigurationException
   */
  public function getUserDataBySsoId(string $ssoId, bool $useCache = true)
  {
    if ($this->userData && $useCache) {
      return $this->userData;
    }

    $authData = $this->getAuthData();

    $uri = $this->config->get('impexium_sso_api_get_user_endpoint');

    if (! $uri) {
      throw new ApiConnectionException('Could not get impexium user. Missing URL in config.');
    }

    $uri = $uri . $ssoId;

    $client = new \GuzzleHttp\Client();

    $response = $client->request(
      'GET',
      $uri,
      [
        'headers' => [
          'appToken' =>  $authData->getAppToken(),
          'userToken' =>  $authData->getUserToken()
        ]
      ]
    );

    $this->userData = $this->responseHandler->handleResponse($response, ImpexiumUser::class);
    return $this->userData;
  }
}
