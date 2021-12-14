OpenAgenda
==========

INTRODUCTION
------------

This modules allows you to integrate agendas from [OpenAgenda](https://www.openagenda.com) on your Drupal site.

REQUIREMENTS
------------

OpenAgenda requires at least Drupal 8.0:
- `3.x` versions are compatible with Drupal 8.8+/9.
- `8.x-2.x` versions are compatible with Drupal 8.3 to 8.7.
- `8.x-1.x` versions are compatible with Drupal 8.0 to 8.2.

OpenAgenda requires openagenda/sdk-php >= 1.0.0.

INSTALLATION
------------

Install as usual, see [Installing Modules](https://www.drupal.org/docs/extending-drupal/installing-modules) for further information.

CONFIGURATION
-------------

1. Navigate to the settings form through `Admin > Configuration > Web services > OpenAgenda`   
or directly at path `/admin/config/services/openagenda`
2. Enter your OpenAgenda key in the corresponding field.
3. The other settings set up the default configuration options for Openagenda nodes and filters.

USAGE
-----
The module creates an OpenAgenda content type, containing one OpenAgenda field, in which  
you enter the UID of the agenda you want to display.

Alternatively, you can re-use the OpenAgenda field and attach it to your own content types.

FILTERS
-------

The module includes an implementation of 5 filter types using react.js: text search, map, calendar, relative date and cities.  
These filters can be added through the block interface (`Admin > Structure > Block Layout`) to any  
of your theme's region. Agenda events are refreshed on filters on change event.
The map filter (tiles type) and the search filter(input field placeholder) have custom configuration options  
available in their block's settings.

Additional agenda blocks are availables :
- The active filters block displays the currently active filters.
- The total results block displays the total of events for active filters.

Check the `openagenda.module` file for the name of the theme hooks and the theming variables available.

EVENT VIEW
----------

To show the map when displaying an event, you can use the event map block.
An event timetable block is also available.

You can also include those in the Twig files (see Filters above).

THEMING
-------

Agenda is displayed using OpenAgenda style by default, adding a css. This can be disabled in OpenAgenda settings.

Every display aspect of the module has a corresponding Twig template file sitting in the `templates` directory.  

To customize a template, copy the corresponding Twig file in your theme's directory. Additionnally, you may  
want to alter the variables available to the templates by adding a corresponding preprocess function in your  
`mytheme.theme` file.

Never directly modify the module's files!

See [Theming Drupal](https://www.drupal.org/docs/theming-drupal) for more information.
