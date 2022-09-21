<?php

namespace Drupal\openagenda\Plugin\Block;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\openagenda\OpenagendaHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the OpenAgenda a map Block for an event.
 *
 * @Block(
 *   id = "openagenda_event_map_block",
 *   admin_label = @Translation("OpenAgenda - Event map"),
 *   category = @Translation("OpenAgenda"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   },
 * )
 */
class OpenagendaEventMapBlock extends BlockBase implements ContainerFactoryPluginInterface
{

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
   * OpenAgenda module configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, OpenagendaHelperInterface $helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = Drupal::config('openagenda.settings');
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
    if (!empty($event) && $node && $node->hasField('field_openagenda') && !empty($event['location']) && !empty($event['location']['latitude'])) {
      $module_handler = Drupal::service('module_handler');
      $module_path = $GLOBALS['base_url'] . '/' . $module_handler->getModule('openagenda')->getPath();
      $style = $this->config->get('openagenda.default_style', 'default');
      $block = [
        '#theme' => 'block__openagenda_event_map',
        '#attached' => [
          'library' => [
            'openagenda/openagenda.style.' . $style,
          ],
          'drupalSettings' => [
            'openagenda' => [
              'event' => [
                'lat' => $event['location']['latitude'],
                'lon' => $event['location']['longitude'],
                'mapTilesUrl' => $this->config->get('openagenda.default_map_filter_tiles_uri'),
              ],
              'leaflet' => [
                'markerUrl' => $module_path . '/assets/img/marker-icon.svg',
              ],
            ],
          ],
        ],
      ];

      // Defaut style library ?
      if ($default_style = $this->config->get('openagenda.default_style')) {
        $block['#attached']['library'][] = 'openagenda/openagenda.' . $default_style;
      }
    }

    return $block;
  }

  /**
   * @return int
   *   Cache max age.
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
