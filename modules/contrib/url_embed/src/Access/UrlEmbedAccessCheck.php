<?php

namespace Drupal\url_embed\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\editor\EditorInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Routing requirement access check for URL Embed
 */
class UrlEmbedAccessCheck implements AccessInterface {

  /**
   * Checks whether the URL Embed button is enabled for the given text editor.
   *
   * Returns allowed if the editor toolbar contains the embed button or neutral
   * otherwise.
   *
   * Compare to Drupal\embed\Access\EmbedButtonEditorAccessCheck
   *
   * @code
   * pattern: '/url-embedcke5/dialog/{editor}'
   * requirements:
   *   _url_embed_editor_access: 'TRUE'
   * @endcode
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {

    $parameters = $route_match->getParameters();

    $access_result = AccessResult::allowedIf($parameters->has('editor'))
      // Vary by 'route' because the access depends on the 'editor' parameter.
      ->addCacheContexts(['route']);

    if ($access_result->isAllowed()) {
      $editor = $parameters->get('editor');
      if ($editor instanceof EditorInterface) {
        return $access_result
          // Besides having the 'editor' route parameter, it's also necessary to
          // be allowed to use the text format associated with the text editor.
          ->andIf($editor->getFilterFormat()->access('use', $account, TRUE))
          // And on top of that, 'urlembed' needs to be present in the
          // text editor's configured toolbar.
          ->andIf($this->checkEditorAccess($editor));
      }
    }

    // No opinion, so other access checks should decide if access should be
    // allowed or not.
    return $access_result;
  }

  /**
   * Checks if the urlembed button is enabled in an editor configuration.
   *
   * @param \Drupal\editor\EditorInterface $editor
   *   The editor entity to check.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   When the received Text Editor entity does not use CKEditor. This is
   *   currently only capable of detecting buttons used by CKEditor.
   */
  protected function checkEditorAccess(EditorInterface $editor) {
    if (!in_array($editor->getEditor(), ['ckeditor5'])) {
      throw new HttpException(500, 'Currently, only CKEditor5 is supported.');
    }

    $has_button = FALSE;
    $settings = $editor->getSettings();
    if ($editor->getEditor() === 'ckeditor') {
      foreach ($settings['toolbar']['rows'] as $row) {
        foreach ($row as $group) {
          if (in_array('urlembed', $group['items'])) {
            $has_button = TRUE;
            break 2;
          }
        }
      }
    }
    elseif ($editor->getEditor() === 'ckeditor5') {
      // The schema for CKEditor5 has changed, therefore we need to check for
      // the toolbar items differently.
      if ($settings['toolbar']['items'] && in_array('urlembed', $settings['toolbar']['items'])) {
        $has_button = TRUE;
      }
    }

    return AccessResult::allowedIf($has_button)
      ->addCacheableDependency($editor);
  }
}
