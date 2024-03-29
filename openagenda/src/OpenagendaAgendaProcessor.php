<?php

namespace Drupal\openagenda;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Pager\PagerParametersInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
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
  public $connector;

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
   * OpenAgenda module configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(OpenagendaConnectorInterface $connector, OpenagendaHelperInterface $helper, RequestStack $request_stack, PagerManagerInterface $pager_manager, PagerParametersInterface $pager_parameters) {
    $this->config = \Drupal::config('openagenda.settings');
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
   * @param bool|null $ajax
   *   Whether it is an ajax or not.
   *
   * @param int|null $page
   *   Whether it is an ajax or not.
   *
   * @return array
   *   The render array.
   */
  public function buildRenderArray(EntityInterface $entity, ?bool $ajax = FALSE, ?int $page = NULL) {
    if (!$entity->hasField('field_openagenda')) {
      return [];
    }

    // Get request parameters : page.
    $size = (int) $entity->get('field_openagenda')->events_per_page;
    $from = $page ?? $this->pagerParameters->findPage() * $size;

    // Get request filters.
    $queryInfo = UrlHelper::parse($this->requestStack->getCurrentRequest()->getUri());
    $filters = $queryInfo['query'];

    // Remove pager params.
    unset($filters['page']);

    // Get pre-filters and add them to filters if defined.
    $preFilters = $this->helper->getPreFilters($entity);

    // Current & upcoming events only.
    $currentValue = $entity->get('field_openagenda')->current;
    if (!empty($currentValue)) {
      $preFilters['relative'] = [
        'current',
        'upcoming',
      ];
    }
    $filters += $preFilters;

    // Get events.
    $agenda_uid = $entity->get('field_openagenda')->uid;
    $filters += ['detailed' => 1];
    $data = $this->connector->getAgendaEvents($agenda_uid, $filters, $from, $size);

    // Success ?
    if (isset($data['success']) && $data['success'] == FALSE) {
      return [
        '#markup' => $this->t("This agenda doesn't exist."),
      ];
    }

    // Security if page is higher than page count.
    $total = !empty($data['total']) ? $data['total'] : 0;
    if ($from > $total) {
      $page = floor(($total - 1) / $size) + 1;

      return $this->buildRenderArray($entity, FALSE, $page);
    }

    // Localize events.
    $events = $data['events'];
    $lang = $entity->get('field_openagenda')->language;
    foreach ($events as $key => &$event) {
      $this->helper->localizeEvent($event, $lang);
    }

    // Build render array.
    $language = $this->helper->getPreferredLanguage($entity->get('field_openagenda')->language);
    $nid = $entity->id();
    $build = [
      '#theme' => 'openagenda_agenda',
      '#entity' => $entity,
      '#events' => !empty($data['events']) ? $data['events'] : [],
      '#total' => $total,
      '#from' => $from,
      '#lang' => $language,
      '#columns' => $this->config->get('openagenda.default_columns', 3),
      '#filters' => $filters,
      '#ajax' => $ajax,
      '#attached' => [
        'library' => [
          'openagenda/openagenda.pager',
        ],
        'drupalSettings' => [
          'openagenda' => [
            'nid' => $nid,
            'lang' => $language,
            'ajaxUrl' => base_path() . Url::fromRoute('openagenda.ajax', ['node' => $nid])->getInternalPath(),
            'filtersUrl' => base_path() . Url::fromRoute('openagenda.filters', ['node' => $nid])->getInternalPath(),
            'preFilters' => $preFilters,
          ],
        ],
      ],
    ];

    if (!$ajax) {
      $style = $this->config->get('openagenda.default_style', 'default');
      $build['#attached']['library'][] = 'openagenda/openagenda.style.' . $style;
    }

    // Add pager if needed.
    if (!empty($data['total'])) {
      $this->pagerManager->createPager($total, $size);
      $build['#pager'] = ['#type' => 'pager'];
    }

    return $build;
  }

}
