<?php

namespace Drupal\oembed_providers\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\oembed_providers\OEmbed\ProviderRepositoryDecorator;
use Drupal\oembed_providers\Traits\HelperTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure allowed providers settings form.
 */
class AllowedProvidersForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'oembed_providers.settings';

  /**
   * The decorated oEmbed ProviderRepository.
   *
   * @var \Drupal\oembed_providers\OEmbed\ProviderRepositoryDecorator
   */
  protected $providerRepository;

  /**
   * Constructs an AllowedProvidersForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\oembed_providers\OEmbed\ProviderRepositoryDecorator $provider_repository
   *   The decorated oEmbed ProviderRepository.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ProviderRepositoryDecorator $provider_repository) {
    $this->setConfigFactory($config_factory);
    $this->providerRepository = $provider_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('media.oembed.provider_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'oembed_allowed_providers_settings';
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

    $form['security_warning'] = [
      '#markup' => HelperTrait::disabledProviderSecurityWarning(),
      // Simulate warning message.
      '#prefix' => '<div role="contentinfo" aria-label="Warning message" class="messages messages--warning">',
      '#suffix' => '</div>',
    ];

    if (empty($config->get('allowed_providers'))) {
      $form['install_markup'] = [
        '#markup' => $this->t('The <em>oEmbed Providers</em> module now manages oEmbed providers. Allowed oEmbed providers must be configured below.'),
        // Simulate warning message.
        '#prefix' => '<div role="contentinfo" aria-label="Warning message" class="messages messages--warning">',
        '#suffix' => '</div>',
      ];
    }

    $form['markup'] = [
      '#markup' => $this->t('<p>Providers enabled below will be made available as media sources.</p>'),
    ];

    $providers = $this->providerRepository->getAll();
    $provider_keys = [];
    foreach ($providers as $provider) {
      $provider_keys[$provider->getName()] = $provider->getName();
    }

    $form['allowed_providers'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed Providers'),
      '#default_value' => ($config->get('allowed_providers')) ? $config->get('allowed_providers') : [],
      '#options' => $provider_keys,
    ];

    $form['#attached']['library'][] = 'oembed_providers/settings_form';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $allowed_providers = [];
    foreach ($form_state->getValue('allowed_providers') as $provider) {
      if ($provider) {
        $allowed_providers[] = $provider;
      }
    }

    $this->configFactory->getEditable(static::SETTINGS)
      ->set('allowed_providers', $allowed_providers)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
