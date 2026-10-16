# Removing Features from your site

In some cases you might want to remove Features from a site previously built using them. Examples of when this might be the case:

- You previously used Features to manage configuration for the site but now you use some other solution such as Drupal core's configuration staging system.
- You built your site using a distribution but now want to take it its own direction.


How to remove Features depends partly on how your Features modules are written.

- If they're doing nothing more than packaging configuration, you can probably safely uninstall them and then remove them from the codebase. Any configuration they've already provided should be unaffected--that is, should remain on the site.
- Alternately, if the Feature modules provide any non-configuration such as custom code, removing them might cause breakage.

Of course, test anything on a development instance of the site and ensure everything's working as expected before deploying to a live environment.
