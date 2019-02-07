<?php

namespace Drupal\Tests\block_style_plugins\Unit\Plugin;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\block\BlockForm;
use Drupal\block\Entity\Block;
use Drupal\Core\Block\BlockPluginInterface;

/**
 * @coversDefaultClass \Drupal\block_style_plugins\Plugin\BlockStyleBase
 * @group block_style_plugins
 */
class BlockStyleBaseTest extends UnitTestCase {

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
   * Mocked Block Plugin.
   *
   * @var \Drupal\Core\Block\BlockPluginInterface
   */
  protected $blockPlugin;

  /**
   * Instance of the BlockStyleBase plugin.
   *
   * @var \Drupal\block_style_plugins\Plugin\BlockStyleBase
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

    // Block plugin.
    $this->blockPlugin = $this->prophesize(BlockPluginInterface::CLASS);
    $this->blockPlugin->getBaseId()->willReturn('block_content');
    $this->blockPlugin->getDerivativeId()->willReturn('uuid-1234');
    $this->blockPlugin->getPluginId()->willReturn('basic_block');

    $configuration = [];
    $plugin_id = 'block_style_plugins';
    $plugin_definition['provider'] = 'block_style_plugins';

    $this->plugin = new MockBlockStyleBase(
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
   * Tests the create method.
   *
   * @see ::create()
   */
  public function testCreate() {
    $configuration = [];
    $plugin_id = 'block_style_plugins';
    $plugin_definition['provider'] = 'block_style_plugins';

    $container = $this->prophesize(ContainerInterface::CLASS);
    $container->get('entity.repository')->willReturn($this->entityRepository->reveal());
    $container->get('entity_type.manager')->willReturn($this->entityTypeManager->reveal());

    $instance = MockBlockStyleBase::create(
      $container->reveal(),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $this->assertInstanceOf('Drupal\block_style_plugins\Plugin\BlockStyleInterface', $instance);
  }

  /**
   * Tests the prepareForm() method.
   *
   * @see ::prepareForm()
   */
  public function testPrepareForm() {
    $block = $this->prophesize(Block::CLASS);
    $block->getPlugin()->willReturn($this->blockPlugin->reveal());
    $block->getThirdPartySetting('block_style_plugins', 'block_style_plugins')->willReturn(['test_style' => TRUE]);

    $blockForm = $this->prophesize(BlockForm::CLASS);
    $blockForm->getEntity()->willReturn($block->reveal());

    $this->formState->getFormObject()->willReturn($blockForm->reveal());

    $form = [];
    $form['actions']['submit']['#submit'] = [];
    $return = $this->plugin->prepareForm($form, $this->formState->reveal());

    // Check the callback function attached.
    $return_callback = $return['actions']['submit']['#submit'][0];
    $this->assertInstanceOf('Drupal\block_style_plugins\Plugin\BlockStyleBase', $return_callback[0]);
    $this->assertEquals('submitForm', $return_callback[1]);

    // Check that a block_styles array is set.
    $this->assertArrayHasKey('block_styles', $return);

    // Check that styles were set.
    $styles = $this->plugin->getConfiguration();
    $expected_styles = [
      'sample_class' => '',
      'sample_checkbox' => '',
      'test_style' => TRUE,
    ];
    $this->assertArrayEquals($expected_styles, $styles);

    // Check third party settings.
    $expected_third_party_settings['block_style_plugins']['block_style_plugins'] = [
      '#type' => 'container',
      '#group' => 'block_styles',
    ];
    $this->assertArrayEquals($expected_third_party_settings, $return['third_party_settings']);
  }

  /**
   * Tests the defaultConfiguration method.
   *
   * @see ::defaultConfiguration()
   */
  public function testDefaultConfiguration() {
    $expected = [
      'sample_class' => '',
      'sample_checkbox' => FALSE,
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
    $form = [];
    $return = $this->plugin->buildConfigurationForm($form, $this->formState->reveal());

    $this->assertArrayEquals([], $return);
  }

  /**
   * Tests the formAlter method.
   *
   * @see ::formAlter()
   */
  public function testFormAlter() {
    $form = ['test'];
    $return = $this->plugin->formAlter($form, $this->formState->reveal());

    $this->assertArrayEquals($form, $return);
  }

  /**
   * Tests the validateForm method.
   *
   * @see ::validateForm()
   */
  public function testValidateForm() {
    $form = ['third_party_settings' => ['block_style_plugins' => [$this->plugin->getPluginId() => []]]];
    $return = $this->plugin->validateForm($form, $this->formState->reveal());

    $this->assertNull($return);
  }

  /**
   * Tests the submitForm method.
   *
   * @see ::submitForm()
   */
  public function testSubmitForm() {
    $form = ['third_party_settings' => ['block_style_plugins' => [$this->plugin->getPluginId() => []]]];
    $return = $this->plugin->submitForm($form, $this->formState->reveal());

    $this->assertNull($return);
  }

  /**
   * Tests the build method.
   *
   * @see ::build()
   * @TODO Create a provider so that more combinations can be tested.
   */
  public function testBuild() {
    $block = $this->prophesize(ConfigEntityInterface::CLASS);

    $storage = $this->prophesize(EntityStorageInterface::CLASS);
    $storage->load(1)->willReturn($block->reveal());

    $this->entityTypeManager->getStorage('block')->willReturn($storage->reveal());

    // No element ID is passed through the variables.
    $variables = [];
    $return = $this->plugin->build($variables);
    $this->assertArrayEquals($variables, $return);

    // No styles attached to the block.
    $block->getThirdPartySetting('block_style_plugins', 'block_style_plugins')
      ->willReturn(FALSE);

    $variables = ['elements' => ['#id' => 1]];
    $return = $this->plugin->build($variables);
    $this->assertArrayEquals($variables, $return);

    // Return the third party styles set in the plugin.
    $block->getThirdPartySetting('block_style_plugins', 'block_style_plugins')
      ->willReturn(['class1', 'class2']);

    $variables = ['elements' => ['#id' => 1]];
    $expected = [
      'elements' => ['#id' => 1],
      'block_styles' => [
        'block_style_plugins' => ['class1', 'class2'],
      ],
      'attributes' => [
        'class' => [
          'class1',
          'class2',
        ],
      ],
    ];
    $return = $this->plugin->build($variables);
    $this->assertArrayEquals($expected, $return);

    // Don't set a class for integers.
    $block->getThirdPartySetting('block_style_plugins', 'block_style_plugins')
      ->willReturn(['class1', 1, 'class2', 0]);

    $variables = ['elements' => ['#id' => 1]];
    $expected = [
      'elements' => ['#id' => 1],
      'block_styles' => [
        'block_style_plugins' => ['class1', 1, 'class2', 0],
      ],
      'attributes' => [
        'class' => [
          'class1',
          'class2',
        ],
      ],
    ];
    $return = $this->plugin->build($variables);
    $this->assertArrayEquals($expected, $return);
  }

  /**
   * Tests the getConfiguration method.
   *
   * @see ::getConfiguration()
   */
  public function testGetConfiguration() {
    $expected = [
      'sample_class' => '',
      'sample_checkbox' => FALSE,
    ];
    $this->plugin->setConfiguration([]);
    $return = $this->plugin->getConfiguration();

    $this->assertArrayEquals($expected, $return);
  }

  /**
   * Tests the setConfiguration method.
   *
   * @see ::setConfiguration()
   */
  public function testSetConfiguration() {
    $expected = [
      'sample_class' => '',
      'sample_checkbox' => FALSE,
      'new_key' => 'new_val',
    ];

    $new_styles = ['new_key' => 'new_val'];
    $this->plugin->setConfiguration($new_styles);
    $return = $this->plugin->getConfiguration();

    $this->assertArrayEquals($expected, $return);

    // Overwrite styles.
    $expected = [
      'sample_class' => 'class_name',
      'sample_checkbox' => TRUE,
    ];

    $this->plugin->setConfiguration($expected);
    $return = $this->plugin->getConfiguration();

    $this->assertArrayEquals($expected, $return);
  }

  /**
   * Tests the exclude method.
   *
   * @see ::exclude()
   *
   * @dataProvider excludeProvider
   */
  public function testExclude($plugin, $bundle, $expected) {
    // Stub the blockPlugin.
    $this->setProtectedProperty('blockPlugin', $this->blockPlugin->reveal());

    if ($plugin) {
      $this->setProtectedProperty('pluginDefinition', ['exclude' => [$plugin]]);
    }
    if ($bundle) {
      $this->setProtectedProperty('blockContentBundle', $bundle);
    }
    $return = $this->plugin->exclude();
    $this->assertEquals($expected, $return);
  }

  /**
   * Provider for testExclude()
   */
  public function excludeProvider() {
    return [
      'No exclude options are passed' => [FALSE, NULL, FALSE],
      'Exclude basic_block' => ['basic_block', NULL, TRUE],
      'Exclude a block that is not the current one' => [
        'wrong_block',
        NULL,
        FALSE,
      ],
      'Exclude a custom content block' => [
        'custom_block',
        'custom_block',
        TRUE,
      ],
      'Exclude a custom content block that is not the current block' => [
        'wrong_custom_block',
        'custom_block',
        FALSE,
      ],
    ];
  }

  /**
   * Tests the includeOnly method.
   *
   * @see ::includeOnly()
   *
   * @dataProvider includeOnlyProvider
   */
  public function testIncludeOnly($plugin, $bundle, $expected) {
    // Stub the blockPlugin.
    $this->setProtectedProperty('blockPlugin', $this->blockPlugin->reveal());

    if ($plugin) {
      $this->setProtectedProperty('pluginDefinition', ['include' => [$plugin]]);
    }
    if ($bundle) {
      $this->setProtectedProperty('blockContentBundle', $bundle);
    }
    $return = $this->plugin->includeOnly();
    $this->assertEquals($expected, $return);
  }

  /**
   * Provider for testIncludeOnly()
   */
  public function includeOnlyProvider() {
    return [
      'No include options are passed' => [NULL, NULL, TRUE],
      'Include basic_block' => ['basic_block', NULL, TRUE],
      'Include only a sample_block' => ['wrong_block', NULL, FALSE],
      'Include a custom content block' => [
        'custom_block',
        'custom_block',
        TRUE,
      ],
      'Include a custom content block which is not the current one' => [
        'wrong_custom_block',
        'custom_block',
        FALSE,
      ],
    ];
  }

  /**
   * Tests the setBlockContentBundle method.
   *
   * @see ::setBlockContentBundle()
   */
  public function testSetBlockContentBundle() {
    // Stub the blockPlugin.
    $this->setProtectedProperty('blockPlugin', $this->blockPlugin->reveal());

    $entity = $this->prophesize(EntityInterface::CLASS);
    $entity->bundle()->willReturn('basic_custom_block');

    $this->entityRepository->loadEntityByUuid('block_content', 'uuid-1234')
      ->willReturn($entity->reveal());

    $this->plugin->setBlockContentBundle();
    $bundle = $this->getProtectedProperty('blockContentBundle');

    $this->assertEquals('basic_custom_block', $bundle);
  }

  /**
   * Get a protected property on the plugin via reflection.
   *
   * @param string $property
   *   Property on instance.
   *
   * @return mixed
   *   Return the value of the protected property.
   */
  public function getProtectedProperty($property) {
    $reflection = new \ReflectionClass($this->plugin);
    $reflection_property = $reflection->getProperty($property);
    $reflection_property->setAccessible(TRUE);
    return $reflection_property->getValue($this->plugin);
  }

  /**
   * Sets a protected property on the plugin via reflection.
   *
   * @param string $property
   *   Property on instance being modified.
   * @param mixed $value
   *   New value of the property being modified.
   */
  public function setProtectedProperty($property, $value) {
    $reflection = new \ReflectionClass($this->plugin);
    $reflection_property = $reflection->getProperty($property);
    $reflection_property->setAccessible(TRUE);
    $reflection_property->setValue($this->plugin, $value);
  }

}
