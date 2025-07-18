<?php

declare(strict_types=1);

namespace Drupal\migration_source_module_test\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * A test source plugin without a source_module.
 *
 * @MigrateSource(
 *   id = "no_source_module",
 * )
 */
class NoSourceModule extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    throw new \BadMethodCallException('This method should never be called');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    throw new \BadMethodCallException('This method should never be called');
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    throw new \BadMethodCallException('This method should never be called');
  }

}
