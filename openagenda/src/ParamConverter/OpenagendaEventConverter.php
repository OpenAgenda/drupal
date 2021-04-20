<?php

namespace Drupal\openagenda\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\openagenda\OpenAgendaConnectorInterface;
use Drupal\openagenda\OpenagendaEventProcessorInterface;
use Drupal\openagenda\OpenAgendaHelperInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Converts parameters for upcasting event to full objoect.
 */
class OpenagendaEventConverter implements ParamConverterInterface {

  /**
   * Our OpenAgendaConnector service.
   *
   * @var \Drupal\openagenda\OpenagendaConnectorInterface
   */
  protected $connector;

  /**
   * The event processor service.
   *
   * @var \Drupal\openagenda\OpenagendaEventProcessorInterface
   */
  protected $eventProcessor;

  /**
   * The OpenAgenda helper service.
   *
   * @var \Drupal\openagenda\OpenagendaHelperInterface
   */
  protected $helper;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new OpenAgendaEventConverter.
   *
   * @param \Drupal\openagenda\OpenagendaConnectorInterface $connector
   *   The OpenAgenda connector service.
   * @param \Drupal\openagenda\OpenagendaEventProcessorInterface $event_processor
   *   The event processor service.
   * @param \Drupal\openagenda\OpenagendaHelperInterface $helper
   *   The OpenAgenda helper service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(OpenagendaConnectorInterface $connector, OpenagendaEventProcessorInterface $event_processor, OpenagendaHelperInterface $helper, RequestStack $request_stack) {
    $this->connector = $connector;
    $this->eventProcessor = $event_processor;
    $this->helper = $helper;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if (!empty($value) && !empty($defaults['node']) && $defaults['node']->hasField('field_openagenda')) {
      $agenda_uid = $defaults['node']->get('field_openagenda')->uid;
      $lang = $this->helper->getPreferredLanguage($defaults['node']->get('field_openagenda')->language);
      $request = $this->requestStack->getCurrentRequest();
      $oac = $request->get('oac');
      $base_url = $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo();

      // If an oac parameter is provided, we first try to get an event triplet
      // to get previous, current & next event in one request.
      if (!empty($oac)) {
        $context = $this->helper->decodeOac($oac);

        if (isset($context['index'])) {
          $filters = !empty($context['search']) ? $context['search'] : [];
          $event_triplet = $this->connector->getEventTriplet($agenda_uid, $filters, $context['index']);

          // We check at least a current event was found and also that its slug
          // matches with the url in case a wrong oac was given.
          if (!empty($event_triplet)
              && !empty($event_triplet['current'])
              && !empty($event_triplet['current']['slug'])
              && $event_triplet['current']['slug'] == $value) {
            $event = $event_triplet['current'];

            if (!empty($event_triplet['previous']) && !empty($event_triplet['previous']['slug'])) {
              $event['previousEventSlug'] = $event_triplet['previous']['slug'];
            }

            if (!empty($event_triplet['next']) && !empty($event_triplet['next']['slug'])) {
              $event['nextEventSlug'] = $event_triplet['next']['slug'];
            }
          }
        }
      }

      // Failing that, we try to get the event from its slug.
      if (empty($event)) {
        $event = $this->connector->getEventBySlug($agenda_uid, $value);
      }

      $event['baseUrl'] = $base_url;
      $event['timetable'] = $this->eventProcessor->processEventTimetable($event, $lang);

      return $event;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'openagenda_event');
  }

}
