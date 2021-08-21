<?php
namespace Drupal\impexium_sso\Form;

use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\impexium_sso\Helper\UserFieldsHelper;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\impexium_sso\Service\ImpexiumSsoService;
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

    $form = array_merge($form, $this->getUserRoleMappingForm($form_state));

    $form['test_response_container']['response'] = [
      '#type' => 'markup',
      '#markup' => '<div class="test-response"></div>',
    ];

    $form['dirty_form_container']['response'] = [
      '#type' => 'markup',
      '#markup' => '<div class="dirty-form-alert"></div>',
    ];

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
    $header = [
      'source_key' => t('Source Field Key'),
      'destination_field' => t('Destination Field'),
      'action' => '',
    ];

    $form['user_map_container'] = [
      '#type' => 'details',
      '#title' => $this->t('User Field Map TWO')
    ];

    $form['user_map_container']['user_map_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#empty' => t('No content has been found.')
    ];

    $config = $this->config('impexium_sso.settings');
    $userMap = json_decode($config->get('impexium_sso_user_field_json_map'), true) ?? [];

    $index = 0;
    foreach ($userMap as $index => $row) {
      $row = $this->getRowForUserMap($row, $index);
      $row['action'] = $this->getActionRowElementForUserMap('Remove', $index);
      $form['user_map_container']['user_map_table'][] = $row;
    }

    $addRow = $this->getRowForUserMap('', $index + 1);
    $addRow['action'] = $this->getActionRowElementForUserMap('Add', $index + 1);

    $form['user_map_container']['user_map_table'][] = $addRow;

    return $form;

  }

  /**
   * @param $row
   * @param $index
   * @return array
   */
  protected function getRowForUserMap($row, $index)
  {

    return [
      'source_key' => $this->getSourceRowElementForUserMap($row['source_field'] ?? '', $index),
      'destination_field' => $this->getDestinationRowElementForUserMap($row['destination_field'] ?? '', $index)
    ];

  }

  /**
   * @param $sourceField
   * @param int $index
   * @return array
   */
  protected function getSourceRowElementForUserMap($sourceField, int $index)
  {
    return [
      '#type' => 'textfield',
      '#default_value' => $sourceField
    ];
  }

  /**
   * @param $destinationField
   * @param int $index
   * @return array
   */
  protected function getDestinationRowElementForUserMap($destinationField, int $index)
  {
    return [
      '#type' => 'select',
      '#options' => $this->userFieldsHelper->getUserAccountFields(true),
      '#default_value' => $destinationField
    ];
  }

  /**
   * @param $actionFieldValue
   * @param int $index
   * @return array|Link|mixed[]
   */
  protected function getActionRowElementForUserMap($actionFieldValue, int $index)
  {
    if ($actionFieldValue === 'Add') {
      return [
        '#type' => 'submit',
        '#value' => $this->t($actionFieldValue),
        '#name' => 'user_field_add_row_'.$index,
        '#submit' => ['::addRow'],
        '#attributes' => [
          'data-id' => $index,
          'data-map-setting' => 'impexium_sso_user_field_json_map',
          'data-table-selector' => 'user_map_table',
          'data-table-row-selector' => 'edit-user-map-table-row'.$index
        ],
        '#ajax' => [
          'callback' => '::ajaxUserMapTableAddCallback',
          'wrapper' => 'user-map-table'
        ]
      ];
    }

    //for some reason hit major snags trying to delete this row and offer a rerender in the ajax callback from the form.
    //moved this stuff out to a controller and it works fine.
    $delete = Url::fromUserInput("/admin/config/impexium_sso/settings/map/impexium_sso_user_field_json_map/user_map_container/user_map_table/edit-user-map-container/delete/{$index}");
    $deleteLink = Link::fromTextAndUrl(t('Delete'), $delete);
    $deleteLink  = $deleteLink->toRenderable();
    $deleteLink['#attributes'] = ['class'=>'use-ajax'];

    return $deleteLink;
  }

  /**
   * @param FormStateInterface $form_state
   * @return mixed
   */
  protected function getUserRoleMappingForm(FormStateInterface $form_state)
  {

    $header = [
      'source_role' => t('Source Security Role'),
      'destination_role' => t('Destination Role'),
      'action' => '',
    ];

    $form['user_role_container'] = [
      '#type' => 'details',
      '#title' => $this->t('User Role Map'),
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

    $index = 0;
    foreach (array_values($userMap) as $index => $row) {
      $row = $this->getRow($row);
      $row['action'] = $this->getActionRowElement('Remove', $index);
      $form['user_role_container']['user_role_table'][] = $row;
    }

    $index = $index + 1;
    $addRow = $this->getRow('');
    $addRow['action'] = $this->getActionRowElement('Add', $index);

    $form['user_role_container']['user_role_table'][] = $addRow;

    return $form;
  }

  /**
   * @param $row
   * @return array
   */
  protected function getRow($row)
  {

      return [
        'source_role' => $this->getSourceRowElement($row['source_field'] ?? ''),
        'destination_role' => $this->getDestinationRowElement($row['destination_field'] ?? '')
      ];

  }

  /**
   * @param $sourceField
   * @return array
   */
  protected function getSourceRowElement($sourceField)
  {
    return [
      '#type' => 'textfield',
      '#default_value' => $sourceField
    ];
  }

  /**
   * @param $destinationField
   * @return array
   */
  protected function getDestinationRowElement($destinationField)
  {
    return [
      '#type' => 'select',
      '#options' => user_role_names(),
      '#default_value' => $destinationField
    ];
  }

  /**
   * @param $actionFieldValue
   * @param int $index
   * @return array|Link|mixed[]
   */
  protected function getActionRowElement($actionFieldValue, int $index)
  {
    if ($actionFieldValue === 'Add') {
      return [
            '#type' => 'submit',
            '#value' => $this->t($actionFieldValue),
            '#submit' => ['::addRow'],
            '#attributes' => [
              'data-id' => $index,
              'data-map-setting' => 'impexium_sso_user_json_role_map',
              'data-table-selector' => 'user_role_table',
              'data-table-row-selector' => 'edit-user-role-table-row'.$index
            ],
            '#ajax' => [
              'callback' => '::ajaxUserRoleTableAddCallback',
              'wrapper' => 'edit-user-role-container'
            ]
        ];
    }

    //for some reason hit major snags trying to delete this row and offer a rerender in the ajax callback from the form.
    //moved this stuff out to a controller and it works fine.
    $delete = Url::fromUserInput("/admin/config/impexium_sso/settings/map/impexium_sso_user_json_role_map/user_role_container/user_role_table/edit-user-role-container/delete/{$index}");
    $deleteLink = Link::fromTextAndUrl(t('Delete'), $delete);
    $deleteLink  = $deleteLink->toRenderable();
    $deleteLink['#attributes'] = ['class'=>'use-ajax'];

    return $deleteLink;
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

    if (! isset($form_state->getTriggeringElement()['#attributes']['data-id'])) {
      return;
    }

    $setting = $form_state->getTriggeringElement()['#attributes']['data-map-setting'];
    $tableSelector = $form_state->getTriggeringElement()['#attributes']['data-table-selector'];
    $dataId = $form_state->getTriggeringElement()['#attributes']['data-id'];

    if (! $form_state->getValue($tableSelector)) {
      return;
    }

    $currentRowValue = $form_state->getValue($tableSelector)[$dataId];

    $config = $this->config('impexium_sso.settings');

    $arrayMap = array_values(json_decode($config->get($setting), true));

    $arrayMap[] = [
      'source_field_setting' => 'source_role',
      'source_field' => $currentRowValue['source_role'],
      'destination_field' => $currentRowValue['destination_role'],
      'destination_field_setting' => 'destination_role',
    ];

    $config->set($setting, json_encode($arrayMap));
    $config->save();

    $form_state->setRebuild();
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return AjaxResponse
   */
  public function ajaxUserMapTableAddCallback(array &$form, FormStateInterface $form_state)
  {
    $response = new AjaxResponse();

    $response->addCommand(new HtmlCommand('.dirty-form-alert', ''));
    $response->addCommand(new HtmlCommand('.dirty-form-alert', $this->getDirtyFormAlertContent()));

    $form['user_map_container']['#open'] = true;
    $response->addCommand(new ReplaceCommand('[data-drupal-selector="edit-user-map-container"]', $form['user_map_container']));
    return $response;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return AjaxResponse
   */
  public function ajaxUserRoleTableAddCallback(array &$form, FormStateInterface $form_state)
  {
    $response = new AjaxResponse();

    $response->addCommand(new HtmlCommand('.dirty-form-alert', ''));
    $response->addCommand(new HtmlCommand('.dirty-form-alert', $this->getDirtyFormAlertContent()));

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
        ''
      )
    );

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
   * @return string
   */
  private function getDirtyFormAlertContent()
  {
    return "<div class=\"messages messages--warning\"><div>
              You have unsaved changes on your configuration form
        </div></div>";
  }

}
