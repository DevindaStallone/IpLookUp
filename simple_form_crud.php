<?php
/**
 * Plugin Name: Simple Form Plugin with CRUD
 * Description: A simple WordPress plugin with a form that performs CRUD operations on a custom database table using AJAX.
 * Version: 1.0
 * Author: Devinda Stallone
 */

// Create custom table on plugin activation
function create_custom_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'form_data';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'create_custom_table');

// Enqueue scripts
function enqueue_scripts() {
    wp_enqueue_script('simple-form-plugin-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0', true);
    wp_localize_script('simple-form-plugin-script', 'simple_form_plugin_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
}

add_action('wp_enqueue_scripts', 'enqueue_scripts');

// Process AJAX request for adding data
function process_ajax_add_request() {
    global $wpdb;

    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);

    // Insert data into the custom table
    $table_name = $wpdb->prefix . 'form_data';
    $wpdb->insert($table_name, array('name' => $name, 'email' => $email));

    $response = array('status' => 'success', 'message' => 'Data inserted successfully!');
    echo json_encode($response);

    // Always exit to avoid extra output
    exit();
}

add_action('wp_ajax_process_add_form', 'process_ajax_add_request');
add_action('wp_ajax_nopriv_process_add_form', 'process_ajax_add_request'); // for non-logged in users

// Process AJAX request for retrieving data
function process_ajax_get_request() {
    global $wpdb;

    // Retrieve data from the custom table
    $table_name = $wpdb->prefix . 'form_data';
    $data = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

    $response = array('status' => 'success', 'data' => $data);
    echo json_encode($response);

    // Always exit to avoid extra output
    exit();
}

add_action('wp_ajax_process_get_data', 'process_ajax_get_request');
add_action('wp_ajax_nopriv_process_get_data', 'process_ajax_get_request'); // for non-logged in users

// Display the form and data
function display_form_and_data() {
    ob_start();
    ?>
    <form id="simple-form" action="#" method="post">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <input type="submit" value="Submit">
    </form>
    <div id="form-message"></div>

    <h2>Form Data</h2>
    <ul id="form-data-list"></ul>

    <script>
        // Fetch and display data on page load
        jQuery(document).ready(function($) {
            $.ajax({
                type: 'post',
                url: simple_form_plugin_ajax.ajax_url,
                data: {
                    action: 'process_get_data',
                },
                success: function(response) {
                    var result = $.parseJSON(response);
                    if (result.status === 'success') {
                        // Display data in the list
                        var dataList = $('#form-data-list');
                        dataList.empty();
                        $.each(result.data, function(index, item) {
                            dataList.append('<li>' + item.name + ' - ' + item.email + '</li>');
                        });
                    }
                },
            });

            // Handle form submission
            $('#simple-form').submit(function(e) {
                e.preventDefault();

                var name = $('#name').val();
                var email = $('#email').val();

                $.ajax({
                    type: 'post',
                    url: simple_form_plugin_ajax.ajax_url,
                    data: {
                        action: 'process_add_form',
                        name: name,
                        email: email,
                    },
                    success: function(response) {
                        var result = $.parseJSON(response);
                        $('#form-message').html('<p>' + result.message + '</p>');

                        // Refresh the data list after submission
                        $.ajax({
                            type: 'post',
                            url: simple_form_plugin_ajax.ajax_url,
                            data: {
                                action: 'process_get_data',
                            },
                            success: function(response) {
                                var result = $.parseJSON(response);
                                if (result.status === 'success') {
                                    // Display data in the list
                                    var dataList = $('#form-data-list');
                                    dataList.empty();
                                    $.each(result.data, function(index, item) {
                                        dataList.append('<li>' + item.name + ' - ' + item.email + '</li>');
                                    });
                                }
                            },
                        });
                    },
                });
            });
        });
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('simple_form_and_data', 'display_form_and_data');
