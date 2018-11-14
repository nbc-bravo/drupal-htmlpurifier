<?php

namespace Drupal\htmlpurifier\EventSubscriber;

use Drupal\htmlpurifier\Event\ConfigLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * HTML Purifier event subscriber.
 */
class HtmlPurifierSubscriber implements EventSubscriberInterface {

  /**
   * Config loaded event handler.
   *
   * @param \Drupal\htmlpurifier\Event\ConfigLoadedEvent $event
   *   The ConfigLoaded event.
   */
  public function onConfigLoaded(ConfigLoadedEvent $event) {
    // Define the entity_embed module's <drupal-entity> tag.
    $purifier_config = $event->getConfig();
    $def = $purifier_config->getHTMLDefinition(TRUE);
    $def->addElement(
      'drupal-entity',
      'Block',
      'Flow',
      'Common',
      [
        'data-embed-button' => 'CDATA',
        'data-entity-embed-display' => 'CDATA',
        'data-entity-id' => 'CDATA',
        'data-entity-type' => 'CDATA',
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ConfigLoadedEvent::EVENT_NAME => ['onConfigLoaded'],
    ];
  }

}
