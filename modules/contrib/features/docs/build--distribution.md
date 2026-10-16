---
hide:
  - toc
---

# Building a distribution with Features

With Drupal Recipes now the mainstream tool for sharing prepackaged functionality, a use case that remains for Features is to assist with building and maintaining well designed and interoperable Drupal distributions.

## Build the site with namespaced packaging in mind

Before beginning to build out a new area of functionality, select a machine name to use. For example, for an event-related feature, you might use the machine name `event`.

When adding new configuration, decide if it's specific to the area of functionality. If it is, give it a machine name that's prefixed with your Feature machine name. For example, a vocabulary used for types of events is specific to the `event` Feature and could be named `event_type`. A date field specific to the event content type might be called `event_date`. However, a tags vocabulary used with events but also potentially with other content types shouldn't get the prefix and could be named simply `tags`.

Following this naming pattern will ensure that the Namespace package assignment plugin can correctly assign your configuration to an appropriate feature.

## Adding an install profile

If you select the option on the package generation screen to add an install profile, or use the equivalent Drush command, your generated code will include a basic install profile adapted from the Standard profile that ships with Drupal core. If you've downloaded an archive, you can extract it into the `profiles` directory to test installing your new distribution. If you used the Drush command, it will automatically be written to the `profiles` directory.

If you edit the generated code (either the profile or a contained module), your changes will be retained when you next generate the packages.
