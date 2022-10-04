<?php

namespace Drupal\openagenda\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\openagenda\OpenagendaHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the OpenAgenda cities filter Block.
 *
 * @Block(
 *   id = "openagenda_cities_filter_block",
 *   admin_label = @Translation("OpenAgenda - Cities filter"),
 *   category = @Translation("OpenAgenda"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   },
 * )
 */
class OpenagendaCitiesFilterBlock extends BlockBase implements ContainerFactoryPluginInterface {

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

    // Check that we have an OpenAgenda node and that we are hitting the base
    // route (not an event).
    if ($node && $node->hasField('field_openagenda') && $this->routeMatch->getRouteName() == 'entity.node.canonical') {
      $block = [
        '#theme' => 'block__openagenda_cities_filter',
      ];
    }

    return $block;
  }

  /**
   * @return int
   *   Cache max age.
   */
  public function getCacheMaxAge()
  {
    return 0;
  }

}
