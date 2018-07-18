<?php

namespace Drupal\sharemessage\Entity\Handler;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Render controller for nodes.
 */
class ShareMessageViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = $this->viewMultiple([$entity], $view_mode, $langcode);
    return reset($build);
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    if (empty($entities)) {
      return [];
    }

    $build = [];
    foreach ($entities as $entity) {
      /* @var \Drupal\sharemessage\ShareMessageInterface $entity */

      // EntityViewController expects the entity to be in #sharemessage.
      $cacheability_metadata = CacheableMetadata::createFromObject($entity);
      $build[$entity->id()] = [
        '#sharemessage' => $entity,
        '#contextual_links' => [
          'sharemessage' => [
            'route_parameters' => [
              'sharemessage' => $entity->id(),
            ],
          ],
        ],
      ];
      $cacheability_metadata->addCacheableDependency(\Drupal::config('sharemessage.settings'));
      $cacheability_metadata->applyTo($build[$entity->id()]);

      $context = $entity->getContext($view_mode);

      $is_overridden = \Drupal::request()->query->get('smid') && \Drupal::config('sharemessage.settings')->get('message_enforcement');

      // Add OG Tags to the page if there are none added yet and a corresponding
      // view mode was set (or it was altered into such a view mode in getContent()).
      $og_view_modes = ['full', 'only_og_tags', 'no_attributes'];
      if (!$is_overridden && in_array($context['view_mode'], $og_view_modes)) {
        $build[$entity->id()]['#attached']['html_head'] = $this->mapHeadElements($entity->buildOGTags($context));
      }

      // Add twitter card meta tags if setting is active.
      if (\Drupal::config('sharemessage.settings')->get('add_twitter_card')) {
        $twitter_tags = $entity->buildTwitterCardTags($context);
        $build[$entity->id()]['#attached']['html_head'] = array_merge($build[$entity->id()]['#attached']['html_head'], $twitter_tags);
      }

      if ($entity->hasPlugin() && $view_mode != 'only_og_tags') {
        $attributes_view_modes = ['full', 'attributes_only'];
        $plugin_attributes = in_array($context['view_mode'], $attributes_view_modes);
        $build[$entity->id()][$entity->getPluginID()] = $entity->getPlugin()->build($context, $plugin_attributes);
      }
    }
    return $build;
  }

  /**
   * Modifies a buildOGTags structure to work with drupal_add_html_head.
   *
   * @param array $elements
   *   An array containing the Open Graph meta tags:
   *     - title
   *     - image: If at least one image Url is given.
   *     - video, video:width, video:height, video:type:
   *       If at least one video Url is given.
   *     - url
   *     - description
   *     - type
   *
   * @return array $mapped
   *   The new structured buildOGTags array to work with drupal_add_html_head.
   */
  protected function mapHeadElements(array $elements) {
    $mapped = [];
    foreach ($elements as $element) {
      $mapped[] = [
        $element,
        str_replace(':', '_', $element['#attributes']['property']),
      ];
    }
    return $mapped;
  }

}
