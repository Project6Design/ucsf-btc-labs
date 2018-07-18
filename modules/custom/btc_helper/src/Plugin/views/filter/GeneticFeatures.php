<?php

namespace Drupal\btc_helper\Plugin\views\filter;


use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Core\Database\Query\Condition;
use Drupal\views\ViewExecutable;

/**
 * Clinical Trial Genetic Features filter.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("genetic_features")
 */
class GeneticFeatures extends InOperator {

    /**
    * {@inheritdoc}
    */
    public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
        parent::init($view, $display, $options);
        $this->valueTitle = t('Allowed genetic features');
        $this->definition['options callback'] = array($this, 'generateOptions');
    }

    /**
    * Implement the logic for Must and Must Not Have.
    */
    public function query() {
        $off_values = array_filter($this->value, function($val) {
            return $val == 0;
        });

        $on_values = array_filter($this->value);

        if (!in_array('none', $on_values)) {

            // Must have logic
            if (!empty($off_values)) {
                $this->ensureMyTable();

                $this->ensureMyTable();

                $configuration = [
                  'type'       => 'LEFT',
                  'table'      => 'node__field_gf_must_have',
                  'field'      => 'entity_id',
                  'left_table' => 'node_field_data',
                  'left_field' => 'nid',
                  'operator'   => '=',
                ];

                $join = \Drupal\views\Views::pluginManager('join')
                    ->createInstance('standard', $configuration);
                $rel = $this->query->addRelationship('gf_must_have', $join, 'node_field_data');
                $this->query->addTable('node__field_gf_must_have', $rel, $join, 'gf_must_have');

                $where = new Condition('OR');
                $where->condition("gf_must_have.field_gf_must_have_target_id", array_keys($off_values), 'NOT IN');
                $where->condition("gf_must_have.field_gf_must_have_target_id", NULL, 'IS NULL');

                $this->query->addWhere($this->options['group'], $where);
            }

            // Must not have logic.
            if (!empty($on_values)) {
                $this->ensureMyTable();

                $configuration = [
                  'type'       => 'LEFT',
                  'table'      => 'node__field_gf_must_not_have',
                  'field'      => 'entity_id',
                  'left_table' => 'node_field_data',
                  'left_field' => 'nid',
                  'operator'   => '=',
                ];

                $join = \Drupal\views\Views::pluginManager('join')
                    ->createInstance('standard', $configuration);
                $rel = $this->query->addRelationship('gf_must_not_have', $join, 'node_field_data');
                $this->query->addTable('node__field_gf_must_not_have', $rel, $join, 'gf_must_not_have');

                $where = new Condition('OR');
                $where->condition("gf_must_not_have.field_gf_must_not_have_target_id", array_keys($on_values), 'NOT IN');
                $where->condition("gf_must_not_have.field_gf_must_not_have_target_id", NULL, 'IS NULL');

                $this->query->addWhere($this->options['group'], $where);
            }
        }
    }

    /**
    * Skip validation if no options have been chosen so we can use it as a
    * non-filter.
    */
    public function validate() {
        if (!empty($this->value)) {
            parent::validate();
        }
    }

    /**
    * Helper function that generates the options.
    * @return array
    */
    public function generateOptions() {
        $options = ['none' => t('Not tested yet/Don\'t know')];
        $gf_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('genetic_features');

        foreach($gf_terms as $gft) {
            $options[$gft->tid] = $gft->name;
        }

        return $options;
    }

}
