<?php
/*
Plugin Name: Form Modal Shortcode
Description: A Shortcode that generates a button that displays a modal form for user submitted content with a table to show the results in the dashboard.
Plugin URI: https://github.com/Matthewpco/WP-Plugin-Modal-Form-Shortcode
Version: 1.1.0
Author: Gary Matthew Payne
Author URI: https://wpwebdevelopment.com/
License: GPL2
*/

// Create custom database table on plugin activation
function form_modal_shortcode_activation() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'form_modal_shortcode';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        artist_name varchar(255) NOT NULL,
        title_of_work varchar(255) NOT NULL,
        instagram_handle varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        image_file varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'form_modal_shortcode_activation');


// Create popup admin page to display submitted data
function form_modal_shortcode_admin_menu() {
    add_menu_page(
        'FMS Entries',
        'FMS Entries',
        'manage_options',
        'form-modal-shortcode',
        'form_modal_shortcode_admin_page'
    );
}
add_action('admin_menu', 'form_modal_shortcode_admin_menu');


// Register custom options
function form_modal_shortcode_register_settings() {
    register_setting('form_modal_shortcode', 'form_modal_shortcode_email');
    register_setting('form_modal_shortcode', 'form_modal_shortcode_send_email');
}
add_action('admin_init', 'form_modal_shortcode_register_settings');


// Enqueue JavaScript for AJAX form submission and styles
function form_modal_shortcode_enqueue_scripts() {
    wp_enqueue_script('form-modal-shortcode', plugin_dir_url(__FILE__) . 'js/form-modal-shortcode.js', array(), false, true);
    wp_enqueue_style('form-modal-shortcode', plugin_dir_url(__FILE__) . 'css/form-modal-shortcode.css');
    wp_localize_script('form-modal-shortcode', 'form_modal_shortcode_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('form_modal_shortcode_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'form_modal_shortcode_enqueue_scripts');


// Handle AJAX form submission
function form_modal_shortcode_submit() {
    check_ajax_referer('form_modal_shortcode_nonce');
    global $wpdb;
    $table_name = $wpdb->prefix . 'form_modal_shortcode';
    $artist_name = sanitize_text_field($_POST['artist_name']);
    $title_of_work = sanitize_text_field($_POST['title_of_work']);
    $instagram_handle = sanitize_text_field($_POST['instagram_handle']);
    $email = sanitize_email($_POST['email']);
    $image_file = sanitize_text_field($_POST['image_file']);

    $wpdb->insert($table_name, array(
        'artist_name' => $artist_name,
        'title_of_work' => $title_of_work,
        'instagram_handle' => $instagram_handle,
        'email' => $email,
        'image_file' => $image_file
    ));

    // Check if email sending is enabled
    if (get_option('form_modal_shortcode_send_email')) {
        // Send data to email
        $to = get_option('form_modal_shortcode_email');
        $subject = 'New Form Submission';
        $message = 'Artist Name: ' . $artist_name . "\r\n";
        $message .= 'Title of Work: ' . $title_of_work . "\r\n";
        $message .= 'Instagram Handle: ' . $instagram_handle . "\r\n";
        $message .= 'Email: ' . $email . "\r\n";
        $message .= 'Image File: ' . $image_file;
        wp_mail($to, $subject, $message);
    }

    wp_die();
}
add_action('wp_ajax_form_modal_shortcode_submit', 'form_modal_shortcode_submit');
add_action('wp_ajax_nopriv_form_modal_shortcode_submit', 'form_modal_shortcode_submit');


// Create shortcode for button and modal
function form_modal_shortcode_shortcode() {
    ob_start();
    ?>

    <button id="form-modal-shortcode-button" class="motd-media-button white size-medium">SUBMIT HERE</button>
    <div id="form-modal-shortcode-overlay"></div>
    <div id="form-modal-shortcode-modal" style="display:none;">

        <span id="modal-close">X</span>

        <div class="center" style="padding: 0 10% 2% 8%;">
            <h2>Submit Your Art</h2>
            <p>To submit your artwork into our contest, please fill out the information below and upload your artwork:</p>
            <p id="form-modal-shortcode-message" class="hidden">Submission Received.</p>
        </div>

        <form id="form-modal-shortcode" class="display-flex flex-wrap">

            <div class="one-half-column display-flex flex-column">
                <label for="artist-name">Artist Name:</label>
                <input type="text" id="artist-name" name="artist-name"><br><br>
                <label for="instagram-handle">Instagram Handle:</label>
                <input type="text" id="instagram-handle" name="instagram-handle"><br><br>
            </div>

            <div class="one-half-column display-flex flex-column">
                <label for="title-of-work">Title of Work:</label>
                <input type="text" id="title-of-work" name="title-of-work"><br><br>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email"><br><br>
            </div>
            
            <div class="display-flex flex-column full-column">
                <label for="url">Url link to image:</label>
                <input type="url" id="image-file" name="image-file" style="width: 88%;"><br><br>
                <input type="submit" value="Submit" style="margin-top: 3%;width: 92%;background-color: #0345ad;color: #fff;">
            </div>

        </form>

    <?php
    return ob_get_clean();
}
add_shortcode('form_modal_shortcode', 'form_modal_shortcode_shortcode');


function form_modal_shortcode_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'form_modal_shortcode';

    // Check if the form was submitted
    if (isset($_POST['delete_all'])) {
        // Delete all entries from the table
        $wpdb->query("TRUNCATE TABLE $table_name");
    } elseif (isset($_POST['delete_entry'])) {
        // Delete individual entry
        $entry_id = intval($_POST['entry_id']);
        $wpdb->delete($table_name, array('id' => $entry_id));
    }

    $results = $wpdb->get_results("SELECT * FROM $table_name");

    echo '<h1>Popup Modal Form Settings and Submissions</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields('form_modal_shortcode');
    do_settings_sections('form_modal_shortcode');
    echo '<label for="form_modal_shortcode_send_email">Enable Email on Form Submission:</label>';
    echo '<input type="checkbox" id="form_modal_shortcode_send_email" name="form_modal_shortcode_send_email" value="1"' . checked(1, get_option('form_modal_shortcode_send_email'), false) . '>';
    echo '<br>';
    echo '<br>';
    echo '<label for="form_modal_shortcode_email">Send Email To:</label>';
    echo '<input type="email" id="form_modal_shortcode_email" name="form_modal_shortcode_email" value="' . esc_attr(get_option('form_modal_shortcode_email')) . '">';
    submit_button();
    echo '</form>';

    // Add CSS styles for the table
    echo '<style>
        table {
            border-collapse: collapse;
            width: 90%;
        }
        th, td {
            text-align: left;
            padding: 8px;
        }
        tr:nth-child(even) {
            background-color: #e4e4e6;
        }
        th {
            background-color: #1587d1;
            color: white;
        }
    </style>';

    echo '<table>';
    echo '<tr>';
    echo '<th>Artist Name</th>';
    echo '<th>Title of Work</th>';
    echo '<th>Instagram Handle</th>';
    echo '<th>Email</th>';
    echo '<th>Image File</th>';
    echo '<th>Delete Entry</th>';
    echo '</tr>';
    
    foreach ($results as $result) {
        echo '<tr>';
        echo '<td>' . esc_html($result->artist_name) . '</td>';
        echo '<td>' . esc_html($result->title_of_work) . '</td>';
        echo '<td>' . esc_html($result->instagram_handle) . '</td>';
        echo '<td>' . esc_html($result->email) . '</td>';
        echo '<td><a href="' . esc_html($result->image_file) . '">' . esc_html($result->image_file) . '</a></td>';
        echo '<td><form method="post"><input type="hidden" name="delete_entry" value="1">
        <input type="hidden" name="entry_id" value="' . esc_attr($result->id) . '">
        <input type="submit" value="Delete"></form></td>';
        echo '</tr>';
    }

    echo '</table>';
    echo '<br>';
    echo '<form method="post">';
    echo '<input type="hidden" name="delete_all" value="1">';
    echo '<input type="submit" value="Delete All Entries">';
    echo '</form>';
}
