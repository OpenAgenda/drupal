<?php

namespace Drupal\openagenda\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Url;
use Drupal\openagenda\OpenagendaHelperInterface;
use Drupal\openagenda\OpenagendaAgendaProcessorInterface;
use Drupal\openagenda\OpenagendaEventProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;

/**
 * The OpenAgenda Controller.
 *
 * Handles the event route and AJAX updates.
 */
class OpenagendaController extends ControllerBase {

  /**
   * Our helper service.
   *
   * @var \Drupal\openagenda\OpenagendaHelperInterface
   */
  protected $helper;

  /**
   * The agenda processor service.
   *
   * @var \Drupal\openagenda\OpenagendaAgendaProcessorInterface
   */
  protected $agendaProcessor;

  /**
   * The event processor service.
   *
   * @var \Drupal\openagenda\OpenagendaEventProcessorInterface
   */
  protected $eventProcessor;

  /**
   * {@inheritdoc}
   */
  public function __construct(OpenagendaHelperInterface $helper, OpenagendaAgendaProcessorInterface $agenda_processor, OpenagendaEventProcessorInterface $event_processor) {
    $this->helper = $helper;
    $this->agendaProcessor = $agenda_processor;
    $this->eventProcessor = $event_processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openagenda.helper'),
      $container->get('openagenda.agenda_processor'),
      $container->get('openagenda.event_processor')
    );
  }

  /**
   * Renders themed event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node the event relates to.
   * @param array $event
   *   The event parameter, upcasted.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A single event render array.
   */
  public function event(EntityInterface $node, array $event, Request $request) {
    // Make sure that we successfully upcasted an event and that our node is
    // a valid OpenAgenda node.
    if (empty($event) || !$node->hasField('field_openagenda')) {
      throw new NotFoundHttpException();
    }

    $oac = $request->get('oac');

    // Localize the event.
    $lang = $node->get('field_openagenda')->language;
    $this->helper->localizeEvent($event, $lang);

    return [
      '#title' => $event['title'],
      '#theme' => 'openagenda_event_single',
      '#entity' => $node,
      '#event' => $event,
      '#oac' => $oac,
      '#lang' => $this->helper->getPreferredLanguage($node->get('field_openagenda')->language),
      '#attached' => [
        'html_head' => $this->eventProcessor->processEventMetadata($event),
        'library' => [
          'openagenda/openagenda.event',
        ],
        'drupalSettings' => [
          'openagenda' => [
            'isEvent' => TRUE,
            'nid' => $node->id(),
          ],
        ],
      ],
    ];
  }

  /**
   * Handle AJAX calls.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The OpenAgenda node for which we call an Ajax update.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response containing the commands to execute.
   */
  public function ajaxCallback(EntityInterface $node, Request $request) {
    $response = new AjaxResponse();

    // Remove _wrapper_format from request so that it doesn't bleed
    // in our pager links.
    // see https://www.drupal.org/project/drupal/issues/2504709
    $request->query->remove(MainContentViewSubscriber::WRAPPER_FORMAT);
    $request->request->remove(MainContentViewSubscriber::WRAPPER_FORMAT);

    if ($node->hasField('field_openagenda')) {
      // If we are showing an event, redirect to agenda with the new parameters.
      if ($request->query->get('is_event')) {
        $url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()]);
        $oaq = $request->query->get('oaq');

        if (!empty($oaq)) {
          $url->setOption('query', ['oaq' => $oaq]);
        }

        $response->addCommand(new RedirectCommand($url->toString()));
      }
      else {
        // Otherwise, re-render the agenda with the new parameters.
        $content = $this->agendaProcessor->buildRenderArray($node);
        $selector = '#openagenda-wrapper';

        $response->addCommand(new ReplaceCommand($selector, $content));
      }
    }

    return $response;
  }

}
