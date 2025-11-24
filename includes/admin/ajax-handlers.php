<?php
if (!defined('ABSPATH')) {
    exit;
}
/* -------------------------
       AJAX handlers
       ------------------------- */

function ajax_approve_user()
{
    check_ajax_referer('ims_nonce', 'nonce');
    if (!current_user_can('manage_options'))
        wp_send_json_error('Unauthorized');

    $id = intval($_POST['id']);
    $res = IMS_Database_Handler::approve_telegram_user_by_id($id);
    if ($res !== false) {
        $row = IMS_Database_Handler::get_telegram_user_by_row_id($id);
        if ($row && !empty($row->chat_id)) {
            $bot = new IMS_Telegram_Bot();
            $bot->send_message($row->chat_id, "✅ Your access has been approved. You may now use the bot.");
        }
        wp_send_json_success();
    }
    wp_send_json_error('Failed');
}

function ajax_set_user_status()
{
    check_ajax_referer('ims_nonce', 'nonce');
    if (!current_user_can('manage_options'))
        wp_send_json_error('Unauthorized');

    $id = intval($_POST['id']);
    $status = sanitize_text_field($_POST['status']);
    $ok = IMS_Database_Handler::set_telegram_user_status($id, $status);
    if ($ok !== false)
        wp_send_json_success();
    wp_send_json_error('Failed');
}

function ajax_set_user_admin()
{
    check_ajax_referer('ims_nonce', 'nonce');
    if (!current_user_can('manage_options'))
        wp_send_json_error('Unauthorized');

    $id = intval($_POST['id']);
    $is_admin = intval($_POST['is_admin']) ? 1 : 0;
    $ok = IMS_Database_Handler::set_telegram_user_admin_flag($id, $is_admin);
    if ($ok !== false)
        wp_send_json_success();
    wp_send_json_error('Failed');
}

function ajax_export_logs()
{
    check_admin_referer('ims_nonce', 'nonce');
    if (!current_user_can('manage_options'))
        wp_die('Unauthorized');

    $filters = [];
    if (!empty($_GET['telegram_id']))
        $filters['telegram_id'] = intval($_GET['telegram_id']);
    if (!empty($_GET['date_from']))
        $filters['date_from'] = sanitize_text_field($_GET['date_from']);
    if (!empty($_GET['date_to']))
        $filters['date_to'] = sanitize_text_field($_GET['date_to']);

    $rows = IMS_Database_Handler::get_all_daily_logs(5000, $filters);

    $filename = 'ims_logs_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $out = fopen('php://output', 'w');
    fputcsv($out, ['sent_time', 'telegram_id', 'telegram_username', 'first_name', 'last_name', 'message']);
    foreach ($rows as $r) {
        fputcsv($out, [$r->sent_time, $r->tg_id, $r->tg_username, $r->first_name, $r->last_name, $r->message]);
    }
    fclose($out);
    exit;
}