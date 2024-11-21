<?php
if (!defined('ABSPATH')) exit;

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
    
    global $wpdb;
    $table = $wpdb->prefix . 'user_activity_visits';
    
    // Handle CSV export
    if (isset($_POST['export_csv']) && check_admin_referer('uat_export_nonce')) {
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        uat_export_csv($start_date, $end_date);
    }
    
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
        
        <!-- Export Form -->
        <div class="card">
            <h2><?php echo esc_html__('Export Data'); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('uat_export_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="start_date"><?php echo esc_html__('Start Date'); ?></label></th>
                        <td><input type="date" id="start_date" name="start_date" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="end_date"><?php echo esc_html__('End Date'); ?></label></th>
                        <td><input type="date" id="end_date" name="end_date" required></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="export_csv" class="button button-primary" value="<?php echo esc_attr__('Export to CSV'); ?>">
                </p>
            </form>
        </div>
        
        <!-- Active Users Card -->
        <div class="card">
            <h2><?php echo esc_html__('Active Users'); ?></h2>
            <p class="active-users"><?php echo intval($active_users); ?> <?php echo esc_html__('users online now'); ?></p>
        </div>
        
        <!-- Recent Visits Table -->
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

function uat_export_csv($start_date, $end_date) {
    global $wpdb;
    $table = $wpdb->prefix . 'user_activity_visits';
    
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT v.*, u.user_login 
        FROM {$table} v 
        LEFT JOIN {$wpdb->users} u ON v.user_id = u.ID
        WHERE v.timestamp BETWEEN %s AND %s
        ORDER BY v.timestamp DESC
    ", $start_date . ' 00:00:00', $end_date . ' 23:59:59'));
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="user-activity-' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, array('User', 'Page URL', 'Timestamp', 'Duration (seconds)', 'IP Address'));
    
    foreach ($results as $row) {
        fputcsv($output, array(
            $row->user_id ? $row->user_login : 'Guest',
            $row->page_url,
            $row->timestamp,
            $row->duration,
            $row->ip_address
        ));
    }
    
    fclose($output);
    exit;
}