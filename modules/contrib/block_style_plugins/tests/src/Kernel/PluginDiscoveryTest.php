<?php

namespace Drupal\Tests\block_style_plugins\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test of the Block Style Plugins discovery integration.
 *
 * @group block_style_plugins
 */
class PluginDiscoveryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block_style_plugins', 'block_style_plugins_test'];

  /**
   * Make sure that plugins are discovered.
   */
  public function testPluginDiscovery() {
    $plugin_manager = $this->container->get('plugin.manager.block_style.processor');
    $style_plugins = $plugin_manager->getDefinitions();

    $expected = [
      'simple_class' => [
        'exclude' => [],
        'include' => [],
        'id' => 'simple_class',
        'label' => 'Simple Class',
        'class' => 'Drupal\block_style_plugins_test\Plugin\BlockStyle\SimpleClass',
        'provider' => 'block_style_plugins_test',
      ],
      'dropdown_with_include' => [
        'exclude' => [],
        'include' => [
          'system_powered_by_block',
          'basic',
        ],
        'id' => 'dropdown_with_include',
        'label' => 'Dropdown with Include',
        'class' => 'Drupal\block_style_plugins_test\Plugin\BlockStyle\DropdownWithInclude',
        'provider' => 'block_style_plugins_test',
      ],
      'checkbox_with_exclude' => [
        'exclude' => [
          'system_powered_by_block',
          'basic',
        ],
        'id' => 'checkbox_with_exclude',
        'label' => 'Checkbox with Exclude',
        'class' => 'Drupal\block_style_plugins_test\Plugin\BlockStyle\CheckboxWithExclude',
        'provider' => 'block_style_plugins_test',
      ],
      'form_fields_created_with_yaml' => [
        'include' => [
          'system_powered_by_block',
        ],
        'id' => 'form_fields_created_with_yaml',
        'label' => 'Styles Created by Yaml',
        'class' => 'Drupal\block_style_plugins\Plugin\BlockStyle',
        'provider' => 'block_style_plugins_test',
        'form' => [
          'test_field' => [
            '#type' => 'textfield',
            '#title' => 'Title Created by Yaml',
            '#default_value' => 'text goes here',
          ],
          'second_field' => [
            '#type' => 'select',
            '#title' => 'Choose a style',
            '#options' => [
              'style-1' => 'Style 1',
              'style-2' => 'Style 2',
            ],
          ],
        ],
      ],
      'template_set_with_yaml' => [
        'id' => 'template_set_with_yaml',
        'label' => 'Template Set by Yaml',
        'class' => 'Drupal\block_style_plugins\Plugin\BlockStyle',
        'provider' => 'block_style_plugins_test',
        'template' => 'block__test_custom',
        'form' => [
          'test_field' => [
            '#type' => 'textfield',
            '#title' => 'Template Title',
          ],
        ],
      ],
    ];
    $this->assertEquals($expected, $style_plugins);
  }

  /**
   * Make sure that plugins are discovered.
   */
  public function testInstanceCreation() {
    $plugin_manager = $this->container->get('plugin.manager.block_style.processor');

    $style_plugin = $plugin_manager->createInstance('simple_class');

    $this->assertInstanceOf('Drupal\block_style_plugins\Plugin\BlockStyleInterface', $style_plugin);
  }

}
