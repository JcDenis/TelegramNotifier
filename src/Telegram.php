<?php

declare(strict_types=1);

namespace Dotclear\Plugin\TelegramNotifier;

use Dotclear\App;
use Dotclear\Helper\Network\HttpClient;
use Exception;

/**
 * @brief       TelegramNotifier message handler.
 * @ingroup     TelegramNotifier
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Telegram
{
    /**
     * Telegram configuration fields.
     */
    public const CONFIGURATION_KEYS = [
        'chat',
        'token',
    ];

    /**
     * Supported telegram message type.
     */
    public const SUPPORTED_TYPES = [
        'message',
    ];

    /**
     * Telegram bot APIURL.
     */
    public const BOT_API_URL = 'https://api.telegram.org/bot%s/%s';

    /**
     * Bot API call timeout in seconds.
     */
    public const TIMEOUT = 5;

    /**
     * Registerded action sstack.
     *
     * @var     array<string, TelegramAction> $actions
     */
    private array $actions = [];

    /**
     * Current action.
     */
    private TelegramAction|false $action;

    /**
     * Current message content.
     */
    private string $content = '';

    /**
     * Current message content format.
     */
    private string $format = '';

    /**
     * Current origin of action.
     */
    private string $origin = '';

    /**
     * Create a new telegram instance.
     */
    public function __construct()
    {
        App::behavior()->callBehavior(My::id() . 'AddActions', $this);
    }

    /**
     * Register actions.
     *
     * @param   array<int, TelegramAction>  $actions
     */
    public function addActions(array $actions): void
    {
        foreach ($actions as $action) {
            $this->actions[$action->id] = $action;
        }
    }

    /**
     * Get all registered actions.
     *
     * @return  array<string, TelegramAction>
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Get a registered action.
     */
    public function getAction(string $id): false|TelegramAction
    {
        return $this->actions[$id] ?? false;
    }

    /**
     * Set current telegram action.
     */
    public function setAction(string $id): self
    {
        $this->action = $this->getAction($id);

        return $this;
    }

    /**
     * Set current telegram content format.
     */
    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Set current telegram content.
     */
    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Set current telegram origin.
     */
    public function setOrigin(string $origin): self
    {
        $this->origin = $origin;

        return $this;
    }

    /**
     * Send telegram.
     */
    /**
     * Get users to send content to.
     *
     * Database is read here, so it can be called before sending after the page is served.
     *
     * @return  array<int, TelegramUser>
     */
    public function getRecipients(): array
    {
        $res = [];
        if (!$this->action || $this->action->type !== 'message' || $this->content === '') {
            return $res;
        }

        foreach ($this->action->getUsers() as $user) {
            // If action has a condition, check it
            if (is_callable($this->action->condition) && !call_user_func($this->action->condition, $user, $this->origin)) {
                continue;
            }

            $res[] = $user;
        }

        return $res;
    }

    /**
     * Send content to users.
     *
     * @param   null|array<int, TelegramUser>   $users      The users, or null to get them now
     * @param   bool                            $log_in_db  Log failures in Dotclear logs (database must be available)
     */
    public function send(?array $users = null, bool $log_in_db = true): void
    {
        foreach ($users ?? $this->getRecipients() as $user) {
            $this->query($user, 'sendMessage', ['text' => $this->content], $log_in_db);
        }
    }

    /**
     * Call bot API, failures are logged.
     *
     * @param   array<string, mixed>    $data
     */
    public function query(TelegramUser $user, string $endpoint, array $data, bool $log_in_db = true): bool
    {
        try {
            $this->queryOrFail($user, $endpoint, $data);
        } catch (Exception $exception) {
            // Log failure without the bot token
            $msg = sprintf('Failed to send Telegram %s to user %s: %s', $endpoint, $user->user, $exception->getMessage());
            if ($log_in_db) {
                try {
                    $cur = App::log()->openLogCursor();
                    $cur->setField('log_table', My::id());
                    $cur->setField('log_msg', $msg);
                    App::log()->addLog($cur);

                    return false;
                } catch (Exception) {
                }
            }

            error_log('TelegramNotifier: ' . $msg);

            return false;
        }

        return true;
    }

    /**
     * Call bot API.
     *
     * @param   array<string, mixed>    $data
     *
     * @throws  Exception   With Telegram error message
     */
    public function queryOrFail(TelegramUser $user, string $endpoint, array $data): void
    {
        $data['chat_id'] = $user->chat;
        if ($this->format !== '') {
            $data['parse_mode'] = $this->format;
        }

        $url  = sprintf(self::BOT_API_URL, $user->token, $endpoint);
        $path = '';

        // init bot API
        $client = HttpClient::initClient($url, $path);
        if ($client === false) {
            throw new Exception(__('Failed to init Telegram API'));
        }

        $client->setTimeout(self::TIMEOUT);

        // call bot API
        $client->post($path, $data);

        // get bot API response
        $rsp = json_decode((string) $client->getContent(), true);
        if (!is_array($rsp) || empty($rsp['ok'])) {
            throw new Exception(is_array($rsp) && is_string($rsp['description'] ?? null) ? $rsp['description'] : __('Failed to call Telegram API'));
        }
    }
}
