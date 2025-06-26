<?php

namespace Drupal\photoswipe_dynamic_caption\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class PhotoswipeDynamicCaptionSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'photoswipe_dynamic_caption_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('photoswipe_dynamic_caption.settings');

    $form['type'] = [
      '#type' => 'radios',
      '#options' => [
        'auto' => $this->t('Auto - Try to automatically determine the best position (depending on available space) <i>(Default)</i>'),
        'below' => $this->t('Below - The caption will always be place below the image'),
        'aside' => $this->t('Aside - The caption will always be placed to the right side of the image'),
      ],
      '#title' => $this->t('Caption Position'),
      '#description' => $this->t('The position type of the caption.'),
      '#default_value' => $config->get('options.type'),
      '#required' => TRUE,
    ];

    $form['mobileLayoutBreakpoint'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#title' => $this->t('Mobile Layout Breakpoint'),
      '#default_value' => $config->get('options.mobileLayoutBreakpoint'),
      '#description' => $this->t('Maximum window width at which the mobile layout should be used.'),
      '#required' => TRUE,
    ];

    $form['horizontalEdgeThreshold'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#title' => $this->t('Horizontal Edge Threshold'),
      '#default_value' => $config->get('options.horizontalEdgeThreshold'),
      '#description' => $this->t('When the horizontal position value of the caption is below this threshold, the class <code>pswp__dynamic-caption--on-hor-edge"</code> gets applied on the caption element<br>(this class can be used to add different styling to the caption, such as horizontal padding).'),
      '#required' => TRUE,
    ];

    $form['mobileCaptionOverlapRatio'] = [
      '#type' => 'number',
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.01,
      '#title' => $this->t('Mobile Caption Overlap Ratio'),
      '#default_value' => $config->get('options.mobileCaptionOverlapRatio'),
      '#description' => $this->t('Defines the amount of horizontal empty space before the mobile caption switches to the "overlap" layout <i>(e.g. 0.3 = 30% of screen, 1 = full screen, etc.)</i>.'),
      '#required' => TRUE,
    ];

    $form['verticallyCenterImage'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Vertically Center Image'),
      '#default_value' => $config->get('options.verticallyCenterImage'),
      '#description' => $this->t('If enabled, the image will always be vertically centered in the remaining space between caption and the rest of viewport.<br> If disabled, the image will only be raised, if the caption does not fit below the picture.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('photoswipe_dynamic_caption.settings')
      ->set('options.type', $form_state->getValue('type'))
      ->set('options.mobileLayoutBreakpoint', $form_state->getValue('mobileLayoutBreakpoint'))
      ->set('options.horizontalEdgeThreshold', $form_state->getValue('horizontalEdgeThreshold'))
      ->set('options.mobileCaptionOverlapRatio', $form_state->getValue('mobileCaptionOverlapRatio'))
      ->set('options.verticallyCenterImage', $form_state->getValue('verticallyCenterImage'))
      ->save();
    parent::submitForm($form, $form_state);

    // @todo This can probably be more fine graded in the future:
    Cache::invalidateTags(['rendered']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['photoswipe_dynamic_caption.settings'];
  }

}
