<?php

namespace Drupal\openagenda\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\openagenda\OpenagendaConnectorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the OpenAgenda tag filter Block.
 *
 * @Block(
 *   id = "openagenda_tag_filter_block",
 *   admin_label = @Translation("OpenAgenda tag filter"),
 *   category = @Translation("OpenAgenda filters"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   },
 * )
 */
class OpenagendaTagFilterBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Our OpenAgendaConnector service.
   *
   * @var \Drupal\openagenda\OpenagendaConnectorInterface
   */
  protected $connector;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, OpenagendaConnectorInterface $connector) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->connector = $connector;
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
      $container->get('openagenda.connector')
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
    if ($node->hasField('field_openagenda') && $this->routeMatch->getRouteName() == 'entity.node.canonical') {
      $agenda_uid = $node->get('field_openagenda')->uid;
      $agenda_settings = $this->connector->getAgendaSettings($agenda_uid);

      // See how many tag groups this agenda uses.
      $tag_groups_count = 0;
      if (!empty($agenda_settings['tagSet']) && !empty($agenda_settings['tagSet']['groups'])) {
        foreach ($agenda_settings['tagSet']['groups'] as $group) {
          if (!empty($group['access']) && $group['access'] == 'public') {
            $tag_groups_count++;
          }
        }
      }

      $block = [
        '#theme' => 'openagenda_tag_filter',
        '#agenda_uid' => $agenda_uid,
        '#tag_groups_count' => $tag_groups_count ? $tag_groups_count : 1,
      ];
    }

    return $block;
  }

}
