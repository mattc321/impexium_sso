<?php
namespace Drupal\impexium_sso\Helper;


use Drupal\Core\Entity\EntityFieldManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UserFieldsHelper
 *
 * Helper class for getting user fields that are available to map to
 *
 * @package Drupal\impexium_sso\Helper
 */
class UserFieldsHelper
{
  const FIELDS_TO_IGNORE = [
    'uid',
    'uuid',
    'langcode',
    'preferred_langcode',
    'preferred_admin_langcode',
    'pass',
    'mail',
    'timezone',
    'status',
    'created',
    'changed',
    'access',
    'login',
    'init',
    'roles',
    'default_langcode',
    'user_picture',
    'field_impexium_user_id'
  ];

  /**
   * @var EntityFieldManager
   */
  private $entityFieldManager;

  /**
   * UserFieldsHelper constructor.
   * @param EntityFieldManager $entityFieldManager
   */
  public function __construct(EntityFieldManager $entityFieldManager)
  {
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * @param ContainerInterface $container
   * @return UserFieldsHelper
   */
  public function create(ContainerInterface $container)
  {
    return new static(
      $container->get('entity_field.manager'),
    );
  }

  /**
   * @param bool $asSimpleArray
   * @return array
   */
  public function getUserAccountFields(bool $asSimpleArray = false)
  {
    $fields = $this->entityFieldManager->getFieldDefinitions('user', 'user');

    $userAccountFields = [];
    foreach ($fields as $fieldName => $field) {
      if (in_array($fieldName, self::FIELDS_TO_IGNORE)) {
        continue;
      }
      if (! $asSimpleArray) {
        $userAccountFields[$fieldName] = $field;
        continue;
      }

      if (method_exists($field, 'getProvider')) {
        $value = (string)$field->getLabel() . ' (' . $field->getProvider() . '.' . $fieldName . ')';
      } else {
        $value = (string)$field->getLabel() . ' (' . $field->get('entity_type') . '.' . $fieldName . ')';
      }

      $userAccountFields[$fieldName] = $value;

    }

    return $userAccountFields;
  }
}
