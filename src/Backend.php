<?php

declare(strict_types=1);

namespace Dotclear\Plugin\TelegramNotifier;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief       TelegramNotifier backend class.
 * @ingroup     TelegramNotifier
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem();

        App::behavior()->addBehaviors([
            'adminBeforeUserOptionsUpdate' => BackendBehaviors::adminBeforeUserUpdate(...),
            'adminPreferencesFormV2'       => BackendBehaviors::adminPreferencesForm(...),
        ]);

        return true;
    }
}
