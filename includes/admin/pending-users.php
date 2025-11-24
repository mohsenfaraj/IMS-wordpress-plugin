<?php
if (!defined('ABSPATH')) {
    exit;
}
if (!current_user_can('manage_options'))
    wp_die('Unauthorized');

$pending = IMS_Database_Handler::get_pending_telegram_users();
?>
<div class="wrap">
    <h1>Pending Telegram Users <span class="ims-pending-small"><?php echo intval(count($pending)); ?></span></h1>

    <div class="ims-card">
        <?php if (empty($pending)): ?>
            <p>No pending users.</p>
        <?php else: ?>
            <table class="widefat fixed striped" id="ims-pending-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Telegram ID</th>
                        <th>Requested</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $u): ?>
                        <tr data-id="<?php echo intval($u->id); ?>">
                            <td><?php echo esc_html($u->first_name . ' ' . $u->last_name); ?></td>
                            <td><?php echo esc_html($u->username ?: '—'); ?></td>
                            <td><?php echo esc_html($u->telegram_id); ?></td>
                            <td><?php echo esc_html($u->registered_at); ?></td>
                            <td><button class="button button-primary ims-approve-btn"
                                    data-id="<?php echo intval($u->id); ?>">Approve</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php
