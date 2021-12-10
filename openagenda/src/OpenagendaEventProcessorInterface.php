<?php

namespace Drupal\openagenda;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Interface for OpenagendaAgendaProcessor.
 *
 * Prepares an agenda's data prior to display.
 */
interface OpenagendaEventProcessorInterface {

  /**
   * Build an event's render array.
   *
   * @param array $event
   *   The event to render.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the event relates to (agenda).
   *
   * @return array
   *   An agenda's render array or a simple markup to report
   *   that no agenda was found.
   */
  public function buildRenderArray(array $event, EntityInterface $entity);

  /**
   * Process relative timing to event.
   *
   * @param array $event
   *   The event to parse.
   * @param string $lang
   *   Language code for date format.
   *
   * @return TranslatableMarkup|null
   *   TranslatableMarkup representing relative timing to event.
   */
  public function processRelativeTimingToEvent(array $event, string $lang = 'default');

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
