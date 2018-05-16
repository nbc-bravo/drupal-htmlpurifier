<?php

namespace Drupal\Tests\htmlpurifier\Kernel;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Form\FormState;
use Drupal\filter\FilterPluginCollection;
use Drupal\KernelTests\KernelTestBase;

class HtmlPurifierFilterTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'filter', 'htmlpurifier'];

  /**
   * @var \Drupal\htmlpurifier\Plugin\Filter\HtmlPurifierFilter
   */
  protected $filter;

  protected function setUp() {
    parent::setUp();

    $manager = $this->container->get('plugin.manager.filter');
    $bag = new FilterPluginCollection($manager, []);
    $this->filter = $bag->get('htmlpurifier');
  }

  public function testMaliciousCode() {
    $input = '<img src="javascript:evil();" onload="evil();" />';
    $expected = '';
    $processed = $this->filter->process($input, 'und')->getProcessedText();
    $this->assertSame($expected, $processed);
  }

  public function testRemoveEmpty() {
    $input = '<a></a>';
    $expected = '<a></a>';
    $processed = $this->filter->process($input, 'und')->getProcessedText();
    $this->assertSame($expected, $processed);

    $configuration = [
      'AutoFormat' => [
        'RemoveEmpty' => TRUE,
      ],
    ];
    $this->filter->settings['htmlpurifier_configuration'] = Yaml::encode($configuration);

    $expected = '';
    $processed = $this->filter->process($input, 'und')->getProcessedText();
    $this->assertSame($expected, $processed);
  }

  public function testConfigurationValidation() {
    $element = [
      '#parents' => [
        'filters',
        'htmlpurifier',
        'settings',
        'htmlpurifier_configuration',
      ],
    ];
    $error_key = 'filters][htmlpurifier][settings][htmlpurifier_configuration';

    // Test empty configuration.
    $form_state = new FormState();
    $filters = [
      'htmlpurifier' => [
        'settings' => [
          'htmlpurifier_configuration' => '',
        ],
      ],
    ];
    $form_state->setValue('filters', $filters);
    $this->filter->settingsFormConfigurationValidate($element, $form_state);
    $errors = [$error_key => 'HTMLPurifier configuration is not valid. Error: Invalid argument supplied for foreach()'];
    $this->assertSame($errors, $form_state->getErrors());

    $purifier_config = \HTMLPurifier_Config::createDefault();
    $default_configuration = Yaml::encode($purifier_config->getAll());

    // Test default configuration gives no errors.
    $form_state = new FormState();
    $filters['htmlpurifier']['settings']['htmlpurifier_configuration'] = $default_configuration;
    $form_state->setValue('filters', $filters);
    $this->filter->settingsFormConfigurationValidate($element, $form_state);
    $errors = [];
    $this->assertSame($errors, $form_state->getErrors());

    // Test null value for a bool expected value.
    $form_state = new FormState();
    $configuration = str_replace('RemoveEmpty: false', 'RemoveEmpty: null', $default_configuration);
    $filters['htmlpurifier']['settings']['htmlpurifier_configuration'] = $configuration;
    $form_state->setValue('filters', $filters);
    $this->filter->settingsFormConfigurationValidate($element, $form_state);
    $errors = [$error_key => 'Value for AutoFormat.RemoveEmpty is of invalid type, should be bool'];
    $this->assertSame($errors, $form_state->getErrors());

    // Test a fake directive.
    $form_state = new FormState();
    $configuration = str_replace('RemoveEmpty:', 'FakeDirective:', $default_configuration);
    $filters['htmlpurifier']['settings']['htmlpurifier_configuration'] = $configuration;
    $form_state->setValue('filters', $filters);
    $this->filter->settingsFormConfigurationValidate($element, $form_state);
    $errors = [$error_key => 'Cannot set undefined directive AutoFormat.FakeDirective to value'];
    $this->assertSame($errors, $form_state->getErrors());

    // Test malformed yaml.
    $form_state = new FormState();
    $configuration = str_replace('RemoveEmpty: false', 'UnexpectedString', $default_configuration);
    $filters['htmlpurifier']['settings']['htmlpurifier_configuration'] = $configuration;
    $form_state->setValue('filters', $filters);
    $this->filter->settingsFormConfigurationValidate($element, $form_state);
    $this->assertStringStartsWith( 'Unable to parse', $form_state->getErrors()[$error_key]);
  }

}
