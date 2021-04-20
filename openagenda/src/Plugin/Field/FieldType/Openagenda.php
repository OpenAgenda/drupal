<?php

namespace Drupal\openagenda\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type for OpenAgenda.
 *
 * @FieldType(
 *   id = "openagenda",
 *   label = @Translation("OpenAgenda"),
 *   description = @Translation("This field contains the settings of an OpenAgenda to display."),
 *   default_formatter = "openagenda_formatter",
 *   default_widget = "openagenda_widget",
 *   cardinality = 1,
 * )
 */
class Openagenda extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'uid' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => TRUE,
        ],
        'events_per_page' => [
          'type' => 'int',
          'size' => 'small',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
        'language' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => TRUE,
        ],
        'include_embedded' => [
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['uid'] = DataDefinition::create('string')
      ->setLabel(t('OpenAgenda UID'))
      ->setRequired(TRUE);

    $properties['events_per_page'] = DataDefinition::create('integer')
      ->setLabel(t('Events per page'))
      ->setRequired(FALSE);

    $properties['language'] = DataDefinition::create('string')
      ->setLabel(t('Agenda language'))
      ->setRequired(FALSE);

    $properties['include_embedded'] = DataDefinition::create('boolean')
      ->setLabel(t('Include embedded content'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('uid')->getValue();
    return $value === NULL || $value === '';
  }

}
