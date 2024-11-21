<?php
/*
Plugin Name: User Activity Tracker
Description: Tracks real-time user activity and page visits with enhanced security
Version: 1.1
Author: Jericho Murito
*/

if (!defined('ABSPATH')) exit;

// Create database tables on plugin activation
register_activation_hook(__FILE__, 'uat_create_tables');

function uat_create_tables() {
    if (!current_user_can('activate_plugins')) {
        return;
    }
    
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $visits_table = $wpdb->prefix . 'user_activity_visits';
    $sql = $wpdb->prepare(
        "CREATE TABLE IF NOT EXISTS %i (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20),
            page_url varchar(255) NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            session_id varchar(32) NOT NULL,
            duration int DEFAULT 0,
            ip_address varchar(45),
            user_agent text,
            PRIMARY KEY  (id)
        ) %s",
        $visits_table,
        $charset_collate
    );
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Track page visits with rate limiting
add_action('wp_footer', 'uat_track_visit');

function uat_track_visit() {
    if (is_admin()) return;

    // Rate limiting
    $ip = uat_get_client_ip();
    $transient_key = 'uat_rate_limit_' . md5($ip);
    
    if (get_transient($transient_key)) {
        return;
    }
    set_transient($transient_key, 1, 2); // Rate limit: one track per 2 seconds

    global $wpdb;
    $table = $wpdb->prefix . 'user_activity_visits';
    
    // Generate secure session ID
    if (!session_id()) {
        session_start(['cookie_httponly' => true, 'cookie_secure' => is_ssl()]);
    }
    $session_id = bin2hex(random_bytes(16));
    
    $user_id = get_current_user_id();
    $page_url = esc_url_raw($_SERVER['REQUEST_URI']);
    
    $data = array(
        'user_id' => $user_id,
        'page_url' => $page_url,
        'session_id' => $session_id,
        'ip_address' => $ip,
        'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'])
    );
    
    $wpdb->insert($table, $data, array('%d', '%s', '%s', '%s', '%s'));
    
    // Add nonce to AJAX request
    $nonce = wp_create_nonce('uat_duration_nonce');
    
    ?>
    <script>
    (function() {
        let startTime = new Date();
        window.addEventListener('beforeunload', function() {
            let duration = Math.round((new Date() - startTime) / 1000);
            navigator.sendBeacon('<?php echo admin_url('admin-ajax.php'); ?>', 
                new URLSearchParams({
                    'action': 'update_duration',
                    'session_id': '<?php echo esc_js($session_id); ?>',
                    'duration': duration,
                    '_ajax_nonce': '<?php echo $nonce; ?>'
                })
            );
        });
    })();
    </script>
    <?php
}

// Handle duration updates with nonce verification
add_action('wp_ajax_update_duration', 'uat_update_duration');
add_action('wp_ajax_nopriv_update_duration', 'uat_update_duration');

function uat_update_duration() {
    check_ajax_referer('uat_duration_nonce', '_ajax_nonce');
    
    global $wpdb;
    $table = $wpdb->prefix . 'user_activity_visits';
    
    $session_id = sanitize_text_field($_POST['session_id']);
    $duration = absint($_POST['duration']);
    
    // Validate session ID format
    if (!preg_match('/^[a-f0-9]{32}$/', $session_id)) {
        wp_die('Invalid session ID format');
    }
    
    // Set reasonable maximum duration (e.g., 12 hours)
    if ($duration > 43200) {
        $duration = 43200;
    }
    
    $wpdb->update(
        $table,
        array('duration' => $duration),
        array('session_id' => $session_id),
        array('%d'),
        array('%s')
    );
    
    wp_die();
}

// Add admin menu with capability check
add_action('admin_menu', 'uat_add_admin_menu');

function uat_add_admin_menu() {
    add_menu_page(
        'User Activity',
        'User Activity',
        'manage_options',
        'user-activity-tracker',
        'uat_admin_page',
        'dashicons-chart-area'
    );
}

function uat_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    // Verify admin page nonce
    $nonce = wp_create_nonce('uat_admin_nonce');
    
    global $wpdb;
    $table = $wpdb->prefix . 'user_activity_visits';
    
    // Secure query with prepared statement
    $visits = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM %i 
        WHERE timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY timestamp DESC 
        LIMIT %d
    ", $table, 50));
    
    $active_users = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT user_id) 
        FROM %i 
        WHERE timestamp > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ", $table));
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('User Activity Tracker'); ?></h1>
        
        <div class="card">
            <h2><?php echo esc_html__('Active Users'); ?></h2>
            <p class="active-users"><?php echo intval($active_users); ?> <?php echo esc_html__('users online now'); ?></p>
        </div>
        
        <div class="card">
            <h2><?php echo esc_html__('Recent Page Visits'); ?></h2>
            <?php wp_nonce_field('uat_admin_nonce', 'uat_nonce'); ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('User'); ?></th>
                        <th><?php echo esc_html__('Page'); ?></th>
                        <th><?php echo esc_html__('Time'); ?></th>
                        <th><?php echo esc_html__('Duration'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($visits as $visit): ?>
                        <tr>
                            <td><?php echo $visit->user_id ? esc_html(get_userdata($visit->user_id)->user_login) : esc_html__('Guest'); ?></td>
                            <td><?php echo esc_html($visit->page_url); ?></td>
                            <td><?php echo esc_html($visit->timestamp); ?></td>
                            <td><?php echo $visit->duration ? esc_html($visit->duration . ' ' . __('seconds')) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <style>
    .active-users {
        font-size: 24px;
        font-weight: bold;
        color: #2271b1;
    }
    .card {
        background: #fff;
        border: 1px solid #ccd0d4;
        padding: 20px;
        margin: 20px 0;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
    </style>
    <?php
}

// Helper function to get client IP
function uat_get_client_ip() {
    $ip = '';
    
    // Check for proxy addresses
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
    } else {
        $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
    }
    
    // Validate IP address
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}

// Cleanup old records (run daily)
if (!wp_next_scheduled('uat_cleanup_old_records')) {
    wp_schedule_event(time(), 'daily', 'uat_cleanup_old_records');
}

add_action('uat_cleanup_old_records', 'uat_cleanup_records');

function uat_cleanup_records() {
    global $wpdb;
    $table = $wpdb->prefix . 'user_activity_visits';
    
    // Delete records older than 30 days
    $wpdb->query($wpdb->prepare("
        DELETE FROM %i 
        WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ", $table));
}