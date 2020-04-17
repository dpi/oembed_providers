<?php

namespace Drupal\oembed_providers\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure oEmbed settings form.
 */
class OembedProvidersSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'oembed_providers.settings';

  /**
   * Cache backend for default cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $defaultCache;

  /**
   * Constructs an OembedProvidersSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Cache\CacheBackendInterface $default_cache
   *   Cache backend for default cache.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $default_cache) {
    $this->setConfigFactory($config_factory);
    $this->defaultCache = $default_cache;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cache.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'oembed_providers_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['oembed_providers_url'] = [
      '#type' => 'url',
      '#title' => $this->t('oEmbed Providers URL'),
      '#description' => $this->t('The URL where Media fetches the list of oEmbed providers'),
      '#default_value' => $this->config('media.settings')->get('oembed_providers_url'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('media.settings')
      ->set('oembed_providers_url', $form_state->getValue('oembed_providers_url'))
      ->save();

    parent::submitForm($form, $form_state);
    $this->defaultCache->delete('oembed_providers:oembed_providers');
  }

}
