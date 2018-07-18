<?php

namespace Drupal\sharemessage\Plugin\sharemessage;

use Drupal\sharemessage\SharePluginBase;
use Drupal\sharemessage\SharePluginInterface;

/**
 * OGHeaders plugin.
 *
 * @SharePlugin(
 *   id = "ogheaders",
 *   label = @Translation("OG Headers"),
 *   description = @Translation("Open graph headers are used when users want to use it as a framework or a background tool only.")
 * )
 */
class OGHeaders extends SharePluginBase implements SharePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build($context, $plugin_attributes) {
    // Og tags are build by default in ShareMessageViewBuilder.
    return;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key) {
  }

}
