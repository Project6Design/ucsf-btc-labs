---
hide:
  - toc
---

# Deploying configuration from other modules

As a general guideline, only export to Features _custom configuration you have created_, such as a view mode or a field.

In other words, if you're preparing to deploy staged configuration provided by other modules, don't use Features to ship that configuration.

A hint that you're working with configuration from another module is if name of the configuration includes another module's name, such as `responsive_tables_filter.settings.yml`. Another hint is if the configuration does not show up by default as available for adding to your Feature (that is, you have to check the option to allow conflicts before it will show up).

There are 3 options for deploying such configuration:

## Option 1: Add it to the installation profile

The installation profile is allowed to override configuration provided by other modules. This option is appropriate if:

*   The configuration should always be active on all sites installed with the install profile; and
*   The configuration is not required by other configuration or functionality. This consideration is because the install profile is typically installed last and nothing should require the install profile.

## Option 2: Add the configuration as an override

Drupal's [Configuration Override System](https://www.drupal.org/docs/drupal-apis/configuration-api/configuration-override-system) provides multiple ways to hardcode configuration settings:

- For simple configuration, you can use [global overrides](https://www.drupal.org/docs/drupal-apis/configuration-api/configuration-override-system#s-global-overrides)
- For more complicated configuration, you can provide [overrides from a module](https://www.drupal.org/docs/drupal-apis/configuration-api/configuration-override-system#s-providing-overrides-from-modules)
- An alternative to writing PHP code is the [Config Rewrite](https://www.drupal.org/project/config_rewrite) module.

## Option 3: Use a "deployment" module

A good convention is to have a dedicated "deployment" module whose purpose is simply to install configuration and provide periodic configuration changes during the lifetime of a site. For example, for your fleet of "Oceania" sites (where all those sites should have consistent configuration of certain elements), you could create an `oceania_deploy` module that consists solely of a `oceania.install` file. Upon enabling this module on sites, [hook_install()](https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Extension%21module.api.php/function/hook_install/11.x) can write various configuration, and subsequent configuration changes can be deployed using [hook_update()](https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Extension%21module.api.php/function/hook_update_N/11.x) implementations can programmatically save configuration to a site.

Writing the configuration is made easy through Drupal's [Configuration API](https://www.drupal.org/docs/drupal-apis/configuration-api/simple-configuration-api#config-writing).
