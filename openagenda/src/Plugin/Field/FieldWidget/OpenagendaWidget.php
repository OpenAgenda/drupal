<?php

namespace Drupal\openagenda\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openagenda\OpenAgendaHelperInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Widget for the OpenAgenda field type.
 *
 * @FieldWidget(
 *   id = "openagenda_widget",
 *   label = @Translation("OpenAgenda widget"),
 *   field_types = {
 *     "openagenda",
 *   },
 * )
 */
class OpenagendaWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * OpenAgenda module configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  // @todo replace with an interface
  /**
   * Our helper service.
   *
   * @var \Drupal\openagenda\OpenagendaHelperInterface
   */
  protected $helper;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, OpenagendaHelperInterface $helper) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->helper = $helper;
    $this->config = \Drupal::config('openagenda.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('openagenda.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += [
      '#type' => 'fieldset',
    ];

    $element['uid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OpenAgenda UID'),
      '#description' => $this->t('OpenAgenda UID of the agenda you want to display.'),
      '#default_value' => isset($items[$delta]->uid) ? $items[$delta]->uid : '',
      '#size' => 10,
      '#maxlength' => 10,
    ];

    $element['events_per_page'] = [
      '#type' => 'number',
      '#title' => $this->t('Events per page'),
      '#description' => $this->t('Number of events displayed per page. Leave at 0 to show all events.'),
      '#default_value' => isset($items[$delta]->events_per_page) ? $items[$delta]->events_per_page : $this->config->get('openagenda.events_per_page'),
      '#size' => 3,
      '#min' => 0,
      '#max' => 300,
    ];

    $language_options = ['default' => $this->t('Use site language')] + $this->helper->getAvailableLanguages();

    $element['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#description' => $this->t('The language to use for this agenda.'),
      '#options' => $language_options,
      '#default_value' => isset($items[$delta]->language) ? $items[$delta]->language : $this->config->get('openagenda.default_language'),
    ];

    $element['include_embedded'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Included embedded content'),
      '#description' => $this->t('Included embedded HTML content in event descriptions. Warning: this is a security risk. Defaults to the main module configuration.'),
      '#return_value' => TRUE,
      '#default_value' => isset($items[$delta]->include_embedded) ? $items[$delta]->include_embedded : $this->config->get('openagenda.include_embedded'),
    ];

    return $element;
  }

}
