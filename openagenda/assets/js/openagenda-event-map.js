/**
 * @file
 * Contains the definition of the OpenAgenda event map behaviour.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.openAgendaEventMap = {
    attach: function () {

      // Map.
      let eventMap = () => {

        if (!$('#event-map').length || $('#event-map').hasClass('map-initialized')) {
          return;
        }

        $('#event-map').addClass('map-initialized');

        let map = L.map('event-map').setView([drupalSettings.openagenda.event.lat, drupalSettings.openagenda.event.lon], 12);
        L.tileLayer(drupalSettings.openagenda.event.mapTilesUrl, {
          minZoom: 5,
          maxZoom: 17
        }).addTo(map);

        // Event marker.
        let icon = L.icon({
          iconUrl: drupalSettings.openagenda.leaflet.markerUrl,
          iconSize: [36, 48],
          iconAnchor: [18, 45]
        });

        const marker = L.marker([drupalSettings.openagenda.event.lat, drupalSettings.openagenda.event.lon], { icon: icon }).addTo(map);

        clearInterval(mapInterval);
      }

      let mapInterval = setInterval(eventMap, 500);
    }
  };

})(jQuery, Drupal, drupalSettings);
