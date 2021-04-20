<?php

namespace Drupal\openagenda;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for OpenagendaAgendaProcessor.
 *
 * Prepares an agenda's data prior to display.
 */
interface OpenagendaAgendaProcessorInterface {

  /**
   * Build an agenda's render array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity with a field_openagenda attached to it.
   *
   * @return array
   *   An agenda's render array or a simple markup to report
   *   that no agenda was found.
   */
  public function buildRenderArray(EntityInterface $entity);

}
