<?php

namespace Drupal\htmlpurifier\Plugin\Filter;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\htmlpurifier\Event\ConfigLoadedEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * HTML Purifier filter.
 *
 * @Filter(
 *   id = "htmlpurifier",
 *   title = @Translation("HTML Purifier"),
 *   description = @Translation("Removes malicious HTML code and ensures that the output is standards compliant."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_HTML_RESTRICTOR
 * )
 */
class HtmlPurifierFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * Array of error messages from HTMLPurifier configuration assignments.
   *
   * @var array
   */
  protected $configErrors = [];

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * HTML purifier configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $htmlPurifierSettings;

  /**
   * Constructs a \Drupal\htmlpurifier\Plugin\Filter\HtmlPurifierFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileSystemInterface $file_system, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileSystem = $file_system;
    $this->htmlPurifierSettings = $config_factory->get('htmlpurifier.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if (!empty($this->settings['htmlpurifier_configuration'])) {
      $purifier_config = $this->applyPurifierConfig($this->settings['htmlpurifier_configuration']);
    }
    else {
      $purifier_config = \HTMLPurifier_Config::createDefault();
    }

    // Set Serializer path to the temporary directory, so it can be written.
    $cache_serializer_path = $this->htmlPurifierSettings->get('cache.serializerpath');
    if (empty($cache_serializer_path)) {
      $cache_serializer_path = $this->fileSystem->getTempDirectory() . '/htmlpurifier';
    }
    $this->fileSystem->prepareDirectory($cache_serializer_path, FileSystemInterface::MODIFY_PERMISSIONS | FileSystemInterface::CREATE_DIRECTORY);
    $purifier_config->set('Cache.SerializerPath', $cache_serializer_path);

    // Allow other modules to alter the HTML Purifier configuration.
    $event = new ConfigLoadedEvent($purifier_config);
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch(ConfigLoadedEvent::EVENT_NAME, $event);

    // Purify the text.
    $purifier = new \HTMLPurifier($purifier_config);
    $purified_text = $purifier->purify($text);
    return new FilterProcessResult($purified_text);
  }

  /**
   * Applies the configuration to a HTMLPurifier_Config object.
   *
   * @param string $configuration
   *   The configuration encoded as a YAML string.
   *
   * @return \HTMLPurifier_Config
   *   The applied configuration object.
   */
  protected function applyPurifierConfig($configuration) {
    /* @var $purifier_config \HTMLPurifier_Config */
    $purifier_config = \HTMLPurifier_Config::createDefault();

    $settings = Yaml::decode($configuration);
    foreach ($settings as $namespace => $directives) {

      // Keep Cache managing out of the text formats scope.
      if ($namespace !== 'Cache') {

        if (is_array($directives)) {
          foreach ($directives as $key => $value) {
            $purifier_config->set("$namespace.$key", $value);
          }
        }
        else {
          $this->configErrors[] = 'Invalid value for namespace $namespace, must be an array of directives.';
        }
      }
    }

    return $purifier_config;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    if (empty($this->settings['htmlpurifier_configuration'])) {
      $purifier_config = \HTMLPurifier_Config::createDefault();
      $config_array = $purifier_config->getAll();

      // Keep Cache managing out of the text formats scope.
      unset($config_array['Cache']);

      $default_value = Yaml::encode($config_array);
    }
    else {
      $default_value = $this->settings['htmlpurifier_configuration'];
    }

    $form['htmlpurifier_configuration'] = [
      '#type' => 'textarea',
      '#rows' => 50,
      '#title' => t('HTML Purifier Configuration'),
      '#description' => t('These are the config directives in YAML format, according to the <a href="@url">HTML Purifier documentation</a>', ['@url' => 'http://htmlpurifier.org/live/configdoc/plain.html']),
      '#default_value' => $default_value,
      '#element_validate' => [
        [$this, 'settingsFormConfigurationValidate'],
      ],
    ];

    return $form;
  }

  /**
   * Settings form validation callback for htmlpurifier_configuration element.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function settingsFormConfigurationValidate(array $element, FormStateInterface $form_state) {
    $values = $form_state->getValue('filters');
    if (isset($values['htmlpurifier']['settings']['htmlpurifier_configuration'])) {
      $this->configErrors = [];

      // HTMLPurifier library uses triger_error() for not valid settings.
      set_error_handler([$this, 'configErrorHandler']);
      try {
        $this->applyPurifierConfig($values['htmlpurifier']['settings']['htmlpurifier_configuration']);
      }
      catch (\Exception $ex) {
        // This could be a malformed YAML or any other exception.
        $form_state->setError($element, $ex->getMessage());
      }
      restore_error_handler();

      if (!empty($this->configErrors)) {
        foreach ($this->configErrors as $error) {
          $form_state->setError($element, $error);
        }
        $this->configErrors = [];
      }
    }
  }

  /**
   * Custom error handler to manage invalid purifier configuration assignments.
   *
   * @param int $errno
   *   The error number.
   * @param string $errstr
   *   The error string.
   */
  public function configErrorHandler($errno, $errstr) {
    // Do not set a validation error if the error is about a deprecated use.
    if ($errno < E_DEPRECATED) {
      // \HTMLPurifier_Config::triggerError() adds ' invoked on line ...' to the
      // error message. Remove that part from our validation error message.
      $needle = 'invoked on line';
      $pos = strpos($errstr, $needle);
      if ($pos !== FALSE) {
        $message = substr($errstr, 0, $pos - 1);
        $this->configErrors[] = $message;
      }
      else {
        $this->configErrors[] = 'HTMLPurifier configuration is not valid. Error: ' . $errstr;
      }
    }
  }

}
