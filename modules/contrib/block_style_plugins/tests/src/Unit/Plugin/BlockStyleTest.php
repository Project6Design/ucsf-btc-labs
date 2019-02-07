<?php

namespace Drupal\Tests\block_style_plugins\Unit\Plugin;

use Drupal\Tests\UnitTestCase;
use Drupal\block_style_plugins\Plugin\BlockStyle;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * @coversDefaultClass \Drupal\block_style_plugins\Plugin\BlockStyle
 * @group block_style_plugins
 */
class BlockStyleTest extends UnitTestCase {

  /**
   * Mocked entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepository
   */
  protected $entityRepository;

  /**
   * Mocked entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Mocked form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * Instance of the BlockStyle plugin.
   *
   * @var \Drupal\block_style_plugins\Plugin\BlockStyle
   */
  protected $plugin;

  /**
   * Create the setup for constants and configFactory stub.
   */
  protected function setUp() {
    parent::setUp();

    // Stub the Iconset Finder Service.
    $this->entityRepository = $this->prophesize(EntityRepositoryInterface::CLASS);

    // Stub the Entity Type Manager.
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::CLASS);

    // Form state double.
    $this->formState = $this->prophesize(FormStateInterface::CLASS);

    $configuration = [];
    $plugin_id = 'block_style_plugins';
    $plugin_definition = [
      'provider' => 'block_style_plugins',
      'form' => [
        'test_field' => [
          '#type' => 'textfield',
          '#title' => 'this is a title',
          '#default_value' => 'default text',
        ],
        'second_field' => [
          '#type' => 'checkbox',
          '#title' => 'Checkbox title',
          '#default_value' => 1,
        ],
        'third_field' => [
          '#type' => 'textfield',
          '#title' => 'Third Box',
        ],
      ],
    ];

    $this->plugin = new BlockStyle(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $this->entityRepository->reveal(),
      $this->entityTypeManager->reveal()
    );

    // Create a translation stub for the t() method.
    $translator = $this->getStringTranslationStub();
    $this->plugin->setStringTranslation($translator);
  }

  /**
   * Tests the defaultConfiguration method.
   *
   * @see ::defaultConfiguration()
   */
  public function testDefaultConfiguration() {
    $expected = [
      'test_field' => 'default text',
      'second_field' => 1,
    ];
    $default = $this->plugin->defaultConfiguration();

    $this->assertArrayEquals($expected, $default);
  }

  /**
   * Tests the buildConfigurationForm method.
   *
   * @see ::buildConfigurationForm()
   */
  public function testBuildConfigurationForm() {
    $expected = [
      'test_field' => [
        '#type' => 'textfield',
        '#title' => 'this is a title',
        '#default_value' => 'default text',
      ],
      'second_field' => [
        '#type' => 'checkbox',
        '#title' => 'Checkbox title',
        '#default_value' => 1,
      ],
      'third_field' => [
        '#type' => 'textfield',
        '#title' => 'Third Box',
        '#default_value' => 'user set value',
      ],
    ];

    // Use reflection to alter the protected $this->plugin->styles.
    $reflectionObject = new \ReflectionObject($this->plugin);
    $property = $reflectionObject->getProperty('configuration');
    $property->setAccessible(TRUE);
    $property->setValue($this->plugin, ['third_field' => 'user set value']);

    $form = [];
    $return = $this->plugin->buildConfigurationForm($form, $this->formState->reveal());

    $this->assertArrayEquals($expected, $return);
  }

  /**
   * Tests the themeSuggestion method.
   *
   * @see ::themeSuggestion()
   */
  public function testThemeSuggestion() {
    $block = $this->prophesize(ConfigEntityInterface::CLASS);

    $storage = $this->prophesize(EntityStorageInterface::CLASS);
    $storage->load(1)->willReturn($block->reveal());

    $this->entityTypeManager->getStorage('block')->willReturn($storage->reveal());

    // Return the third party styles set in the plugin.
    $block->getThirdPartySetting('block_style_plugins', 'block_style_plugins')
      ->willReturn(['class1', 'class2']);

    // Use reflection to alter the protected $this->plugin->pluginDefinition.
    $reflectionObject = new \ReflectionObject($this->plugin);
    $property = $reflectionObject->getProperty('pluginDefinition');
    $property->setAccessible(TRUE);
    $property->setValue($this->plugin, ['template' => 'custom_template']);

    $suggestions = [];
    $variables = ['elements' => ['#id' => 1]];
    $expected = [
      'custom_template',
    ];

    $return = $this->plugin->themeSuggestion($suggestions, $variables);

    $this->assertArrayEquals($expected, $return);
  }

}
