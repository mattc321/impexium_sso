<?php
namespace Drupal\impexium_sso\Form;

use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Render\Element\Textfield;
use Drupal\Core\Render\Markup;
use Drupal\impexium_sso\Exception\Types\ApiConnectionException;
use Drupal\impexium_sso\Exception\Types\EmptyResponseException;
use Drupal\impexium_sso\Exception\Types\MissingConfigurationException;
use Drupal\impexium_sso\Helper\UserFieldsHelper;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\impexium_sso\Service\ImpexiumSsoService;
use Drupal\user\Entity\Role;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Throwable;

class Settings extends ConfigFormBase
{
  /**
   * @var UserFieldsHelper
   */
  protected $userFieldsHelper;
  /**
   * @var ImpexiumSsoService
   */
  protected $impexiumSsoService;

  /**
   * Settings constructor.
   * @param ConfigFactoryInterface $config_factory
   * @param UserFieldsHelper $userFieldsHelper
   * @param ImpexiumSsoService $impexiumSsoService
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    UserFieldsHelper $userFieldsHelper,
    ImpexiumSsoService $impexiumSsoService
  ) {
    $this->userFieldsHelper = $userFieldsHelper;
    $this->impexiumSsoService = $impexiumSsoService;
    parent::__construct($config_factory);
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('config.factory'),
      $container->get('impexium_sso.user_fields_helper'),
      $container->get('impexium_sso.api_service')
      );
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames()
  {
    return [
      'impexium_sso.settings',
    ];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId()
  {
    return 'impexium_sso_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('impexium_sso.settings');

    $form = parent::buildForm($form, $form_state);
    $form['test_response_container']['response'] = [
      '#type' => 'markup',
      '#markup' => '<div class="test-response"></div>',
    ];
    $form['container'] = [
      '#type' => 'details',
      '#title' => $this->t('API Configuration')
    ];
    $form['container']['impexium_sso_api_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Endpoint'),
      '#default_value' => $config->get('impexium_sso_api_endpoint')
    ];
    $form['container']['impexium_sso_api_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login Email'),
      '#default_value' => $config->get('impexium_sso_api_email')
    ];
    $form['container']['impexium_sso_api_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Login Password'),
      '#default_value' => $config->get('impexium_sso_api_password'),
      '#attributes' => [
        'placeholder' => $config->get('impexium_sso_api_password')
          ? $this->t('Login password is set')
          : '',
      ]
    ];
    $form['container']['impexium_sso_api_app_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App Name'),
      '#default_value' => $config->get('impexium_sso_api_app_name')
    ];
    $form['container']['impexium_sso_api_app_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App Key'),
      '#default_value' => $config->get('impexium_sso_api_app_key')
    ];
    $form['container']['impexium_sso_api_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App Id'),
      '#default_value' => $config->get('impexium_sso_api_app_id')
    ];
    $form['container']['impexium_sso_api_app_password'] = [
      '#type' => 'password',
      '#title' => $this->t('App Password'),
      '#default_value' => $config->get('impexium_sso_api_app_password'),
      '#attributes' => [
        'placeholder' => $config->get('impexium_sso_api_password')
          ? $this->t('App password is set')
          : '',
      ]
    ];

    $form['settings_container'] = [
      '#type' => 'details',
      '#title' => $this->t('Settings')
    ];

    $form['settings_container']['handle_exceptions_gracefully'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Handle exceptions gracefully'),
      '#default_value' => $config->get('handle_exceptions_gracefully'),
      '#description' => $this->t('Report exceptions as messages')
    ];

    $form['settings_container']['exception_message_to_display'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Error Message Override'),
      '#default_value' => $config->get('exception_message_to_display'),
      '#description' => $this->t('Leave blank to display the error message. Works with graceful exceptions turned on.')
    ];

    $form['settings_container']['impexium_sso_api_redirect_success'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect URL on Authentication Success'),
      '#default_value' => $config->get('impexium_sso_api_redirect_success')
    ];

    $form['settings_container']['impexium_sso_api_redirect_fail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect URL on Authentication Failure'),
      '#default_value' => $config->get('impexium_sso_api_redirect_fail')
    ];

    $form = array_merge($form, $this->getUserFieldMappingForm($form_state));

//    $form = array_merge($form, $this->getUserRoleMappingForm($form_state));
    $form = array_merge($form, $this->getUserRoleMappingFormTwo($form_state));

    $form['actions']['test']['impexium_sso_api_test'] = [
      '#type' => 'button',
      '#value' => $this->t('Test API Connection'),
      '#attributes' => [
        'class' => [
          'use-ajax'
        ]
      ],
      '#ajax' => [
        'callback' => '::testApiConnection',
        'wrapper' => 'impexium-sso-settings-form'
      ]
    ];

    return $form;
  }

  /**
   * @param FormStateInterface $form_state
   * @return mixed
   */
  private function getUserFieldMappingForm(FormStateInterface $form_state)
  {
    $config = $this->config('impexium_sso.settings');
    $header = [
      'title' => t('Source Field Map'),
      'content' => t('Destination Field'),
      'action' => '',
    ];

    $description = Markup::create(
      '<div>
                <p>For addresses, only the first primary address or the only address, is accessible. Custom fields support customFields.name = \'condition\' to get their value.
                Check the Impexium user response from their API for more fields to map. Custom fields must be mapped to text fields only.
                </p>                
              </div>'
    );


    $form['user_map_container'] = [
      '#type' => 'details',
      '#title' => $this->t('User Field Map'),
      '#description' => $description,
      '#prefix' => '<div id="js-user-map-container">',
      '#suffix' => '</div>',
    ];

    $form['user_map_container']['user_map_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#empty' => t('No content has been found.'),
      '#attributes' => [
        'id' => 'user-map-table'
      ]
    ];

    $userMap = json_decode($config->get('impexium_sso_user_field_json_map'), true) ?? [];

    if (! $userMap) {
      $userMap[] = 1;
    }

    if ($additionalRows = $form_state->get('additional_user_map_rows')) {
      for($i = 0; $i < $additionalRows; $i++) {
        $userMap[] = 1;
      }
    }

    if ($userMap && ! $additionalRows) {
      $userMap[] = 1;
    }

    $count = count($userMap);
    $index = 0;
    $removedRow = false;
    foreach ($userMap as $userMapIndex => $fieldToMap) {

      if ($removeRowIndex = $form_state->get('remove_user_map_row')) {
        if ($index === $removeRowIndex && ! $removedRow) {
          $index = $index === 0 ? 0 : $index - 1;
          $count--;
          $removedRow = true;
          continue;
        }
      }

      $form['user_map_container']['user_map_table']['row'.$index]['impexium_sso_user_field_map_source_'.$index] = [
        '#type' => 'textfield',
        '#default_value' => $fieldToMap['source_field'] ?? ''
      ];
      $form['user_map_container']['user_map_table']['row'.$index]['impexium_sso_user_field_map_destination_'.$index] = [
        '#type' => 'select',
        '#options' => $this->userFieldsHelper->getUserAccountFields(true),
        '#default_value' => $fieldToMap['destination_field'] ?? ''
      ];

      if ($index + 1 === $count) {
        $form['user_map_container']['user_map_table']['row'.$index]['add_row_'.$index]['actions']['submit_'.$index] = [

        ];
      } else {
        $form['user_map_container']['user_map_table']['row'.$index]['add_row_'.$index]['actions']['submit_'.$index] = [
          '#type' => 'submit',
          '#name' => 'remove_'.$index,
          '#value' => $this->t('Remove'),
          '#submit' => ['::removeRow'],
          '#attributes' => [
            'data-id' => $index,
            'data-map-setting' => 'impexium_sso_user_field_json_map',
            'data-form-row-setting' => 'additional_user_map_rows',
          ],
          '#ajax' => [
            'callback' => '::ajaxUserMapTableCallback',
            'wrapper' => 'user-map-table'
          ]
        ];
      }
      $index++;
    }

    return $form;
  }

  /**
   * @param FormStateInterface $form_state
   * @return mixed
   */
  private function getUserRoleMappingForm(FormStateInterface $form_state)
  {
    $config = $this->config('impexium_sso.settings');
    $header = [
      'title' => t('Source Security Role'),
      'content' => t('Destination Role'),
      'action' => '',
    ];

    $form['user_role_container'] = [
      '#type' => 'details',
      '#title' => $this->t('User Role Map'),
      '#prefix' => '<div id="js-user-role-container">',
      '#suffix' => '</div>',
    ];

    $form['user_role_container']['user_role_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#empty' => t('No content has been found.'),
      '#attributes' => [
        'id' => 'user-role-table'
      ]
    ];

    $userMap = json_decode($config->get('impexium_sso_user_json_role_map'), true) ?? [];

    $userMap[] = 1;

    $count = count($userMap);
    foreach ($userMap as $index => $fieldToMap) {
      $form['user_role_container']['user_role_table']['row'.$index]['impexium_sso_user_field_role_source_'.$index] = [
        '#type' => 'textfield',
        '#default_value' => $fieldToMap['source_field'] ?? ''
      ];
      $form['user_role_container']['user_role_table']['row'.$index]['impexium_sso_user_field_role_destination_'.$index] = [
        '#type' => 'select',
        '#options' => user_role_names(),
        '#default_value' => $fieldToMap['destination_field'] ?? ''
      ];

      if ($index + 1 === $count) {
        $form['user_role_container']['user_role_table']['row'.$index]['add_row_'.$index]['actions']['submit_'.$index] = [
          '#type' => 'submit',
          '#value' => $this->t('Add Row'),
          '#name' => 'role_add_row_'.$index,
          '#submit' => ['::addRow'],
          '#attributes' => [
            'data-id' => $index,
            'data-map-setting' => 'impexium_sso_user_json_role_map',
            'data-table-selector' => 'user_role_table',
            'data-table-row-selector' => 'edit-user-role-table-row'.$index
        ],
          '#ajax' => [
            'callback' => '::ajaxUserRoleTableCallback',
            'wrapper' => 'user-role-table'
          ]
        ];
      } else {
        $form['user_role_container']['user_role_table']['row'.$index]['add_row_'.$index]['actions']['submit_'.$index] = [
          '#type' => 'submit',
          '#name' => 'role_remove_'.$index,
          '#value' => $this->t('Remove'),
          '#submit' => ['::removeRow'],
          '#attributes' => [
            'data-id' => $index,
            'data-map-setting' => 'impexium_sso_user_json_role_map',
            'data-table-selector' => 'user_role_table',
            'data-table-row-selector' => 'edit-user-role-table-row'.$index
          ],
          '#ajax' => [
            'callback' => '::ajaxUserRoleTableRemoveCallback',
            'wrapper' => 'user-role-table'
          ]
        ];
      }
    }

    return $form;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function addRow(array &$form, FormStateInterface $form_state)
  {

    if (! isset($form_state->getTriggeringElement()['#attributes']['data-map-setting'])) {
      return;
    }

    if (! isset($form_state->getTriggeringElement()['#attributes']['data-table-selector'])) {
      return;
    }

    $setting = $form_state->getTriggeringElement()['#attributes']['data-map-setting'];
    $tableSelector = $form_state->getTriggeringElement()['#attributes']['data-table-selector'];

    $this->saveUserMapConfigValues(
      $form_state,
      $setting,
      $tableSelector
    );

    $form_state->setRebuild();
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function removeRow(array &$form, FormStateInterface $form_state)
  {
    if (! isset($form_state->getTriggeringElement()['#attributes']['data-id'])) {
      return;
    }

    if (! isset($form_state->getTriggeringElement()['#attributes']['data-map-setting'])) {
      return;
    }

    if (! isset($form_state->getTriggeringElement()['#attributes']['data-table-selector'])) {
      return;
    }

    $keyToRemove = $form_state->getTriggeringElement()['#attributes']['data-id'];
    $setting = $form_state->getTriggeringElement()['#attributes']['data-map-setting'];
    $tableSelector = $form_state->getTriggeringElement()['#attributes']['data-table-selector'];

    $this->saveUserMapConfigValues(
      $form_state,
      $setting,
      $tableSelector,
      $keyToRemove
    );

    $form_state->setRebuild();
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return AjaxResponse
   */
  public function ajaxUserMapTableCallback(array &$form, FormStateInterface $form_state)
  {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('[data-drupal-selector="edit-user-map-table"]', $form['user_map_container']['user_map_table']));
    return $response;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return AjaxResponse
   */
  public function ajaxUserRoleTableRemoveCallback(array &$form, FormStateInterface $form_state)
  {
    $response = new AjaxResponse();

    if (isset($form_state->getTriggeringElement()['#attributes']['data-table-row-selector'])) {
      $removeClass = $form_state->getTriggeringElement()['#attributes']['data-table-row-selector'];
      $removeSelector = '[data-drupal-selector="'.$removeClass.'"]';
      $response->addCommand(new InvokeCommand($removeSelector, 'remove'));
      return $response;
    }


    $response->addCommand(new ReplaceCommand('[data-drupal-selector="edit-user-role-table"]', $form['user_role_container']['user_role_table']));
    return $response;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return AjaxResponse
   */
  public function ajaxUserRoleTableCallback(array &$form, FormStateInterface $form_state)
  {
    $response = new AjaxResponse();
    $form['user_role_container']['#open'] = true;
    $response->addCommand(new ReplaceCommand('[data-drupal-selector="edit-user-role-container"]', $form['user_role_container']));

    return $response;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array|AjaxResponse
   */
  public function testApiConnection(array &$form, FormStateInterface $form_state)
  {
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand(
        '.test-response',
        $this->getTestApiResponseOutput()
      )
    );
    return $response;
  }

  /**
   * @return string
   */
  private function getTestApiResponseOutput()
  {
    try {
      $testAuthData = $this->impexiumSsoService->getClient()->getAuthData();
    } catch (Throwable $t) {
      return "<div class=\"messages messages--error\"><div>Exception occurred: {$t->getMessage()}</div></div>";
    }

    return ($testAuthData->getUserToken())
      ? '<div class="messages messages--status"><div>Connection successful!</div></div>'
      : "<div class=\"messages messages--warning\"><div>
              Connection succeeded, but a user token was not received. Check config.
        </div></div>";
  }

  /**
   * @param $form_state
   * @param $setting
   * @param $tableSelector
   * @param null $keyToRemove
   */
  public function saveUserMapConfigValues($form_state, $setting, $tableSelector, $keyToRemove = null)
  {
    $config = $this->config('impexium_sso.settings');
    if (! $userRoleMapValues = $this->getJsonMapFromInputs(
      $form_state, $tableSelector, $keyToRemove)) {
      return;
    }
    $config->set($setting, $userRoleMapValues);
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('impexium_sso.settings');
    $config->set('impexium_sso_api_endpoint', $form_state->getValue('impexium_sso_api_endpoint'));
    $config->set('impexium_sso_api_email', $form_state->getValue('impexium_sso_api_email'));
    $config->set('impexium_sso_api_app_name', $form_state->getValue('impexium_sso_api_app_name'));
    $config->set('impexium_sso_api_app_key', $form_state->getValue('impexium_sso_api_app_key'));
    $config->set('impexium_sso_api_app_id', $form_state->getValue('impexium_sso_api_app_id'));
    $config->set('impexium_sso_api_redirect_success', $form_state->getValue('impexium_sso_api_redirect_success'));
    $config->set('impexium_sso_api_redirect_fail', $form_state->getValue('impexium_sso_api_redirect_fail'));

    $config->set('handle_exceptions_gracefully', $form_state->getValue('handle_exceptions_gracefully'));
    $config->set('exception_message_to_display', $form_state->getValue('exception_message_to_display'));

    if ($userMapValues = $this->getJsonMapFromInputs($form_state, 'user_map_table')) {
      $config->set('impexium_sso_user_field_json_map', $userMapValues);
    }

    if ($userRoleMapValues = $this->getJsonMapFromInputs($form_state, 'user_role_table')) {
      $config->set('impexium_sso_user_json_role_map', $userRoleMapValues);
    }

    if ($form_state->getValue('impexium_sso_api_password') && !$this->isOverridden('impexium_sso_api_password')) {
      $config->set('impexium_sso_api_password', $form_state->getValue('impexium_sso_api_password'));
    }

    if ($form_state->getValue('impexium_sso_api_app_password') && !$this->isOverridden('impexium_sso_api_app_password')) {
      $config->set('impexium_sso_api_app_password', $form_state->getValue('impexium_sso_api_app_password'));
    }
    $config->save();
  }

  /**
   * @param $name
   * @return bool
   */
  protected function isOverridden($name) {
    $original = $this->configFactory->getEditable('impexium_sso.settings')->get($name);
    $current = $this->configFactory->get('impexium_sso.settings')->get($name);
    return $original != $current;
  }

  /**
   * @param FormStateInterface $form_state
   * @param string $tableSelector
   * @param null $keyToRemove
   * @return false|string|null
   */
  private function getJsonMapFromInputs(FormStateInterface $form_state, string $tableSelector, $keyToRemove = null)
  {
    $formMapValues = $form_state->getValue($tableSelector);

    if ($keyToRemove !== null && isset($formMapValues[$keyToRemove])) {
      unset($formMapValues[$keyToRemove]);
    }

    if (! $formMapValues) {
      return null;
    }

    $results = [];
    foreach (array_values($formMapValues) as $rowIndex => $row) {
      $source = '';
      $sourceSetting = '';
      $destination = '';
      $destinationSetting = '';
      foreach ($row as $key => $value) {
          //if its there but its falsey allow it to be reset
          if (strpos($key, 'source') !==false) {
            $source = $value;
            $sourceSetting = $key;
            continue;
          }
          if (strpos($key, 'destination') !==false) {
            $destination = $value;
            $destinationSetting = $key;
            continue;
          }
      }

      if (! $source || ! $destination) {
        continue;
      }

      $results[$rowIndex] = [
        'source_field_setting' => $sourceSetting,
        'source_field' => $source,
        'destination_field' => $destination,
        'destination_field_setting' => $destinationSetting,
      ];
    }

    return json_encode($results);
  }

  private function getUserRoleMappingFormTwo(FormStateInterface $form_state)
  {
    $header = [
      'source_role' => t('Source Security Role'),
      'destination_role' => t('Destination Role'),
      'action' => '',
    ];

    $form['user_role_container'] = [
      '#type' => 'details',
      '#title' => $this->t('User Role Map'),
      '#prefix' => '<div id="js-user-role-container">',
      '#suffix' => '</div>',
    ];

    $form['user_role_container']['user_role_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#empty' => t('No content has been found.'),
      '#attributes' => [
        'id' => 'user-role-table'
      ]
    ];

    $config = $this->config('impexium_sso.settings');
    $userMap = json_decode($config->get('impexium_sso_user_json_role_map'), true) ?? [];

    $results = [];
    $index = 0;
    foreach ($userMap as $index => $row) {
      $row = $this->getRow($row, $index);
      $row['action'] = $this->getActionRowElement('Remove', $index);
      $form['user_role_container']['user_role_table'][] = $row;
    }

    $addRow = $this->getRow('', $index + 1);
    $addRow['action'] = $this->getActionRowElement('Add', $index);

    $form['user_role_container']['user_role_table'][] = $addRow;

    return $form;
  }

  private function getRow($row, $index)
  {

      return [
        'source_role' => $this->getSourceRowElement($row['source_field'] ?? '', $index),
        'destination_role' => $this->getDestinationRowElement($row['destination_field'] ?? '', $index)
      ];

  }

  private function getSourceRowElement($sourceField, int $index)
  {
    return [
      '#type' => 'textfield',
      '#default_value' => $sourceField
    ];
  }

  private function getDestinationRowElement($destinationField, int $index)
  {
    return [
      '#type' => 'select',
      '#options' => user_role_names(),
      '#default_value' => $destinationField
    ];
  }

  private function getActionRowElement($actionFieldValue, int $index)
  {

    if ($actionFieldValue === 'Add') {
      return [
            '#type' => 'submit',
            '#value' => $this->t($actionFieldValue),
            '#name' => 'role_add_row_'.$index,
            '#submit' => ['::addRow'],
            '#attributes' => [
              'data-id' => $index,
              'data-map-setting' => 'impexium_sso_user_json_role_map',
              'data-table-selector' => 'user_role_table',
              'data-table-row-selector' => 'edit-user-role-table-row'.$index
            ],
            '#ajax' => [
              'callback' => '::ajaxUserRoleTableCallback',
              'wrapper' => 'user-role-table'
            ]
        ];
    }

    return [
      '#type' => 'submit',
      '#name' => 'role_remove_'.$index,
      '#value' => $this->t($actionFieldValue),
      '#submit' => ['::removeRow'],
      '#attributes' => [
        'data-id' => $index,
        'data-map-setting' => 'impexium_sso_user_json_role_map',
        'data-table-selector' => 'user_role_table',
        'data-table-row-selector' => 'edit-user-role-table-row'.$index
      ],
      '#ajax' => [
        'callback' => '::ajaxUserRoleTableCallback',
        'wrapper' => 'user-role-table'
      ]
    ];
  }
  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array|AjaxResponse
   */
  public function ajaxTestCallback(array &$form, FormStateInterface $form_state)
  {
    $response = new AjaxResponse();
    $response->addCommand(new AlertCommand(
        'test'
      )
    );

    $response->addCommand(new HtmlCommand('#user-role-table','' ));
    $response->addCommand(new \Drupal\Core\Ajax\AppendCommand('.result_message', $render_array));

    return $response;
  }

}
