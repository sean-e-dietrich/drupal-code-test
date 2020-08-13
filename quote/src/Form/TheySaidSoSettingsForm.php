<?php

namespace Drupal\quote\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\quote\TheySaidSo;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class TheySaidSoSettingsForm.
 *
 * @package Drupal\quote\Form
 */
class TheySaidSoSettingsForm extends ConfigFormBase {

  /**
   * Service used to communicate with They Said So.
   *
   * @var \Drupal\quote\TheySaidSo
   */
  protected $theySaidSo;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, MessengerInterface $messenger, TheySaidSo $theySaidSo) {
    parent::__construct($config_factory);
    $this->setMessenger($messenger);
    $this->theySaidSo = $theySaidSo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('messenger'),
      $container->get('quote.tss')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('quote.settings');

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('Secret token used to communicate with the "They Said So" service.'),
      '#default_value' => isset($config->api_key) ? $config->api_key : '',
    ];

    $options = $this->theySaidSo->getCategories();
    $form['category'] = [
      '#title' => $this->t('Categories'),
      '#description' => $this->t('The different classifications for the quotes.'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => isset($config->categories) ? $config->categories : [],
      '#multiple' => TRUE,
    ];

    $form['attribution'] = [
      '#title' => $this->t('Show Attribution'),
      '#description' => $this->t('Show the "They Said So" attribution as part of the quote.'),
      '#type' => 'checkbox',
      '#default_value' => isset($config->attribution) ? $config->attribution : TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('api_key')) && $form_state->getValue('attribution') == FALSE) {
      $form_state->setErrorByName('attribution', 'The attribution must be checked if no API Key is provided.');
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('quote.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('categories', $form_state->getValue('categories'))
      ->set('attribution', $form_state->getValue('attribution'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'they_said_so_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['quote.settings'];
  }

}
