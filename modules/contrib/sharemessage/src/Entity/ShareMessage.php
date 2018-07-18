<?php

namespace Drupal\sharemessage\Entity;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\sharemessage\ShareMessageInterface;

/**
 * Entity class for the Share Message entity.
 *
 * @ConfigEntityType(
 *   id = "sharemessage",
 *   label = @Translation("Share Message"),
 *   handlers = {
 *     "access" = "Drupal\sharemessage\Entity\Handler\ShareMessageAccessControlHandler",
 *     "view_builder" = "Drupal\sharemessage\Entity\Handler\ShareMessageViewBuilder",
 *     "list_builder" = "Drupal\sharemessage\Entity\Handler\ShareMessageListBuilder",
 *     "form" = {
 *       "add" = "Drupal\sharemessage\Form\ShareMessageForm",
 *       "edit" = "Drupal\sharemessage\Form\ShareMessageForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "title",
 *     "message_long",
 *     "message_short",
 *     "image_url",
 *     "fallback_image",
 *     "video_url",
 *     "share_url",
 *     "plugin",
 *     "enforce_usage",
 *     "settings",
 *     "extra_field_entity_type",
 *     "extra_field_bundles",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/services/sharemessage/manage/{sharemessage}",
 *     "delete-form" = "/admin/config/services/sharemessage/manage/{sharemessage}/delete",
 *     "collection" = "/admin/config/services/sharemessage",
 *   }
 * )
 */
class ShareMessage extends ConfigEntityBase implements ShareMessageInterface {

  /**
   * The machine name of this Share Message.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the Share Message.
   *
   * @var string
   */
  public $label;

  /**
   * The flag for enforcing the usage of the Share Message.
   *
   * @var string
   */
  public $enforce_usage;

  /**
   * The settings of the Share Message.
   *
   * @var string
   */
  public $settings;

  /**
   * The title of the Share Message.
   *
   * @var string
   */
  public $title;

  /**
   * The long share text of the Share Message.
   *
   * @var string
   */
  public $message_long;

  /**
   * The short text of the Share Message, used for twitter.
   *
   * @var string
   */
  public $message_short;

  /**
   * The image URL that will be used for sharing.
   *
   * @var string
   */
  public $image_url;

  /**
   * An optional fallback image as file UUID if the image URL does not resolve.
   *
   * @var string
   */
  public $fallback_image;

  /**
   * A video URL to use for sharing.
   *
   * @var string
   */
  public $video_url;

  /**
   * Specific URL that will be shared, defaults to the current page
   *
   * @var string
   */
  public $share_url;

  /**
   * Share plugin ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The entity type to filter its bundles to display on the UI.
   *
   * @var string
   */
  protected $extra_field_entity_type;

  /**
   * The entity types bundles where the Share Message will be displayed.
   *
   * @var string[]
   */
  protected $extra_field_bundles = [];

  /**
   * The runtime token context.
   *
   * @var array
   */
  protected $runtimeContext = [];

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->set('label', $label);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return \Drupal::service('plugin.manager.sharemessage.share')->createInstance($this->plugin, ['sharemessage' => $this]);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginID($plugin_id) {
    $this->plugin = $plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPlugin() {
    if (!empty($this->plugin) && \Drupal::service('plugin.manager.sharemessage.share')->hasDefinition($this->plugin)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    /** @var \Drupal\sharemessage\SharePluginBase $share_plugin */
    $share_plugin = $this->getPlugin();
    return $share_plugin->getPluginDefinition();
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key) {
    if (!empty($this->settings[$key])) {
      return $this->settings[$key];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setRuntimeContext(array $context) {
    $this->runtimeContext = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($view_mode = 'full') {
    $context = ['sharemessage' => $this, 'view_mode' => $view_mode];
    // Add a runtime context to the context list.
    $context += $this->runtimeContext;
    // Attempt to use the current node as context if none has been set
    // explicitly as runtime context.
    $node = \Drupal::request()->attributes->get('node');
    if (!isset($context['node']) && $node instanceof NodeInterface) {
      $context['node'] = $node;
    }

    // Let other modules alter the sharing context that will be used for token
    // as base for replacements.
    \Drupal::moduleHandler()->alter('sharemessage_token_context', $this, $context);

    return $context;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOGTags($context) {
    $tags = [];

    // Base value for og:type meta tag.
    // @todo don't hardcode this, make configurable per Share Message entity.
    $type = 'website';

    // OG: Title.
    $tags[] = [
      '#type' => 'html_tag',
      '#tag' => 'meta',
      '#attributes' => [
        'property' => 'og:title',
        'content' => $this->getTokenizedField($this->title, $context),
      ],
    ];

    // OG: Image, also used for video thumbnail.
    if ($image_url = $this->getImageUrl($context)) {
      $tags[] = [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'property' => 'og:image',
          'content' => $image_url,
        ],
      ];
    }

    // OG: Video.
    if ($video_url = $this->getTokenizedField($this->video_url, $context)) {
      $tags[] = [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'property' => 'og:video',
          'content' => $video_url . '?fs=1',
        ],
      ];
      $tags[] = [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'property' => 'og:video:width',
          'content' => \Drupal::config('sharemessage.addthis')->get('shared_video_width'),
        ],
      ];
      $tags[] = [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'property' => 'og:video:height',
          'content' => \Drupal::config('sharemessage.addthis')->get('shared_video_height'),
        ],
      ];
      $tags[] = [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'property' => 'og:video:type',
          'content' => 'application/x-shockwave-flash',
        ],
      ];
      // Override og:type to video.
      $type = 'video';
    }

    // OG: URL.
    $tags[] = [
      '#type' => 'html_tag',
      '#tag' => 'meta',
      '#attributes' => [
        'property' => 'og:url',
        'content' => $this->getUrl($context),
      ],
    ];

    // OG: Description.
    $tags[] = [
      '#type' => 'html_tag',
      '#tag' => 'meta',
      '#attributes' => [
        'property' => 'og:description',
        'content' => $this->getTokenizedField($this->message_long, $context),
      ],
    ];

    // OG: Type.
    $tags[] = [
      '#type' => 'html_tag',
      '#tag' => 'meta',
      '#attributes' => [
        'property' => 'og:type',
        'content' => $type,
      ],
    ];

    return $tags;
  }

  /**
   * Adds meta tags in order to share images on Twitter.
   *
   * @param array $context
   *   The context for the token replacements.
   *
   * @return array
   *   The twitter tags.
   */
  public function buildTwitterCardTags($context) {
    $twitter = [
      '#type' => 'html_tag',
      '#tag' => 'meta',
      '#attributes' => [
        'property' => 'twitter:card',
        'content' => 'summary_large_image',
      ],
    ];
    $tags[] = [$twitter, 'twitter_card'];

    $twitter = [
      '#type' => 'html_tag',
      '#tag' => 'meta',
      '#attributes' => [
        'property' => 'twitter:site',
        'content' => \Drupal::config('sharemessage.settings')->get('twitter_user'),
      ],
    ];
    $tags[] = [$twitter, 'twitter_site'];

    $twitter = [
      '#type' => 'html_tag',
      '#tag' => 'meta',
      '#attributes' => [
        'property' => 'twitter:description',
        'content' => $this->getTokenizedField($this->message_long, $context),
      ],
    ];
    $tags[] = [$twitter, 'twitter_description'];

    if ($image_url = $this->getImageUrl($context)) {
      $twitter = [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'property' => 'twitter:image',
          'content' => $image_url,
        ],
      ];
      $tags[] = [$twitter, 'twitter_image'];
    }

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenizedField($property_value, $context, $default = '') {
    if ($property_value) {
      return strip_tags(PlainTextOutput::renderFromHtml(\Drupal::token()->replace($property_value, $context, ['clear' => TRUE])));
    }
    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl($context) {
    $options = ['absolute' => TRUE];
    if ($this->enforce_usage) {
      $options['query'] = ['smid' => $this->id];
    }
    $uri = $this->getTokenizedField($this->share_url, $context, Url::fromRoute('<current>')->getInternalPath());
    if (strpos($uri, '://') !== FALSE) {
      return Url::fromUri($uri, $options)->toString();
    }
    // Try to find a matching route.
    elseif ($url = \Drupal::pathValidator()->getUrlIfValid($uri)) {
      return $url->setAbsolute()->toString();
    }
    else {
      return Url::fromUri('internal:/' . $uri, $options)->toString();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getExtraFieldEntityType() {
    return $this->extra_field_entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function setExtraFieldEntityType($extra_field_entity_type) {
    $this->extra_field_entity_type = $extra_field_entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtraFieldBundles() {
    return $this->extra_field_bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function setExtraFieldBundles(array $extra_field_bundles) {
    $this->extra_field_bundles = $extra_field_bundles;
  }

  /**
   * Gets the image url of the ShareMessage.
   *
   * @param array $context
   *   The context for the token replacements.
   *
   * @return bool|string
   *   The found URL or FALSE.
   */
  protected function getImageUrl($context) {
    // Get image url either from dedicated file field or by resolving token.
    $image_url = $this->getTokenizedField($this->image_url, $context);
    // If the returned image URl is empty, try to use the fallback image if
    // one is defined.
    if (!$image_url && !empty($this->fallback_image)) {
      $entity_repository = \Drupal::getContainer()->get('entity.repository');
      /** @var \Drupal\file\FileInterface $image */
      $image = $entity_repository->loadEntityByUuid('file', $this->fallback_image);
      if ($image) {
        $image_url = file_create_url($image->getFileUri());
      }
    }
    return $image_url;
  }
}
