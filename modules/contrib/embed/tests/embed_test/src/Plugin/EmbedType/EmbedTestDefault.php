<?php

namespace Drupal\embed_test\Plugin\EmbedType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\embed\Attribute\EmbedType;
use Drupal\embed\EmbedType\EmbedTypeBase;

/**
 * Default test embed type.
 */
#[EmbedType(
  id: 'embed_test_default',
  label: new TranslatableMarkup('Default'),
)]
class EmbedTestDefault extends EmbedTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultIconUrl() {
    return $this->getModulePath('embed_test') . '/default.png';
  }

}
