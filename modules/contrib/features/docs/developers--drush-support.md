---
hide:
  - toc
---

# Drush support

Features supports the following Drush commands:

## features:list:packages
Display a list of all generate-able existing features and packages and their status compared to active configuration.

```drush features:list:packages```

## features:components
Interactive prompt to list by component type.

```drush features:components```

## features:diff
Show the difference between the active config and the default config stored in a Feature package.

```drush features:diff <feature-machine-name>```


## features:export
Export the configuration on your site into a custom module.

```drush features:export <feature-machine-name>```

## features:import
Import a module config into your site.

```drush features:import <feature-machine-name>```

```drush features:import:all```
