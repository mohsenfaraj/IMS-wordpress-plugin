<?php
if (!defined('ABSPATH')) {
    exit;
}
if (!current_user_can('manage_options'))
    wp_die('Unauthorized');

// If viewing a specific user
if (isset($_GET['view']) && $_GET['view'] === 'user' && !empty($_GET['tid'])) {
    $tid = intval($_GET['tid']);
    $this->user_detail_page($tid);
    return;
}
$order_by = sanitize_text_field($_GET['order_by'] ?? 'registered_at');
$order_dir = sanitize_text_field($_GET['order_dir'] ?? 'DESC');
$users = IMS_Database_Handler::get_all_telegram_users(['order_by' => $order_by, 'order_dir' => $order_dir, 'limit' => 500]);

?>
<div class="wrap">
    <h1>All Telegram Users</h1>
    <div class="ims-card">
        <p>Sort by:
            <a
                href="<?php echo esc_url(add_query_arg(['order_by' => 'registered_at', 'order_dir' => 'DESC'])); ?>">Joined</a>
            |
            <a href="<?php echo esc_url(add_query_arg(['order_by' => 'total_hours', 'order_dir' => 'DESC'])); ?>">Total
                Hours</a>
        </p>
        <table id="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Telegram ID</th>
                    <th>Status</th>
                    <th>Date Joined</th>
                    <th>Total Hours</th>
                    <th>Admin</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr data-id="<?php echo intval($u->id); ?>">
                        <td><a
                                href="<?php echo esc_url(admin_url('admin.php?page=ims-users&view=user&tid=' . intval($u->telegram_id))); ?>"><?php echo esc_html(trim($u->first_name . ' ' . $u->last_name) ?: $u->username); ?></a>
                        </td>
                        <td><a href="<?php echo $u->username ? ("https://t.me/" . $u->username) : '' ?>"
                                target="_blank">@<?php echo esc_html($u->username ?: '—'); ?></a></td>
                        <td><?php echo esc_html($u->telegram_id); ?></td>
                        <td><?php echo esc_html($u->status); ?></td>
                        <td><?php echo esc_html($u->registered_at); ?></td>
                        <td><?php echo esc_html(gmdate('H:i', IMS_Database_Handler::get_user_total_seconds_by_tid($u->telegram_id))); ?>
                        </td>
                        <td><input class="ims-admin-toggle" data-id="<?php echo intval($u->id); ?>" type="checkbox" <?php checked($u->is_admin, 1); ?> /></td>
                        <td>
                            <select class="ims-user-status-select" data-id="<?php echo intval($u->id); ?>">
                                <option value="approved" <?php selected($u->status, 'approved'); ?>>Approved</option>
                                <option value="pending" <?php selected($u->status, 'pending'); ?>>Pending</option>
                            </select>
                            <button class="button ims-set-status" data-id="<?php echo intval($u->id); ?>">Set</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
