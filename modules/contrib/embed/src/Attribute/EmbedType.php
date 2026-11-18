<?php

declare(strict_types=1);

namespace Drupal\embed\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an embed type attribute object.
 *
 * @ingroup embed_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class EmbedType extends Plugin {

  /**
   * Constructs an EmbedType attribute.
   *
   * @param string $id
   *   The embed type ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable name of the embed type.
   * @param string|null $embed_form_class
   *   (optional) The name of the embed form class associated with this
   *   embed type.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly ?string $embed_form_class = NULL,
    public readonly ?string $deriver = NULL,
  ) {}

}
