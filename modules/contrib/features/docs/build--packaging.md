---
hide:
  - toc
---

# Configure packaging settings

Out of the box, Features should come preconfigured to automatically produce well designed Features. However, it is also highly configurable to enable you to customize the way your site configuration is packaged.

Features includes the following plugins to assign configuration to features:

*   **Base type**: Designated types of configuration are used as the base for features. For example, if content types are selected as a base type, a package will be generated for each content type, including all configuration dependent on that content type.
*   **Core type** : Designated types of configuration are assigned to a core feature. For example, if image styles are selected as a core type, a core Feature will be generated and image styles will be assigned to it.
*   **Dependency**: Add to features configuration dependent on items already in that feature.
*   **Exclude**: Configuration items are excluded from packaging by various methods including by configuration type.
*   **Namespace**: Add to features configuration with a machine name containing that feature's machine name.

## Enabling, disabling, and reordering assignment plugins

To configure the assignment plugins, navigate to **Administration > Configuration > Development > Features > Configure bundles** (`admin/config/development/features/bundle`) and select the [bundle](/features/build--bundles/) you want to configure plugins for.

The simplest way to customize packaging is by selectively enabling, disabling, or reordering the plugins.

The plugin order is important because once a given piece of configuration has been assigned to a Feature it will not be reassigned to a different feature. Also, a Feature must exist for configuration to be assigned to it. So, for example, the Base Type plugin - which is configured by default to create a Feature for each content type - is weighted so as to run before the Namespace plugin. This way, if an `article` Feature is created by the Base Type plugin, the Namespace plugin will come along afterwards and assign an `article_type` vocabulary to the `article` feature.

By turning plugins on and off and/or reordering them, you can significantly alter the packaging--for better or for worse! Particular care is warranted when reordering plugins.

Some reasons you might want to disable plugins:

*   **I want to write my own install profile from scratch and not be weighed down by code and configuration copied over from the Standard profile.** Easy: disable the Profile plugin.

## Configuring individual plugins

Some plugins offer a form where you can tweak the plugin's behaviour. The most common configuration option is to select types of configuration acted on by the plugin.

The Exclude plugin can be configured to select which types of configuration should be entirely excluded from packaging. For example, if you don't want any shortcut sets to be packaged, configure the Exclude plugin and check "Shortcut set" under "Types".
