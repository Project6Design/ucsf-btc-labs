<?php

/**
 * Implements hook_views_data_alter().
 */
function btc_helper_views_data_alter(array &$data) {
    $data['node']['prior_treatments'] = [
        'title' => t('Prior Treatments'),
        'filter' => [
            'title' => t('Clincal Trial: Prior Treatments'),
            'group' => 'Custom',
            'help' => t('Implements custom logic for prior treatments.'),
            'id' => 'prior_treatments',
        ],
    ];

    $data['node']['genetic_features'] = [
        'title' => t('Genetic Features'),
        'filter' => [
            'title' => t('Clincal Trial: Genetic Features'),
            'group' => 'Custom',
            'help' => t('Implements custom logic for genetic features.'),
            'id' => 'genetic_features',
        ],
    ];
}
