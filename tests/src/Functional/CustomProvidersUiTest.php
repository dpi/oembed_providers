<?php

namespace Drupal\Tests\oembed_providers\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class CustomProvidersUiTest.
 *
 * @group oembed_providers
 */
class CustomProvidersUiTest extends BrowserTestBase {

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
    'media',
    'oembed_providers',
    'oembed_providers_test',
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
        'administer oembed providers',
      ]);
    // Create a non-admin user.
    $this->nonAdminUser = $this
      ->drupalCreateUser([
        'access administration pages',
      ]);
  }

  /**
   * Tests route permissions.
   */
  public function testRoutePermissions() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->nonAdminUser);
    $this->drupalGet('/admin/config/media/oembed-providers/custom-providers');
    $assert_session->statusCodeEquals(403, "Non-admin user is unable to access Customer Providers listing page");
    $this->drupalGet('/admin/config/media/oembed-providers/custom-providers/add');
    $assert_session->statusCodeEquals(403, "Non-admin user is unable to access Customer Providers add page");
    $this->drupalGet('/admin/config/media/oembed-providers/custom-providers/unl_mediahub/edit');
    $assert_session->statusCodeEquals(403, "Non-admin user is unable to access Customer Providers edit page");
    $this->drupalGet('/admin/config/media/oembed-providers/custom-providers/unl_mediahub/delete');
    $assert_session->statusCodeEquals(403, "Non-admin user is unable to access Customer Providers delete page");

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/media/oembed-providers/custom-providers');
    $assert_session->statusCodeEquals(200, "Admin user is able to access Customer Providers listing page");
    $this->drupalGet('/admin/config/media/oembed-providers/custom-providers/add');
    $assert_session->statusCodeEquals(200, "Admin user is able to access Customer Providers add page");
    $this->drupalGet('/admin/config/media/oembed-providers/custom-providers/unl_mediahub/edit');
    $assert_session->statusCodeEquals(200, "Admin user is able to access Customer Providers edit page");
    $this->drupalGet('/admin/config/media/oembed-providers/custom-providers/unl_mediahub/delete');
    $assert_session->statusCodeEquals(200, "Admin user is able to access Customer Providers delete page");
  }

}
