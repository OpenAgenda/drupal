<?php

namespace Drupal\openagenda;

/**
 * Interface for OpenagendaConnector.
 */
interface OpenagendaConnectorInterface {

  /**
   * Get agenda.
   *
   * @param string $agenda_uid
   *   The agenda UID.
   *
   * @return array
   *   Data from the OpenAgenda server, including an event array.
   */
  public function getAgenda(string $agenda_uid);

  /**
   * Get agenda settings.
   *
   * @param string $agenda_uid
   *   The agenda UID.
   *
   * @return array
   *   Data from the Openagenda server representing this agenda's settings.
   */
  public function getAgendaSettings(string $agenda_uid);

  /**
   * Get an event from its slug.
   *
   * @param string $agenda_uid
   *   The agenda UID.
   * @param string $event_slug
   *   Slug of the event to get.
   *
   * @return array
   *   An array representing an event in this agenda.
   */
  public function getEventBySlug(string $agenda_uid, string $event_slug);

  /**
   * Get next event slug in a filter configuration.
   *
   * @param string $agenda_uid
   *   The agenda UID.
   * @param array $filters
   *   An array of filter parameters.
   * @param int $offset
   *   Current offset.
   *
   * @return string
   *   The slug corresponding to the next event in this agenda.
   */
  public function getNextEventSlug(string $agenda_uid, array $filters, int $offset);

  /**
   * Get previous event slug in a filter configuration.
   *
   * @param string $agenda_uid
   *   The agenda UID.
   * @param array $filters
   *   An array of filter parameters.
   * @param int $offset
   *   Current offset.
   *
   * @return string
   *   The slug corresponding to the previous event in this agenda.
   */
  public function getPreviousEventSlug(string $agenda_uid, array $filters, int $offset);

  /**
   * Get event triplet (previous, current, next).
   *
   * @param string $agenda_uid
   *   The agenda UID.
   * @param array $filters
   *   An array of filter parameters.
   * @param int $offset
   *   Get events starting from that offset.
   *
   * @return array
   *   An array with three events (pevious, current, next).
   */
  public function getEventTriplet(string $agenda_uid, array $filters, int $offset);

}
