<?php

namespace Drupal\openagenda\Plugin\Block;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\openagenda\OpenagendaConnectorInterface;
use Drupal\openagenda\OpenagendaHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the OpenAgenda preview Block.
 *
 * @Block(
 *   id = "openagenda_preview_block",
 *   admin_label = @Translation("OpenAgenda - Preview block"),
 *   category = @Translation("OpenAgenda"),
 * )
 */
class OpenagendaPreviewBlock extends BlockBase implements ContainerFactoryPluginInterface
{

    /**
     * The OpenAgenda helper service.
     *
     * @var \Drupal\openagenda\OpenagendaHelperInterface
     */
    protected $helper;

    /**
     * The OpenAgenda connector service.
     *
     * @var \Drupal\openagenda\OpenagendaConnectorInterface
     */
    protected $connector;

    /**
     * Entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * OpenAgenda module configuration object.
     *
     * @var \Drupal\Core\Config\ImmutableConfig
     */
    protected $config;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, OpenagendaHelperInterface $helper, OpenagendaConnectorInterface $connector, EntityTypeManagerInterface $entity_type_manager)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->config = \Drupal::config('openagenda.settings');
        $this->helper = $helper;
        $this->connector = $connector;
        $this->entityTypeManager = $entity_type_manager;
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
            $container->get('openagenda.helper'),
            $container->get('openagenda.connector'),
            $container->get('entity_type.manager')
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
    public function blockForm($form, FormStateInterface $form_state)
    {
        $form = parent::blockForm($form, $form_state);
        $config = $this->getConfiguration();

        $agenda_options = $this->helper->getOpenagendaNodes();

        $form['agenda_reference'] = [
            '#type' => 'select',
            '#title' => $this->t('OpenAgenda node'),
            '#description' => $this->t('A content with an OpenAgenda field.'),
            '#options' => $agenda_options,
            '#default_value' => isset($config['agenda_reference']) ? $config['agenda_reference'] : '',
            '#required' => TRUE,
        ];

        $form['events_in_preview'] = [
            '#type' => 'number',
            '#title' => $this->t('Number of events in preview'),
            '#description' => $this->t('The number of events to show in the block. Enter 0 for unlimited.'),
            '#min' => 0,
            '#max' => 300,
            '#size' => 3,
            '#default_value' => isset($config['events_in_preview']) ? $config['events_in_preview'] : 3,
        ];

        $order_options = [
            'default' => $this->t('Default order'),
            'featured' => $this->t('Only featured events'),
            'custom_filter' => $this->t('Custom filter'),
        ];

        $form['order'] = [
            '#type' => 'radios',
            '#title' => $this->t('Order'),
            '#options' => $order_options,
            '#default_value' => isset($config['order']) ? $config['order'] : 'default',
        ];

        $form['custom_filter'] = [
            '#type' => 'textfield',
            '#maxlength' => 65536,
            '#placeholder' => $this->t('Enter filters'),
            '#states' => [
                'enabled' => [
                    ':input[name="settings[order]"]' => ['value' => 'custom_filter'],
                ],
            ],
            '#default_value' => isset($config['custom_filter']) ? $config['custom_filter'] : '',
            '#description' => $this->t('Paste the URL of this OpenAgenda with corresponding filters set.'),
        ];

        $language_options = ['default' => $this->t("Use site's language")] + $this->helper->getAvailableLanguages();

        $form['language'] = [
            '#type' => 'select',
            '#title' => $this->t('Language'),
            '#description' => $this->t('The language to use for this OpenAgenda.'),
            '#options' => $language_options,
            '#default_value' => isset($config['language']) ? $config['language'] : 'default',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function blockSubmit($form, FormStateInterface $form_state)
    {
        parent::blockSubmit($form, $form_state);
        $values = $form_state->getValues();
        $this->configuration['agenda_reference'] = $values['agenda_reference'];
        $this->configuration['events_in_preview'] = $values['events_in_preview'];
        $this->configuration['language'] = $values['language'];
        $this->configuration['order'] = $values['order'];
        $this->configuration['custom_filter'] = $values['custom_filter'];
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        $block = [];

        if (!empty($this->configuration['agenda_reference'])) {
            $entity = $this->entityTypeManager->getStorage('node')->load($this->configuration['agenda_reference']);

            if ($entity && $entity->hasField('field_openagenda')) {
                $agenda_uid = $entity->get('field_openagenda')->uid;
                $events_in_preview = $this->configuration['events_in_preview'];

                $filters = [];

                if ($this->configuration['order'] == 'featured') {
                    $filters['featured'] = '1';
                }

                if ($this->configuration['order'] == 'custom_filter') {
                    $parsed_url = UrlHelper::parse($this->configuration['custom_filter'], PHP_URL_QUERY);

                    if (!empty($parsed_url['query'])) {
                        $filters = $parsed_url['query'];
                    }
                }

                // Get pre-filters and add them to filters if defined.
                $preFilters = $this->helper->getPreFilters($entity);

                // Current & upcoming events only.
                $currentValue = $entity->get('field_openagenda')->current;
                if (!empty($currentValue)) {
                    $preFilters['relative'] = [
                        'current',
                        'upcoming',
                    ];
                }
                $filters += $preFilters;

                $data = $this->connector->getAgendaEvents($agenda_uid, $filters + ['detailed' => 1], 0, $events_in_preview);

                if (isset($data['success']) && $data['success'] == FALSE) {
                    $block = [
                        '#markup' => $this->t("This OpenAgenda doesn't exist."),
                    ];
                } else {
                    if (!empty($data['events'])) {
                        $style = $this->config->get('openagenda.default_style', 'default');
                        // Block.
                        $block = [
                            '#theme' => 'block__openagenda_preview',
                            '#entity' => $entity,
                            '#events' => $data['events'],
                            '#lang' => $this->configuration['language'],
                            '#columns' => $this->config->get('openagenda.default_columns', 3),
                            '#attached' => [
                                'library' => [
                                    'openagenda/openagenda.style.' . $style,
                                ],
                            ],
                        ];
                    }
                }
            }
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
