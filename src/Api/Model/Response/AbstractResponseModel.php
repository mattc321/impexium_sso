<?php
namespace Drupal\impexium_sso\Api\Model\Response;


abstract class AbstractResponseModel implements ResponseModelInterface
{

  private $additionalFields = [];

  /**
   * AccessData constructor.
   * @param array $data
   */
  public function __construct(
    array $data
  ) {
    foreach ($data as $field => $value) {
      if (property_exists($this, $field)) {
        $this->{$field} = $value;
        continue;
      }
      $this->additionalFields[$field] = $value;
    }
  }

  /**
   * @return array|null
   */
  public function getAdditionalFields(): ?array
  {
    return $this->additionalFields;
  }

  /**
   * @param string $propertyName
   * @return mixed|null
   */
  public function get(string $propertyName)
  {
    if (property_exists($this,$propertyName)) {
      return $this->{$propertyName};
    }

    return null;
  }
}
