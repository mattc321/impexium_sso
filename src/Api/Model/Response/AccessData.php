<?php
namespace Drupal\impexium_sso\Api\Model\Response;


class AccessData extends AbstractResponseModel
{
  /**
   * @var string|null
   */
  protected $uri;
  /**
   * @var string|null
   */
  protected $accessToken;
  /**
   * @var string|null
   */
  protected $appToken;
  /**
   * @var string|null
   */
  protected $logoUrl;
  /**
   * @var string|null
   */
  protected $userId;
  /**
   * @var string|null
   */
  protected $userToken;
  /**
   * @var string|null
   */
  protected $ssoToken;

  /**
   * @return string|null
   */
  public function getUri(): ?string
  {
    return $this->uri;
  }

  /**
   * @return string|null
   */
  public function getAccessToken(): ?string
  {
    return $this->accessToken;
  }

  /**
   * @return string|null
   */
  public function getAppToken(): ?string
  {
    return $this->appToken;
  }

  /**
   * @return string|null
   */
  public function getLogoUrl(): ?string
  {
    return $this->logoUrl;
  }

  /**
   * @return string|null
   */
  public function getUserId(): ?string
  {
    return $this->userId;
  }

  /**
   * @return string|null
   */
  public function getUserToken(): ?string
  {
    return $this->userToken;
  }

  /**
   * @return string|null
   */
  public function getSsoToken(): ?string
  {
    return $this->ssoToken;
  }
}
