<?php

namespace Drupal\htmlpurifier\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when HTMLPurifier configuration is loaded.
 */
class ConfigLoadedEvent extends Event {

  const EVENT_NAME = 'htmlpurifier_config_loaded';

  /**
   * The HTMLPurifier configuration.
   *
   * @var \HTMLPurifier_Config
   */
  protected $config;

  /**
   * Constructs the object.
   *
   * @param \HTMLPurifier_Config $config
   *   The HTMLPurifier configuration.
   */
  public function __construct(\HTMLPurifier_Config $config) {
    $this->config = $config;
  }

  /**
   * Returns the HTMLPurifier configuration.
   *
   * @return \HTMLPurifier_Config
   *   The HTMLPurifier configuration.
   */
  public function getConfig() {
    return $this->config;
  }

}
