<?php

namespace Drupal\htmlpurifier\Plugin\Filter;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
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

    // Get and apply the configuration set in the filter form.
    if (!empty($this->settings['htmlpurifier_configuration'])) {
      $settings = Yaml::decode($this->settings['htmlpurifier_configuration']);
      foreach ($settings as $namespace => $directives) {
        foreach ($directives as $key => $value) {
          $config->set("$namespace.$key", $value);
        }
      }
    }

    $purifier = new \HTMLPurifier($config);
    $purified_text = $purifier->purify($text);

    return new FilterProcessResult($purified_text);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    if (empty($this->settings['htmlpurifier_configuration'])) {
      /* @var $config \HTMLPurifier_Config */
      $config = \HTMLPurifier_Config::createDefault();
      $configuration = Yaml::encode($config->getAll());
    }
    else {
      $configuration = $this->settings['htmlpurifier_configuration'];
    }

    $form['htmlpurifier_configuration'] = [
      '#type' => 'textarea',
      '#rows' => 50,
      '#title' => t('HTML Purifier Configuration'),
      '#description' => t('These are the config directives in YAML format, according to the <a href="@url">HTML Purifier documentation</a>', ['@url' => 'http://htmlpurifier.org/live/configdoc/plain.html']),
      '#default_value' => $configuration,
    ];

    return $form;
  }

}
