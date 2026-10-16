---
hide:
  - toc
---

# Suggested workflow for Features

This page introduces the workflow for generating and editing sets of features. For more explanation, including explanations of terms and concepts, follow the links on each step.

A typical workflow for generating features includes:

## Step 1: Install Drupal and configure a site as you wish, creating content types, adding fields, configuring views, and so on.

When building your site, keep in mind the [interoperability principles](https://drupal.org/node/2404943) built into Features. Specifically, it helps to plan whether given items like fields and vocabularies are specific to a given area of functionality and name them accordingly.

For example, a date field specific to events and attached to an `event` content type could be named `field_event_date`, while a vocabulary of event types could be named `event_type`, with a corresponding entity reference field of `field_event_type` on the `event` content type.

By following this naming convention, you ensure that by default your event-related field storages and taxonomies are assigned to the `event` feature. For the easiest workflow, avoid doing configuration on the site that you will not be packaging into a set of features.

## Step 2: [Create a bundle](/features/build--create)

If you're creating a set of features for a distribution, you might name your bundle for the distribution. Decide at this time if you wish to create an install profile for your set of features. Creating an install profile is usually recommended, as it makes it easier to install your features on a new site.

If desired, [configure the assignment plugins](/features/build--packaging) that determine how configuration is automatically packaged. Note that the default configuration may work fine in many cases.

Returning to **Administration > Configuration > Development > Features** (`/admin/config/development/features`), select some or all of the features. Note that some features may be dependent on others, so the easiest approach is usually to select all features in the bundle.

## Step 3: Generate some Features

[Generate the features](/features/build--create) by clicking one of the buttons, "Download archive" or "Write". If you select a single Feature to download and click "Download archive", the resulting file will be named for the feature. Otherwise, it will be named for the bundle.

If you have generated an install profile, before being able to continue developing your features, reinstall a fresh site, using the install profile you generated, and enable Features. If you have customized the bundle's assignment plugin settings, manually create a Feature on the original site and export the features bundle to it, so that when you enable the Feature on your newly installed site you will have the correct bundle settings.

## Step 4: Enable Feature modules you generated

Features modules are regular Drupal modules that contain packaged configuration for installation on multiple sites. Features modules are installed and enabled just like any other module.

## Step 5: [Edit individual features](/features/usage--update)

As needed, add or remove specific configuration items. In some cases a Feature may have been automatically assigned configuration that isn't appropriate. In that case, you will want to edit the Feature and remove those items.
*   Analyze the configuration items that show up as "Unassigned". You may wish to [create new features](/features/build--create) as needed to capture those items.
*   Continue to develop your site, regenerating features as needed.

See also the page on [building a distribution with Features](/features/build--distribution).
