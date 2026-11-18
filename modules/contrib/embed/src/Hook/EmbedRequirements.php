<?php

declare(strict_types=1);

namespace Drupal\embed\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Requirements for the Embed module.
 */
class EmbedRequirements {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ModuleHandlerInterface $moduleHandler,
    #[Autowire(service: 'file.mime_type.guesser')]
    protected readonly MimeTypeGuesserInterface $mimeTypeGuesser,
  ) {}

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $requirements = [];

    // Check for any icons that are not SVG files when CKEditor5 is installed.
    if ($this->moduleHandler->moduleExists('ckeditor5')) {
      $button_icons_not_svg = [];
      /** @var \Drupal\embed\Entity\EmbedButton[] $buttons */
      $buttons = $this->entityTypeManager->getStorage('embed_button')->loadMultiple();
      foreach ($buttons as $button) {
        $icon = $button->getIconUrl();
        if ($this->mimeTypeGuesser->guessMimeType($icon) !== 'image/svg+xml') {
          $label = $button->label() . ': ' . $icon;
          $button_icons_not_svg[] = $button->toLink($label, 'edit-form');
        }
      }
      if (!empty($button_icons_not_svg)) {
        $requirements['embed_buttons_ckeditor5'] = [
          'title' => $this->t('Embed button CKEditor5 compatibility'),
          'value' => $this->t('The following embed button(s) are not using SVG icons and will not be compatible with CKEditor5'),
          'description' => [
            '#theme' => 'item_list',
            '#items' => $button_icons_not_svg,
          ],
          'severity' => RequirementSeverity::Error,
        ];
      }
    }

    return $requirements;
  }

}
