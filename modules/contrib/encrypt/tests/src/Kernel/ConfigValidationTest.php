<?php

declare(strict_types=1);

namespace Drupal\Tests\encrypt\Kernel;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\key\Entity\Key;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Validates that all encrypt config fully validates.
 *
 * Every config schema type provided by encrypt is marked FullyValidatable,
 * so configuration must produce zero constraint violations.
 *
 * @group encrypt
 */
#[Group('encrypt')]
class ConfigValidationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'encrypt',
    'encrypt_test',
    'key',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['encrypt']);
  }

  /**
   * Tests that the shipped settings config object is fully valid.
   */
  public function testSettingsConfigIsFullyValid(): void {
    $this->assertConfigIsFullyValid('encrypt.settings');
  }

  /**
   * Tests that encryption profile config entities are fully valid.
   */
  public function testEncryptionProfileIsFullyValid(): void {
    Key::create([
      'id' => 'testing_key_128',
      'label' => 'Testing Key 128 bit',
      'key_type' => 'encryption',
      'key_type_settings' => ['key_size' => '128'],
      'key_provider' => 'config',
      'key_provider_settings' => ['key_value' => 'mustbesixteenbit'],
    ])->save();

    // A profile whose encryption method has no configuration exercises the
    // encryption_method.config.* fallback type.
    EncryptionProfile::create([
      'id' => 'encryption_profile_1',
      'label' => 'Encryption profile 1',
      'encryption_method' => 'test_encryption_method',
      'encryption_key' => 'testing_key_128',
    ])->save();
    $this->assertConfigIsFullyValid('encrypt.profile.encryption_profile_1');

    // A profile whose encryption method has configuration exercises the
    // method-specific schema provided by the plugin's module.
    EncryptionProfile::create([
      'id' => 'encryption_profile_2',
      'label' => 'Encryption profile 2',
      'encryption_method' => 'config_test_encryption_method',
      'encryption_method_configuration' => ['mode' => 'CFB'],
      'encryption_key' => 'testing_key_128',
    ])->save();
    $this->assertConfigIsFullyValid('encrypt.profile.encryption_profile_2');
  }

  /**
   * Asserts that a config object produces zero validation violations.
   *
   * @param string $name
   *   The config object name.
   */
  protected function assertConfigIsFullyValid(string $name): void {
    $this->assertFalse($this->config($name)->isNew(), "Config '$name' exists.");
    $typed_config_manager = $this->container->get(TypedConfigManagerInterface::class);
    $violations = $typed_config_manager->get($name)->validate();
    $messages = [];
    foreach ($violations as $violation) {
      $messages[] = $violation->getPropertyPath() . ': ' . (string) $violation->getMessage();
    }
    $this->assertSame([], $messages, "Config '$name' must be fully valid.");
  }

}
