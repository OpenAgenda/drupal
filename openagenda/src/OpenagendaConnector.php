<?php

namespace Drupal\openagenda;

use Drupal\Component\Serialization\Json;
use OpenAgendaSdk\OpenAgendaSdk;
use Symfony\Component\HttpFoundation\RequestStack;
use \Drupal;

/**
 * Class OpenagendaConnector.
 *
 * Gets data from the OpenAgenda server.
 */
class OpenagendaConnector implements OpenagendaConnectorInterface {

  /**
   * OpenAgenda SDK.
   *
   * @var OpenAgendaSdk
   */
  protected $sdk;

  /**
   * The request stack to access request parameter.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * OpenAgenda module configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * OpenagendaConnector constructor.
   *
   * @param RequestStack $request_stack
   *  Request stack.
   *
   * @throws \Exception
   */
  public function __construct(RequestStack $request_stack) {
    $this->config = \Drupal::config('openagenda.settings');
    $this->requestStack = $request_stack;
    $this->sdk = new OpenAgendaSdk($this->config->get('openagenda.public_key', ''));
  }

  /**
   * Get events from the OpenAgenda server.
   *
   * @param string $agenda_uid
   *   The agenda UID.
   * @param array $params
   *   An array of params for OpenAgenda SDK.
   *
   * @return array|mixed
   *   Data from the OpenAgenda server, including an event array.
   */
  protected function getData(string $agenda_uid, array $params = []) {
    $data = [];

    // Make request.
    try {
      $data = Json::decode($this->sdk->getEvents($agenda_uid, $params));
    } catch (\Exception $exception) {
      Drupal::logger('openagenda')->error($exception->getMessage());
    }

    return $data;
  }

  /**
   * Get agenda settings.
   *
   * @param string $agenda_uid
   *   The agenda UID.
   *
   * @return array
   *   Data from the Openagenda server representing this agenda's settings.
   */
  public function getAgendaSettings(string $agenda_uid) {
    $data = [];

    try {
      $data = Json::decode($this->sdk->getAgenda($agenda_uid));
    } catch (\Exception $exception) {
      Drupal::logger('openagenda')->error($exception->getMessage());
    }

    return $data;
  }

  /**
   * Get agenda events.
   *
   * @param string $agenda_uid
   *   The agenda UID.
   * @param array $filters
   *   An array of filter parameters.
   * @param int $from
   *   Get events starting from.
   * @param int $size
   *   Number of events to get.
   * @param string $sort
   *   Event sort parameter. One of the following values:
   *      - timingsWithFeatured.asc
   *      - timings.asc
   *      - updatedAt.desc
   *      - updatedAt.asc
   * @param bool $include_embedded
   *   Wether include embedded code in event html or not.
   *
   * @return array|mixed
   *   Data from the OpenAgenda server, including an event array.
   */
  public function getAgendaEvents(string $agenda_uid,
                                  array $filters = [],
                                  int $from = 0,
                                  int $size = self::DEFAULT_EVENTS_SIZE,
                                  string $sort = 'timingsWithFeatured.asc',
                                  bool $include_embedded = TRUE) {
    // Build param array.
    $params = $filters;
    $params += ['from' => $from];
    if (!isset($filters['size'])) {
      $params += ['size' => $size];
    }
    $params += ['sort' => $sort];
    $params += ['longDescriptionFormat' => $include_embedded ? 'HTMLWithEmbeds' : 'HTML'];

    return $this->getData($agenda_uid, $params);
  }

  /**
   * Get an event from its slug.
   *
   * @param string $agenda_uid
   *   The agenda UID.
   * @param string $slug
   *   Slug of the event to get.
   *
   * @return array|null
   *   An array representing an event in this agenda.
   */
  public function getEventBySlug(string $agenda_uid, string $slug) {
    $data = $this->getData($agenda_uid, ['slug' => $slug, 'detailed' => 1]);
    $event = !empty($data['events']) ? array_pop($data['events']) : NULL;

    return $event;
  }

  /**
   * Get an event slug by offset.
   *
   * @param string $agenda_uid
   *   The agenda UID.
   * @param array $filters
   *   An array of filter parameters.
   * @param int $from
   *   Offset.
   *
   * @return string
   *   The slug corresponding to the event at that offset in this agenda.
   */
  protected function getEventSlugByOffset(string $agenda_uid, array $filters, int $from) {
    $data = $this->getData($agenda_uid, $filters + ['detailed' => 1], $from, 1);

    if (!empty($data['events'])) {
      $event = array_pop($data['events']);
      $slug = !empty($event['slug']) ? $event['slug'] : '';
    }

    return $slug;
  }

  /**
   * Get next event slug in a filter configuration.
   *
   * @param string $agenda_uid
   *   The agenda UID.
   * @param array $filters
   *   An array of filter parameters.
   * @param int $from
   *   Current offset.
   *
   * @return string
   *   The slug corresponding to the next event in this agenda.
   */
  public function getNextEventSlug(string $agenda_uid, array $filters, int $from) {
    return $this->getEventSlugByOffset($agenda_uid, $filters, $from + 1);
  }

  /**
   * Get previous event slug in a filter configuration.
   *
   * @param string $agenda_uid
   *   The agenda UID.
   * @param array $filters
   *   An array of filter parameters.
   * @param int $from
   *   Current offset.
   *
   * @return string
   *   The slug corresponding to the previous event in this agenda.
   */
  public function getPreviousEventSlug(string $agenda_uid, array $filters, int $from) {
    return $from > 0 ? getEventSlugByOffset($agenda_uid, $filters, $from - 1) : '';
  }

  /**
   * Get event triplet (previous, current, next).
   *
   * @param string $agenda_uid
   *   The agenda UID.
   * @param array $filters
   *   An array of filter parameters.
   * @param int $from
   *   Get events starting from that offset.
   *
   * @return array
   *   An array with three events (previous, current, next).
   */
  public function getEventTriplet(string $agenda_uid, array $filters, int $from) {
    if ($from == 0) {
      $data = $this->getAgendaEvents($agenda_uid, $filters + ['detailed' => 1], $from, 2);
    }
    else {
      $data = $this->getAgendaEvents($agenda_uid, $filters + ['detailed' => 1], $from - 1, 3);
    }

    $eventTriplet = [
      'previous' => $from > 0 ? array_shift($data['events']) : NULL,
      'current' => array_shift($data['events']),
      'next' => array_shift($data['events']),
    ];

    return $eventTriplet;
  }

}
