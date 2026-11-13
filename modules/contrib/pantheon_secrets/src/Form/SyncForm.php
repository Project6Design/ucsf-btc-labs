<?php

namespace Drupal\pantheon_secrets\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pantheon_secrets\SecretsSyncer\SecretsSyncerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements an example form.
 */
class SyncForm extends FormBase {

  /**
   * The secrets syncer.
   *
   * @var \Drupal\pantheon_secrets\SecretsSyncer\SecretsSyncerInterface
   */
  protected SecretsSyncerInterface $secretsSyncer;

  /**
   * SyncForm constructor.
   *
   * @param \Drupal\pantheon_secrets\SecretsSyncer\SecretsSyncerInterface $secrets_syncer
   *   The secrets syncer service.
   */
  public function __construct(SecretsSyncerInterface $secrets_syncer) {
    $this->secretsSyncer = $secrets_syncer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('pantheon_secrets.secrets_syncer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pantheon_secrets.sync_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Hit the "Sync Keys" button to sync your secrets to Key entities.'),
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sync Keys'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $added = $this->secretsSyncer->sync();
    if (empty($added)) {
      $this->messenger()->addStatus($this->t('No new secrets to sync.'));
      return;
    }
    $this->messenger()->addStatus($this->t('Synced secrets: @secrets', ['@secrets' => implode(', ', $added)]));
  }

}
