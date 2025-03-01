<?php

declare(strict_types=1);

namespace Dotclear\Plugin\TelegramNotifier;

use Dotclear\App;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief       TelegramNotifier backend class.
 * @ingroup     TelegramNotifier
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class BackendBehaviors
{
    /**
     * Save user preferences, color syntax activation and its theme.
     */
    public static function adminBeforeUserUpdate(): void
    {
        try {
            // Get instances
            $user    = TelegramUser::newFromUser((string) App::auth()->userID());
            $actions = new Telegram();

            // Save user configuration
            $user->setForm();

            // Save user actions
            foreach($actions->getActions() as $action) {
                $action->setForm($user);
            }

        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }
    }

    /**
     * Display user preferences, color syntax activation and theme selection.
     */
    public static function adminPreferencesForm(): void
    {
        $user    = TelegramUser::newFromUser((string) App::auth()->userID());
        $actions = new Telegram();

        $odd   = 1;
        $items = [0 => [], 1 => []];
        foreach($actions->getActions() as $action) {
            $odd           = $odd ? 0 : 1;
            $items[$odd][] = $action->getForm($user);
        }

        echo (new Fieldset())
            ->id(My::id() . 'prefs')
            ->legend(new Legend(My::name()))
            ->fields([
                (new Div())
                    ->items([
                        (new Text('h5', __('Configuration:'))),
                        ... $user->getForm(),
                    ]),
                (new Div())
                    ->items([
                        (new Text('h5', __('Send telegram on:'))),
                        (new Div())
                            ->class('two-cols')
                            ->items([
                                (new Div())
                                    ->class('col')
                                    ->items($items[0]),
                                (new Div())
                                    ->class('col')
                                    ->items($items[1]),
                            ]),
                    ]),
            ])
            ->render();
    }
}
