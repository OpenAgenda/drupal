<?php

namespace Drupal\openagenda\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that agenda public key has access to the submitted agenda uid.
 *
 * @Constraint(
 *   id = "OpenagendaPermission",
 *   label = @Translation("Unique Integer", context = "Validation"),
 *   type = "string"
 * )
 */
class OpenagendaPermission extends Constraint {

  public $forbidden = "Agenda (uid: %value) doesn't exist or you don't have permission to access this agenda. You may also check OpenAgenda key configuration.";

  public $key = "Your OpenAgenda public key is not recognized.";

}
