# Embed

## Branches

- **8.x-1.x**: the stable branch. Bug fixes and backwards-compatible changes only, including Drupal core compatibility (8.x-1.12 adds Drupal 12 and requires Drupal 11.3).
- **2.0.x**: backwards-incompatible changes, such as removing CKEditor 4 support and the APIs deprecated "for removal from embed:2.0.0", or dropping a Drupal core major version that 8.x-1.x still supports. Don't backport these to 8.x-1.x.

## Known dependents

Changes to public APIs (`EmbedTypeBase`, the `EmbedType` plugin type, `DomHelperTrait`, `EmbedCKEditor5PluginBase`) can break these projects: entity_embed, url_embed, paragraphs_entity_embed, views_entity_embed, element_embed, webform_entity_embed, cl_block, entity_embed_extras, entity_embed_permissions, ephoto_dam, md_viewer.
