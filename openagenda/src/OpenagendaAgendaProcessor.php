<?php

namespace Drupal\openagenda;

use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Pager\PagerParametersInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class OpenagendaAgendaProcessor.
 *
 * Prepares an agenda's data prior to display.
 */
class OpenagendaAgendaProcessor implements OpenagendaAgendaProcessorInterface {
  use StringTranslationTrait;

  /**
   * OpenAgenda connector service.
   *
   * @var \Drupal\openagenda\OpenagendaConnectorInterface
   */
  protected $connector;

  /**
   * The OpenAgenda helper service.
   *
   * @var \Drupal\openagenda\OpenagendaHelperInterface
   */
  protected $helper;

  /**
   * The request stack to access request parameter.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The pager manager service.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * The pager parameters service.
   *
   * @var \Drupal\Core\Pager\PagerParametersInterface
   */
  protected $pagerParameters;

  /**
   * {@inheritdoc}
   */
  public function __construct(OpenagendaConnectorInterface $connector, OpenagendaHelperInterface $helper, RequestStack $request_stack, PagerManagerInterface $pager_manager, PagerParametersInterface $pager_parameters) {
    $this->connector = $connector;
    $this->helper = $helper;
    $this->requestStack = $request_stack;
    $this->pagerManager = $pager_manager;
    $this->pagerParameters = $pager_parameters;
  }

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
  public function buildRenderArray(EntityInterface $entity) {
    $build = [];

    if ($entity->hasField('field_openagenda')) {
      $agenda_uid = $entity->get('field_openagenda')->uid;
      $events_per_page = $entity->get('field_openagenda')->events_per_page;
      // Get request parameters : page.
      $offset = $this->pagerParameters->findPage() * $events_per_page;
      // Get request parameters : filters.
      $oaq = $this->requestStack->getCurrentRequest()->get('oaq');
      // If no oaq parameter in request, set filters to an empty array.
      $filters = !empty($oaq) ? $oaq : [];

      $data = $this->connector->getAgenda($agenda_uid, $filters, $offset, $events_per_page);

      if (isset($data['success']) && $data['success'] == FALSE) {
        $build = [
          '#markup' => $this->t("This agenda doesn't exist."),
        ];
      }
      else {
        $build = [
          '#theme' => 'openagenda_agenda',
          '#entity' => $entity,
          '#events' => !empty($data['events']) ? $data['events'] : [],
          '#total' => !empty($data['total']) ? $data['total'] : 0,
          '#filters' => !empty($data['filters']) ? $data['filters'] : [],
          '#lang' => $this->helper->getPreferredLanguage($entity->get('field_openagenda')->language),
          '#attached' => [
            'library' => [
              'openagenda/openagenda.agenda',
              'openagenda/openagenda.pager',
            ],
            'drupalSettings' => [
              'openagenda' => ['nid' => $entity->id()],
            ],
          ],
        ];

        if (!empty($data['total'])) {
          $this->pagerManager->createPager($data['total'], $events_per_page);
          $build['#pager'] = ['#type' => 'pager'];
        }
      }
    }

    return $build;
  }

}
