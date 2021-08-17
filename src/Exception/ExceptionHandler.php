<?php
namespace Drupal\impexium_sso\Exception;


use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\Messenger;
use Exception;
use Throwable;

class ExceptionHandler
{
  /**
   * @var ImmutableConfig
   */
  private $config;
  /**
   * @var LoggerChannelInterface
   */
  private $logger;
  /**
   * @var Messenger
   */
  private $messenger;

  /**
   * ExceptionHandler constructor.
   * @param ConfigFactoryInterface $configFactory
   * @param LoggerChannelFactory $loggerFactory
   * @param Messenger $messenger
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    LoggerChannelFactory $loggerFactory,
    Messenger $messenger
  ) {
    $this->config = $configFactory->get('impexium_sso.settings');
    $this->logger = $loggerFactory->get('impexium_sso');
    $this->messenger = $messenger;
  }

  /**
   * @param Throwable $t
   * @throws Throwable
   */
  public function handleException(Throwable $t):void
  {
    $useGracefulExceptions = $this->config->get('handle_exceptions_gracefully');
    $messageOverride = $this->config->get('exception_message_to_display');

    //if we arent graceful just rethrow
    if (! $useGracefulExceptions) {
      throw $t;
    }

    //override message or message the error
    if (! $messageOverride) {
      $this->messenger->addError("Impexium Error: {$t->getMessage()}");
    } else {
      $this->messenger->addError($messageOverride);
    }

    //wd log it with context if its an exception
    if ($t instanceof Exception) {
      watchdog_exception('impexium_sso', $t);
      return;
    }

    $this->logger->error("Impexium Error: {$t->getMessage()}");
  }
}
