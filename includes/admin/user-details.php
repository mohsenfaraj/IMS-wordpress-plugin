<?php
if (!current_user_can('manage_options'))
    wp_die('Unauthorized');

$telegram_user = IMS_Database_Handler::get_telegram_user($tid);
if (!$telegram_user) {
    echo '<div class="wrap"><h1>User not found</h1></div>';
    return;
}

$sessions = IMS_Database_Handler::get_user_sessions_by_tid($tid);
$messages = IMS_Database_Handler::get_user_messages_by_tid($tid, 1000);
$seconds_today = IMS_Database_Handler::get_user_total_seconds_today_by_tid($tid);
$total_seconds = IMS_Database_Handler::get_user_total_seconds_by_tid($tid);
$display_name = trim($telegram_user->first_name . ' ' . $telegram_user->last_name) ?: $telegram_user->username;
?>
<div class="wrap">
    <h1>User Details: <?php echo esc_html($display_name); ?> <span style="font-size:13px;color:#666;">(ID:
            <?php echo esc_html($telegram_user->telegram_id); ?>)</span></h1>

    <div class="ims-dashboard" style="grid-template-columns:1fr 1fr;">
        <div class="ims-card">
            <h2>Summary</h2>
            <p><strong>Name:</strong> <?php echo esc_html($display_name); ?></p>
            <p><strong>Telegram Username:</strong> @<?php echo esc_html($telegram_user->username ?: '—'); ?></p>
            <p><strong>Date Joined:</strong> <?php echo esc_html($telegram_user->registered_at); ?></p>
            <p><strong>Today's total:</strong> <?php echo gmdate('H:i', max(0, $seconds_today)); ?></p>
            <p><strong>Total Hours:</strong> <?php echo gmdate('H:i', max(0, $total_seconds)); ?></p>
        </div>

        <div class="ims-card">
            <h2>Daily Logs</h2>
            <div class="ims-logs-scroll" style="max-height:300px;overflow:auto;padding-right:6px;">
                <?php if ($messages): ?>
                    <ul style="list-style:none;padding:0;margin:0;">
                        <?php foreach ($messages as $m): ?>
                            <li style="padding:8px 0;border-bottom:1px solid #f0f0f0;">
                                <div style="font-size:12px;color:#666;"><?php echo esc_html($m->sent_time); ?></div>
                                <div><?php echo esc_html($m->message); ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No logs.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="ims-card" style="grid-column: span 2;">
            <h2>Sessions</h2>
            <table id="table">
                <thead>
                    <tr>
                        <th>Start</th>
                        <th>End</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($sessions):
                        foreach ($sessions as $s): ?>
                            <tr>
                                <td><?php echo date('Y-m-d H:i', strtotime($s->start_time)); ?></td>
                                <td><?php echo $s->end_time ? date('Y-m-d H:i', strtotime($s->end_time)) : 'Active'; ?></td>
                                <td><?php echo $s->duration ? gmdate('H:i', $s->duration) : '--:--'; ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="3">No sessions yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
