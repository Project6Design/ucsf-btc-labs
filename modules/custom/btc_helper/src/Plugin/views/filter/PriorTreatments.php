<?php

namespace Drupal\btc_helper\Plugin\views\filter;


use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Core\Database\Query\Condition;
use Drupal\views\ViewExecutable;

/**
 * Clinical Trial Prior Treatments filter.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("prior_treatments")
 */
class PriorTreatments extends InOperator {

    /**
    * {@inheritdoc}
    */
    public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
        parent::init($view, $display, $options);
        // $this->valueTitle = t('Allowed prior treatments');
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

        // Check, if we should ignore this filter
        if (!in_array('none', $on_values)) {
            // Must have logic
            if (!empty($off_values)) {
                $this->ensureMyTable();

                $configuration = [
                  'type'       => 'LEFT',
                  'table'      => 'node__field_prior_treatments_must_have',
                  'field'      => 'entity_id',
                  'left_table' => 'node_field_data',
                  'left_field' => 'nid',
                  'operator'   => '=',
                ];

                $join = \Drupal\views\Views::pluginManager('join')
                    ->createInstance('standard', $configuration);
                $rel = $this->query->addRelationship('pt_must_have', $join, 'node_field_data');
                $this->query->addTable('node__field_prior_treatments_must_have', $rel, $join, 'pt_must_have');

                $where = new Condition('OR');
                $where->condition("pt_must_have.field_prior_treatments_must_have_target_id", array_keys($off_values), 'NOT IN');
                $where->condition("pt_must_have.field_prior_treatments_must_have_target_id", NULL, 'IS NULL');

                $this->query->addWhere($this->options['group'], $where);
            }

            // Must not have logic.
            if (!empty($on_values)) {
                $this->ensureMyTable();

                $configuration = [
                  'type'       => 'LEFT',
                  'table'      => 'node__field_treatments_must_not_have',
                  'field'      => 'entity_id',
                  'left_table' => 'node_field_data',
                  'left_field' => 'nid',
                  'operator'   => '=',
                ];

                $join = \Drupal\views\Views::pluginManager('join')
                    ->createInstance('standard', $configuration);
                $rel = $this->query->addRelationship('pt_must_not_have', $join, 'node_field_data');
                $this->query->addTable('node__field_treatments_must_not_have', $rel, $join, 'pt_must_not_have');

                $where = new Condition('OR');
                $where->condition("pt_must_not_have.field_treatments_must_not_have_target_id", array_keys($on_values), 'NOT IN');
                $where->condition("pt_must_not_have.field_treatments_must_not_have_target_id", NULL, 'IS NULL');

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
        $options = ['none' => t('Prefer not to answer/Don\'t know')];
        $pt_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('prior_treatments');

        foreach($pt_terms as $ptt) {
            $options[$ptt->tid] = $ptt->name;
        }

        return $options;
    }

}
