<?php
if (!defined('ABSPATH'))
    exit;
if (!current_user_can('manage_options'))
    wp_die('Unauthorized');

function ims_get_bridge()
{
    $bridge = get_option('ims_bridge', '');
    return rtrim($bridge ?: 'https://api.telegram.org', '/') . '/';
}

$token = get_option('ims_telegram_bot_token', '');
$bridge = ims_get_bridge();
$webhook_info = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_admin_referer('ims_settings_save', 'ims_settings_nonce');

    // Save bot token
    if (isset($_POST['ims_telegram_bot_token'])) {
        $token = trim(sanitize_text_field($_POST['ims_telegram_bot_token']));
        update_option('ims_telegram_bot_token', $token);
        echo '<div class="updated"><p>Bot token saved.</p></div>';
    }

    // Save bridge URL
    if (isset($_POST['ims_bridge'])) {
        $bridge_raw = trim(sanitize_text_field($_POST['ims_bridge']));
        update_option('ims_bridge', $bridge_raw);
        $bridge = ims_get_bridge();
        echo '<div class="updated"><p>Bridge URL saved.</p></div>';
    }

    // Set webhook
    if (isset($_POST['ims_set_webhook']) && $token) {
        $res = IMS_Telegram_Bot::set_webhook($token, $bridge);
        if (!empty($res['ok'])) {
            echo '<div class="updated"><p>Webhook successfully set.</p></div>';
        } else {
            $err = $res['description'] ?? 'Unknown error';
            echo '<div class="error"><p>Failed to set webhook: ' . esc_html($err) . '</p></div>';
        }
    }

    // Delete webhook
    if (isset($_POST['ims_delete_webhook']) && $token) {
        $res = IMS_Telegram_Bot::delete_webhook($token, $bridge);
        if (!empty($res)) {
            echo '<div class="updated"><p>Webhook successfully deleted.</p></div>';
        } else {
            echo '<div class="error"><p>Failed to delete webhook. Check token.</p></div>';
        }
    }
}

// Fetch current webhook info from Telegram
if ($token) {
    $resp = wp_remote_get($bridge . "bot{$token}/getWebhookInfo", ['timeout' => 10]);
    if (!is_wp_error($resp)) {
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        if (!empty($json['ok']))
            $webhook_info = $json['result'] ?? null;
    }
}
?>

<div class="wrap">
    <h1>Intern Management Settings</h1>
    <form method="post">
        <?php wp_nonce_field('ims_settings_save', 'ims_settings_nonce'); ?>
        <table class="form-table">
            <tr>
                <th><label for="ims_telegram_bot_token">Telegram Bot Token</label></th>
                <td>
                    <input type="password" id="ims_telegram_bot_token" name="ims_telegram_bot_token"
                        value="<?php echo esc_attr($token); ?>" class="regular-text" />
                    <p class="description">Enter your bot token from @BotFather.</p>
                </td>
            </tr>

            <tr>
                <th>Webhook</th>
                <td>
                    <p>REST endpoint: <code><?php echo esc_html(rest_url('ims/v1/webhook')); ?></code></p>

                    <?php if ($webhook_info): ?>
                        <p><strong>Current webhook URL:</strong> <?php echo esc_html($webhook_info['url'] ?? '—'); ?></p>
                        <p><strong>Last error:</strong>
                            <?php echo esc_html($webhook_info['last_error_message'] ?? 'None'); ?></p>
                        <p><strong>Last update:</strong>
                            <?php echo isset($webhook_info['last_error_date']) ? date('Y-m-d H:i', intval($webhook_info['last_error_date'])) : '—'; ?>
                        </p>
                    <?php else: ?>
                        <p><em>No webhook info available (token might be empty or invalid).</em></p>
                    <?php endif; ?>

                    <p>
                        <button type="submit" name="ims_set_webhook" class="button button-primary">Set Webhook</button>
                        <button type="submit" name="ims_delete_webhook" class="button">Remove Webhook</button>
                    </p>
                </td>
            </tr>

            <tr>
                <th>Bridge URL</th>
                <td>
                    <input type="url" name="ims_bridge" value="<?php echo esc_attr($bridge); ?>" class="regular-text" />
                    <p class="description">If Telegram API is blocked, provide a bridge URL to use.</p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" name="ims_settings_submit" class="button button-primary">Save Settings</button>
        </p>
    </form>
</div>