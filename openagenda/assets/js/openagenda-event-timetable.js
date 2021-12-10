/**
 * @file
 * Contains the definition of the OpenAgenda event timetable behaviour.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.eventTimetable = {
    attach: function (context) {

      // Event timetable navigation.
      $( '.js_timings .js_next' ).click( navTimings.bind( null, 'next' ) );
      $( '.js_timings .js_prev' ).click( navTimings.bind( null, 'prev' ) );

      function navTimings( direction, e ) {
        $( e.target ).closest( '.js_month' ).removeClass( 'displayed' )[ direction ]().addClass( 'displayed' );
      }

    }
  };
})(jQuery, Drupal, drupalSettings);
