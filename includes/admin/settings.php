<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options'))
    wp_die('Unauthorized');

// Handle saving bot token and admin ids + webhook actions
if (isset($_POST['ims_settings_submit'])) {
    check_admin_referer('ims_settings_save', 'ims_settings_nonce');

    // Save bot token
    if (isset($_POST['ims_telegram_bot_token'])) {
        $token = trim(sanitize_text_field($_POST['ims_telegram_bot_token']));
        update_option('ims_telegram_bot_token', $token);
        echo '<div class="updated"><p>Bot token saved.</p></div>';
    }

    // Webhook actions
    $token_now = get_option('ims_telegram_bot_token', '');
    if (isset($_POST['ims_set_webhook']) && $token_now) {
        if (IMS_Telegram_Bot::set_webhook($token_now)) {
            echo '<div class="updated"><p>Webhook set to REST endpoint.</p></div>';
        } else {
            echo '<div class="error"><p>Failed to set webhook. Check token and REST endpoint (or check debug log).</p></div>';
        }
    }

    if (isset($_POST['ims_delete_webhook']) && $token_now) {
        if (IMS_Telegram_Bot::delete_webhook($token_now)) {
            echo '<div class="updated"><p>Webhook removed.</p></div>';
        } else {
            echo '<div class="error"><p>Failed to delete webhook. Check token.</p></div>';
        }
    }
}

$token = get_option('ims_telegram_bot_token', '');

// Try to fetch webhook info from Telegram if token exists
$webhook_info = null;
if ($token) {
    $resp = wp_remote_get("https://api.telegram.org/bot{$token}/getWebhookInfo", array('timeout' => 10));
    if (!is_wp_error($resp)) {
        $body = wp_remote_retrieve_body($resp);
        $json = json_decode($body, true);
        if (is_array($json) && isset($json['ok']) && $json['ok']) {
            $webhook_info = $json['result'] ?? null;
        }
    }
}

?>
<div class="wrap">
    <h1>Intern Management Settings</h1>

    <form method="post" style="margin-bottom:20px;">
        <?php wp_nonce_field('ims_settings_save', 'ims_settings_nonce'); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="ims_telegram_bot_token">Telegram Bot Token</label></th>
                <td>
                    <input type="password" id="ims_telegram_bot_token" name="ims_telegram_bot_token"
                        value="<?php echo esc_attr($token); ?>" class="regular-text" />
                    <p class="description">Enter your bot token from @BotFather. Required for bot actions and
                        notifications.</p>
                </td>
            </tr>

            <tr>
                <th scope="row">Webhook</th>
                <td>
                    <p>REST webhook endpoint: <code><?php echo esc_html(rest_url('ims/v1/webhook')); ?></code></p>

                    <?php if ($webhook_info): ?>
                        <p><strong>Current webhook URL:</strong> <?php echo esc_html($webhook_info['url'] ?? '—'); ?></p>
                        <p><strong>Last error:</strong>
                            <?php echo esc_html($webhook_info['last_error_message'] ?? 'None'); ?></p>
                        <p><strong>Last update:</strong>
                            <?php echo esc_html(isset($webhook_info['last_error_date']) ? date('Y-m-d H:i', intval($webhook_info['last_error_date'])) : '—'); ?>
                        </p>
                    <?php else: ?>
                        <p><em>No webhook info available (token might be empty or invalid).</em></p>
                    <?php endif; ?>

                    <p style="margin-top:10px;">
                        <button type="submit" name="ims_set_webhook" class="button button-primary">Set Webhook</button>
                        <button type="submit" name="ims_delete_webhook" class="button">Remove Webhook</button>
                    </p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" name="ims_settings_submit" class="button button-primary">Save Settings</button>
        </p>
    </form>
</div>
<?php
