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
      const preFilters = drupalSettings.openagenda.preFilters;
      const agenda = document.querySelector('.oa-agenda');

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
          filtersRef: null,
          values: null,
          queryParams: null,
          onLoad: async (values, aggregations, filtersRef, _form) => {
            oa.filtersRef = filtersRef;
            oa.values = values;
            let queryParams = {...values, aggregations};
            oa.queryParams = queryParams;
            computeQueryParams(preFilters,true);

            // Update filters and widgets.
            oa.updateFiltersAndWidgets();
          },
          onFilterChange: async (values, aggregations, filtersRef, _form) => {
            oa.filtersRef = filtersRef;
            oa.values = values;
            let queryParams = {...values, aggregations};
            oa.queryParams = queryParams;
            computeQueryParams(preFilters,false);

            // Show Ajax Throbber, automatically removed when content is replaced/page reloaded.
            $('#oa-wrapper').append(Drupal.theme.ajaxProgressIndicatorFullscreen());

            // Load event then update filters.
            Drupal.ajax({
              url: ajaxUrl + '?' + $.param(oa.queryParams)
            }).execute();
          },
          updateFiltersAndWidgets: async () => {
            // Update location, filters & widgets.
            if (!oa.filtersRef) {
              return;
            }
            $.ajax({
              url: filtersUrl + '?' + $.param(oa.queryParams),
              type: 'GET',
              dataType: 'json',
              async: false,
              complete: (data) => {
                oa.filtersRef.updateFiltersAndWidgets(oa.values, data.responseJSON);
                oa.filtersRef.updateLocation(oa.values);
              }
            });
          }
        };
      }

      // Agenda mutation observer.
      if (agenda !== undefined) {
        const observer = new MutationObserver((mutations, observer) => {
          oa.updateFiltersAndWidgets();
        });
        observer.observe(agenda, {
          subtree: true,
          attributes: true,
        });
      }

      // Mix preFilters & oa queryParams/values.
      const computeQueryParams = (preFilters, onLoad) => {
        let preFiltersEntries =  Object.entries(preFilters);

        // Apply preFilters.
        preFiltersEntries.forEach((preFilter) => {
          if (!oa.queryParams[preFilter[0]] || onLoad) {
            oa.queryParams[preFilter[0]] = preFilter[1];
            oa.values[preFilter[0]] = preFilter[1];
          }
        });

        // Do not apply relative filter if timing filter is used.
        if (oa.queryParams['timings'] && oa.queryParams['relative']) {
          oa.queryParams['relative'] = undefined;
          oa.values['relative'] = undefined;
        }
      }

    }
  };
})(jQuery, Drupal, drupalSettings);
