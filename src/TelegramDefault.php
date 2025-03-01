<?php

declare(strict_types=1);

namespace Dotclear\Plugin\TelegramNotifier;

use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Interface\Core\BlogInterface;

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
                description: __('Send message on new comment')
            ),
            // ...
        ]);
    }

    /**
     * Message on comment creation.
     */
    public static function publicAfterCommentCreate(Cursor $cur, ?int $comment_id): void
    {
        // skip unwanted message
        if (!App::blog()->isDefined() || (int) $cur->getField('comment_status') == App::status()->comment()::JUNK) {
            return;
        }

        // Information on comment author and post author
        App::frontend()->context()->preview = true; //bad hack to get all comments
        $rs = App::auth()->sudo(App::blog()->getComments(...), ['comment_id' => $comment_id]);
        App::frontend()->context()->preview = false;
        if (is_null($rs) || $rs->isEmpty()) {
            return;
        }

        $message = 
        sprintf('*%s*', __('New comment')) . "\n" .
        "-- \n" .
        sprintf(__('*Blog:* %s'), App::blog()->name()) . "\n" .
        sprintf(__('*Entry:* [%s](%s)'), $rs->f('post_title'), $rs->getPostURL()) . "\n" .
        sprintf(__('*Comment by:* %s <%s>'), $rs->f('comment_author'), $rs->f('comment_email')) . "\n" ;

        self::sendMessage(My::id() . 'CommentCreate', $message);
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