<?php

namespace Drupal\url_embed\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\embed\DomHelperTrait;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\url_embed\UrlEmbedHelperTrait;
use Drupal\url_embed\UrlEmbedInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to display embedded URLs based on data attributes.
 *
 * @Filter(
 *   id = "url_embed",
 *   title = @Translation("Display embedded URLs"),
 *   description = @Translation("Embeds URLs using data attribute: data-embed-url."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
#[Filter(
  id: "url_embed",
  title: new TranslatableMarkup("Display embedded URLs"),
  description: new TranslatableMarkup("Embeds URLs using data attribute: data-embed-url."),
  type: FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
)]
class UrlEmbedFilter extends FilterBase implements ContainerFactoryPluginInterface {
  use DomHelperTrait;
  use UrlEmbedHelperTrait;

  /**
   * The Renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a UrlEmbedFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\url_embed\UrlEmbedInterface $url_embed
   *   The URL embed service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UrlEmbedInterface $url_embed, Renderer $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setUrlEmbed($url_embed);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('url_embed'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    if (strpos($text, 'data-embed-url') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      $config = $this->getConfiguration();

      foreach ($xpath->query('//drupal-url[@data-embed-url]') as $node) {
        /** @var \DOMElement $node */
        $url = $node->getAttribute('data-embed-url');
        $url_output = '';
        $ratio = !empty($this->settings['default_ratio']) ? $this->settings['default_ratio'] : '66.7';
        try {
          if (($info = $this->urlEmbed()->getEmbed($url)) && !empty($info->code->html)) {
            $url_output = $info->code->html;
            if (!empty($info->code->ratio)) {
              $ratio = $info->code->ratio;
            }
          }
        }
        catch (\Exception $e) {
          \Drupal::logger('url_embed')->error($e->getMessage());
        }
        if (!empty($this->settings['enable_responsive'])) {
          // Wrap the embed code in a container to make it responsive.
          $responsive_embed = [
            '#theme' => 'responsive_embed',
            '#ratio' => $ratio,
            '#url_output' => $url_output,
          ];
          $url_output = $this->renderer->render($responsive_embed);
          $result
            ->setAttachments([
              'library' => [
                'url_embed/responsive_styles',
              ],
            ]);
        }
        $this->replaceNodeContent($node, $url_output);
      }

      $result->setProcessedText(Html::serialize($dom));
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('
        <p>You can embed URLs. Additional properties can be added to the URL tag like data-caption and data-align if supported. Examples:</p>
        <ul>
          <li><code>&lt;drupal-url data-embed-url="https://www.youtube.com/watch?v=xxXXxxXxxxX" data-url-provider="YouTube" /&gt;</code></li>
        </ul>');
    }
    else {
      return $this->t('You can embed URLs.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['enable_responsive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Embeds fill container (responsive behavior)'),
      '#description' => $this->t('Automatically scale the embed to fill the size of the container.'),
      '#default_value' => $this->settings['enable_responsive'] ?? TRUE,
    ];
    $form['default_ratio'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default ratio (as a percent)'),
      '#description' => $this->t('Embeds normally derive their aspect ratio from information from the provider. In cases where this information is not present, provide a fallback. Example: 56.25'),
      '#default_value' => $this->settings['default_ratio'] ?? '66.7',
    ];
    return $form;
  }

}
