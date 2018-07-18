<?php

namespace Drupal\sharemessage\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sharemessage\Plugin\sharemessage\SocialSharePrivacy;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures Social Share Privacy settings.
 */
class SocialSharePrivacySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sharemessage.socialshareprivacy',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sharemessage_social_share_privacy_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $form = parent::buildForm($form, $form_state);

    $social_share_privacy_config = $this->config('sharemessage.socialshareprivacy');

    $form['services'] = [
      '#title' => $this->t('Default visible services'),
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => SocialSharePrivacy::allServices(),
      '#default_value' => $social_share_privacy_config->get('services'),
      '#size' => 11,
    ];
    $form['facebook_action'] = [
      '#title' => $this->t('Choose facebook action'),
      '#type' => 'radios',
      '#default_value' => $social_share_privacy_config->get('facebook_action') ?: 'like',
      '#options' => ['like' => $this->t('Like'), 'recommend' => $this->t('Recommend')],
      // @todo Uncomment after https://www.drupal.org/node/1149078 lands.
      /*'#states' => [
        'visible' => [
          'select[name="services[]"]' => ['value' => ['facebook']],
        ],
      ],*/
    ];
    $form['disqus_shortname'] = [
      '#title' => $this->t('Disqus shortname'),
      '#type' => 'textfield',
      '#description' => $this->t('You can get shortname from <a href=":url">Disqus</a>.', [
        ':url' => 'https://disqus.com/',
      ]),
      '#default_value' => $social_share_privacy_config->get('disqus_shortname'),
      // @todo Uncomment after https://www.drupal.org/node/1149078 lands.
      /*'#states' => [
        'visible' => [
          'select[name="services[]"]' => ['value' => ['disqus']],
        ],
      ],*/
    ];
    $form['flattr_uid'] = [
      '#title' => $this->t('Flattr user id'),
      '#type' => 'textfield',
      '#description' => $this->t('You can get user id from <a href=":url">Flattr</a>.', [
        ':url' => 'https://flattr.com/',
      ]),
      '#default_value' => $social_share_privacy_config->get('flattr_uid'),
      // @todo Uncomment after https://www.drupal.org/node/1149078 lands.
      /*'#states' => [
        'visible' => [
          'select[name="services[]"]' => ['value' => ['flattr']],
        ],
      ],*/
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('sharemessage.socialshareprivacy')
      ->set('services', $form_state->getValue('services'))
      ->set('facebook_action', $form_state->getValue('facebook_action'))
      ->set('disqus_shortname', $form_state->getValue('disqus_shortname'))
      ->set('flattr_uid', $form_state->getValue('flattr_uid'))
      ->save();
  }

}
