<?php

namespace Drupal\htmlpurifier\Plugin\Filter;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Filter(
 *   id = "htmlpurifier",
 *   title = @Translation("HTML Purifier"),
 *   description = @Translation("Removes malicious HTML code and ensures that the output is standards compliant."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_HTML_RESTRICTOR
 * )
 */
class HtmlPurifierFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * htmlpurifier.config_directives configuration settings array
   *
   * @var array
   */
  protected $drupalConfig;

  public function setDrupalConfig(array $config) {
    $this->drupalConfig = $config;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')->get('htmlpurifier.config_directives')->get()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $drupal_config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->drupalConfig = $drupal_config;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    /* @var $config \HTMLPurifier_Config */
    $config = \HTMLPurifier_Config::createDefault();

    // Get and apply the configuration stored in Drupal.
    $this->applyConfigSettings($config, $this->drupalConfig);

    $purifier = new \HTMLPurifier($config);
    $purified_text = $purifier->purify($text);

    return new FilterProcessResult($purified_text);
  }

  /**
   * Applies the configuration settings in the HTMLPurifier_Config object.
   *
   * @param \HTMLPurifier_Config $config
   * @param array $settings
   * @param string $namespace
   */
  protected function applyConfigSettings(\HTMLPurifier_Config $config, $settings, $namespace = '') {
    foreach ($settings as $key => $value) {
      if (is_array($value)) {
         $namespace .= "$key.";
         $this->applyConfigSettings($config, $value, $namespace);
      }
      else {
        $config->set($namespace . $key, $value);
      }
    }
  }
}
