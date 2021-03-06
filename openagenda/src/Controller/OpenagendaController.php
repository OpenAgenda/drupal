<?php

namespace Drupal\openagenda\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Url;
use Drupal\openagenda\OpenagendaAgendaProcessorInterface;
use Drupal\openagenda\OpenagendaEventProcessorInterface;
use Drupal\openagenda\OpenagendaHelperInterface;
use OpenAgendaSdk\OpenAgendaSdk;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Component\Serialization\Json;

/**
 * The OpenAgenda Controller.
 *
 * Handles the event route and AJAX updates.
 */
class OpenagendaController extends ControllerBase
{

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
   * OpenAgenda SDK.
   *
   * @var OpenAgendaSdk
   */
  protected $sdk;

  /**
   * {@inheritdoc}
   */
  public function __construct(OpenagendaHelperInterface $helper, OpenagendaAgendaProcessorInterface $agenda_processor, OpenagendaEventProcessorInterface $event_processor)
  {
    $this->helper = $helper;
    $this->agendaProcessor = $agenda_processor;
    $this->eventProcessor = $event_processor;
    $config = \Drupal::config('openagenda.settings');
    $this->sdk = new OpenAgendaSdk($config->get('openagenda.public_key'));
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
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
  public function event(EntityInterface $node, array $event, Request $request)
  {
    // Make sure that we successfully upcasted an event and that our node is
    // a valid OpenAgenda node.
    if (empty($event) || !$node->hasField('field_openagenda')) {
      throw new NotFoundHttpException();
    }

    $oac = $request->get('oac');
    $context = !empty($oac) ? $this->helper->decodeContext($oac) : [];

    return $this->eventProcessor->buildRenderArray($event, $node, $context);
  }

  /**
   * @param EntityInterface $node
   * @param Request $request
   * @return JsonResponse
   * @throws \OpenAgendaSdk\OpenAgendaSdkException
   */
  public function filtersCallback(EntityInterface $node, Request $request)
  {
    if ($node->hasField('field_openagenda')) {
      $queryInfo = UrlHelper::parse($request->getUri());
      $filters = $queryInfo['query'];
      $filters += ['detailed' => 1]; // Required to get lat/lon data.
      $agenda_uid = $node->get('field_openagenda')->uid;
      $data = Json::decode($this->sdk->getEvents($agenda_uid, $filters), TRUE);

      return new JsonResponse($data);
    }

    return new JsonResponse(['error' => 'This node has no opendagenda field'], 404);
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
  public function ajaxCallback(EntityInterface $node, Request $request)
  {
    $response = new AjaxResponse();

    if ($node->hasField('field_openagenda')) {
      // Remove _wrapper_format from request so that it doesn't bleed
      // in our pager links.
      // see https://www.drupal.org/project/drupal/issues/2504709
      $request->query->remove(MainContentViewSubscriber::WRAPPER_FORMAT);
      $request->request->remove(MainContentViewSubscriber::WRAPPER_FORMAT);

      // Re-render the agenda with the new parameters.
      $content = $this->agendaProcessor->buildRenderArray($node);
      $selector = '#oa-wrapper';

      $response->addCommand(new ReplaceCommand($selector, $content));
    }

    return $response;
  }

}
