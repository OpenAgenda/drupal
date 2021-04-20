<?php

namespace Drupal\openagenda;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class OpenagendaConnector.
 *
 * Gets data from the OpenAgenda server.
 */
class OpenagendaConnector implements OpenagendaConnectorInterface {

  /**
   * Base URL of OpenAgenda server.
   */
  const OPENAGENDA_URL = 'https://openagenda.com/';

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

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
   * {@inheritdoc}
   */
  public function __construct(ClientInterface $http_client, RequestStack $request_stack) {
    $this->httpClient = $http_client;
    $this->requestStack = $request_stack;
    $this->config = \Drupal::config('openagenda.settings');
  }

  /**
   * Get data from the OpenAgenda server.
   *
   * @param string $agenda_uid
   *   The agenda UID.
   * @param array $filters
   *   An array of filter parameters.
   * @param int $offset
   *   Get events starting from that offset.
   * @param int $limit
   *   Number of events to get.
   * @param bool $include_embedded
   *   Include embedded code in event html.
   * @param bool $include_passed
   *   Include passed events. Only used on a recursion call if first call didn't
   *   return any event.
   *
   * @return array
   *   Data from the OpenAgenda server, including an event array.
   */
  protected function getData(string $agenda_uid, array $filters, int $offset = -1, int $limit = -1, bool $include_embedded = FALSE, bool $include_passed = FALSE) {
    $data = [];

    // Include passed events.
    if ($include_passed) {
      $filters['passed'] = '1';
      $filters['order'] = 'latest';
    }

    // Build query.
    $query = [
      'key' => $this->config->get('openagenda.public_key'),
      'oaq' => $filters,
      'include_embedded' => $include_embedded ? '1' : '0',
    ];

    if ($offset > -1) {
      $query['offset'] = $offset;
    }

    if ($limit > -1) {
      $query['limit'] = $limit;
    }

    // Make request.
    try {
      $data = Json::decode($this->httpClient->get(
        static::OPENAGENDA_URL . 'agendas/' . $agenda_uid . '/events.json',
        ['query' => $query])->getBody());
    }
    catch (RequestException $exception) {
      watchdog_exception('openagenda.connector', $exception->getMessage());
    }

    // If no event was found, check passed events.
    if (!$include_passed && empty($data['events'])) {
      $data = $this->getData($agenda_uid, $filters, $offset, $limit, $include_embedded, TRUE);
    }

    // Shift keys in the events array according to the offset.
    if ($offset > 0) {
      $data['events'] = array_combine(range($offset, $offset + count($data['events']) - 1), $data['events']);
    }

    // Add filters to the data for non-recursive call.
    if (!$include_passed) {
      $data['filters'] = $filters;
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
    $query = [
      'key' => $this->config->get('openagenda.public_key'),
    ];

    try {
      $data = Json::decode($this->httpClient->get(
        static::OPENAGENDA_URL . 'agendas/' . $agenda_uid . '/settings.json',
        ['query' => $query])->getBody());
    }
    catch (RequestException $exception) {
      watchdog_exception('openagenda.connector', $exception->getMessage());
    }

    return $data;
  }

  /**
   * Get agenda.
   *
   * @param string $agenda_uid
   *   The agenda UID.
   * @param array $filters
   *   An array of filter parameters.
   * @param int $offset
   *   Get events starting from offset.
   * @param int $limit
   *   Number of events to get.
   *
   * @return array
   *   Data from the OpenAgenda server, including an event array.
   */
  public function getAgenda(string $agenda_uid, array $filters = [], int $offset = -1, int $limit = -1) {
    return $this->getData($agenda_uid, $filters, $offset, $limit);
  }

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
  public function getEventBySlug(string $agenda_uid, string $event_slug) {
    $data = $this->getData($agenda_uid, ['slug' => $event_slug]);
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
   * @param int $offset
   *   Offset.
   *
   * @return string
   *   The slug corresponding to the event at that offset in this agenda.
   */
  protected function getEventSlugByOffset(string $agenda_uid, array $filters, int $offset) {
    $data = $this->getData($agenda_uid, $filters, $offset, 1);

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
   * @param int $offset
   *   Current offset.
   *
   * @return string
   *   The slug corresponding to the next event in this agenda.
   */
  public function getNextEventSlug(string $agenda_uid, array $filters, int $offset) {
    return getEventSlugByOffset($agenda_uid, $filters, $offset + 1);
  }

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
  public function getPreviousEventSlug(string $agenda_uid, array $filters, int $offset) {
    return $offset > 0 ? getEventSlugByOffset($agenda_uid, $filters, $offset - 1) : '';
  }

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
  public function getEventTriplet(string $agenda_uid, array $filters, int $offset) {
    $eventTriplet = [];

    if ($offset == 0) {
      $data = $this->getData($agenda_uid, $filters, $offset, 2);
    }
    else {
      $data = $this->getData($agenda_uid, $filters, $offset - 1, 3);
    }

    if (!empty($data['events'])) {
      $eventTriplet = [
        'previous' => $offset != 0 ? array_shift($data['events']) : NULL,
        'current' => !empty($data['events']) ? array_shift($data['events']) : NULL,
        'next' => !empty($data['events']) ? array_shift($data['events']) : NULL,
      ];
    }

    return $eventTriplet;
  }

}
