<?php

namespace Drupal\openagenda\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\openagenda\OpenagendaHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the OpenAgenda a timetable Block for an event.
 *
 * @Block(
 *   id = "openagenda_event_timetable_block",
 *   admin_label = @Translation("OpenAgenda event timetable"),
 *   category = @Translation("OpenAgenda filters"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   },
 * )
 */
class OpenagendaEventTimetableBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The OpenAgenda helper service.
   *
   * @var \Drupal\openagenda\OpenagendaHelperInterface
   */
  protected $helper;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, OpenagendaHelperInterface $helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('openagenda.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $block = [];
    $event = $this->routeMatch->getParameter('event');

    // Check we have an event and the current node is a valid OpenAgenda node.
    if (!empty($event) && $node->hasField('field_openagenda')) {
      $lang = $this->helper->getPreferredLanguage($node->get('field_openagenda')->language);

      $block = [
        '#theme' => 'openagenda_event_timetable',
        '#event' => $event,
        '#lang' => $lang,
      ];
    }

    return $block;
  }

}
