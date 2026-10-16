---
hide:
  - toc
---

# Interoperability principles in Features

The default settings of Features should facilitate creating and maintaining configuration modules and distributions that integrate well with each other--that are _interoperable_.

## About interoperability in Drupal features

Can you mix and match features from one Featureset or distribution with those of another? For example, can you take an event Feature produced for one distribution and use it on a site that was built using a different distribution?

Up to now, the answer typically has been "no". Features from different distributions or sets of features usually are incompatible due to differences in how they are architected.

Features for Drupal 8 includes plugins that are configured by default to provide best practices for features building that will, at least, open new possibilities for interoperability.

## Specific ways that interoperability is supported

The following table lists specific ways that Features addresses interoperability.

| Interoperability principle | Implementation |
| --- | --- |
| **Namespace prefix** | Generated code uses a namespace that combines the Featureset (or distribution) namespace and the short form namespace of a given feature. For example, if the distribution's machine name is `oceania` and a feature's short machine name is `event`, the feature's code namespace will be `oceania_event`. |
| **Configuration item namespace** | As noted above, components named with the Feature prefix will be added to the feature. More specific namespaces will be matched before general ones. For example, if the a distribution Feature  `oceania` exists, and a separate `event_registration` Feature  exists, a view called `event_registration_listing` would be assigned to the more specific `event_registration` package. |
| **Excluded configuration** | Certain configuration items and types are considered restricted from use by a feature. These are items that are often more general than the use cases commonly targeted by individual packages. The Exclude package assignment plugin includes a curated list of configuration that is excluded from packaging. |
