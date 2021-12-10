<?php

namespace Drupal\openagenda;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Class OpenagendaHelper.
 *
 * Utility functions.
 */
class OpenagendaHelper implements OpenagendaHelperInterface
{

  /**
   * The JSON serializer/deserializer service.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $json;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The language priority list.
   *
   * @var array
   */
  protected $languagePriorityList;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(SerializationInterface $json, LanguageManagerInterface $language_manager, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager)
  {
    $this->json = $json;
    $this->languageManager = $language_manager;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Encode the context request parameter.
   *
   * @param int $index
   *   Position of event in current search.
   * @param int $total
   *   Total number of events returned by current search.
   * @param array $filters
   *   Array of search parameters.
   *
   * @return string
   *   Encoded context.
   */
  public function encodeContext(int $index, int $total, array $filters)
  {
    $context = [
      'index' => $index,
      'total' => $total,
    ];

    if (!empty($filters)) {
      $context['filters'] = $filters;
    }

    return base64_encode($this->json->encode($context));
  }

  /**
   * Decode the context request parameter.
   *
   * @param string $serialized_context
   *   The context parameter to decode.
   *
   * @return array
   *   Decoded context.
   */
  public function decodeContext(string $serialized_context)
  {
    return $this->json->decode(base64_decode($serialized_context));
  }

  /**
   * Create an event url from a slug.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node the event is related to.
   * @param string $event_slug
   *   The event's slug.
   * @param string $oac
   *   The oac parameter (serialized context)
   *
   * @return \Drupal\Core\Url
   *   The event's url.
   */
  public function createEventUrl(EntityInterface $node, string $event_slug, string $oac)
  {
    $url = Url::fromRoute('openagenda.event', [
      'node' => $node->id(),
      'event' => $event_slug,
    ]);

    if (!empty($oac)) {
      $url->setOption('query', ['oac' => $oac]);
    }

    return $url;
  }

  /**
   * Get the value of an agenda/event property best matching the language.
   *
   * Language priority order:
   *   user > OA settings (node) > OA settings (main) > site > agenda > fr.
   *
   * @param array $data
   *   An agenda or an event.
   * @param string $key
   *   The property to get the value from.
   * @param string $content_language
   *   The content language.
   *
   * @return string
   *   The value best matching the language.
   */
  public function getLocalizedValue(array $data, string $key, string $content_language = 'default')
  {
    $value = '';
    $language_priority_list = array_keys($this->getLanguagePriorityList($content_language));

    // Check the property exists in our array.
    if (!empty($data[$key])) {
      // Pick first non empty value in our language priorities order.
      foreach ($language_priority_list as $language) {
        if (!empty($data[$key][$language])) {
          $value = $data[$key][$language];
          break;
        }
      }
    }

    return $value;
  }

  /**
   * Localize event fields.
   *
   * @param array $event
   *   Event to localize.
   * @param string $content_language
   *   The content language.
   */
  public function localizeEvent(array &$event, string $content_language = 'default')
  {
    $localized_properties = ['title', 'description', 'country', 'dateRange', 'longDescription', 'keywords', 'conditions'];

    foreach ($localized_properties as $localized_property) {
      $event[$localized_property] = $this->getLocalizedValue($event, $localized_property, $content_language);
    }
  }

  /**
   * Get language priority list.
   *
   * @param string $content_language
   *   The content language.
   *
   * @return array
   *   The ordered language priority list.
   */
  protected function getLanguagePriorityList(string $content_language = 'default')
  {
    if (empty($this->languagePriorityList)) {
      $this->setLanguagePriorityList($content_language);
    }

    return $this->languagePriorityList;
  }

  /**
   * Get the preferred language.
   *
   * @param string $content_language
   *   The content language.
   *
   * @return string
   *   The preferred language code.
   */
  public function getPreferredLanguage(string $content_language = 'default')
  {
    $langcode_priority_list = array_keys($this->getLanguagePriorityList($content_language));
    return reset($langcode_priority_list);
  }

  /**
   * Set language priority list.
   *
   * Language priority order:
   *   user > [OA settings (node) > OA settings (main)] > site > fr.
   *
   *   'fr' is already taken account for through the way we build
   *   the available language list.
   *
   * @param string $content_language
   *   The content language.
   *
   * @return $this
   */
  protected function setLanguagePriorityList(string $content_language = 'default')
  {
    $language_list = $this->getAvailableLanguages();
    $ordered_langcodes = [];

    // Content Language.
    if ($content_language != 'default') {
      $ordered_langcodes[] = $content_language;
    }

    // User language (is not anonymous).
    if (!$this->currentUser->isAnonymous()) {
      $ordered_langcodes[] = $this->currentUser->getPreferredLangcode();
    }

    // Site language.
    $ordered_langcodes[] = $this->languageManager->getCurrentLanguage()->getId();

    foreach (array_reverse($ordered_langcodes) as $langcode) {
      $language = $language_list[$langcode];
      unset($language_list[$langcode]);
      $language_list = [$langcode => $language] + $language_list;
    }

    $this->languagePriorityList = $language_list;

    return $this;
  }

  /**
   * Get a list of available languages.
   *
   * @return array
   *   The available languages keyed by language code.
   */
  public function getAvailableLanguages()
  {
    return [
      'fr' => 'Français',
      'en' => 'English',
      'de' => 'Deutsch',
      'es' => 'Español',
      'it' => 'Italiano',
    ];
  }

  /**
   * Get a list of nodes with an OpenAgenda selected.
   *
   * @return array
   *   List of node titles keyed by their id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getOpenagendaNodes()
  {
    $nodes = [];

    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $query->exists('field_openagenda.uid');
    $query->accessCheck(TRUE);
    $query->addTag('node_access');

    $result = $query->execute();

    if (!empty($result)) {
      $entities = $this->entityTypeManager->getStorage('node')->loadMultiple($result);

      foreach ($entities as $entity_id => $entity) {
        $nodes[$entity_id] = Html::escape($entity->label());
      }
    }

    return $nodes;
  }

}
