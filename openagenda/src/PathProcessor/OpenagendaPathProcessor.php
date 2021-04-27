<?php

namespace Drupal\openagenda\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the inbound path using path alias lookups.
 */
class OpenagendaPathProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * The path processor.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Recursive switch as PathValidator methods also go through processInbound.
   *
   * @var bool
   */
  protected $recursiveCall;

  /**
   * Builds PathProcessor object.
   *
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   Alias path processor service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(InboundPathProcessorInterface $path_processor, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    $this->pathProcessor = $path_processor;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $request_path = $this->getPath($request->getPathInfo());

    // The path won't be processed if the path has been already modified by
    // a path processor (including this one), or if this is a recursive call.
    if ($path !== $request_path || $this->recursiveCall) {
      return $path;
    }

    $path_array = explode('/', ltrim($path, '/'));

    if (count($path_array) > 1) {
      $subpath = array_pop($path_array);

      // Check if first part of path points to a node URL.
      $candidate_path = '/' . implode('/', $path_array);

      $this->recursiveCall = TRUE;
      $processed_candidate_path = $this->pathProcessor->processInbound($candidate_path, $request);
      $this->recursiveCall = FALSE;

      if ($processed_candidate_path !== $candidate_path && preg_match('/^\/node\/(\d+)$/', $processed_candidate_path, $matches)) {
        // See if that node contains an OpenAgenda field.
        if ($this->entityTypeManager->getStorage('node')->load($matches[1])->hasField('field_openagenda')) {
          // Rewrite the path to point to the route to our event controller.
          $path = $processed_candidate_path . '/event/' . $subpath;
        }
      }
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if (preg_match('/^(\/node\/(\d+))\/event\/(\S+)$/', $path, $path_parts)) {
      if ($this->entityTypeManager->getStorage('node')->load($path_parts[2])->hasField('field_openagenda')) {
        $processedNodePath = $this->pathProcessor->processOutbound($path_parts[1], $options, $request, $bubbleable_metadata);
        if ($processedNodePath == $path_parts[1]) {
          $processedNodePath .= '/event';
        }
        $path = $processedNodePath . '/' . $path_parts[3];
      }
    }

    return $path;
  }

  /**
   * Helper function to handle multilingual paths.
   *
   * @param string $path_info
   *   Path that might contain language prefix.
   *
   * @return string
   *   Path without language prefix.
   */
  protected function getPath($path_info) {
    $language_prefix = '/' . $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId() . '/';

    if (substr($path_info, 0, strlen($language_prefix)) == $language_prefix) {
      $path_info = '/' . substr($path_info, strlen($language_prefix));
    }

    return $path_info;
  }

}
