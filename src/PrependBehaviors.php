<?php

declare(strict_types=1);

namespace Dotclear\Plugin\TelegramNotifier;

use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;

/**
 * @brief       TelegramNotifier default actions.
 * @ingroup     TelegramNotifier
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class PrependBehaviors
{
    /**
     * Add default actions.
     */
    public static function AddActions(Telegram $telegram): void
    {
        $telegram->addActions([
            // On comment creation
            new TelegramAction(
                id: My::id() . 'CommentCreate',
                type: 'message',
                name: __('New comment'),
                description: __('Send message on new comment'),
                permissions: App::auth()->makePermissions([
                    App::auth()::PERMISSION_CONTENT_ADMIN,
                    App::auth()::PERMISSION_USAGE,
                    App::auth()::PERMISSION_PUBLISH,
                    App::auth()::PERMISSION_DELETE,
                ])
            ),
            // On comment creation (if not me = post author)
            new TelegramAction(
                id: My::id() . 'CommentCreateNotMe',
                type: 'message',
                name: __('New comment (not from me)'),
                description: __('Send message on new comment (if not from me)'),
                permissions: App::auth()->makePermissions([
                    App::auth()::PERMISSION_CONTENT_ADMIN,
                    App::auth()::PERMISSION_USAGE,
                    App::auth()::PERMISSION_PUBLISH,
                    App::auth()::PERMISSION_DELETE,
                ]),
                condition: fn (TelegramUser $user, string $origin): bool => !in_array($origin, $user->getEmails())
            ),
        ]);
    }

    /**
     * Message on comment creation.
     */
    public static function publicAfterCommentCreate(Cursor $cur, ?int $comment_id): void
    {
        // skip unwanted message
        if (!App::blog()->isDefined() || (is_numeric($status = $cur->getField('comment_status')) ? (int) $status : 0) === App::status()->comment()::JUNK) {
            return;
        }

        // Information on comment author and post author

        $current_preview                    = App::frontend()->context()->preview ?? false;
        App::frontend()->context()->preview = true; //bad hack to get all comments

        $rs = App::auth()->sudo(App::blog()->getComments(...), ['comment_id' => $comment_id]);

        App::frontend()->context()->preview = $current_preview;

        if (!$rs instanceof MetaRecord || $rs->isEmpty()) {
            return;
        }

        $url    = $rs->__call('getPostURL', []);
        $origin = $rs->strField('comment_email');

        // Visitor email is not sent to Telegram, only used to know if comment is from user
        $message = sprintf('<b>%s</b>', Html::escapeHTML(__('New comment'))) . "\n" .
        "-- \n" .
        sprintf('<b>%s</b> %s', Html::escapeHTML(__('Blog:')), Html::escapeHTML(App::blog()->name())) . "\n" .
        sprintf('<b>%s</b> <a href="%s">%s</a>', Html::escapeHTML(__('Entry:')), Html::escapeHTML(is_string($url) ? $url : ''), Html::escapeHTML($rs->strField('post_title'))) . "\n" .
        sprintf('<b>%s</b> %s', Html::escapeHTML(__('Comment by:')), Html::escapeHTML($rs->strField('comment_author'))) . "\n";

        // Get recipients now (database is closed after the page), send messages after the visitor page is served
        $telegrams = [];
        foreach ([My::id() . 'CommentCreate', My::id() . 'CommentCreateNotMe'] as $action) {
            $telegram = (new Telegram())
                ->setAction($action)
                ->setOrigin($origin)
                ->setContent($message)
                ->setFormat('HTML');
            $users = $telegram->getRecipients();
            if ($users !== []) {
                $telegrams[] = [$telegram, $users];
            }
        }

        if ($telegrams === []) {
            return;
        }

        register_shutdown_function(function () use ($telegrams): void {
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }

            foreach ($telegrams as [$telegram, $users]) {
                $telegram->send($users, false);
            }
        });
    }
}
