<?php

namespace Drupal\pantheon_secrets\SecretsSyncer;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use PantheonSystems\CustomerSecrets\CustomerSecrets;
use PantheonSystems\CustomerSecrets\CustomerSecretsClientInterface;

/**
 * Sync secrets from Pantheon to key entities.
 */
class SecretsSyncer implements SecretsSyncerInterface {

  /**
   * The customer secrets client.
   *
   * @var \PantheonSystems\CustomerSecrets\CustomerSecretsClientInterface
   */
  protected CustomerSecretsClientInterface $secretsClient;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The transliteration manager.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected TransliterationInterface $transliteration;

  /**
   * Constructs a new SecretsSyncer object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, TransliterationInterface $transliteration) {
    $this->entityTypeManager = $entityTypeManager;
    $this->transliteration = $transliteration;
    $this->secretsClient = CustomerSecrets::create()->getClient();
  }

  /**
   * Get machine name for a given key.
   */
  protected function getMachineName(string $secretName): string {
    $transliterated = $this->transliteration->transliterate(strtolower($secretName));
    $transliterated = preg_replace('@[^a-z0-9_.]+@', '_', $transliterated);
    return $transliterated;
  }

  /**
   * {@inheritdoc}
   */
  public function sync(): array {
    $secrets = $this->secretsClient->getSecrets();
    $query = $this->entityTypeManager->getStorage('key')->getQuery();
    $key_ids = $query
      ->accessCheck(FALSE)
      ->condition('key_provider', 'pantheon')
      ->execute();
    $keys = $this->entityTypeManager->getStorage('key')->loadMultiple($key_ids);
    $saved = [];
    foreach ($secrets as $secret) {
      if (!$this->secretInUse($secret->getName(), $keys)) {
        // Create and save a new key item only if the secret is not in use.
        $key = $this->entityTypeManager->getStorage('key')->create([
          'id' => $this->getMachineName($secret->getName()),
          'label' => $secret->getName(),
          'key_provider' => 'pantheon',
          'key_type' => 'authentication',
          'key_provider_settings' => [
            'secret_name' => $secret->getName(),
          ],
        ]);
        $key->save();
        $saved[] = $key->id();
      }
    }
    return $saved;
  }

  /**
   * Determine whether the given secret is in use on any of the given keys.
   */
  protected function secretInUse(string $secretName, array $keys): bool {
    foreach ($keys as $key) {
      $keyPlugin = $key->getPlugin('key_provider');
      if ($keyPlugin->getConfiguration()['secret_name'] === $secretName) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
