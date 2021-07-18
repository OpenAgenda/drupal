<?php

namespace Drupal\openagenda\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openagenda\OpenagendaHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the OpenAgenda settings form.
 */
class OpenagendaForm extends ConfigFormBase {

  /**
   * The OpenAgenda helper service.
   *
   * @var \Drupal\openagenda\OpenagendaHelperInterface
   */
  protected $helper;

  /**
   * {@inheritdoc}
   */
  public function __construct(OpenagendaHelperInterface $helper) {
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openagenda.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openagenda_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('openagenda.settings');

    $form['public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OpenAgenda public key'),
      '#description' => $this->t('Enter your OpenAgenda account public key.'),
      '#default_value' => $config->get('openagenda.public_key'),
    ];

    $form['default_openagenda_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Default OpenAgenda content settings'),
    ];

    $form['default_openagenda_settings']['events_per_page'] = [
      '#type' => 'number',
      '#title' => $this->t('Events per page'),
      '#description' => $this->t('Number of events displayed per page. Enter 0 to show all events.'),
      '#min' => 0,
      '#max' => 300,
      '#size' => 3,
      '#default_value' => $config->get('openagenda.events_per_page'),
    ];

    $language_options = ['default' => $this->t("Use site's language")] + $this->helper->getAvailableLanguages();

    $form['default_openagenda_settings']['default_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Default language'),
      '#description' => $this->t('The default language to use for OpenAgendas and events.'),
      '#options' => $language_options,
      '#default_value' => $config->get('openagenda.default_language'),
    ];

    $form['default_openagenda_settings']['include_embedded'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include embedded content'),
      '#description' => $this->t('Include embedded HTML content in event descriptions. Warning: this is a security risk.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('openagenda.include_embedded'),
    ];

    $form['default_map_filter_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Default map filter settings'),
    ];

    $form['default_map_filter_settings']['default_map_filter_tiles_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default map tiles URL'),
      '#description' => $this->t('Default URL of the map tiles used for the filter.'),
      '#default_value' => $config->get('openagenda.default_map_filter_tiles_uri'),
    ];

    $form['default_map_filter_settings']['default_map_filter_tiles_attribution'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default map tiles attribution'),
      '#description' => $this->t('Default map tiles attribution to display on filter.'),
      '#default_value' => $config->get('openagenda.default_map_filter_tiles_attribution'),
    ];

    $form['default_search_filter_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Default search filter settings'),
    ];

    $form['default_search_filter_settings']['default_search_filter_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default search filter placeholder'),
      '#description' => $this->t('Text to use as the search filter input field placeholder.'),
      '#default_value' => $config->get('openagenda.default_search_filter_placeholder'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('openagenda.settings');
    $config->set('openagenda.public_key', $form_state->getValue('public_key'));
    $config->set('openagenda.events_per_page', $form_state->getValue('events_per_page'));
    $config->set('openagenda.default_language', $form_state->getValue('default_language'));
    $config->set('openagenda.include_embedded', $form_state->getValue('include_embedded'));
    $config->set('openagenda.default_map_filter_tiles_uri', $form_state->getValue('default_map_filter_tiles_uri'));
    $config->set('openagenda.default_map_filter_tiles_attribution', $form_state->getValue('default_map_filter_tiles_attribution'));
    $config->set('openagenda.default_search_filter_placeholder', $form_state->getValue('default_search_filter_placeholder'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'openagenda.settings',
    ];
  }

}
