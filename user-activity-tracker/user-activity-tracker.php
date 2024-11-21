<?php
/*
Plugin Name: User Activity Tracker
Description: Tracks real-time user activity and page visits with enhanced security
Version: 1.1
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

// Include required files
require_once plugin_dir_path(__FILE__) . 'includes/database.php';
require_once plugin_dir_path(__FILE__) . 'includes/tracking.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin.php';

// Register activation hook
register_activation_hook(__FILE__, 'uat_create_tables');

// Register actions
add_action('wp_footer', 'uat_track_visit');
add_action('admin_menu', 'uat_add_admin_menu');

// Handle duration updates
add_action('wp_ajax_update_duration', 'uat_update_duration');
add_action('wp_ajax_nopriv_update_duration', 'uat_update_duration');

function uat_update_duration() {
    check_ajax_referer('uat_duration_nonce', '_ajax_nonce');
    
    global $wpdb;
    $table = $wpdb->prefix . 'user_activity_visits';
    
    $session_id = sanitize_text_field($_POST['session_id']);
    $duration = absint($_POST['duration']);
    
    if (!preg_match('/^[a-f0-9]{32}$/', $session_id)) {
        wp_die('Invalid session ID format');
    }
    
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

// Schedule cleanup
if (!wp_next_scheduled('uat_cleanup_old_records')) {
    wp_schedule_event(time(), 'daily', 'uat_cleanup_old_records');
}

add_action('uat_cleanup_old_records', 'uat_cleanup_records');

function uat_cleanup_records() {
    global $wpdb;
    $table = $wpdb->prefix . 'user_activity_visits';
    
    $wpdb->query($wpdb->prepare("
        DELETE FROM %i 
        WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ", $table));
}