<?php
/*
Plugin Name: IPInfo Plugin
Description: A simple WordPress plugin to get information about an IP address using the ipinfo API and save results to a custom database table.
Version: 1.0
Author: devinda
*/

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';


// Define the option name for the API key
define('IPINFO_API_KEY_OPTION', 'ipinfo_api_key');

// Register settings menu
function ipinfo_plugin_menu() {
    add_menu_page(
        'IPInfo Plugin Settings',
        'IPInfo Settings',
        'manage_options',
        'ipinfo-settings',
        'ipinfo_settings_page'
    );
}

add_action('admin_menu', 'ipinfo_plugin_menu');

// Settings page content
function ipinfo_settings_page() {
    ?>
    <div class="wrap">
        <h2>IPInfo Plugin Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('ipinfo_settings_group'); ?>
            <?php do_settings_sections('ipinfo-settings'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register and initialize settings
function ipinfo_settings_init() {
    register_setting('ipinfo_settings_group', IPINFO_API_KEY_OPTION, 'sanitize_api_key');

    add_settings_section(
        'ipinfo_settings_section',
        'API Key Settings',
        'ipinfo_settings_section_callback',
        'ipinfo-settings'
    );

    add_settings_field(
        'ipinfo_api_key',
        'API Key',
        'ipinfo_api_key_callback',
        'ipinfo-settings',
        'ipinfo_settings_section'
    );
}

add_action('admin_init', 'ipinfo_settings_init');

// Section callback
function ipinfo_settings_section_callback() {
    echo '<p>Enter your ipinfo API key below:</p>';
}

// API Key field callback
function ipinfo_api_key_callback() {
    $api_key = get_option(IPINFO_API_KEY_OPTION);
    echo '<input type="text" name="' . IPINFO_API_KEY_OPTION . '" value="' . esc_attr($api_key) . '" />';
}

// Sanitize API key
function sanitize_api_key($input) {
    return sanitize_text_field($input);
}

// Create custom database table during plugin activation
function ipinfo_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ipinfo_data';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        ip_address VARCHAR(50) NOT NULL,
        city VARCHAR(100),
        region VARCHAR(100),
        country VARCHAR(100),
        org VARCHAR(255),
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'ipinfo_create_table');

// Save results to the custom database table
function ipinfo_save_to_database($ip_address, $data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ipinfo_data';

    $wpdb->insert(
        $table_name,
        array(
            'ip_address' => $ip_address,
            'city' => $data['city'] ?? '',
            'region' => $data['region'] ?? '',
            'country' => $data['country'] ?? '',
            'org' => $data['org'] ?? '',
        )
    );
}


// Use cache functionality from ipinfo/php library
use Ipinfo\Ipinfo;

// Modify the API key in the plugin code to use the saved option
function get_ipinfo_api_key() {
    return get_option(IPINFO_API_KEY_OPTION, 'YOUR_DEFAULT_API_KEY');
}

function ipinfo_plugin_page() {
    ?>
    <div class="ipinfo-plugin-container">
        <h2>IPInfo Plugin</h2>
        
        <form method="post">
            <label for="ip_address">Enter IP Address:</label>
            <input type="text" name="ip_address" id="ip_address" required>
            <button type="submit">Get Info</button>
        </form>

        <?php
        if (isset($_POST['ip_address'])) {
            $ip_address = sanitize_text_field($_POST['ip_address']);
            $api_key = get_ipinfo_api_key();

            // Use cache from the ipinfo/php library
            $ipinfo = new Ipinfo(['token' => $api_key, 'cache' => ['enabled' => true]]);
            $data = $ipinfo->getDetails($ip_address);

            // Save results to custom database table
            ipinfo_save_to_database($ip_address, $data);

            echo '<h3>IP Information:</h3>';
            echo '<pre class="ipinfo-results">';
            print_r($data);
            echo '</pre>';
        }
        ?>
    </div>

    <style>
        .ipinfo-plugin-container {
            max-width: 600px;
            margin: 20px auto;
        }

        label {
            display: block;
            margin-bottom: 10px;
        }

        input {
            width: 100%;
            padding: 8px;
            margin-bottom: 20px;
        }

        button {
            padding: 10px;
            background-color: #0073aa;
            color: #fff;
            border: none;
            cursor: pointer;
        }

        .ipinfo-results {
            background-color: #f8f8f8;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: auto;
        }

        .ipinfo-results pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
    <?php
}

add_shortcode('ipinfo_plugin', 'ipinfo_plugin_page');
