<?php

namespace Drupal\Tests\block_style_plugins\Unit\Plugin;

use Drupal\Tests\UnitTestCase;
use Drupal\block_style_plugins\Plugin\BlockStyle;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * @coversDefaultClass \Drupal\block_style_plugins\Plugin\BlockStyle
 * @group block_style_plugins
 */
class BlockStyleTest extends UnitTestCase
{

  /**
   * @var \Drupal\Core\Entity\EntityRepository
   */
  protected $entityRepository;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * @var \Drupal\block_style_plugins\Plugin\BlockStyleBase
   */
  protected $plugin;

  /**
   * Create the setup for constants and configFactory stub
   */
  protected function setUp()
  {
    parent::setUp();

    // Stub the Iconset Finder Service
    $this->entityRepository = $this->prophesize(EntityRepositoryInterface::CLASS);

    // Stub the Entity Type Manager
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::CLASS);

    // Form state double
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
        ]
      ]
    ];

    $this->plugin = new BlockStyle(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $this->entityRepository->reveal(),
      $this->entityTypeManager->reveal()
    );

    // Create a translation stub for the t() method
    $translator = $this->getStringTranslationStub();
    $this->plugin->setStringTranslation($translator);
  }

  /**
   * Tests the defaultStyles method.
   *
   * @see ::defaultStyles()
   */
  public function testDefaultStyles() {
    $expected = [
      'test_field' => 'default text',
      'second_field' => 1,
    ];
    $default = $this->plugin->defaultStyles();

    $this->assertArrayEquals($expected, $default);
  }

  /**
   * Tests the formElements method.
   *
   * @see ::formElements()
   */
  public function testFormElements() {
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
      ]
    ];

    // Use reflection to alter the protected $this->plugin->styles
    $reflectionObject = new \ReflectionObject($this->plugin);
    $property = $reflectionObject->getProperty('styles');
    $property->setAccessible(true);
    $property->setValue($this->plugin, ['third_field' => 'user set value']);

    $form = [];
    $return = $this->plugin->formElements($form, $this->formState->reveal());

    $this->assertArrayEquals($expected, $return);
  }

}
