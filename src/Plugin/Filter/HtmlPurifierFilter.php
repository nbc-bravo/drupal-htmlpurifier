<?php

namespace Drupal\htmlpurifier\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
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

  public function process($text, $langcode) {
    $config_name = isset($this->settings['htmlpurifier_config_name']) ? $this->settings['htmlpurifier_config_name'] : 'htmlpurifier_basic';
    $config = _htmlpurifier_get_config($this, $config_name);

    $purifier = new \HTMLPurifier($config);
    $purified_text = $purifier->purify($text);

    return new FilterProcessResult($purified_text);
  }

  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['htmlpurifier_help'] = [
      '#type' => 'checkbox',
      '#title' => t('Display Help Text'),
      '#default_value' => isset($this->settings['htmlpurifier_help']) ? $this->settings['htmlpurifier_help'] : TRUE,
      '#description' => t('If enabled, a short note will be added to the filter tips explaining that HTML will be transformed to conform with HTML standards. You may want to disable this option when the HTML Purifier is used to check the output of another filter like BBCode.'),
    ];

    $info_list = htmlpurifier_get_info();

    // An AJAX request calls the form builder function for every change.
    // Here we grab the config_name so we can grab applicable data from the
    // htmlpurifier_info
    if (isset($form_state->getValue('filters')['htmlpurifier']['settings']['htmlpurifier_config_name'])) {
      $config_name = $form_state->getValue('filters')['htmlpurifier']['settings']['htmlpurifier_config_name'];
    }
    elseif (isset($this->settings['htmlpurifier_config_name'])) {
      $config_name = $this->settings['htmlpurifier_config_name'];
    }
    else {
      $config_name = 'htmlpurifier_basic';
    }

    $form['htmlpurifier_filter_help'] = array(
      '#type' => 'textfield',
      '#title' => t('Help Text'),
      '#default_value' => $info_list[$config_name]['htmlpurifier_filter_tips'],

      // @TODO: Find it what is this for:
      //'#default_value' => isset($this->settings['htmlpurifier_filter_tips']) && !$reset ? $this->settings['htmlpurifier_filter_tips'] : $infos[$config_name]['htmlpurifier_filter_tips'],

      '#description' => t('Override the default help text to be something more useful.'),
      '#states' => array(
        // Hide the settings when the htmlpurifier_help checkbox is disabled.
        'invisible' => array(
          ':input[name="filters[htmlpurifier][settings][htmlpurifier_help]"]' => array(
            'checked' => FALSE,
          ),
        ),
      ),
    );

    // Sort the radio values by weight
    uasort($info_list, '_htmlpurifier_option_cmp');
    $options = array();
    foreach ($info_list as $name => $info) {
      $options += array(
        $name => t($info['name']) . ': ' . t($info['description'])
      );
    }

    // Radio buttons for the config you want to use
    $form['htmlpurifier_config_name'] = array(
      '#title' => t("Choose an HTML Purifier Configuration"),
      '#type' => 'radios',
      '#required' => TRUE,
      '#options' => $options,
      '#default_value' => $config_name,
      '#ajax' => array(
        'callback' => '_htmlpurifer_config_form_ajax_callback',
        'wrapper' => 'htmlpurifier_config_form',
      ),
    );

    // Generate the custom HTML Purifier Config form
    $reset = FALSE; // See the @TODO below regarding htmlpurifier_config_reset
    $config = _htmlpurifier_get_config($this, $config_name, $reset);
    $config_form = new \HTMLPurifier_Printer_ConfigForm($config_name . '_config', 'http://htmlpurifier.org/live/configdoc/plain.html#%s');
    $allowed = $info_list[$config_name]['allowed'];
    $rendered_form = $config_form->render($config, $allowed, FALSE);

    $intro = '<div class="form-item"><div class="description">'
      . t('Please click on a directive name for more information on what it does before enabling or changing anything!
        Changes will not apply to old entries until you clear the cache (see the <a href="@url">settings page</a>).',
        array('@url' => Url::fromRoute('htmlpurifier.config')->toString())) . '</div></div>';

    // This entire form element will be replaced whenever
    // 'htmlpurifier_config_name' is updated.
    $form['htmlpurifier_config_form'] = array(
      '#markup' => $intro . $rendered_form,
      '#prefix' => '<div id="htmlpurifier_config_form">',
      '#suffix' => '</div>',
      //'#after_build' => array('_htmlpurifier_set_config'),
    );
    // @TODO: Figure out if this would replace the #after_build and why we would need it
    //$this->setConfig($form_state);

    $form['#attached']['library'][] = 'htmlpurifier/config_form';

    // @TODO: Find out why this doesn't work
    /*$form['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#value' => \HTMLPurifier_Printer_ConfigForm::getJavaScript(),
        '#attributes' => array('src' => ''),
      ],
      'htmlpurifier_printer_config_form',
    ];*/

    // @TODO Set the reset button and variable.
    /*
    // Check if we need to reset the form
    $reset = isset($form_state->getValue('filters')['htmlpurifier']['htmlpurifier_config_reset']) ? $form_state->getValue('filters')['htmlpurifier']['htmlpurifier_config_reset'] : FALSE;

    // Adds a new button to clear the form.
    // There is no difference between the validate and submit handler
    // so they are both pointed to the same function.
    $form['htmlpurifier_config_reset'] = array(
      '#type' => 'submit',
      '#value' => 'Reset to Defaults',
      '#validate' => array('_htmlpurifier_config_reset'),
      '#submit' => array('_htmlpurifier_config_reset'),
    );*/

    return $form;
  }

  /**
   * Fills out the form state with extra post data originating from the
   * HTML Purifier configuration form.
   */
  protected function setConfig(FormStateInterface &$form_state) {
    /** @TODO: Figure out why this is expecting an input value. */
    if (isset($form_state->getValue('input')['htmlpurifier_config'])) {
      $htmlpurifier_config = $form_state->getValue('input')['htmlpurifier_config'];

      // Copy over the values from the custom HTML Purifier Config form into the settings array
      $filters = $form_state->getValue('filters');
      $filters['htmlpurifier']['settings']['htmlpurifier_config'] = $htmlpurifier_config;
      $form_state->setValue('filters', $filters);

      // Do some sanity checking for CSSTidy
      if (!empty($htmlpurifier_config['Filter.ExtractStyleBlocks'])) {
        if (!empty($htmlpurifier_config['Null_Filter.ExtractStyleBlocks.Scope'])) {
          //dpm($htmlpurifier_config['Null_Filter.ExtractStyleBlocks.Scope']);
          drupal_set_message(
            "You have not set <code>Filter.ExtractStyleBlocks.Scope</code>; this means that users can add CSS that affects all of your Drupal theme and not just their content block.  It is recommended to set this to <code>#node-[%HTMLPURIFIER:NID%]</code> (including brackets) which will automatically ensure that CSS directives only apply to their node.",
            'warning', FALSE);
        }
        elseif (!isset($htmlpurifier_config['Filter.ExtractStyleBlocks.Scope'])
          || $htmlpurifier_config['Filter.ExtractStyleBlocks.Scope'] !== '#node-[%HTMLPURIFIER:NID%]'
        ) {
          drupal_set_message(
            "You have enabled Filter.ExtractStyleBlocks.Scope, but you did not set it to <code>#node-[%HTMLPURIFIER:NID%]</code>; CSS may not work unless you have special theme support.",
            'warning', FALSE);
        }
      }
    }
  }
}
