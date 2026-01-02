<?php

declare(strict_types=1);

namespace Dotclear\Plugin\TelegramNotifier;

use Dotclear\App;
use Dotclear\Database\Cursor;

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
                name: __('New comment (not from post author)'),
                description: __('Send message on new comment (if not from the post author)'),
                permissions: App::auth()->makePermissions([
                    App::auth()::PERMISSION_CONTENT_ADMIN,
                    App::auth()::PERMISSION_USAGE,
                    App::auth()::PERMISSION_PUBLISH,
                    App::auth()::PERMISSION_DELETE,
                ])
            ),
            // ...
        ]);
    }

    /**
     * Message on comment creation.
     */
    public static function publicAfterCommentCreateHelper(Cursor $cur, ?int $comment_id, bool $not_me = false): void
    {
        // skip unwanted message
        if (!App::blog()->isDefined() || (int) $cur->getField('comment_status') === App::status()->comment()::JUNK) {
            return;
        }

        // Information on comment author and post author

        $current_preview                    = App::frontend()->context()->preview ?? false;
        App::frontend()->context()->preview = true; //bad hack to get all comments

        $rs = App::auth()->sudo(App::blog()->getComments(...), ['comment_id' => $comment_id]);

        App::frontend()->context()->preview = $current_preview;

        if (is_null($rs) || $rs->isEmpty()) {
            return;
        }
        if ($not_me && $rs->isMe()) {
            return;
        }

        $message = sprintf('*%s*', __('New comment')) . "\n" .
        "-- \n" .
        sprintf(__('*Blog:* %s'), App::blog()->name()) . "\n" .
        sprintf(__('*Entry:* [%s](%s)'), $rs->f('post_title'), $rs->getPostURL()) . "\n" .
        sprintf(__('*Comment by:* %s <%s>'), $rs->f('comment_author'), $rs->f('comment_email')) . "\n" ;

        self::sendMessage(My::id() . 'CommentCreate', $message);
    }

    /**
     * Message on comment creation.
     */
    public static function publicAfterCommentCreate(Cursor $cur, ?int $comment_id): void
    {
        self::publicAfterCommentCreateHelper($cur, $comment_id, false);
    }

    /**
     * Message on comment creation.
     */
    public static function publicAfterCommentCreateNotMe(Cursor $cur, ?int $comment_id): void
    {
        self::publicAfterCommentCreateHelper($cur, $comment_id, true);
    }

    /**
     * Commons.
     */
    private static function sendMessage(string $action, string $message): void
    {
        $telegram = new Telegram();
        $telegram
            ->setAction($action)
            ->setContent($message)
            ->setFormat('markdown')
            ->send();
    }
}
