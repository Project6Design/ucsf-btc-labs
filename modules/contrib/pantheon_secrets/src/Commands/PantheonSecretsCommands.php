<?php

namespace Drupal\pantheon_secrets\Commands;

use Drupal\pantheon_secrets\SecretsSyncer\SecretsSyncerInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush command file.
 */
class PantheonSecretsCommands extends DrushCommands {

  /**
   * The secrets syncer.
   *
   * @var \Drupal\pantheon_secrets\SecretsSyncer\SecretsSyncerInterface
   */
  protected SecretsSyncerInterface $secretsSyncer;

  /**
   * PantheonSecretsCommands constructor.
   */
  public function __construct(SecretsSyncerInterface $secrets_syncer) {
    $this->secretsSyncer = $secrets_syncer;
  }

  /**
   * Sync Pantheon secrets with key entities.
   *
   * @command pantheon-secrets:sync
   */
  public function sync() {
    try {
      $added = $this->secretsSyncer->sync();
    }
    catch (\Exception $e) {
      $this->logger()->error(dt('An error ocurred adding secrets: @error', ['@error' => $e->getMessage()]));
      return self::EXIT_FAILURE;
    }
    if (empty($added)) {
      $this->logger()->notice(dt('No new secrets to sync.'));
      return self::EXIT_SUCCESS;
    }
    $this->logger()->notice(dt('Synced secrets: @secrets', ['@secrets' => implode(', ', $added)]));
    return self::EXIT_SUCCESS;
  }

}
