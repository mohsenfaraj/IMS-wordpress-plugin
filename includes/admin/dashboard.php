<?php
if (!defined('ABSPATH')) {
    exit;
}
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}
$active_users = IMS_Database_Handler::get_active_users_admin();
$today_sessions = IMS_Database_Handler::get_today_sessions();
$recent_activity = IMS_Database_Handler::get_recent_activity(100);
$received_logs = IMS_Database_Handler::get_received_log_count();
$total_interns = IMS_Database_Handler::get_total_intern_count();
$pending_count = IMS_Database_Handler::get_pending_count();
?>
<div class="wrap">
    <h1>Intern Management Dashboard <?php if ($pending_count): ?><span class="ims-pending-indicator">Pending:
                <?php echo intval($pending_count); ?></span><?php endif; ?></h1>

    <div class="ims-dashboard">
        <div class="ims-card">
            <h2>Currently Active Interns</h2>
            <?php if ($active_users): ?>
                <ul class="ims-active-users">
                    <?php foreach ($active_users as $u): ?>
                        <li>
                            <div>
                                <strong>
                                    <a
                                        href="<?php echo esc_url(admin_url('admin.php?page=ims-users&view=user&tid=' . intval($u->tg_id))); ?>">
                                        <?php $name = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                                        echo esc_html($name ?: ($u->tg_username ?? '—')); ?>
                                    </a>
                                </strong>
                                <div style="font-size:12px;color:#666">@<?php echo esc_html($u->tg_username ?: '—'); ?> · ID:
                                    <?php echo esc_html($u->tg_id ?: '—'); ?>
                                </div>
                            </div>
                            <div style="text-align:right;font-size:12px;color:#666">Started:
                                <?php echo human_time_diff(strtotime($u->start_time)); ?> ago
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No active interns at the moment.</p>
            <?php endif; ?>
        </div>

        <div class="ims-card">
            <h2>Overview</h2>
            <div class="ims-stats-grid">
                <div class="ims-stat">
                    <span class="ims-stat-number"><?php echo esc_html($received_logs); ?></span>
                    <span class="ims-stat-label">Received Log Count</span>
                </div>
                <div class="ims-stat">
                    <span class="ims-stat-number"><?php echo esc_html($total_interns); ?></span>
                    <span class="ims-stat-label">Total Intern Count</span>
                </div>
                <div class="ims-stat">
                    <span class="ims-stat-number"><?php echo count($active_users); ?></span>
                    <span class="ims-stat-label">Active Now</span>
                </div>
            </div>
        </div>

        <div class="ims-card" style="grid-column: span 2;">
            <h2>Recent Activity</h2>
            <table id="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Telegram</th>
                        <th>Telegram ID</th>
                        <th>Start</th>
                        <th>End</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_activity as $s): ?>
                        <tr>
                            <td>
                                <a
                                    href="<?php echo esc_url(admin_url('admin.php?page=ims-users&view=user&tid=' . intval($s->tg_id))); ?>">
                                    <?php $n = trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? ''));
                                    echo esc_html($n ?: ($s->tg_username ?? '—')); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html("@" . $s->tg_username ?: '—'); ?></td>
                            <td><?php echo esc_html($s->tg_id ?: '—'); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($s->start_time)); ?></td>
                            <td><?php echo $s->end_time ? date('Y-m-d H:i', strtotime($s->end_time)) : 'Active'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>