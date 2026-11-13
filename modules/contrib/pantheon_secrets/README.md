# Pantheon Secrets

Pantheon Secrets integration with the [Key](https://drupal.org/project/key) module.

[![Pantheon Secrets](https://github.com/pantheon-systems/pantheon_secrets/actions/workflows/ci.yml/badge.svg?branch=1.x)](https://github.com/pantheon-systems/pantheon_secrets/actions/workflows/ci.yml)

[![Limited Availability](https://img.shields.io/badge/Pantheon-Limited_Availability-yellow?logo=pantheon&color=FFDC28)](https://pantheon.io/docs/oss-support-levels#limited-availability)

## Requirements

This module is for you if you meet the following requirements:

* Using Drupal >= 10

* Use PHP >= 8.2

* Part of the Secrets EA Program

* Hosting the Drupal site on Pantheon's platform

* Have Dashboard access to the platform (necessary to deploy code changes)

* Comfortable using [terminus](https://pantheon.io/docs/terminus)

* Using the [Secrets Manager Plugin](https://github.com/pantheon-systems/terminus-secrets-manager-plugin) to set your secrets.


## What it provides

This module provides [Drupal](https://drupal.org) integration with the Secrets EA Program in the form of a Key Provider plugin for the [Key](https://drupal.org/project/key) module.

## Install

To require this module in your composer file:

```
composer require drupal/pantheon_secrets:^1 --prefer-dist
```

Install the module and push an updated `composer.lock` file to your Pantheon environment.

## Usage

1) Use terminus to set some secrets like this:

    ```
    terminus secret:set <site> --scope=web --type=runtime <secret_name> <secret_value>
    ```
    Please note that you should be using scope "web" for secrets to be available to the Drupal application.

1) Now that the secret is available, you could add the corresponding Key entity in one of the different available ways:
 
    1) Add a new key through the Key module UI. Select Pantheon Secret as the key provider and your secret name from the dropdown
    1) Go to /admin/config/system/keys/pantheon and click on the "Sync Keys" button to get all of the available secrets into Key entities.
    1) Use the provided drush command to sync all of your secrets into Key entities:
        ```
        terminus drush <site>.<env> -- pantheon-secrets:sync
        ```
1) Use the Key where it is needed.

See [our detailed example](docs/example.md) for an end to end example on how to set things up.

## Feedback and collaboration

For real time discussion of the module find Pantheon developers in our [Community Slack](https://docs.pantheon.io/pantheon-community). Bug reports and feature requests should be posted in the drupal.org issue queue. For code changes, please submit pull requests against the GitHub repository rather than posting patches to drupal.org.
