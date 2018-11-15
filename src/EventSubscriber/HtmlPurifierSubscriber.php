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
    $purifier_config = $event->getConfig();
    $def = $purifier_config->getHTMLDefinition(TRUE);

    // Define the Entity Embed module's <drupal-entity> tag.
    $def->addElement(
      'drupal-entity',
      'Block',
      'Flow',
      'Common',
      [
        'data-align' => 'CDATA',
        'data-caption' => 'CDATA',
        'data-cke-widget-data' => 'CDATA',
        'data-cke-widget-upcasted' =>'CDATA',
        'data-cke-widget-keep-attr' => 'CDATA',
        'data-embed-button' => 'CDATA',
        'data-entity-embed-display' => 'CDATA',
        'data-entity-embed-display-settings' => 'CDATA',
        'data-entity-id' => 'CDATA',
        'data-entity-uuid' => 'CDATA',
        'data-entity-type' => 'CDATA',
        'data-view-mode' => 'CDATA',
        'data-widget' => 'CDATA',
      ]
    );

    // Define the URL Embed module's drupal-url element.
    $def->addElement(
      'drupal-url',
      'Block',
      'Flow',
      'Common',
      [
        'data-embed-button' => 'CDATA',
        'data-embed-url' => 'CDATA',
        'data-label' => 'CDATA',
        'data-url-provider' => 'CDATA',
      ]
    );

    // Define the additional attributes that LinkIt adds to anchor tags.
    $def->addAttribute('a', 'data-entity-href', 'URI');
    $def->addAttribute('a', 'data-entity-substitution', 'CDATA');
    $def->addAttribute('a', 'data-entity-title', 'CDATA');
    $def->addAttribute('a', 'data-entity-type', 'CDATA');
    $def->addAttribute('a', 'data-entity-uuid', 'CDATA');
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
