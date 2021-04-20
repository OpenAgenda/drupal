/**
 * @file
 * Contains the definition of the OpenAgenda filters behaviour.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.openAgenda = {
    attach: function (context) {
      // Load only once.
      if (context !== document) {
        return;
      }

      // window.oa could be instantiated previously.
      if (typeof window.oa === 'undefined') {
        window.oa = {};
      }

      // Event shot when a filter value changes.
      window.oa.onWidgetUpdate = (filter, update, query) => {
        
        // Make sure we have the necessary settings available.
        if (!$.isEmptyObject(drupalSettings.openagenda)) {
          let ajaxUrl = 'openagenda/' + drupalSettings.openagenda.nid + '/ajax';
          let ajaxQueryParameters = {};
          
          if (!$.isEmptyObject(query)) {
            ajaxQueryParameters.oaq = query;
          }

          // If we are showing an event, add an extra parameter to tell the system we need
          // to trigger a redirect.
          if (drupalSettings.openagenda.isEvent) {
            ajaxQueryParameters.is_event = '1';
          }

          ajaxUrl += (!$.isEmptyObject(ajaxQueryParameters)) ? '?' + $.param(ajaxQueryParameters) : '';

          // Show Ajax Throbber, automagically removed when content is replaced/page reloaded.
          $( '#openagenda-wrapper' ).append('<div class="ajax-progress ajax-progress-fullscreen">&nbsp;</div>');

          // Ajax query execution.
          Drupal.ajax({
            url: Drupal.url(ajaxUrl)
          }).execute();

          // Strip the page parameter from history state to keep the address bar in sync.
          if (typeof window.history !== 'undefined' || typeof window.history.pushState !== 'undefined') {
            if (window.location.search) {
              const search = /([^&=]+)=?([^&]*)/g;

              let currentRequest = window.location.href.split('?'),
                match,
                url = currentRequest[0],
                queryString = currentRequest[1],
                parameters = '';
              
              while (match = search.exec(queryString)) {
                if (match[1] != 'page') {
                  if (parameters != '') {
                    parameters += '&';
                  }

                  parameters += match[1] + '=' + match[2];
                }
              }

              if (parameters) {
                url += '?' + parameters;
              }

              history.replaceState(query, null, url);
            }
          }
        }
      };
    }
  };
})(jQuery, Drupal, drupalSettings);