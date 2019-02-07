## About

Block Style Plugins is an API module that allows a module or theme to add style
options to block configuration by creating a custom plugin. It is advisable to
do this primarily in themes since the majority of styles should be specific to a
theme.

**Tutorial Video:** [https://youtu.be/Y0t8owlV2_4](https://youtu.be/Y0t8owlV2_4) 

### Dependencies

- block
- block_content

## Basic Setup

### Creating plugins and form fields in a Yaml file

The easiest way to get started is to use a single yaml file to declare each
plugin and the fields to display. This can go into either a module or better
yet, a theme. Name it `mymodule.blockstyle.yml` or `mytheme.blockstyle.yml`
replacing the first part with the name or your module or theme.

```
sample_block_style:
  label: 'Sample Block Style'
  form:
    field_name:
      '#type': 'textfield'
      '#title': 'Add a custom css class'
      '#default_value': 'my-class'
  template: block__my_custom_template
```

> Notice: Form fields need to be on the same level and cannot currently be
nested.

By adding a `template` you may specify a template theme suggestion of your
choosing for the block.

### Example Plugins

The `tests` directory has a module which demonstrates how to build your own
plugins `tests/modules/block_style_plugins_test`.

Example Block Style plugins can be found in the `tests` directory
`tests/modules/block_style_plugins_test/src/Plugin/BlockStyle/SimpleClass.php`
`tests/modules/block_style_plugins_test/src/Plugin/BlockStyle/DropdownWithInclude.php`
`tests/modules/block_style_plugins_test/src/Plugin/BlockStyle/CheckboxWithExclude.php`

Also, check out the example Yaml file for easily declaring plugins at
`tests/modules/block_style_plugins_test/block_style_plugins_test.blockstyle.yml`

## Advanced Usage

### Setting up a block style in a module with a PHP class

Even more power is available if a custom plugin is created in PHP.

Create a new plugin class extending the BlockStyleBase class. Be sure to add in
proper plugin Annotations such as:

```php
/**
 * Provides a 'SimpleClass' block style for adding a class in a text field.
 *
 * @BlockStyle(
 *  id = "simple_class",
 *  label = @Translation("Simple Class"),
 * )
 */
```

Override the `BlockStyleBase::buildConfigurationForm` method to extend the `$form` array
with your own custom style options using the
[Form API](https://api.drupal.org/api/drupal/elements).

A `block_styles` fieldset is automatically provided that can be used to do some
automatic loading of values as classes onto the block attributes.

```
$styles = $this->getConfiguration();

$form['sample_class'] = array(
  '#type' => 'textfield',
  '#title' => $this->t('Add a custom class to this block'),
  '#description' => $this->t('Do not add the "period" to the start of the class'),
  '#default_value' => $styles['sample_class'],
);
```

### Adding a block style to a theme with a custom PHP class

Instead of defining form fields in Yaml, a theme can use a PHP class just like a
module. Just add a `class` and path to the class name in the yaml file.

Standard class Annotations are not discoverable in a theme. Thus the need to use
a `themename.blockstyle.yml` file

```
sample_block_style:
  label: 'Sample Block Style'
  class: '\Drupal\themename\Plugin\BlockStyle\SampleBlockStyle'
```

Then add a BlockStyle plugin class into 
`themename\Plugin\BlockStyle\SampleBlockStyle.php` which will extend the 
BlockStyleBase class

## Visibility rules for restricting style options to specific blocks

"include" and "exclude" attributes are available to only include certain blocks 
or to exclude certain blocks from accessing your custom block styles. Choose one
or the other. Don't use both "include" and "exclude" at the same time.

Pass in a "block plugin id" or a custom block content "block content type"
bundle name into the "include" or "exclude" attributes.

**Include only specific block content types**

```
sample_block_style:
  label: 'Sample Block Style'
  include:
    - 'block_content_type'
```

**Exclude specific blocks**

```
sample_block_style:
  label: 'Sample Block Style'
  exclude:
    - 'block_plugin_id'
```

### How to discover a 'block plugin id'

It might be frustrating at first trying to figure out how to get a block's
plugin id. In a theme's `themename.theme` file just preprocess a block. One of
the variables available is the `base_plugin_id`.

```php
/**
 * Implements hook_preprocess_block().
 */
function themename_preprocess_block(array &$variables) {
  // Get the base plugin id
  $base_plugin_id = $variables['base_plugin_id'];
  print '<pre>' . $base_plugin_id . '</pre>';
}
```

### How to discover the name of a "block content type"

All blocks created from block content types show the same base plugin id
`block_content`. Thus, a the block's content type is it's machine name. Go to
`/admin/structure/block/block-content/types` and **Edit** one of the block types
shown. To the right of the label will be a "Machine name". For example the
"Basic block" type created by the Standard profile has a machine name `basic`.
