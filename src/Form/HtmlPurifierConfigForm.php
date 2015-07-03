<?php

/**
 * @file
 * Contains \Drupal\htmlpurifier\Form\HtmlPurifierConfigForm.
 */

namespace Drupal\htmlpurifier\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Entity\FilterFormat;

class HtmlPurifierConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'htmlpurifier.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'htmlpurifier_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['htmlpurifier_introduction'] = array(
      '#type' => 'item',
      '#markup' => '<p>' . t('This page contains global settings for all HTML Purifier enabled filters.  If you are looking for specific filter configuration options, check <a href="@format">the format configurations page</a> and select the specific format you would like to configure.', array('@format' => \Drupal::url('filter.admin_overview'))) . '</p>',
    );
    $form['htmlpurifier_clear_cache'] = array(
      '#type' => 'submit',
      '#value' => t('Clear cache (Warning: Can result in performance degradation)'),
      '#submit' => array('_htmlpurifier_clear_cache'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Resave all text formats so that the new cache settings for each format
    // are recorded.
    // TODO: There should be a better way to do this.
    foreach (filter_formats() as $format) {
      $format->filters = $filter_format->filters($format->format);
      foreach ($format->filters as &$filter) {
        $filter = (array) $filter;
      }
      FilterFormat::load($format);
    }
    parent::submitForm($form, $form_state);
  }

}
