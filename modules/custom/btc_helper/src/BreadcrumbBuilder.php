<?php
/**
 * @file
 * Contains \Drupal\btc_helper\BreadcrumbBuilder.
 */
namespace Drupal\btc_helper;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\system\PathBasedBreadcrumbBuilder;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
/**
 * Adds the current page title to the breadcrumb.
 *
 * Extend PathBased Breadcrumbs to include the current page title as an unlinked
 * crumb. The module uses the path if the title is unavailable and it excludes
 * all admin paths.
 *
 * {@inheritdoc}
 */
class BreadcrumbBuilder extends PathBasedBreadcrumbBuilder {
    /**
     * {@inheritdoc}9p#g8Ka*7UbT!hac
     */
    public function build(RouteMatchInterface $route_match) {
        $breadcrumbs = parent::build($route_match);
        $request = \Drupal::request();
        $path = trim($this->context->getPathInfo(), '/');
        $path_elements = explode('/', $path);
        $route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT);

        // Do not adjust the breadcrumbs on admin paths.
        if ($route && !$route->getOption('_admin_route')) {
            $title = $this->titleResolver->getTitle($request, $route);
            if (!isset($title)) {
                // Fallback to using the raw path component as the title if the
                // route is missing a _title or _title_callback attribute.
                $title = str_replace(array('-', '_'), ' ', Unicode::ucfirst(end($path_elements)));
            }

            if (($node = $request->attributes->get('node'))) {
                switch ($node->getType()) {
                    case 'treatment':
                        $breadcrumbs = new Breadcrumb();
                        $links = [Link::createFromRoute('Treatments', 'entity.node.canonical', ['node' => 13])];

                        $type_id = $node->field_treatment_type->target_id;
                        if ($type = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($type_id)) {
                            $related_page = $type->field_related_page->getValue();
                            $url = Url::fromUri($type->field_related_page->uri);

                            $links[] = Link::fromTextAndUrl($type->getName(), $url);
                        }

                        $breadcrumbs->setLinks($links);
                        $breadcrumbs->addLink(Link::createFromRoute($title, '<none>'));
                        break;

                    case 'condition':
                        $breadcrumbs = new Breadcrumb();
                        $links = [Link::createFromRoute('Conditions', 'entity.node.canonical', ['node' => 12])];

                        $breadcrumbs->setLinks($links);
                        $breadcrumbs->addLink(Link::createFromRoute($title, '<none>'));
                        break;

                    case 'research':
                        $breadcrumbs = new Breadcrumb();
                        $links = [Link::createFromRoute('Research', 'entity.node.canonical', ['node' => 30])];

                        $breadcrumbs->setLinks($links);
                        $breadcrumbs->addLink(Link::createFromRoute($title, '<none>'));
                        break;

                    case 'sf_page':
                        $breadcrumbs = new Breadcrumb();
                        break;
                }
            }
        }
        return $breadcrumbs;
    }
}
