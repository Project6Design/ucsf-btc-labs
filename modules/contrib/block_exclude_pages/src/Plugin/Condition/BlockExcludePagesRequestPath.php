<?php

namespace Drupal\block_exclude_pages\Plugin\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\system\Plugin\Condition\RequestPath;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {@inheritdoc}
 */
class BlockExcludePagesRequestPath extends RequestPath {

  use StringTranslationTrait;

  /**
   * The logger.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->logger = $container->get('logger.channel.block_exclude_pages');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['pages']['#description'] .= '<p>'
      . $this->t("<b>To exclude specific pages</b>, prefix the path with a '!'. Example excluded path <em class='placeholder'>!/user/jc</em>")
      . '<br>' . $this->t('The order of the lines matters!')
      . '<br>' . $this->t("For More detail instructions to exclude pages visit: <a href='https://www.drupal.org/project/block_exclude_pages'>https://www.drupal.org/project/block_exclude_pages</a>")
      . '</p>';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $paths = array_map('trim', explode("\n", $form_state->getValue('pages')));
    foreach ($paths as $path) {
      // Added extra condition to if statement to allow for '!/' to be used.
      if (empty($path) || $path === '<front>' || $path === '!<front>' || str_starts_with($path, '/') || str_starts_with($path, '!/')) {
        continue;
      }
      $form_state->setErrorByName('pages', $this->t("The path %path requires a leading forward slash or exclamation point when used with the Pages setting.", ['%path' => $path]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // <-- testing/debugging - only.
    $debug_output = [];
    $debug = FALSE;

    // Copied from parent::evaluate().
    // Convert path to lowercase. This allows comparison of the same path
    // with different case. Ex: /Page, /page, /PAGE.
    $pages = mb_strtolower($this->configuration['pages'] ?? '');
    if (!$pages) {
      return TRUE;
    }

    $request = $this->requestStack->getCurrentRequest();
    // Compare the lowercase path alias (if any) and internal path.
    $path = $this->currentPath->getPath($request);
    // Do not trim a trailing slash if that is the complete path.
    $path = $path === '/' ? $path : rtrim($path, '/');
    $path_alias = mb_strtolower($this->aliasManager->getAliasByPath($path) ?? '');

    $pages = explode(PHP_EOL, $pages);
    $pattern = '#^!#';

    $path_match = FALSE;
    $is_exclude = FALSE;
    foreach ($pages as $p) {
      $path_matcher = $this->pathMatcher;

      // - check if exclude conditions is set in the pattern.
      if (!preg_match($pattern, $p)) {
        // <-- Debug only
        if ($debug) {
          $debug_output[] = "$p - SKIPPED";
        }
        elseif ($path_matcher->matchPath($path_alias, $p)
          || $path_matcher->matchPath($path, $p)
        ) {
          $path_match = TRUE;
          $is_exclude = FALSE;
        }
        continue;
      }

      // - clean exclude pattern for pattern matching now:
      $exclude_path = trim(preg_replace($pattern, "", $p));

      // - check patterns to exclude:
      if ($debug) {
        $debug_output = block_exclude_pages_debug_trace($path_matcher, $exclude_path, $path_alias, $path);
      }
      elseif ($path_matcher->matchPath($path_alias, $exclude_path)
        || $path_matcher->matchPath($path, $exclude_path)
      ) {
        $path_match = TRUE;
        $is_exclude = TRUE;
      }
    }

    // -- replaced by block access as of 2.2.x .
    // $block->setVisibilityConfig('request_path', $config);
    // - output testing/debug info.
    if ($debug) {
      $debug_log = [
        'type' => 'debug_log',
        'block_id' => $this->pluginId,
        'block_data' => $this->configuration,
        'debug_output' => $debug_output,
      ];
      $this->logger->info(print_r($debug_log, 1));
    }

    return ($path_match xor $is_exclude);
  }

}
