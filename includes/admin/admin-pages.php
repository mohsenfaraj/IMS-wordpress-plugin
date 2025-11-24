<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once IMS_PLUGIN_PATH . '/includes/admin/ajax-handlers.php';

class IMS_Admin_Pages
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // AJAX endpoints
        add_action('wp_ajax_ims_approve_user', 'ajax_approve_user');
        add_action('wp_ajax_ims_set_user_status', 'ajax_set_user_status');
        add_action('wp_ajax_ims_set_user_admin', 'ajax_set_user_admin');
        add_action('wp_ajax_ims_export_logs', 'ajax_export_logs');

    }

    public function add_admin_menu()
    {
        $slug = 'ims-dashboard';

        $pending = IMS_Database_Handler::get_pending_count();
        $menu_title = 'IMS' . ($pending ? " ({$pending})" : '');

        add_menu_page($menu_title, $menu_title, 'manage_options', $slug, array($this, 'dashboard_page'), 'dashicons-nametag', 30);
        add_submenu_page($slug, 'Dashboard', 'Dashboard', 'manage_options', $slug, array($this, 'dashboard_page'));
        add_submenu_page($slug, 'All Users', 'All Users', 'manage_options', 'ims-users', array($this, 'all_users_page'));
        add_submenu_page($slug, 'Pending Users', 'Pending Users', 'manage_options', 'ims-pending-users', array($this, 'pending_users_page'));
        add_submenu_page($slug, 'Daily Logs', 'Daily Logs', 'manage_options', 'ims-daily-logs', array($this, 'daily_logs_page'));
        add_submenu_page($slug, 'Settings', 'Settings', 'manage_options', 'ims-settings', array($this, 'settings_page'));
    }

    public function dashboard_page()
    {
        require IMS_PLUGIN_PATH . '/includes/admin/dashboard.php';
    }

    public function all_users_page()
    {
        require IMS_PLUGIN_PATH . '/includes/admin/users.php';
    }

    public function pending_users_page()
    {
        require IMS_PLUGIN_PATH . '/includes/admin/pending-users.php';
    }

    public function daily_logs_page()
    {
        require IMS_PLUGIN_PATH . '/includes/admin/daily-logs.php';
    }

    private function user_detail_page($tid)
    {
        require IMS_PLUGIN_PATH . '/includes/admin/user-details.php';
    }


    public function settings_page()
    {
        require IMS_PLUGIN_PATH . '/includes/admin/settings.php';
    }

}
