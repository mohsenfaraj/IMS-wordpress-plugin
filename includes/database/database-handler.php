<?php
if (!defined('ABSPATH')) {
    exit;
}

class IMS_Database_Handler
{
    public static function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $tbl_sessions = $wpdb->prefix . 'ims_sessions';
        $tbl_messages = $wpdb->prefix . 'ims_messages';
        $tbl_telegram = $wpdb->prefix . 'ims_telegram_users';

        $sql_sessions = "CREATE TABLE $tbl_sessions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            telegram_id bigint NULL,
            start_time datetime DEFAULT CURRENT_TIMESTAMP,
            end_time datetime NULL,
            duration int DEFAULT 0,
            date date NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $sql_messages = "CREATE TABLE $tbl_messages (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            telegram_id bigint NULL,
            message text NOT NULL,
            sent_time datetime DEFAULT CURRENT_TIMESTAMP,
            date date NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $sql_telegram = "CREATE TABLE $tbl_telegram (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            telegram_id bigint NOT NULL,
            chat_id bigint NOT NULL,
            username varchar(255),
            first_name varchar(255),
            last_name varchar(255),
            status varchar(20) NOT NULL DEFAULT 'pending',
            is_admin tinyint(1) NOT NULL DEFAULT 0,
            log_mode tinyint(1) NOT NULL DEFAULT 0,
            registered_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY telegram_user (telegram_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_sessions);
        dbDelta($sql_messages);
        dbDelta($sql_telegram);
    }

    /* -------------------------
       Sessions & messages (tid)
       ------------------------- */

    public static function get_active_session_by_tid($telegram_id)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . 'ims_sessions';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tbl WHERE telegram_id = %d AND end_time IS NULL ORDER BY start_time DESC LIMIT 1",
            $telegram_id
        ));
    }

    public static function start_session_by_tid($telegram_id)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . 'ims_sessions';

        // prevent double start
        $active = self::get_active_session_by_tid($telegram_id);
        if ($active)
            return false;

        return $wpdb->insert($tbl, [
            'user_id' => 0,
            'telegram_id' => $telegram_id,
            'start_time' => current_time('mysql'),
            'date' => current_time('Y-m-d')
        ], ['%d', '%d', '%s', '%s']);
    }

    public static function end_session_by_tid($telegram_id)
    {
        global $wpdb;
        $active = self::get_active_session_by_tid($telegram_id);
        if (!$active)
            return false;

        $tbl = $wpdb->prefix . 'ims_sessions';
        $end_time = current_time('mysql');
        $duration = strtotime($end_time) - strtotime($active->start_time);

        return $wpdb->update(
            $tbl,
            ['end_time' => $end_time, 'duration' => $duration],
            ['id' => $active->id],
            ['%s', '%d'],
            ['%d']
        );
    }

    public static function save_message_by_tid($telegram_id, $message)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . 'ims_messages';
        return $wpdb->insert($tbl, [
            'user_id' => 0,
            'telegram_id' => $telegram_id,
            'message' => wp_kses_post($message),
            'sent_time' => current_time('mysql'),
            'date' => current_time('Y-m-d')
        ], ['%d', '%d', '%s', '%s', '%s']);
    }

    /* -------------------------
       Telegram users management
       ------------------------- */

    public static function insert_pending_telegram_user($from)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . 'ims_telegram_users';

        $exists = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tbl WHERE telegram_id = %d", $from['id']));
        if ($exists)
            return $exists;

        $wpdb->insert($tbl, [
            'user_id' => 0,
            'telegram_id' => $from['id'],
            'chat_id' => $from['id'],
            'username' => $from['username'] ?? '',
            'first_name' => $from['first_name'] ?? '',
            'last_name' => $from['last_name'] ?? '',
            'status' => 'pending',
            'is_admin' => 0
        ], ['%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d']);

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $tbl WHERE telegram_id = %d", $from['id']));
    }

    public static function approve_telegram_user_by_id($id)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . 'ims_telegram_users';
        return $wpdb->update($tbl, ['status' => 'approved'], ['id' => intval($id)], ['%s'], ['%d']);
    }

    public static function set_telegram_user_status($id, $status)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . 'ims_telegram_users';
        return $wpdb->update($tbl, ['status' => sanitize_text_field($status)], ['id' => intval($id)], ['%s'], ['%d']);
    }

    public static function set_telegram_user_admin_flag($id, $is_admin)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . 'ims_telegram_users';
        return $wpdb->update($tbl, ['is_admin' => $is_admin ? 1 : 0], ['id' => intval($id)], ['%d'], ['%d']);
    }

    public static function get_telegram_user($telegram_id)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . 'ims_telegram_users';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $tbl WHERE telegram_id = %d", $telegram_id));
    }

    public static function get_telegram_user_by_row_id($id)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . 'ims_telegram_users';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $tbl WHERE id = %d", $id));
    }

    public static function get_pending_telegram_users()
    {
        global $wpdb;
        $tbl = $wpdb->prefix . 'ims_telegram_users';
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $tbl WHERE status = %s ORDER BY registered_at DESC", 'pending'));
    }

    public static function get_pending_count()
    {
        global $wpdb;
        $tbl = $wpdb->prefix . 'ims_telegram_users';
        return intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tbl WHERE status = %s", 'pending')));
    }

    /* -------------------------
       Admin helpers & stats
       ------------------------- */

    public static function get_active_users_admin()
    {
        global $wpdb;
        $tbl_sessions = $wpdb->prefix . 'ims_sessions';
        $tbl_tg = $wpdb->prefix . 'ims_telegram_users';

        return $wpdb->get_results(
            "SELECT s.*, t.first_name, t.last_name, t.username as tg_username, t.telegram_id as tg_id, t.registered_at, t.status, t.is_admin
             FROM $tbl_sessions s
             LEFT JOIN $tbl_tg t ON s.telegram_id = t.telegram_id
             WHERE s.end_time IS NULL
             ORDER BY s.start_time DESC"
        );
    }

    public static function get_recent_activity($limit = 50)
    {
        global $wpdb;
        $tbl_sessions = $wpdb->prefix . 'ims_sessions';
        $tbl_tg = $wpdb->prefix . 'ims_telegram_users';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, t.first_name, t.last_name, t.username as tg_username, t.telegram_id as tg_id
             FROM $tbl_sessions s
             LEFT JOIN $tbl_tg t ON s.telegram_id = t.telegram_id
             ORDER BY s.start_time DESC
             LIMIT %d",
            $limit
        ));
    }

    public static function get_today_sessions($user_id = null)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . 'ims_sessions';
        $today = current_time('Y-m-d');

        if ($user_id) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tbl WHERE user_id = %d AND date = %s ORDER BY start_time DESC",
                $user_id,
                $today
            ));
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tbl WHERE date = %s ORDER BY start_time DESC",
            $today
        ));
    }

    public static function get_received_log_count()
    {
        global $wpdb;
        $tbl = $wpdb->prefix . 'ims_messages';
        return intval($wpdb->get_var("SELECT COUNT(*) FROM $tbl"));
    }

    public static function get_total_intern_count()
    {
        global $wpdb;
        $tbl = $wpdb->prefix . 'ims_telegram_users';
        return intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tbl WHERE status = %s", 'approved')));
    }

    /* -------------------------
       Users listing + stats
       ------------------------- */

    public static function get_all_telegram_users($args = [])
    {
        global $wpdb;
        $tbl = $wpdb->prefix . 'ims_telegram_users';

        $limit = intval($args['limit'] ?? 200);
        $offset = intval($args['offset'] ?? 0);
        $order_by = sanitize_text_field($args['order_by'] ?? 'registered_at');
        $order_dir = strtoupper($args['order_dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        if ($order_by === 'total_hours') {
            // join sessions and sum durations
            $sql = $wpdb->prepare(
                "SELECT t.*, COALESCE(SUM(s.duration),0) as total_seconds
                 FROM $tbl t
                 LEFT JOIN {$wpdb->prefix}ims_sessions s ON s.telegram_id = t.telegram_id AND s.end_time IS NOT NULL
                 GROUP BY t.id
                 ORDER BY total_seconds $order_dir
                 LIMIT %d OFFSET %d",
                $limit,
                $offset
            );
            return $wpdb->get_results($sql);
        }

        $allowed = ['registered_at', 'first_name', 'last_name', 'username', 'status'];
        if (!in_array($order_by, $allowed))
            $order_by = 'registered_at';

        $sql = $wpdb->prepare("SELECT * FROM $tbl ORDER BY $order_by $order_dir LIMIT %d OFFSET %d", $limit, $offset);
        return $wpdb->get_results($sql);
    }
    public static function get_admins()
    {
        global $wpdb;
        $tbl = $wpdb->prefix . 'ims_telegram_users';

        $sql = $wpdb->prepare("SELECT * FROM $tbl WHERE is_admin=1");
        return $wpdb->get_results($sql);
    }

    public static function get_user_total_seconds_by_tid($telegram_id)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . 'ims_sessions';

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT COALESCE(SUM(duration),0) as total_seconds FROM $tbl WHERE telegram_id = %d AND end_time IS NOT NULL",
            $telegram_id
        ));
        return $row ? intval($row->total_seconds) : 0;
    }

    public static function get_user_total_seconds_today_by_tid($telegram_id)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . 'ims_sessions';
        $today = current_time('Y-m-d');

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT COALESCE(SUM(duration),0) as total_seconds FROM $tbl WHERE telegram_id = %d AND date = %s AND end_time IS NOT NULL",
            $telegram_id,
            $today
        ));
        return $row ? intval($row->total_seconds) : 0;
    }

    public static function get_user_sessions_by_tid($telegram_id)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . 'ims_sessions';
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $tbl WHERE telegram_id = %d ORDER BY start_time DESC", $telegram_id));
    }

    public static function get_user_messages_by_tid($telegram_id, $limit = 1000)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . 'ims_messages';
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $tbl WHERE telegram_id = %d ORDER BY sent_time DESC LIMIT %d", $telegram_id, $limit));
    }

    public static function get_all_daily_logs($limit = 500, $filters = [])
    {
        global $wpdb;
        $tbl_msgs = $wpdb->prefix . 'ims_messages';
        $tbl_tg = $wpdb->prefix . 'ims_telegram_users';

        $where_parts = [];
        $params = [];

        if (!empty($filters['telegram_id'])) {
            $where_parts[] = 'm.telegram_id = %d';
            $params[] = intval($filters['telegram_id']);
        }
        if (!empty($filters['date_from'])) {
            $where_parts[] = 'm.sent_time >= %s';
            $params[] = sanitize_text_field($filters['date_from'] . ' 00:00:00');
        }
        if (!empty($filters['date_to'])) {
            $where_parts[] = 'm.sent_time <= %s';
            $params[] = sanitize_text_field($filters['date_to'] . ' 23:59:59');
        }

        $where_sql = '';
        if ($where_parts) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_parts);
        }

        $sql = "SELECT m.*, t.first_name, t.last_name, t.username as tg_username, t.telegram_id as tg_id
                FROM $tbl_msgs m
                LEFT JOIN $tbl_tg t ON m.telegram_id = t.telegram_id
                $where_sql
                ORDER BY m.sent_time DESC
                LIMIT %d";

        $params[] = intval($limit);

        return $wpdb->get_results($wpdb->prepare($sql, ...$params));
    }

    public static function set_log_mode($tid, $mode)
    {
        global $wpdb;
        $tbl = $wpdb->prefix . 'ims_telegram_users';
        return $wpdb->update(
            $tbl,
            ['log_mode' => intval($mode)],
            ['telegram_id' => $tid],
            ['%d'],
            ['%d']
        );
    }
}
