<?php
/**
 * @file
 * @brief       The plugin TelegramNotifier definition
 * @ingroup     TelegramNotifier
 *
 * @defgroup    TelegramNotifier Plugin cinecturlink2.
 *
 * Allow session on frontend.
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

$this->registerModule(
    'Telegram Notifier',
    'Receive blog updates on Telegram.',
    'Jean-Christian Paul Denis and Contributors',
    '0.1',
    [
        'requires'    => [['core', '2.33']],
        'settings'    => ['self'],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/src/branch/master/README.md',
        'repository'  => 'https://github.com/JcDenis/' . $this->id . '/raw/branch/master/dcstore.xml',
        'date'        => '2025-03-01T09:45:42+00:00',
    ]
);
