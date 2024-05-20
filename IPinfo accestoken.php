<?php
/*
Plugin Name: IPInfo Plugin
Description: A simple WordPress plugin to get information about an IP address using the ipinfo API.
Version: 1.0
Author: Devinda Stallone IPInfo
*/

function ipinfo_plugin_page() {
    ?>
    <div class="ipinfo-plugin-container">
        <h2>IPInfo Plugin</h2>
        <!-- front end form -->
        <form method="post">
            <label for="ip_address">Enter IP Address:</label>
            <input type="text" name="ip_address" id="ip_address" required>
            <button type="submit">Get Info</button>
        </form>

        <?php
        if (isset($_POST['ip_address'])) {
            $ip_address = sanitize_text_field($_POST['ip_address']);
            $api_key = 'e292d3a74aa3f3'; //access token of the IPinfo

            $api_url = "http://ipinfo.io/{$ip_address}?token={$api_key}";
            $response = wp_remote_get($api_url);

            if (is_wp_error($response)) {
                echo '<p>Error fetching IP information.</p>';
            } else {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                echo '<h3>IP Information:</h3>';
                echo '<pre>';
                print_r($data);
                echo '</pre>';
            }
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
    </style>
    <?php
}

add_shortcode('ipinfo_plugin', 'ipinfo_plugin_page');
