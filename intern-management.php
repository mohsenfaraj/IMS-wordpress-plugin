<?php
/**
 * Plugin Name: Intern Management System
 * Description: Intern time tracking with Telegram integration and admin-approval flow.
 * Version: 1.1.0
 * Author: Mohsen Farajollahi
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('IMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IMS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('IMS_PLUGIN_VERSION', '1.1.0');

require_once IMS_PLUGIN_PATH . 'includes/database/database-handler.php';
require_once IMS_PLUGIN_PATH . 'includes/telegram/telegram-bot.php';
require_once IMS_PLUGIN_PATH . 'includes/admin/admin-pages.php';

class InternManagementSystem
{
    public function __construct()
    {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function activate()
    {
        IMS_Database_Handler::create_tables();
        flush_rewrite_rules();
    }

    public function deactivate()
    {
        flush_rewrite_rules();
    }

    public function init()
    {
        new IMS_Telegram_Bot();
        new IMS_Admin_Pages();
    }
    public function enqueue_admin_scripts($hook)
    {
        if (strpos($hook, 'ims-') !== false) {
            wp_enqueue_style('ims-admin-datatables-css', IMS_PLUGIN_URL . 'assets/css/datatables.min.css', [], IMS_PLUGIN_VERSION);
            wp_enqueue_style('ims-admin-main-css', IMS_PLUGIN_URL . 'assets/css/admin.css', [], IMS_PLUGIN_VERSION);

            wp_enqueue_script('ims-admin-datatables-js', IMS_PLUGIN_URL . 'assets/js/datatables.min.js', ['jquery'], IMS_PLUGIN_VERSION, true);
            wp_enqueue_script('ims-admin-main-js', IMS_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], IMS_PLUGIN_VERSION, true);

            wp_localize_script('ims-admin-main-js', 'ims_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ims_nonce')
            ]);
        }
    }
}
new InternManagementSystem();
