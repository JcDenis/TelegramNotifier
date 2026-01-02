<?php

declare(strict_types=1);

namespace Dotclear\Plugin\TelegramNotifier;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;

/**
 * @brief       TelegramNotifier module prepend.
 * @ingroup     TelegramNotifier
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Prepend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // Add default actions
        App::behavior()->addBehaviors([
            My::id() . 'AddActions'    => PrependBehaviors::AddActions(...),
            'publicAfterCommentCreate' => PrependBehaviors::publicAfterCommentCreate(...),
        ]);
        App::behavior()->addBehaviors([
            'publicAfterCommentCreate' => PrependBehaviors::publicAfterCommentCreateNotMe(...),
        ]);

        return true;
    }
}
