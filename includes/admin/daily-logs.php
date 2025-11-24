<?php
if (!defined('ABSPATH')) {
    exit;
}
if (!current_user_can('manage_options'))
    wp_die('Unauthorized');

$filters = [];
if (!empty($_GET['telegram_id']))
    $filters['telegram_id'] = intval($_GET['telegram_id']);
if (!empty($_GET['date_from']))
    $filters['date_from'] = sanitize_text_field($_GET['date_from']);
if (!empty($_GET['date_to']))
    $filters['date_to'] = sanitize_text_field($_GET['date_to']);

$logs = IMS_Database_Handler::get_all_daily_logs(500, $filters);
?>
<div class="wrap">
    <h1>All Daily Logs</h1>
    <div class="ims-card" style="margin-bottom:12px;">
        <form method="get" style="display:flex;gap:8px;align-items:center;">
            <input type="hidden" name="page" value="ims-daily-logs" />
            <input type="text" name="telegram_id" placeholder="Telegram ID"
                value="<?php echo esc_attr($_GET['telegram_id'] ?? ''); ?>" />
            <input type="date" name="date_from" value="<?php echo esc_attr($_GET['date_from'] ?? ''); ?>" />
            <input type="date" name="date_to" value="<?php echo esc_attr($_GET['date_to'] ?? ''); ?>" />
            <button class="button" type="submit">Filter</button>
        </form>
    </div>

    <div class="ims-card">
        <table id="table">
            <thead>
                <tr>
                    <th>Sent</th>
                    <th>Intern</th>
                    <th>Telegram</th>
                    <th>Telegram ID</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($logs):
                    foreach ($logs as $l): ?>
                        <tr>
                            <td><?php echo esc_html($l->sent_time); ?></td>
                            <td><a
                                    href="<?php echo esc_url(admin_url('admin.php?page=ims-users&view=user&tid=' . intval($l->tg_id))); ?>"><?php echo esc_html(trim($l->first_name . ' ' . $l->last_name) ?: ($l->user_id ? get_userdata($l->user_id)->display_name : '—')); ?></a>
                            </td>
                            <td><a href="<?php echo $l->tg_username ? ("https://t.me/" . $l->tg_username) : '#' ?>"
                                    target="_blank">@<?php echo esc_html($l->tg_username ?: '—'); ?></a></td>
                            <td><?php echo esc_html($l->tg_id ?: '—'); ?></td>
                            <td style="max-width:560px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?php echo esc_html($l->message); ?>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="5">No logs found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
