<?php
namespace Drupal\impexium_sso\Controller;

use Drupal\impexium_sso\Api\Model\Response\ImpexiumUser;
use Drupal\impexium_sso\Exception\ExceptionHandler;
use Drupal\impexium_sso\Helper\UserDataHelper;
use Drupal\impexium_sso\Model\AllowLoginResponse;
use Drupal\impexium_sso\Service\ImpexiumSsoService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Throwable;

class SSOController extends ControllerBase
{
  const USER_ID_PARAM = 'UserId';
  const SSO_PARAM = 'sso';
  const SSO_LOGOUT_ACTION_PARAM = 'Action';
  const SSO_LOGOUT_ACTION_VALUE = 'Logout';


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

      //impexium is telling us to logout from here
      if ($this->isLoggingOutOfSSO()) {
        $this->doLogout();
        return $this->afterLogoutRedirect();
      }

      return $this->successRedirect();
    }

    $userId = $this->request->getCurrentRequest()->query->get(self::USER_ID_PARAM);
    $ssoId = $this->request->getCurrentRequest()->query->get(self::SSO_PARAM);

    //missing url parameters
    if (! $userId || ! $ssoId) {
      $this->exceptionHandler->handleException(new InvalidParameterException('Missing required parameters.'));
      return $this->failRedirect();
    }

    //could not get impexium user from api
    if (! $impexiumUser = $this->impexiumSsoService->getImpexiumUser($ssoId)) {
      return $this->failRedirect();
    }

    //user id on record does not match the one supplied in the url params
    if (! $this->doesImpexiumUserIdMatchRequestedUserId($impexiumUser, $userId)) {
      $this->exceptionHandler->handleException(new InvalidParameterException('Invalid User Id supplied.'));
      return $this->failRedirect();
    }

    //try to get a matching drupal user. If found, update its fields and auth.
    if ($drupalUser = $this->impexiumSsoService->getMatchingDrupalUserFromImpexiumUser($userId, $impexiumUser)) {
      $updatedUser = $this->userDataHelper->updateDrupalUserDataFromImpexiumUser($impexiumUser, $drupalUser);

      if (! $updatedUser) {
        return $this->failRedirect();
      }

      if ($this->doAuthenticateUser($drupalUser, $impexiumUser)) {
        return $this->successRedirect();
      }
      return $this->failRedirect();
    }

    //couldnt find a drupal user. Make a new one and authenticate it.
    $drupalUser = $this->userDataHelper->createDrupalUserFromImpexiumUser($impexiumUser);

    if (! $drupalUser) {
      return $this->failRedirect();
    }

    if ($this->doAuthenticateUser($drupalUser, $impexiumUser)) {
      return $this->successRedirect();
    }
    return $this->failRedirect();
  }

  /**
   * @param UserInterface $drupalUser
   * @param ImpexiumUser $impexiumUser
   * @return bool
   */
  private function doAuthenticateUser(UserInterface $drupalUser, ImpexiumUser $impexiumUser)
  {
    $allowLoginResponse = new AllowLoginResponse(true);

    \Drupal::moduleHandler()->invokeAll(
      'impexium_sso_should_authenticate_user',
      [$drupalUser, $impexiumUser, $allowLoginResponse]
    );

    if ($allowLoginResponse->isAllowLogin() || $allowLoginResponse->isForceTrue()) {
      user_login_finalize($drupalUser);
      return true;
    }

    return false;
  }

  /**
   * @return RedirectResponse
   */
  private function successRedirect()
  {
    //do not cache redirect responses.
    $successRedirect = $this->impexiumSsoService->getConfig()->get('impexium_sso_api_redirect_success')
      ?? '<front>';
    return $this->getNoCacheRedirect($successRedirect);
  }

  /**
   * @return RedirectResponse
   */
  private function afterLogoutRedirect()
  {
    //do not cache redirect responses.
    $afterLogoutRedirect = $this->impexiumSsoService->getConfig()->get('impexium_sso_api_redirect_after_logout')
      ?? '<front>';
    return $this->getNoCacheRedirect($afterLogoutRedirect);
  }

  /**
   * @return RedirectResponse
   */
  private function failRedirect()
  {
    $failRedirect = $this->impexiumSsoService->getConfig()->get('impexium_sso_api_redirect_fail')
      ?? '<front>';
    return $this->getNoCacheRedirect($failRedirect);
  }

  /**
   * @param string $url
   * @return RedirectResponse
   */
  private function getNoCacheRedirect(string $url)
  {
    //this is stupid I think but according to everyone this is an OK way to
    //prevent redirects from being cached for anonymous users.
    \Drupal::service('page_cache_kill_switch')->trigger();
    return $this->redirect($url);
  }

  /**
   * @param ImpexiumUser $impexiumUser
   * @param $userId
   * @return bool
   */
  private function doesImpexiumUserIdMatchRequestedUserId(ImpexiumUser $impexiumUser, $userId)
  {
    return $impexiumUser->getId() === $userId;
  }

  /**
   * Check if Impexium is trying to logout the user
   * @return bool
   */
  private function isLoggingOutOfSSO()
  {
    $action = $this->request->getCurrentRequest()->query->get(self::SSO_LOGOUT_ACTION_PARAM);

    if (! $action) {
      return false;
    }

    return $action === self::SSO_LOGOUT_ACTION_VALUE;

  }

  /**
   * Logout a user
   * @return void
   */
  private function doLogout()
  {
    user_logout();
  }
}
