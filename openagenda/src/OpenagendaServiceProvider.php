<?php

namespace Drupal\openagenda;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Update\UpdateKernel;

/**
 * Defines a service provider for the Openagenda module.
 *
 * The Subpathauto module seems to indicate we need this to
 * not break anything during database updates.
 */
class OpenagendaServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // The alias-based processor requires the path_alias entity schema to be
    // installed, so we prevent it from being registered to the path processor
    // manager. We do this by removing the tags that the compiler pass looks
    // for. This means that the URL generator can safely be used during the
    // database update process.
    if ($container->get('kernel') instanceof UpdateKernel && $container->hasDefinition('openagenda.path_processor')) {
      $container->getDefinition('openagenda.path_processor')
        ->clearTag('path_processor_inbound')
        ->clearTag('path_processor_outbound');
    }
  }

}
