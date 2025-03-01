<?php

declare(strict_types=1);

namespace Dotclear\Plugin\TelegramNotifier;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Core\Backend\{
    Notices,
    Page
};
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Form,
    Hidden,
    Input,
    Label,
    Note,
    Number,
    Para,
    Submit,
    Text
};
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief       TelegramNotifier backend class.
 * @ingroup     TelegramNotifier
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Manage extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }


        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        Page::openModule(My::name());

            echo
            Page::breadcrumb([
                __('Plugins')   => '',
                My::name()      => '',
            ]) .
            Notices::getNotices() .

            (new Div())->items([

            ])->render();

        Page::closeModule();
    }
}
