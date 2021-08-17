<?php

namespace Drupal\impexium_sso\Api\Model\Response;

interface ResponseModelInterface
{
  /**
   * @return array|null
   */
  public function getAdditionalFields(): ?array;
}
