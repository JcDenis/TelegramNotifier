<?php
/**
 * @file
 * @brief       The plugin TelegramNotifier definition
 * @ingroup     TelegramNotifier
 *
 * @defgroup    TelegramNotifier Plugin cinecturlink2.
 *
 * Receive blog updates on Telegram.
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

$this->registerModule(
    'Telegram Notifier',
    'Receive blog updates on Telegram.',
    'Jean-Christian Paul Denis and Contributors',
    '0.5',
    [
        'requires'    => [['core', '2.36']],
        'settings'    => ['pref' => '#user-options.' . $this->id . '_prefs'],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-09-11T20:04:48+00:00',
    ]
);
