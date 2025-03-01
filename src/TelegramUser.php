<?php

declare(strict_types=1);

namespace Dotclear\Plugin\TelegramNotifier;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Html;
use Dotclear\Interface\Core\UserWorkspaceInterface;
use Exception;

/**
 * @brief       TelegramNotifier user handler.
 * @ingroup     TelegramNotifier
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class TelegramUser
{
    /**
     * User selected actions.
     *
     * @var     array<int, string>  $actions
     */
    private array $actions = [];

    /**
     * Create a new user instance.
     */
	public function __construct(
		public readonly string $user = '',
		public readonly int $chat = 0,
		public readonly string $token = ''
	) {
        if ($this->isConfigured()) {
            // Get user selected actions
            $sql = new SelectStatement();
            $sql
                ->columns([
                    'pref_id',
                    'pref_value',
                ])
                ->from(App::con()->prefix() . UserWorkspaceInterface::WS_TABLE_NAME)
                ->and('pref_ws = ' . $sql->quote(My::id()))
                ->where('user_id = ' . $sql->quote($this->user));

            $rs = $sql->select();

            if ($rs instanceof MetaRecord) {
                while ($rs->fetch()) {
                    if (!in_array($rs->f('pref_id'), Telegram::CONFIGURATION_KEYS)) {
                        if ((bool) $rs->f('pref_value')) {
                            $this->actions[] = trim((string) $rs->f('pref_id'));
                        }
                    }
                }
            }
        }
	}

    /**
     * Check if configuration is not empty.
     */
	public function isConfigured(): bool
	{
		return !empty($this->user) && !empty($this->chat) && !empty($this->token);
	}

    /**
     * Get current user configuration form.
     *
     * @return  array<int, Para>
     */
    public function getForm(): array
    {
        if (App::auth()->userID() == $this->user) {
            return [
                (new Para())
                    ->class('field')
                    ->items([
                        (new Input(My::id() . 'chat'))
                            ->size(60)
                            ->maxlength(255)
                            ->value(Html::escapeHTML((string) $this->chat))
                            ->label(new Label(__('Telegram chat ID:'), Label::INSIDE_TEXT_BEFORE))
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Input(My::id() . 'token'))
                            ->size(60)
                            ->maxlength(255)
                            ->value(Html::escapeHTML((string) $this->token))
                            ->label(new Label(__('Telegram bot token:'), Label::INSIDE_TEXT_BEFORE))
                    ]),
                ];
        }

        return [];
    }

    /**
     * Save current user configuration form.
     */
    public function setForm(): void
    {
        if (App::auth()->userID() == $this->user) {
            $chat = (int) $_POST[My::id() . 'chat'] ?: 0;
            $token = (string) $_POST[My::id() . 'token'] ?: '';

            if ($chat != $this->chat || $token != $this->token) {
                // Test config
                try {
                    $user = new self($this->user, $chat, $token);
                    $telegram = new Telegram();
                    if ($telegram->query($user, 'getChat', ['chat_id' => $chat]) !== true) {pdump('e');
                        throw new Exception(__('Bad Telegram configuration'));
                    }

                    // Save config
                    My::prefs()->put('chat', $chat, UserWorkspaceInterface::WS_INT);
                    My::prefs()->put('token', $token, UserWorkspaceInterface::WS_STRING);
                } catch (Exception $e) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Check if user has selected an action.
     */
    public function hasAction(string $action): bool
    {
        return in_array($action, $this->actions);
    }

    /**
     * Get a user Telegram API configuration.
     */
    public static function newFromUser(string $user_id): self
    {
        $res = [];
        $sql = new SelectStatement();
        $sql
            ->columns([
                'pref_id',
                'pref_value',
                'pref_type',
            ])
            ->from(App::con()->prefix() . UserWorkspaceInterface::WS_TABLE_NAME)
            ->and('pref_ws = ' . $sql->quote(My::id()))
            ->where('user_id = ' . $sql->quote($user_id));

        $rs = $sql->select();

        if ($rs instanceof MetaRecord) {
            while ($rs->fetch()) {
                if (in_array($rs->f('pref_id'), Telegram::CONFIGURATION_KEYS)) {
                    $name  = trim((string) $rs->f('pref_id'));
                    $value = $rs->f('pref_value');
                    $type  = $rs->f('pref_type');

                    if ($type === UserWorkspaceInterface::WS_ARRAY) {
                        $value = @json_decode((string) $value, true);
                    } elseif ($type === UserWorkspaceInterface::WS_FLOAT || $type === UserWorkspaceInterface::WS_DOUBLE) {
                        $type = UserWorkspaceInterface::WS_FLOAT;
                    } elseif ($type !== UserWorkspaceInterface::WS_BOOL && $type !== UserWorkspaceInterface::WS_INT) {
                        $type = UserWorkspaceInterface::WS_STRING;
                    }

                    settype($value, $type);
                    $res[$name] = $value;
                }
            }
        }

        return new self($user_id, $res['chat'] ?? 0, $res['token'] ?? '');
    }
}