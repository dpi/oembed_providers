<?php

namespace Drupal\oembed_providers\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

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
  }

}
