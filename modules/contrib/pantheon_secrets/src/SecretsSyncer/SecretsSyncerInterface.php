<?php

namespace Drupal\pantheon_secrets\SecretsSyncer;

/**
 * Interface for the service that syncs secrets.
 */
interface SecretsSyncerInterface {

  /**
   * Sync secrets from Pantheon to key entities.
   *
   * @return array
   *   An array of saved key entity ids.
   */
  public function sync(): array;

}
