<?php


namespace Drupal\impexium_sso\Controller;


use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SettingsFormController extends ControllerBase
{

  /**
   * The form builder.
   *
   * @var FormBuilder
   */
  protected $formBuilder;

  /**
   * SettingsFormController constructor.
   * @param FormBuilder $formBuilder
   * @param ConfigFactoryInterface $configFactory
   */
  public function __construct(FormBuilder $formBuilder, ConfigFactoryInterface $configFactory) {
    $this->formBuilder = $formBuilder;
    $this->configFactory = $configFactory;
  }
  /**
   * {@inheritdoc}
   *
   * @param ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('config.factory'),
    );
  }

  /**
   * @param $setting
   * @param $container
   * @param $table
   * @param $dataDrupalSelector
   * @param $id
   * @return AjaxResponse
   */
  public function delete($setting, $container, $table, $dataDrupalSelector, $id)
  {

    $config = $this->configFactory->getEditable('impexium_sso.settings');
    $arrayMap = json_decode($config->get($setting), true);
    $response = new AjaxResponse();

    if (! $arrayMap || ! isset($arrayMap[$id])) {
      $form = $this->formBuilder->getForm('Drupal\impexium_sso\Form\Settings');
      $response->addCommand(new ReplaceCommand('[data-drupal-selector="'.$dataDrupalSelector.'"]', $form[$container][$table]));
      return $response;
    }

    unset($arrayMap[$id]);

    $config->set($setting, json_encode($arrayMap));
    $config->save();

    $form = $this->formBuilder->getForm('Drupal\impexium_sso\Form\Settings');

    if (! isset($form[$container][$table])) {
      $response->addCommand(
        new PrependCommand(
          '[data-drupal-selector="'.$dataDrupalSelector.'"]',
          '<div class="messages messages--error"><div>Table selector not found.</div></div>'
        )
      );
      return $response;
    }

    $response->addCommand(new ReplaceCommand('[data-drupal-selector="'.$dataDrupalSelector.'"]', $form[$container][$table]));

    return $response;
  }
}
