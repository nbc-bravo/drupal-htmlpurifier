<?php

namespace Drupal\Tests\htmlpurifier\Kernel;

use Drupal\Component\Serialization\Yaml;
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
    self::assertSame($expected, $processed);
  }

  public function testRemoveEmpty() {
    $input = '<a></a>';
    $expected = '<a></a>';
    $processed = $this->filter->process($input, 'und')->getProcessedText();
    self::assertSame($expected, $processed);

    $configuration = [
      'AutoFormat' => [
        'RemoveEmpty' => TRUE,
      ],
    ];
    $this->filter->settings['htmlpurifier_configuration'] = Yaml::encode($configuration);

    $expected = '';
    $processed = $this->filter->process($input, 'und')->getProcessedText();
    self::assertSame($expected, $processed);
  }

}
