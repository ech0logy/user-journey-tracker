<?php
if (!defined('ABSPATH')) exit;

function uat_track_visit() {
    if (is_admin()) return;

    $ip = uat_get_client_ip();
    $transient_key = 'uat_rate_limit_' . md5($ip);
    
    if (get_transient($transient_key)) {
        return;
    }
    set_transient($transient_key, 1, 2);

    global $wpdb;
    $table = $wpdb->prefix . 'user_activity_visits';
    
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

function uat_get_client_ip() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
    } else {
        $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}