<?php
if (!defined('ABSPATH')) exit;

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