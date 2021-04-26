<?php

namespace Drupal\openagenda;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class OpenagendaEventProcessor.
 *
 * Prepares an agenda's data prior to display.
 */
class OpenagendaEventProcessor implements OpenagendaEventProcessorInterface {
  use StringTranslationTrait;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The OpenAgenda helper service.
   *
   * @var \Drupal\openagenda\OpenagendaHelperInterface
   */
  protected $helper;

  /**
   * {@inheritdoc}
   */
  public function __construct(DateFormatterInterface $date_formatter, OpenagendaHelperInterface $helper) {
    $this->dateFormatter = $date_formatter;
    $this->helper = $helper;
  }

  /**
   * Build an event's render array.
   *
   * @param array $event
   *   The event to render.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the event relates to (agenda).
   * @param array $context
   *   Context for event navigation.
   *
   * @return array
   *   An agenda's render array or a simple markup to report
   *   that no agenda was found.
   */
  public function buildRenderArray(array $event, EntityInterface $entity, array $context = []) {
    $build = [];

    if ($entity->hasField('field_openagenda')) {
      // Localize the event.
      $lang = $entity->get('field_openagenda')->language;
      $this->helper->localizeEvent($event, $lang);

      $build = [
        '#title' => $event['title'],
        '#theme' => 'openagenda_event_single',
        '#entity' => $entity,
        '#event' => $event,
        '#context' => $context,
        '#lang' => $this->helper->getPreferredLanguage($entity->get('field_openagenda')->language),
        '#attached' => [
          'html_head' => $this->processEventMetadata($event),
          'library' => [
            'openagenda/openagenda.event',
          ],
          'drupalSettings' => [
            'openagenda' => [
              'isEvent' => TRUE,
              'nid' => $entity->id(),
            ],
          ],
        ],
      ];
    }

    return $build;
  }

  /**
   * Process relative timing to event.
   *
   * @param array $event
   *   The event to parse.
   * @param string $lang
   *   Language code for date format.
   *
   * @return string
   *   String representing relative timing to event.
   */
  public function processRelativeTimingToEvent(array $event, string $lang = 'fr') {
    $relative_timing = '';

    if (!empty($event) && !empty($event['timings'])) {
      $request_time = $this->getRequestTime();

      // Find next timing for the event.
      foreach ($event['timings'] as $timing) {
        $t = strtotime($timing['start']);

        if ($t > $request_time) {
          $next_timing = $t;
          break;
        }
      }

      // Modifiy string to reflect that we are working with next or last timing.
      if (!empty($next_timing)) {
        $formatted_time_diff = $this->dateFormatter->formatTimeDiffUntil($next_timing,
        [
          'granularity' => 1,
          'langcode' => $lang,
        ]);

        $relative_timing = $this->t('In @time_diff.', ['@time_diff' => $formatted_time_diff]);
      }
      else {
        $last_timing = strtotime(array_pop($event['timings'])['end']);
        $formatted_time_diff = $this->dateFormatter->formatTimeDiffSince($last_timing,
        [
          'granularity' => 1,
          'langcode' => $lang,
        ]);

        $relative_timing = $this->t('@time_diff ago.', ['@time_diff' => $formatted_time_diff]);
      }
    }

    return $relative_timing;
  }

  /**
   * Process an event's timetable.
   *
   * @param array $event
   *   Event to process.
   * @param string $lang
   *   Language code for date format.
   *
   * @return array
   *   An array of months and weeks with days and time range values.
   */
  public function processEventTimetable(array $event, string $lang = 'fr') {
    $timetable = [];
    $current_month = '';
    $current_month_timings = [];
    $current_week = '';
    $current_week_timings = [];
    $current_day = '';
    $current_day_timings = [];

    // First check event has all the necessary values.
    if (!empty($event) && !empty($event['timings'])) {
      // Set the timezone to the location's timezone.
      if (!empty($event['location']) && !empty($event['location']['timezone'])) {
        $timezone = $event['location']['timezone'];
      }
      else {
        $timezone = 'Europe/Paris';
      }

      // Parse timings.
      foreach ($event['timings'] as $timing) {
        // Check our timing is valid (has a start and an end).
        if (!empty($timing['start']) && !empty($timing['end'])) {

          // Format of day (ex: Thursday 25).
          $timing_day = $this->dateFormatter
            ->format(strtotime($timing['start']), 'custom', 'l j', $timezone, $lang);

          // If this is a new day...
          if ($timing_day != $current_day) {
            // ... and our current day has timings...
            if (!empty($current_day_timings)) {
              // ...push our current day timings in our current week timings...
              array_push($current_week_timings, $current_day_timings);
            }

            $current_day_timings = [
              'label' => $timing_day,
              'timings' => [],
            ];
            $current_day = $timing_day;

            // Format of month (ex: March 2021).
            $timing_month = $this->dateFormatter
              ->format(strtotime($timing['start']), 'custom', 'F Y', $timezone, $lang);
            // Week number is only used to check for week change.
            $timing_week = $this->dateFormatter
              ->format(strtotime($timing['start']), 'custom', 'W', $timezone, $lang);

            // If week or month has changed...
            if ($timing_week != $current_week || $timing_month != $current_month) {
              // ... and the week we were working on has timings...
              if (!empty($current_week_timings)) {
                // ... push it in our current month's array.
                array_push($current_month_timings['weeks'], $current_week_timings);
              }

              $current_week_timings = [];
              $current_week = $timing_week;
            }

            // If month has changed do the whole thing again.
            if ($timing_month != $current_month) {
              if (!empty($current_month_timings)) {
                array_push($timetable, $current_month_timings);
              }

              $current_month_timings = [
                'label' => $timing_month,
                'weeks' => [],
              ];
              $current_month = $timing_month;
            }
          }

          array_push($current_day_timings['timings'], [
            'start' => $this->dateFormatter->format(strtotime($timing['start']), 'custom', 'H:i', $timezone, $lang),
            'end' => $this->dateFormatter->format(strtotime($timing['end']), 'custom', 'H:i', $timezone, $lang),
          ]);
        }
      }

      // Push the last day/week/month's timings.
      if (!empty($current_day_timings)) {
        array_push($current_week_timings, $current_day_timings);
        array_push($current_month_timings['weeks'], $current_week_timings);
        array_push($timetable, $current_month_timings);
      }
    }

    return $timetable;
  }

  /**
   * Process metadata for an event.
   *
   * @param array $event
   *   The event.
   *
   * @return array
   *   Metadata array attachable through html_head in the render array.
   */
  public function processEventMetadata(array $event) {
    $metadata = [];

    // Attaching og:type and og:url.
    array_push($metadata, [
      [
        '#tag' => 'meta',
        '#attributes' => [
          'property' => 'og:type',
          'content' => 'article',
        ],
      ],
      'oa_event_og_type',
    ]);

    if (!empty($event['baseUrl'])) {
      array_push($metadata, [
        [
          '#tag' => 'meta',
          '#attributes' => [
            'property' => 'og:url',
            'content' => $event['baseUrl'],
          ],
        ],
        'oa_event_og_url',
      ]);
    }

    // Attaching title and og:title.
    if (!empty($event['title'])) {
      array_push($metadata, [
        [
          '#tag' => 'meta',
          '#attributes' => [
            'name' => 'title',
            'content' => $event['title'],
          ],
        ],
        'oa_event_title',
      ]);

      array_push($metadata, [
        [
          '#tag' => 'meta',
          '#attributes' => [
            'property' => 'og:title',
            'content' => $event['title'],
          ],
        ],
        'oa_event_og_title',
      ]);
    }

    // Attaching description and og:description.
    if (!empty($event['description'])) {
      array_push($metadata, [
        [
          '#tag' => 'meta',
          '#attributes' => [
            'name' => 'description',
            'content' => $event['description'],
          ],
        ],
        'oa_event_description',
      ]);

      array_push($metadata, [
        [
          '#tag' => 'meta',
          '#attributes' => [
            'property' => 'og:description',
            'content' => $event['description'],
          ],
        ],
        'oa_event_og_description',
      ]);
    }

    // Attaching og:image.
    if (!empty($event['image'])) {
      array_push($metadata, [
        [
          '#tag' => 'meta',
          '#attributes' => [
            'property' => 'og:image',
            'content' => $event['image'],
          ],
        ],
        'oa_event_og_image',
      ]);
    }

    return $metadata;
  }

  /**
   * Wrapper method for REQUEST_TIME constant.
   *
   * @return int
   *   The request time.
   */
  protected function getRequestTime() {
    return defined('REQUEST_TIME') ? REQUEST_TIME : (int) $_SERVER['REQUEST_TIME'];
  }

}
