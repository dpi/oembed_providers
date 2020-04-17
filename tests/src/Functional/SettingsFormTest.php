<?php

namespace Drupal\Tests\oembed_providers\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class SettingsFormTest.
 *
 * @group oembed_providers
 */
class SettingsFormTest extends BrowserTestBase {

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

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/media/oembed-providers');

    $this->assertSame('https://oembed.com/providers.json', $page->findField('oembed_providers_url')->getValue());

    $page
      ->findField('oembed_providers_url')
      ->setValue('https://example.com/providers.json');

    $page->pressButton('Save configuration');

    $assert_session->pageTextContains('The configuration options have been saved.');
    $this->assertSame('https://example.com/providers.json', $this->config('media.settings')->get('oembed_providers_url'));
  }

}
