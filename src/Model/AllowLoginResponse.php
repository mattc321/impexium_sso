<?php
namespace Drupal\impexium_sso\Model;

class AllowLoginResponse
{
  /**
   * @var bool
   */
  private $allowLogin;
  /**
   * @var bool
   */
  private $forceTrue;

  /**
   * Test constructor.
   * @param bool $allowLogin
   * @param bool $forceTrue
   */
  public function __construct(bool $allowLogin, bool $forceTrue = false)
  {

    $this->allowLogin = $allowLogin;
    $this->forceTrue = $forceTrue;
  }

  /**
   * @return bool
   */
  public function isAllowLogin(): bool
  {
    return $this->allowLogin;
  }

  /**
   * @param bool $allowLogin
   */
  public function setAllowLogin(bool $allowLogin): void
  {
    if ($this->allowLogin === $allowLogin) {
      return;
    }

    //once you go false you never go back. use forceTrue.
    if ($this->allowLogin === false && $allowLogin === true) {
      return;
    }

    $this->allowLogin = $allowLogin;
  }

  /**
   * @return bool
   */
  public function isForceTrue(): bool
  {
    return $this->forceTrue;
  }

  /**
   * @param bool $forceTrue
   */
  public function setForceTrue(bool $forceTrue): void
  {
    $this->forceTrue = $forceTrue;
  }
}
