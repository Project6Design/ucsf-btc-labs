---
hide:
  - toc
---

# Related modules

* [Dripyard Recipe Builder](https://www.drupal.org/project/dripyard_recipe_builder) is an interactive terminal UI (TUI) for bundling Drupal configuration into Recipes. For use cases where Recipes are sufficient -- i.e., there is no need to update the configuration periodically -- this is **a good replacement for Features**.
* [Configuration Development](https://www.drupal.org/project/config_devel) provides automated import and export of configuration between the active [configuration storage](https://www.drupal.org/node/2120571) and exported modules.
* [Configuration Update](https://www.drupal.org/project/config_update) provides a report that allows you to see the differences between the configuration items provided by the current versions of your installed modules, themes, and install profile, and the configuration on your site. From this report, you can also import new configuration provided by updates, and revert your site configuration to the provided values. **The necessity of this module is lessened by the more recent ability to import partial sets of configuration with [drush config:import](https://www.drush.org/13.x/commands/config_import/).**
* [Configuration Synchronizer](https://www.drupal.org/project/config_sync) provides methods for safely importing site configuration from updated modules, themes, or distributions. By taking a snapshot of configuration as installed and comparing the snapshot to the new module or theme as well as the current active configuration, Configuration Synchronizer safely merges in updates without overwriting customizations.
* [Single Content Sync](https://www.drupal.org/project/single_content_sync) facilitates exports any content (node, taxonomy, media, users, blocks, paragraphs, and etc.) and importing them to a different environment or to a different website, even if it has a totally different structure. The module generates a YAML code snippet for the content item that can just be copied from one site and pasted into another one.
