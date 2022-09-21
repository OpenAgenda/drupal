<?php

namespace Drupal\openagenda\Plugin\Validation\Constraint;

use OpenAgendaSdk\OpenAgendaSdk;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use \Drupal;

/**
 * Validates the OpenagendaPermission constraint.
 */
class OpenagendaPermissionValidator extends ConstraintValidator
{

  /**
   * OpenAgenda SDK.
   *
   * @var OpenAgendaSdk
   */
  protected $sdk;

  /**
   * OpenagendaPermissionValidator constructor.
   */
  public function __construct()
  {
    $configFactory = Drupal::service('config.factory');
    $config = $configFactory->get('openagenda.settings');
    $this->sdk = new OpenAgendaSdk($config->get('openagenda.public_key', ''));
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint)
  {
    /** @var Drupal\openagenda\Plugin\Field\FieldType\Openagenda $item */
    foreach ($items as $item) {
      $uid = $item->getProperties()['uid']->getValue();
      try {
        if (!$this->sdk->hasPermission($uid)) {
          $this->context->addViolation($constraint->forbidden, ['%value' => $uid]);
        }
      }
      catch(\Exception $ex) {
        $this->context->addViolation($constraint->key, []);
      }
    }
  }

}
