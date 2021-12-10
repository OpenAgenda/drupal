/**
 * @file
 * Contains the definition of the OpenAgenda filters behaviour.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.openAgendaFilters = {
    attach: function (context) {
      // Load only once.
      if (context !== document) {
        return;
      }

      const ajaxUrl = drupalSettings.openagenda.ajaxUrl;
      const filtersUrl = drupalSettings.openagenda.filtersUrl;

      // window.oa.
      if (typeof window.oa === 'undefined') {
        window.oa = {
          query: window.location.search,
          locale: drupalSettings.openagenda.lang,
          locales: {
            en: {
              eventsTotal: '{total, plural, =0 {No events match this search} one {{total} event} other {{total} events}}'
            },
            fr: {
              eventsTotal: '{total, plural, =0 {Aucun événement ne correspond à cette recherche} one {{total} événement} other {{total} événements}}'
            }
          },
          res: drupalSettings.openagenda.filtersUrl,
          onLoad: async (values, aggregations, filtersRef, _form) => {
            values.size = 0;
            const queryParams = {...values, aggregations};

            // Get events with ajax to update filters.
            $.ajax({
              url: filtersUrl + '?' + $.param(queryParams),
              type: 'GET',
              dataType: 'json',
              success(result) {
                // Update widgets.
                filtersRef.updateFiltersAndWidgets(values, result);
              },
            });
          },
          onFilterChange: async (values, aggregations, filtersRef, _form) => {
            // Ajax Drupal add.
            const queryParams = {...values, aggregations};

            // Show Ajax Throbber, automatically removed when content is replaced/page reloaded.
            $( '#oa-wrapper' ).append('<div class="ajax-progress ajax-progress-fullscreen">&nbsp;</div>');

            // Ajax query execution.
            Drupal.ajax({
              url: ajaxUrl + '?' + $.param(queryParams)
            }).execute();

            // Get events with ajax to update filters.
            $.ajax({
              url: filtersUrl + '?' + $.param(queryParams),
              type: 'GET',
              dataType: 'json',
              success(result) {
                // Update location & widgets.
                filtersRef.updateLocation(values);
                filtersRef.updateFiltersAndWidgets(values, result);
              },
            });
          }
        };
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
