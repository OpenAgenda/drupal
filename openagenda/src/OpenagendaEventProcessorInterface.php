<?php

namespace Drupal\openagenda;

/**
 * Interface for OpenagendaAgendaProcessor.
 *
 * Prepares an agenda's data prior to display.
 */
interface OpenagendaEventProcessorInterface {

  /**
   * Process relative timing to event.
   *
   * @param array $event
   *   The event to parse.
   *
   * @return string
   *   String representing relative timing to event.
   */
  public function processRelativeTimingToEvent(array $event);

  /**
   * Process an event's timetable.
   *
   * @param array $event
   *   Event to process.
   *
   * @return array
   *   An array of months and weeks with days and time range values.
   */
  public function processEventTimetable(array $event);

  /**
   * Process metadata for an event.
   *
   * @param array $event
   *   The event.
   *
   * @return array
   *   Metadata array attachable through html_head in the render array.
   */
  public function processEventMetadata(array $event);

}
