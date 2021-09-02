INSTALLATION
============

* Run composer require drupal/htmlpurifier.
* Enable the module.

CONFIGURATION
=============
* Navigate to /admin/config/content/formats.
* Choose which text formats should use HTML purifier by enabling it's filter.
* After enabling the filter, review and edit it's configuration. Usually,
  you will need to allow additional tags/attributes at the HTML.Allowed
  property.

It is recommended that you place HTML Purifier as the last filter in the text format.
Reorder the filters if necessary.

TROUBLESHOOTING
===============

Q. Even though I add custom tags and attributes to HTML.Allowed, HTMLPurifier
filters them out.

A. HTMLPurifier needs to understand the type and contents of custom tags and
attributes. Otherwise, even if you enable them at HTML.Allowed, they will
be removed from the text. This module implements a custom event to define
custom tags and attributes. Have a look at src/EventSubscriber/HtmlPurifierSubscriber.php
for a few custom tags and attributes defined by contributed modules. For further
details, read http://htmlpurifier.org/docs/enduser-customize.html.

After implementing an event subscriber that defines your custom tags/attributes
enable them at the HTML.Allowed property of the HTMLPurifier configuration.
