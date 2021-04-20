<?php

namespace Drupal\openagenda\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\openagenda\OpenagendaAgendaProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formatter for the OpenAgenda field type.
 *
 * @FieldFormatter(
 *   id = "openagenda_formatter",
 *   label = @Translation("OpenAgenda formatter"),
 *   field_types = {
 *     "openagenda",
 *   }
 * )
 */
class OpenagendaFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The agenda processor service.
   *
   * @var \Drupal\openagenda\OpenagendaAgendaProcessorInterface
   */
  protected $agendaProcessor;

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Displays the agenda.');
    return $summary;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('openagenda.agenda_processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, OpenagendaAgendaProcessorInterface $agenda_processor) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->agendaProcessor = $agenda_processor;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = $this->agendaProcessor->buildRenderArray($item->getEntity());
    }

    return $element;
  }

}
