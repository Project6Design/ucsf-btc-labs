---
hide:
  - toc
---

# Configuration management strategies

The configuration management system in Drupal focuses on the use case of *staging configuration from one instance to another of a given site*--for example, from a development instance to a testing instance or from testing to production.

Features focuses on the complementary use case of *sharing configuration among multiple sites*.

Features can be used in place of a staging workflow. However, features can also be combined with staging on a given site. This page outlines different strategies for using features and staging configuration.

## No configuration management

The simplest approach to Drupal 8 site building involves custom site building on a single site instance. In this case, site building is done directly on the production site and there is no need either for core's configuration staging or for Features.

## Staging only

In cases where staging is used but all configuration is customized for the specific site, sites will use core's configuration staging but have no need of the Features module or of modules produced with Features.

## Features only

A third scenario is a site that is based on a set of features or a distribution with little or no customization. In this scenario, there is little need for maintaining multiple versions of the site and hence for the core configuration staging system.

## Features plus core's configuration staging

Finally, in some cases, a site based on shared configuration from a set of features or a distribution will also itself have significant customization. If the organization has the needed capacity, combining features and core's configuration staging system may be a good option here.

## Use features for development and config for deployment

For a possible workflow and less merge conflicts one possible workflow is using features for every development, and then later in QA for example, exporting the configuration into config and then on production just deal with config.
