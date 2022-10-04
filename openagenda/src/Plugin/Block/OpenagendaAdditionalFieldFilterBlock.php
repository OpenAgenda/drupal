<?php

namespace Drupal\openagenda\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\openagenda\OpenagendaHelperInterface;
use OpenAgendaSdk\OpenAgendaSdk;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the OpenAgenda additional field filter Block.
 *
 * @Block(
 *   id = "openagenda_additional_field_filter_block",
 *   admin_label = @Translation("OpenAgenda - Additional field filter"),
 *   category = @Translation("OpenAgenda"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   },
 * )
 */
class OpenagendaAdditionalFieldFilterBlock extends BlockBase implements ContainerFactoryPluginInterface
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
   * OpenAgenda SDK.
   *
   * @var OpenAgendaSdk
   */
  protected $sdk;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, OpenagendaHelperInterface $helper, ConfigFactoryInterface $config_factory)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->helper = $helper;
    $configFactory = $config_factory;
    $config = $configFactory->get('openagenda.settings');
    $this->sdk = new OpenAgendaSdk($config->get('openagenda.public_key', ''));
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('openagenda.helper'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account)
  {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    $node = $this->getContextValue('node');
    $block = [];
    $config = $this->getConfiguration();

    // Check that we have an OpenAgenda node and that we are hitting the base
    // route (not an event).
    if ($node && $node->hasField('field_openagenda') && $this->routeMatch->getRouteName() == 'entity.node.canonical') {
      $preFilters = $this->helper->getPreFilters($node);

      // Only display in preFilters doesn't contain an thematique entry.
      if (!isset($preFilters['thematique'])) {
        $block = [
          '#theme' => 'block__openagenda_additional_field_filter',
          '#additional_field' => $config['additional_field'],
        ];
      }
    }

    return $block;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state)
  {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['additional_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Additional field'),
      '#description' => $this->t('Agenda additional field name'),
      '#default_value' => $config['additional_field'] ?? '',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state)
  {
    $customField = $form_state->getValue('additional_field');
    $exists = FALSE;

    foreach ($this->sdk->getMyAgendasUids() as $uid) {
      $agendaCustomFields = $this->sdk->getAgendaAdditionalFields($uid);
      foreach ($agendaCustomFields as $agendaCustomField) {
        if ($agendaCustomField == $customField) {
          $exists = TRUE;

          break 2;
        }
      }
    }

    if (!$exists) {
      $form_state->setErrorByName('additional_field', $this->t("This additional field doesn't belong to any of your agenda(s)!"));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state)
  {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['additional_field'] = $values['additional_field'];
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
