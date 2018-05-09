<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

if (isset($_SERVER['REQUEST_URI'])) {
    if (\RozbehSharahi\Rest3\BootstrapDispatcher::isRestRoute()) {
        $_GET['eID'] = 'rest3';
    }
}

$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['rest3'] = \RozbehSharahi\Rest3\BootstrapDispatcher::class . '::dispatch';