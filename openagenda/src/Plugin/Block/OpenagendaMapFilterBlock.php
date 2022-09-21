<?php

namespace Drupal\openagenda\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openagenda\OpenagendaHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal;

/**
 * Provides the OpenAgenda map filter Block.
 *
 * @Block(
 *   id = "openagenda_map_filter_block",
 *   admin_label = @Translation("OpenAgenda - OSM Map filter"),
 *   category = @Translation("OpenAgenda"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   },
 * )
 */
class OpenagendaMapFilterBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $this->routeMatch = $route_match;
    $this->helper = $helper;
    $this->config = Drupal::config('openagenda.settings');
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
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['map_tiles_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map tiles URL'),
      '#description' => $this->t('URL of the map tiles to use for this widget.'),
      '#default_value' => isset($config['map_tiles_url']) ? $config['map_tiles_url'] : $this->config->get('openagenda.default_map_filter_tiles_uri'),
    ];

    $form['map_tiles_attribution'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map tiles attribution'),
      '#description' => $this->t('Map tiles attribution to display on the map.'),
      '#default_value' => isset($config['map_tiles_attribution']) ? $config['map_tiles_attribution'] : $this->config->get('openagenda.default_map_filter_tiles_attribution'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['map_tiles_url'] = $values['map_tiles_url'];
    $this->configuration['map_tiles_attribution'] = $values['map_tiles_attribution'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $block = [];

    // Check that we have an OpenAgenda node and that we are hitting the base
    // route (not an event).
    if ($node && $node->hasField('field_openagenda')
      && $this->routeMatch->getRouteName() == 'entity.node.canonical') {
      $lang = $this->helper->getPreferredLanguage($node->get('field_openagenda')->language);
      $map_tiles_url = !empty($this->configuration['map_tiles_url']) ? $this->configuration['map_tiles_url'] : $this->config->get('openagenda.default_map_filter_tiles_uri');
      $map_tiles_attribution = !empty($this->configuration['map_tiles_attribution']) ? $this->configuration['map_tiles_attribution'] : $this->config->get('openagenda.default_map_filter_tiles_attribution');

      $block = [
        '#theme' => 'block__openagenda_map_filter',
        '#map_tiles_url' => $map_tiles_url,
        '#map_tiles_attribution' => $map_tiles_attribution,
        '#auto_search' => TRUE,
        '#lang' => $lang,
      ];

      // Center on event location if we display the map on an event.
      $event = $this->routeMatch->getParameter('event');
      if (!empty($event) && !empty($event['uid'])) {
        $block['#auto_search'] = FALSE;
      }
    }

    return $block;
  }

}
