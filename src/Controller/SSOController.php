<?php
namespace Drupal\impexium_sso\Controller;

use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Url;
use Drupal\impexium_sso\Api\Model\Response\ImpexiumUser;
use Drupal\impexium_sso\Exception\ExceptionHandler;
use Drupal\impexium_sso\Helper\UserDataHelper;
use Drupal\impexium_sso\Service\ImpexiumSsoService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Throwable;

class SSOController extends ControllerBase
{
  const USER_ID_PARAM = 'UserId';
  const SSO_PARAM = 'sso';

  /**
   * @var AccountProxyInterface
   */
  private $accountProxy;
  /**
   * @var RequestStack
   */
  private $request;
  /**
   * @var ImpexiumSsoService
   */
  private $impexiumSsoService;
  /**
   * @var UserDataHelper
   */
  private $userDataHelper;
  /**
   * @var ExceptionHandler
   */
  private $exceptionHandler;


  /**
   * SSOController constructor.
   * @param AccountProxyInterface $accountProxy
   * @param RequestStack $request
   * @param ImpexiumSsoService $impexiumSsoService
   * @param UserDataHelper $userDataHelper
   * @param ExceptionHandler $exceptionHandler
   */
  public function __construct(
    AccountProxyInterface $accountProxy,
    RequestStack $request,
    ImpexiumSsoService $impexiumSsoService,
    UserDataHelper $userDataHelper,
    ExceptionHandler $exceptionHandler
  ) {
    $this->accountProxy = $accountProxy;
    $this->request = $request;
    $this->impexiumSsoService = $impexiumSsoService;
    $this->userDataHelper = $userDataHelper;
    $this->exceptionHandler = $exceptionHandler;
  }

  /**
   * @param ContainerInterface $container
   * @return ControllerBase|SSOController
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('current_user'),
      $container->get('request_stack'),
      $container->get('impexium_sso.api_service'),
      $container->get('impexium_sso.user_data_helper'),
      $container->get('impexium_sso.exception_handler')
    );
  }

  /**
   * @throws Throwable
   */
  public function authenticate()
  {
    //already authenticated
    if ($this->accountProxy->isAuthenticated()) {
      return $this->successRedirect();
    }

    //missing url parameters
    $userId = $this->request->getCurrentRequest()->query->get(self::USER_ID_PARAM);
    $ssoId = $this->request->getCurrentRequest()->query->get(self::SSO_PARAM);

    if (! $userId || ! $ssoId) {
      $this->exceptionHandler->handleException(new InvalidParameterException('Missing required parameters.'));
      return $this->failRedirect();
    }

    //could not get impexium user from api
    if (! $impexiumUser = $this->impexiumSsoService->getImpexiumUser($ssoId)) {
      return $this->failRedirect();
    }

    //user id on record does not match the one supplied in the url params
    if (! $this->doesImpexiumUserIdMatchDrupalUserId($impexiumUser, $userId)) {
      return $this->failRedirect();
    }

    //try to get a matching drupal user. If found, update its fields and auth.
    if ($drupalUser = $this->impexiumSsoService->getMatchingDrupalUserFromImpexiumUser($userId, $impexiumUser)) {
      $updatedUser = $this->userDataHelper->updateDrupalUserDataFromImpexiumUser($impexiumUser, $drupalUser);

      if (! $updatedUser) {
        return $this->failRedirect();
      }

      $this->doAuthenticateUser($drupalUser);
      return $this->successRedirect();
    }

    //couldnt find a drupal user. Make a new one and authenticate it.
    $drupalUser = $this->userDataHelper->createDrupalUserFromImpexiumUser($impexiumUser);

    if (! $drupalUser) {
      return $this->failRedirect();
    }

    $this->doAuthenticateUser($drupalUser);
    return $this->successRedirect();
  }

  /**
   * @param UserInterface $drupalUser
   */
  private function doAuthenticateUser(UserInterface $drupalUser)
  {
    user_login_finalize($drupalUser);
  }

  /**
   * @return LocalRedirectResponse
   */
  private function successRedirect()
  {
    //do not cache redirect responses.
    $successRedirect = $this->impexiumSsoService->getConfig()->get('impexium_sso_api_redirect_success')
      ?? '<front>';
    $response = new LocalRedirectResponse(Url::fromRoute($successRedirect)->toString());
    $response->getCacheableMetadata()->setCacheMaxAge(0);
    return $response;
  }

  /**
   * @return LocalRedirectResponse
   */
  private function failRedirect()
  {
    //do not cache redirect responses.
    $failRedirect = $this->impexiumSsoService->getConfig()->get('impexium_sso_api_redirect_fail')
      ?? '<front>';
    $response = new LocalRedirectResponse(Url::fromRoute($failRedirect)->toString());
    $response->getCacheableMetadata()->setCacheMaxAge(0);
    return $response;
  }

  /**
   * @param ImpexiumUser $impexiumUser
   * @param $userId
   * @return bool
   */
  private function doesImpexiumUserIdMatchDrupalUserId(ImpexiumUser $impexiumUser, $userId)
  {
    return $impexiumUser->getId() === $userId;
  }
}
