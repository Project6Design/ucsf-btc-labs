<?php

namespace Drupal\photoswipe\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * {@inheritdoc}
 */
class PhotoswipeAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'photoswipe_admin_settings';
  }

    /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['photoswipe.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('photoswipe.settings')
    ->set('photoswipe_always_load_non_admin', $form_state->getValue('photoswipe_always_load_non_admin'))
    ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['photoswipe_always_load_non_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load PhotoSwipe on all non-admin pages'),
      '#default_value' => $this->configFactory->get('photoswipe.settings')->get('photoswipe_always_load_non_admin'),
      '#description' => $this->t('Useful if you want to use photoswipe elsewhere by just adding the <code>.photoswipe</code> CSS class.'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
