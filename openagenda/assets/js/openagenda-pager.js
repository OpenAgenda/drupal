/**
 * @file
 * Contains the definition of the OpenAgenda pager behaviour.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.openAgendaPager = {
    attach: function (context) {
      // Load only once.
      if (context !== document) {
        return;
      }

      // Pager navigation.
      $('body').on('click', '#oa-wrapper .pager__link', (event) => {
        event.preventDefault();
        event.stopPropagation();
        const link = $(event.target);
        let ajaxUrl = 'openagenda/' + drupalSettings.openagenda.nid + '/ajax' + link.attr('href');
        let urlSearchParams = new URLSearchParams(ajaxUrl);

        // Show Ajax Throbber, automatically removed when content is replaced/page reloaded.
        $('#oa-wrapper').append(Drupal.theme.ajaxProgressIndicatorFullscreen());

        // Ajax query execution.
        Drupal.ajax({
          url: Drupal.url(ajaxUrl),
        }).execute().done(() => {
          document.getElementById('oa-wrapper').scrollIntoView();
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
