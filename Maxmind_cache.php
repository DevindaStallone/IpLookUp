<?php
/**
 * Plugin Name: MaxMind GeoIP Plugin
 * Description: A simple plugin to demonstrate MaxMind GeoIP2 integration in WordPress with caching.
 * Version: 1.0
 * Author: Devinda Maxmind
 */

// Load MaxMind GeoIP2 library
require_once __DIR__ . '/vendor/autoload.php';

use MaxMind\Db\Reader;

// Hook to add a settings page to the admin menu
add_action('admin_menu', 'mmgeoip_add_admin_menu');
add_action('admin_init', 'mmgeoip_settings_init');

function mmgeoip_add_admin_menu() {
    add_menu_page(
        'MaxMind GeoIP Settings',
        'MaxMind GeoIP',
        'manage_options',
        'mmgeoip_settings',
        'mmgeoip_settings_page'
    );
}

function mmgeoip_settings_init() {
    register_setting('mmgeoip_settings_group', 'mmgeoip_account_id');
    register_setting('mmgeoip_settings_group', 'mmgeoip_license_key');
    register_setting('mmgeoip_settings_group', 'mmgeoip_mmdb_path');

    add_settings_section(
        'mmgeoip_settings_group',
        'MaxMind GeoIP Settings',
        'mmgeoip_settings_section_callback',
        'mmgeoip_settings'
    );

    add_settings_field(
        'mmgeoip_account_id',
        'Account ID',
        'mmgeoip_account_id_render',
        'mmgeoip_settings',
        'mmgeoip_settings_group'
    );

    add_settings_field(
        'mmgeoip_license_key',
        'License Key',
        'mmgeoip_license_key_render',
        'mmgeoip_settings',
        'mmgeoip_settings_group'
    );

    add_settings_field(
        'mmgeoip_mmdb_path',
        'MMDB File Path',
        'mmgeoip_mmdb_path_render',
        'mmgeoip_settings',
        'mmgeoip_settings_group'
    );
}

function mmgeoip_settings_section_callback() {
    echo 'Enter your MaxMind GeoIP settings below:';
}

function mmgeoip_account_id_render() {
    echo '<input type="text" name="mmgeoip_account_id" value="' . esc_attr(get_option('mmgeoip_account_id')) . '">';
}

function mmgeoip_license_key_render() {
    echo '<input type="text" name="mmgeoip_license_key" value="' . esc_attr(get_option('mmgeoip_license_key')) . '">';
}

function mmgeoip_mmdb_path_render() {
    echo '<input type="text" name="mmgeoip_mmdb_path" value="' . esc_attr(get_option('mmgeoip_mmdb_path')) . '">';
}

function mmgeoip_settings_page() {
    ?>
    <div class="wrap">
        <h2>MaxMind GeoIP Settings</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('mmgeoip_settings_group');
            do_settings_sections('mmgeoip_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Hook to update constants on plugin initialization
add_action('plugins_loaded', 'mmgeoip_update_constants');

function mmgeoip_update_constants() {
    define('MMGEOIP_ACCOUNT_ID', get_option('mmgeoip_account_id'));
    define('MMGEOIP_LICENSE_KEY', get_option('mmgeoip_license_key'));
    define('MMGEOIP_MMDB_PATH', get_option('mmgeoip_mmdb_path'));
}

// Hook to add a shortcode for displaying the form
add_shortcode('geoip_form', 'mmgeoip_display_form');

// Callback function to display the form
function mmgeoip_display_form() {
    ob_start(); ?>
    <form action="" method="post">
        <label for="ip_address">Enter IP Address:</label>
        <input type="text" name="ip_address" id="ip_address" required>
        <input type="submit" value="Submit">
    </form>
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ip_address = sanitize_text_field($_POST['ip_address']);
        $location = mmgeoip_get_location($ip_address);

        if ($location) {
            echo '<p>Country: ' . $location['country']['iso_code'] . '</p>';
            echo '<p>City: ' . $location['city']['names']['en'] . '</p>';
        } else {
            echo '<p>Error retrieving location information.</p>';
        }
    }

    return ob_get_clean();
}

// Function to retrieve location information for an IP address with caching
function mmgeoip_get_location($ip_address) {
    // Check if the cache directory exists
    $cache_dir = __DIR__ . '/mmgeoip_cache/';
    if (!file_exists($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }

    // Check if the cache file exists and is still valid
    $cache_file = $cache_dir . md5($ip_address) . '.json';

    if (file_exists($cache_file) && time() - filemtime($cache_file) < 86400) {
        // Cache is valid, retrieve data from the cache file
        $cached_data = file_get_contents($cache_file);
        $record = json_decode($cached_data, true);
    } else {
        try {
            // Cache is not available or expired, fetch data from MaxMind API
            $reader = new Reader(MMGEOIP_MMDB_PATH);
            $record = $reader->get($ip_address);
            $reader->close();

            // Save the data to the cache file
            file_put_contents($cache_file, json_encode($record));
        } catch (Exception $e) {
            // Log or handle the exception as needed
            return null;
        }
    }

    return $record;
}
