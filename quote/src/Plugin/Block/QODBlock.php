<?php

namespace Drupal\quote\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\quote\TheySaidSo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class QODBlock.
 *
 * @package Drupal\quote\Plugin\Block
 *
 * @Block (
 *   id = "quote_of_the_day",
 *   admin_label = @Translation("Quote of the Day"),
 * )
 */
class QODBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * They Said So Service.
   *
   * @var \Drupal\quote\TheySaidSo
   */
  protected $theySaidSo;

  /**
   * QODBlock constructor.
   *
   * @param array $configuration
   *   The Configuration information.
   * @param mixed $plugin_id
   *   The Plugin Id.
   * @param mixed $plugin_definition
   *   Plugin Definition.
   * @param \Drupal\quote\TheySaidSo $theySaidSo
   *   The They Said So Service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TheySaidSo $theySaidSo) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->theySaidSo = $theySaidSo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('quote.tss')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'category' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $options = $this->theySaidSo->getCategories();
    $form['category'] = [
      '#title' => $this->t('Category'),
      '#default_value' => $this->configuration['category'],
      '#type' => 'radios',
      '#description' => $this->t('Select the category of the quote you would like to see.'),
      '#options' => $options,
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['category'] = $form_state->getValue('category');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $category = $this->configuration['category'];
    $quote = $this->theySaidSo->getQuoteOfTheDay($category);
    if (!empty($quote)) {
      return [
        '#theme' => 'quote',
        '#cite' => $quote['permalink'],
        '#author' => $quote['author'],
        '#quote' => $quote['quote'],
        '#title' => $quote['title'],
        '#attribution' => TRUE,
        '#weight' => 1000,
      ];
    }
  }

}
