<?php

namespace Drupal\oembed_providers\OEmbed;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\media\OEmbed\Provider;
use Drupal\media\OEmbed\ProviderException;
use Drupal\media\OEmbed\ProviderRepository;
use Drupal\media\OEmbed\ProviderRepositoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Decorates the oEmbed ProviderRepository provided by core Media module.
 */
final class ProviderRepositoryDecorator implements ProviderRepositoryInterface {

  use UseCacheBackendTrait;

  /**
   * Retrieves and caches information about oEmbed providers.
   *
   * @var \Drupal\media\OEmbed\ProviderRepository
   */
  protected $decorated;

  /**
   * Manages entity type plugin definitions.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * How long the provider data should be cached, in seconds.
   *
   * @var int
   */
  protected $maxAge;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * URL of a JSON document which contains a database of oEmbed providers.
   *
   * @var string
   */
  protected $providersUrl;

  /**
   * Whether or not the external providers list should be fetched.
   *
   * @var bool
   */
  protected $externalFetch;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a ProviderRepository instance.
   *
   * @param \Drupal\media\OEmbed\ProviderRepository $decorated
   *   Retrieves and caches information about oEmbed providers.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   Manages entity type plugin definitions.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   (optional) The cache backend.
   * @param int $max_age
   *   (optional) How long the cache data should be kept. Defaults to a week.
   */
  public function __construct(ProviderRepository $decorated, EntityTypeManager $entity_type_manager, ClientInterface $http_client, ConfigFactoryInterface $config_factory, TimeInterface $time, CacheBackendInterface $cache_backend = NULL, $max_age = 604800) {
    $this->decorated = $decorated;
    $this->entityTypeManager = $entity_type_manager;
    $this->httpClient = $http_client;
    $this->providersUrl = $config_factory->get('media.settings')->get('oembed_providers_url');
    $this->externalFetch = $config_factory->get('oembed_providers.settings')->get('external_fetch');
    $this->time = $time;
    $this->cacheBackend = $cache_backend;
    $this->maxAge = (int) $max_age;
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    $cache_id = 'oembed_providers:oembed_providers';

    $cached = $this->cacheGet($cache_id);
    if ($cached) {
      return $cached->data;
    }

    $custom_providers = $this->getCustomProviders();

    if ($this->externalFetch) {
      try {
        $response = $this->httpClient->request('GET', $this->providersUrl);
      }
      catch (RequestException $e) {
        throw new ProviderException("Could not retrieve the oEmbed provider database from $this->providersUrl", NULL, $e);
      }

      $providers = Json::decode((string) $response->getBody());

      if (!is_array($providers) || empty($providers)) {
        throw new ProviderException('Remote oEmbed providers database returned invalid or empty list.');
      }

      // Providers defined by provider database cannot be modified by
      // custom oEmbed provider definitions.
      $providers = array_merge($custom_providers, $providers);
    }
    else {
      $providers = $custom_providers;
    }

    usort($providers, function ($a, $b) {
      return strcasecmp($a['provider_name'], $b['provider_name']);
    });

    $keyed_providers = [];
    foreach ($providers as $provider) {
      try {
        $name = (string) $provider['provider_name'];
        $keyed_providers[$name] = new Provider($provider['provider_name'], $provider['provider_url'], $provider['endpoints']);
      }
      catch (ProviderException $e) {
        // Just skip all the invalid providers.
        // @todo Log the exception message to help with debugging.
      }
    }

    $this->cacheSet($cache_id, $keyed_providers, $this->time->getCurrentTime() + $this->maxAge);
    return $keyed_providers;
  }

  /**
   * {@inheritdoc}
   */
  public function get($provider_name) {
    return $this->decorated->get($provider_name);
  }

  /**
   * Returns custom providers in format identical to decoded providers.json.
   *
   * @return array
   *   Custom providers.
   */
  public function getCustomProviders() {
    $return = [];
    $custom_providers = $this->entityTypeManager->getStorage('oembed_provider')->loadMultiple();

    $i = 0;
    foreach ($custom_providers as $custom_provider) {
      $return[$i]['provider_name'] = $custom_provider->get('label');
      $return[$i]['provider_url'] = $custom_provider->get('provider_url');

      $j = 0;
      foreach ($custom_provider->get('endpoints') as $endpoint) {
        $return[$i]['endpoints'][$j]['schemes'] = $endpoint['schemes'];
        $return[$i]['endpoints'][$j]['url'] = $endpoint['url'];
        if ($endpoint['discovery']) {
          $return[$i]['endpoints'][$j]['discovery'] = $endpoint['discovery'];
        }
        foreach ($endpoint['formats'] as $format_id => $format_value) {
          if ($format_value) {
            $return[$i]['endpoints'][$j]['formats'][] = $format_id;
          }
        }
        $j++;
      }
      $i++;
    }
    return $return;
  }

}
