<?php

return [
    'ctrl' => [
        'title' => 'Rexample Event',
        'label' => 'title',
        'hideAtCopy' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'editlock' => 'editlock',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
    ],
    'types' => array(
        '0' => array('showitem' => 'title,seminar, locations, topics, sys_language_uid, l10n_parent, l10n_diffsource')
    ),
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'languages',
                'items' => [
                    [
                        'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages',
                        -1,
                        'flags-multiple'
                    ],
                ],
                'default' => 0,
            ]
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_rexample_domain_model_event',
                'foreign_table_where' => 'AND tx_rexample_domain_model_event.pid=###CURRENT_PID### AND tx_rexample_domain_model_event.sys_language_uid IN (-1,0)',
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => true,
                    ],
                ],
                'default' => 0,
            ]
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => ''
            ]
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => 0
            ]
        ],
        'cruser_id' => [
            'label' => 'cruser_id',
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input'
            ]
        ],
        'seminar' => [
            'exclude' => true,
            'label' => 'Seminar',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_rexample_domain_model_seminar',
                'foreign_table_where' => 'AND tx_rexample_domain_model_seminar.pid=###CURRENT_PID### AND tx_rexample_domain_model_seminar.sys_language_uid IN (-1,0)',
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => true,
                    ],
                ],
                'default' => 0,
            ]
        ],
        'locations' => array(
            'exclude' => 1,
            'label' => 'Events',
            'config' => array(
                'type' => 'select',
                'multiple' => 1,
                'foreign_table' => 'tx_rexample_domain_model_location',
                'MM' => 'tx_rexample_location_event_mm',
                'foreign_table_where' => ' AND tx_rexample_domain_model_location.pid=###CURRENT_PID### ORDER BY tx_rexample_domain_model_location.title ',
                'MM_opposite_field' => 'events',
                'minitems' => 0,
                'maxitems' => 99,
            ),
        ),
        'topics' => array(
            'exclude' => 1,
            'label' => 'Topics',
            'config' => array(
                'type' => 'inline',
                'multiple' => 1,
                'foreign_table' => 'tx_rexample_domain_model_topic',
                'foreign_field' => 'event',
                'maxitems' => 100,
                'appearance' => [
                    'collapseAll' => 1,
                    'expandSingle' => 1,
                ],
            ),
        ),
    ]
];