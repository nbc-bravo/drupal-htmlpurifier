<?php

namespace Drupal\htmlpurifier\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * @Filter(
 *   id = "htmlpurifier",
 *   title = @Translation("HTML Purifier"),
 *   description = @Translation("Removes malicious HTML code and ensures that the output is standards compliant."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_HTML_RESTRICTOR
 * )
 */
class HtmlPurifierFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    /* @var $config \HTMLPurifier_Config */
    $config = \HTMLPurifier_Config::createDefault();

    // Get and apply the configuration stored in Drupal.
    $drupal_config = \Drupal::config('htmlpurifier.config_directives')->get();
    $this->applyConfigSettings($config, $drupal_config);

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
