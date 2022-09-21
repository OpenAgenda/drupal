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
 * Provides the OpenAgenda search filter Block.
 *
 * @Block(
 *   id = "openagenda_search_filter_block",
 *   admin_label = @Translation("OpenAgenda - Search filter"),
 *   category = @Translation("OpenAgenda"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   },
 * )
 */
class OpenagendaSearchFilterBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
  protected $moduleConfig;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, OpenagendaHelperInterface $helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->helper = $helper;
    $this->moduleConfig = Drupal::config('openagenda.settings');
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

    $form['input_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#description' => $this->t('Placeholder for the input field.'),
      '#default_value' => isset($config['input_placeholder']) ? $config['input_placeholder'] : $this->moduleConfig->get('openagenda.default_search_filter_placeholder'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['input_placeholder'] = $values['input_placeholder'];
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
      $lang = $this->helper->getPreferredLanguage($node->get('field_openagenda')->language);
      $defaultPlaceholder = $this->moduleConfig->get('openagenda.default_search_filter_placeholder');
      $placeholder = !empty($this->configuration['input_placeholder']) ? $this->configuration['input_placeholder'] : $this->t($defaultPlaceholder);
      $block = [
        '#theme' => 'block__openagenda_search_filter',
        '#placeholder' => $placeholder,
        '#lang' => $lang,
      ];
    }

    return $block;
  }

}
