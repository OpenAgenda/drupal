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
     * @param bool|null $ajax
     *   Whether it is an ajax or not.
     *
     * @param int|null $page
     *   Whether it is an ajax or not.
     *
     * @return array
     *   The render array.
     */
    public function buildRenderArray(EntityInterface $entity, ?bool $ajax = FALSE, ?int $page = NULL);

}
