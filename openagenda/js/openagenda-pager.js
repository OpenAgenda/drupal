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
      $( 'body' ).on( 'click', '#openagenda-wrapper .pager a', function( event ) {
        event.preventDefault();
        let ajaxUrl = 'openagenda/' + drupalSettings.openagenda.nid + '/ajax';

        if (!$(this).closest( '.pager__item' ).hasClass('is-active')) {
          ajaxUrl += $(this).attr('href');

          // Show Ajax Throbber, automagically removed when content is replaced/page reloaded.
          $( '#openagenda-wrapper' ).append(Drupal.theme.ajaxProgressIndicatorFullscreen());

          // Ajax query execution.
          Drupal.ajax({
            url: Drupal.url(ajaxUrl),
          }).execute().done(function() {
            document.getElementById('openagenda-wrapper').scrollIntoView();
          });
        }
      });
    }
  };
})(jQuery, Drupal, drupalSettings);