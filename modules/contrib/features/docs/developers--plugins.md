---
hide:
  - toc
---

# Extending Features with plugins

Features allows developers to customize the configuration packaging process and generation output. This is accomplished by extending or adding Drupal-style plugins. Features plugins have access to the public methods declared in `FeaturesManagerInterface`.

### Assignment plugins

Assignment plugins control the configuration packaging process. They can do tasks including:

*   Create a package. See `FeaturesManagerInterface::initPackage()`.
*   Assign configuration to a package or the install profile. See `FeaturesManagerInterface::assignConfigPackage()`
*   Unset configuration to prevent it being packaged.

Assignment plugins may have a configuration form.

Steps to write an assignment plugin include:

*   Write the plugin file. See the [plugins that ship with the module](https://git.drupalcode.org/project/features) for models to work from, such as the [`FeaturesAssignmentBaseType`](https://git.drupalcode.org/project/features) plugin. The plugin must include an `assignPackages()` method.
*   If the plugin requires configuration:
    *   Write a configuration form. See [`AssignmentFormBase`](https://git.drupalcode.org/project/features) for an example.
    *   Add a routing for the form to a [.routing.yml](https://git.drupalcode.org/project/features) file. Example:

```yaml
features.assignment_base:
  path: '/admin/config/development/configuration/features/bundle/_base/{bundle_name}'
  defaults:
    _form: '\Drupal\features_ui\Form\AssignmentBaseForm'
    _title: 'Configure base package assignment'
    bundle_name: NULL
  requirements:
    _permission: 'administer site configuration'
```

*   Edit the plugin's annotation to specify the configuration route. Example: `config_route_name = "features.assignment_base"`.
*   If you're contributing the plugin as a patch:
    *   Regenerate the [default bundle configuration](https://git.drupalcode.org/project/features) to enable and/or set the default weight of the plugin as well as adding any configuration for the plugin.
    *   Note that while a configuration form and related code like the routing go into Features UI, the assignment plugin itself and its configuration go into Features.

### Generation plugins

The second type of plugin is for generating the packages along with their assigned configuration and an optional install profile.

Two generation plugins ship with Features, one to generate an archive and the other to write packages to the file system.

In the unlikely case that an additional generation plugin is needed, the steps for writing one are similar to those for writing one for an assignment plugin except that there are more required methods. See the [existing plugins](https://git.drupalcode.org/project/features) for examples.
