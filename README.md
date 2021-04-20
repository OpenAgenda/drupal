OpenAgenda
==========

INTRODUCTION
------------

This modules allows you to integrate agendas from [OpenAgenda](https://www.openagenda.com) on your Drupal site.


REQUIREMENTS
------------

Pick the branch corresponding to your Drupal installation:
- `master` is compatible with Drupal 8.8+/9.
- `8.3-8.7` is compatible with Drupal 8.3 to 8.7.
- `8.0-8.2` is compatible with Drupal 8.0 to 8.2.

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
The module includes an implementation of 5 filter types - map, calendar, relative date, per tag and  
search field.  
These filters can be added through the block interface (`Admin > Structure > Block Layout`) to any  
of your theme's region.  
The map filter (tiles type) and the search filter(input field placeholder) have custom configuration options  
available in their block's settings.

The active tags block displays the currently active filters.

Alternatively, the filters can be integrated directly into the Twig template files of your agenda.  
You can either directly use Twig includes :
```
{% include "openagenda-active-tags.html.twig" %}
{% include "openagenda-search-filter.html.twig" with {'placeholder': 'Search'} %}
```
or add extra variables in a preprocess function, and print theme into your Twig file :
```
function mytheme_preprocess_openagenda_agenda(&$variables) {
  $variables['active_tags'] = [
    '#theme' => 'openagenda_active_tags',
    '#agenda_uid' => $variables['agenda_uid'],
    '#lang' => $variables['lang'],
  ];

  $variables['search_filter'] = [
    '#theme' => 'openagenda_search_filter',
    '#agenda_uid' => $variables['agenda_uid'],
    '#placeholder' => 'Search',
    '#lang' => $variables['lang'],
  ];
}
```

Check the `openagenda.module` file for the name of the theme hooks and the theming variables available.

EVENT VIEW
----------

To show the map when displaying an event, you can use the map filter block and check the `Show on events` option.  
An event timetable block is also available.

You can also include those in the Twig files (see Filters above).

THEMING
-------

Every display aspect of the module has a corresponding Twig template file sitting in the `templates` directory.  

To customize a template, copy the corresponding Twig file in your theme's directory. Additionnally, you may  
want to alter the variables available to the templates by adding a corresponding preprocess function in your  
`mytheme.theme` file.

Never directly modify the module's files!

See [Theming Drupal](https://www.drupal.org/docs/theming-drupal) for more information.