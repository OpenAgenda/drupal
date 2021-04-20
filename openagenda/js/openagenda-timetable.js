/**
 * @file
 * Contains the definition of the OpenAgenda event timetable behaviour.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.openAgendaTimetable = {
    attach: function (context) {
      // Load only once.
      if (context !== document) {
        return;
      }

      // Event timetable navigation.
      $( '.js_timings .js_next' ).click( navTimings.bind( null, 'next' ) );
      $( '.js_timings .js_prev' ).click( navTimings.bind( null, 'prev' ) );

      function navTimings( direction, e ) {
        $( e.target ).closest( '.js_month' ).removeClass( 'displayed' )[ direction ]().addClass( 'displayed' );
      }
    }
  };
})(jQuery, Drupal, drupalSettings);