<?php
defined('TYPO3_MODE') or die();
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_groups', [
    'tx_rest3_settings' => [
        'exclude' => 1,
        'label' => 'REST3 Permission',
        'config' => [
            'type' => 'text'
        ]
    ]
]);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'fe_groups',
    '--div--;Rest3,tx_rest3_settings'
);
