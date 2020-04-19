<?php

namespace Drupal\Tests\oembed_providers\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\OEmbedTestTrait;

/**
 * Class SettingsFormTest.
 *
 * @group oembed_providers
 */
class SettingsFormTest extends BrowserTestBase {

  use OEmbedTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The test administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The test non-administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $nonAdminUser;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'media',
    'oembed_providers',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create an admin user.
    $this->adminUser = $this
      ->drupalCreateUser([
        'access administration pages',
        'administer blocks',
        'administer oembed providers',
      ]);
    // Create a non-admin user.
    $this->nonAdminUser = $this
      ->drupalCreateUser([
        'access administration pages',
      ]);

    $this->drupalPlaceBlock('system_messages_block');
  }

  /**
   * Tests route permissions.
   */
  public function testRoutePermissions() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->nonAdminUser);
    $this->drupalGet('/admin/config/media/oembed-providers');
    $assert_session->statusCodeEquals(403, "Non-admin user is unable to access settings page");

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/media/oembed-providers');
    $assert_session->statusCodeEquals(200, "Admin user is unable to access settings page");
  }

  /**
   * Tests settings form.
   */
  public function testSettingsForm() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Set dummy value in cache, so it can be deleted on form submission.
    \Drupal::cache()->set('oembed_providers:oembed_providers', 'test value', REQUEST_TIME + (86400));

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/media/oembed-providers');

    $assert_session->checkboxChecked('Enable external fetch of providers');
    $this->assertSame('https://oembed.com/providers.json', $page->findField('oembed_providers_url')->getValue());

    $page
      ->findField('oembed_providers_url')
      ->setValue('https://example.com/providers.json');

    $page->pressButton('Save configuration');

    $assert_session->pageTextContains('The configuration options have been saved.');
    $this->assertSame('https://example.com/providers.json', $this->config('media.settings')->get('oembed_providers_url'));

    // Verify cached providers are cleared.
    $this->AssertFalse(\Drupal::cache()->get('oembed_providers:oembed_providers'));

    $this->drupalGet('/admin/config/media/oembed-providers');

    $assert_session->checkboxChecked('Enable external fetch of providers');
    $this->assertSame('https://example.com/providers.json', $page->findField('oembed_providers_url')->getValue());

    $page->findField('external_fetch')->uncheck();

    $page->pressButton('Save configuration');

    $assert_session->pageTextContains('The configuration options have been saved.');
    $this->assertSame(FALSE, $this->config('oembed_providers.settings')->get('external_fetch'));

    $this->drupalGet('/admin/config/media/oembed-providers');

    $assert_session->checkboxNotChecked('Enable external fetch of providers');
  }

  /**
   * Tests allowed providers form.
   */
  public function testAllowedProviders() {
    $this->useFixtureProviders();
    $this->lockHttpClientToFixtures();

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Set dummy value in cache, so it can be deleted on form submission.
    \Drupal::service('cache.discovery')->set('media_source_plugins', 'test value', REQUEST_TIME + (86400));

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/media/oembed-providers/allowed-providers');

    $assert_session->checkboxNotChecked('Vimeo');
    $assert_session->checkboxNotChecked('YouTube');

    $page->findField('allowed_providers[Vimeo]')->check();
    $page->pressButton('Save configuration');

    $assert_session->pageTextContains('The configuration options have been saved.');

    $config_allowed_providers = \Drupal::config('oembed_providers.settings')->get('allowed_providers');
    $expected_allowed_providers = ['Vimeo'];
    $this->assertSame($config_allowed_providers, $expected_allowed_providers);

    // Verify cached providers are cleared.
    $this->AssertFalse(\Drupal::service('cache.discovery')->get('media_source_plugins'));

    // Verify media sources have been modified.
    $media_sources = \Drupal::service('plugin.manager.media.source')->getDefinitions();
    $providers = $media_sources['oembed:video']['providers'];

    $this->AssertTrue(in_array('Vimeo', $providers));
    $this->AssertFalse(in_array('YouTube', $providers));
  }

}
