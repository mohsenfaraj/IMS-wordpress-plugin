<?php
if (!defined('ABSPATH')) {
    exit;
}
class IMS_Telegram_Bot
{
    private $api;

    public function __construct()
    {
        $token = get_option('ims_telegram_bot_token');
        if (empty($token))
            throw new Exception('Token is not set');
        $bridge = get_option('ims_bridge', '');
        $this->api = "$bridge/bot$token/" ?: "https://api.telegram.org/bot$token/";
        add_action('wp_ajax_ims_webhook', array($this, 'handle_webhook'));
        add_action('wp_ajax_nopriv_ims_webhook', array($this, 'handle_webhook'));

        add_action('rest_api_init', function () {
            register_rest_route('ims/v1', '/webhook', [
                'methods' => 'POST',
                'callback' => [$this, 'rest_webhook'],
                'permission_callback' => '__return_true',
            ]);
        });
    }

    public function rest_webhook(WP_REST_Request $request)
    {
        try {
            $update = $request->get_json_params();
            if (!$update)
                return new WP_REST_Response(['ok' => false], 200);
            $this->process_update($update);
        } catch (Exception $e) {
            // Error management
        } finally {
            return new WP_REST_Response(['ok' => true], 200);
        }
    }

    public function handle_webhook()
    {
        $input = file_get_contents('php://input');
        $update = json_decode($input, true);
        if (!$update)
            wp_die();

        $this->process_update($update);
        wp_die();

    }

    private function process_update($update)
    {
        if (!isset($update['message']))
            return;

        $msg = $update['message'];
        $chat_id = $msg['chat']['id'];
        $text = $msg['text'] ?? '';
        $from = $msg['from'];
        $tid = $from['id'];

        $user = IMS_Database_Handler::get_telegram_user($tid);

        // New user request system stays untouched
        if (!$user) {
            IMS_Database_Handler::insert_pending_telegram_user($from);
            $this->notify_admin_new_user($from);
            $this->send_message($chat_id, "✅ Request received. An admin will approve you soon.");
            return;
        }

        // Not approved yet
        if ($user->status !== 'approved') {
            $this->send_message($chat_id, "⏳ Your request is still pending.");
            return;
        }

        // ----- LOG MODE HANDLING -----
        if ((int) $user->log_mode === 1) {
            // User is currently in log mode
            if ($text === 'Cancel') {
                IMS_Database_Handler::set_log_mode($tid, 0);
                $this->send_message($chat_id, "❌ Daily log cancelled.");
                $this->show_main_menu($chat_id, $tid);
                return;
            }

            if (!trim($text)) {
                $this->send_message($chat_id, "❌ Log cannot be empty.");
                return;
            }

            // Only accept normal text, ignore all other updates
            if (!isset($msg['entities']) || $msg['entities'][0]['type'] !== 'bot_command') {
                $this->save_daily_log($chat_id, $tid, $text);
                IMS_Database_Handler::set_log_mode($tid, 0);
                $this->show_main_menu($chat_id, $tid);
                return;
            } else {
                $this->send_message($chat_id, "This type of message is still not supported!\n\nPlease send a text message.");
                return;
            }
        }

        // Normal flow
        if ($text === '/start') {
            $this->send_message($chat_id, 'Welcome to Intern Management System! Use the keyboard for interactions.');
            $this->show_main_menu($chat_id, $tid);
            return;
        }

        switch ($text) {
            case 'Start Work':
                $this->start_work($chat_id, $tid);
                break;
            case 'End Work':
                $this->end_work($chat_id, $tid);
                break;
            case 'Send Daily Log':
                $this->request_daily_log($chat_id, $tid);
                return;
            default:
                $this->send_message($chat_id, 'Command is not recognized ');
                break;
        }
        $this->show_main_menu($chat_id, $tid);
    }

    private function show_main_menu($chat_id, $tid)
    {
        $active = IMS_Database_Handler::get_active_session_by_tid($tid);

        $keyboard = [
            'keyboard' => [
                [$active ? 'End Work' : 'Start Work'],
                ['Send Daily Log']
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];

        $message = $active ? "⏰ You are currently working. Click 'End Work' when finished." : "Click 'Start Work' to begin tracking your time.";
        $this->send_message($chat_id, $message, $keyboard);
    }

    private function start_work($chat_id, $tid)
    {
        $res = IMS_Database_Handler::start_session_by_tid($tid);
        $this->send_message($chat_id, $res ? "✅ Work session started!" : "❌ You already have an active work session.");
    }

    private function end_work($chat_id, $tid)
    {
        $res = IMS_Database_Handler::end_session_by_tid($tid);
        $this->send_message($chat_id, $res ? "✅ Work session ended!" : "❌ No active work session found.");
    }

    private function request_daily_log($chat_id, $tid)
    {
        IMS_Database_Handler::set_log_mode($tid, 1);

        $keyboard = [
            "keyboard" => [
                ["Cancel"]
            ],
            "resize_keyboard" => true
        ];

        $this->send_message(
            $chat_id,
            "📝 Send your daily log now.\nIf you change your mind, press Cancel.",
            $keyboard
        );
    }


    private function save_daily_log($chat_id, $tid, $message)
    {
        $res = IMS_Database_Handler::save_message_by_tid($tid, $message);

        if ($res) {
            $this->send_message($chat_id, "✅ Daily log saved!");
            $this->notify_admins($tid, $message);
        } else {
            $this->send_message($chat_id, "❌ Failed to save log.");
        }
    }


    private function notify_admins($tid, $message)
    {
        $telegram_user = IMS_Database_Handler::get_telegram_user($tid);
        $name = $telegram_user ? trim($telegram_user->first_name . ' ' . $telegram_user->last_name) : "Unknown";

        $admin_message = "📝 New Daily Log from {$name}:\n\n{$message}";

        $admins = IMS_Database_Handler::get_admins();
        if (count($admins) == 0)
            return;
        foreach ($admins as $admin) {
            $admin_tg = IMS_Database_Handler::get_telegram_user($admin->telegram_id);
            $this->send_message($admin_tg->chat_id, $admin_message);
        }
    }

    private function notify_admin_new_user($from)
    {
        $name = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? ''));
        $username = $from['username'] ?? '';
        $tid = $from['id'];

        $msg = "🚨 New IMS Access Request\n\nName: {$name}\nUsername: @" . ($username ?: 'n/a') . "\nTelegram ID: {$tid}\n\nApprove here: " . admin_url('admin.php?page=ims-pending-users');

        $admins = IMS_Database_Handler::get_admins();
        if (count($admins) == 0)
            return;
        foreach ($admins as $admin) {
            $admin_tg = IMS_Database_Handler::get_telegram_user($admin->telegram_id);
            $this->send_message($admin_tg->chat_id, $msg);
        }
    }

    public function send_message($chat_id, $text, $reply_markup = null)
    {
        $url = "{$this->api}/sendMessage";
        $data = ['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML'];
        if ($reply_markup)
            $data['reply_markup'] = json_encode($reply_markup);

        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($data),
            'timeout' => 30
        ]);

        return !is_wp_error($response);
    }

    public static function set_webhook($token, $bridge)
    {
        $webhook_url = rest_url('ims/v1/webhook');
        $url = ($bridge ?: "https://api.telegram.org/") . "bot" . $token . "/setWebhook?url=" . urlencode($webhook_url);
        $response = wp_remote_get($url);
        if (is_wp_error($response))
            return false;
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body;
    }

    public static function delete_webhook($token, $bridge)
    {
        $url = ($bridge ?: "https://api.telegram.org/") . "bot" . $token . "/deleteWebhook";
        $response = wp_remote_get($url);
        if (is_wp_error($response))
            return false;
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['ok'] ?? false;
    }
}
?>