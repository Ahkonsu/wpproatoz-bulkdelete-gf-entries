<?php
/*
Plugin Name: Gravity Forms WPProAtoZ Bulk Delete for Individual Gravity Forms
Plugin URI: https://wpproatoz.com
Description: This plugin helps you remove bulk entries for Gravity Forms, a great way to deal with spam.
Version: 1.1
Requires at least: 6.0
Requires PHP: 8.0
Author: WPProAtoZ.com
Author URI: https://wpproatoz.com
Text Domain: wpproatoz-bulkdelete-gf-entries
Update URI: https://github.com/Ahkonsu/wpproatoz-bulkdelete-gf-entries/releases
GitHub Plugin URI: https://github.com/Ahkonsu/wpproatoz-bulkdelete-gf-entries/releases
GitHub Branch: main
Requires Plugins: gravityforms
*/
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin updater
require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/Ahkonsu/wpproatoz-bulkdelete-gf-entries',
    __FILE__,
    'wpproatoz-bulkdelete-gf-entries'
);

//end plugin update checker

$myUpdateChecker->setBranch('main');
// Add admin menu under Settings
add_action('admin_menu', 'wpproatoz_gf_bulk_delete_menu');
function wpproatoz_gf_bulk_delete_menu() {
    add_options_page(
        'GF Bulk Delete',
        'GF Bulk Delete',
        'manage_options',
        'wpproatoz-gf-bulk-delete',
        'wpproatoz_gf_bulk_delete_page'
    );
}

// Register settings
add_action('admin_init', 'wpproatoz_gf_bulk_delete_settings');
function wpproatoz_gf_bulk_delete_settings() {
    register_setting('wpproatoz_gf_bulk_delete_group', 'wpproatoz_gf_bulk_delete_options', 'wpproatoz_gf_sanitize_options');
    
    add_settings_section('wpproatoz_gf_bulk_delete_main', 'Bulk Delete Settings', null, 'wpproatoz-gf-bulk-delete');
    add_settings_field('form_id', 'Select Form', 'wpproatoz_gf_form_id_field', 'wpproatoz-gf-bulk-delete', 'wpproatoz_gf_bulk_delete_main');
    add_settings_field('batch_size', 'Batch Size', 'wpproatoz_gf_batch_size_field', 'wpproatoz-gf-bulk-delete', 'wpproatoz_gf_bulk_delete_main');
    add_settings_field('pause_time', 'Pause Time (seconds)', 'wpproatoz_gf_pause_time_field', 'wpproatoz-gf-bulk-delete', 'wpproatoz_gf_bulk_delete_main');
    add_settings_field('entry_status', 'Entry Status to Delete', 'wpproatoz_gf_entry_status_field', 'wpproatoz-gf-bulk-delete', 'wpproatoz_gf_bulk_delete_main');
}

// Sanitize inputs
function wpproatoz_gf_sanitize_options($input) {
    $sanitized = array();
    $sanitized['form_id'] = absint($input['form_id']);
    $sanitized['batch_size'] = absint($input['batch_size']);
    $sanitized['pause_time'] = floatval($input['pause_time']);
    $sanitized['entry_status'] = array_map('sanitize_text_field', (array) $input['entry_status']);
    return $sanitized;
}

// Field callbacks
function wpproatoz_gf_form_id_field() {
    $options = get_option('wpproatoz_gf_bulk_delete_options');
    $form_id = isset($options['form_id']) ? $options['form_id'] : '';
    $forms = GFAPI::get_forms();
    echo '<select name="wpproatoz_gf_bulk_delete_options[form_id]">';
    echo '<option value="">Select a Form</option>';
    foreach ($forms as $form) {
        echo '<option value="' . esc_attr($form['id']) . '" ' . selected($form_id, $form['id'], false) . '>' . esc_html($form['title']) . ' (ID: ' . $form['id'] . ')</option>';
    }
    echo '</select>';
}

function wpproatoz_gf_batch_size_field() {
    $options = get_option('wpproatoz_gf_bulk_delete_options');
    $batch_size = isset($options['batch_size']) ? $options['batch_size'] : 1000;
    echo '<input type="number" name="wpproatoz_gf_bulk_delete_options[batch_size]" value="' . esc_attr($batch_size) . '" min="1" />';
    echo '<p class="description">Number of entries to process per batch.</p>';
}

function wpproatoz_gf_pause_time_field() {
    $options = get_option('wpproatoz_gf_bulk_delete_options');
    $pause_time = isset($options['pause_time']) ? $options['pause_time'] : 1;
    echo '<input type="number" step="0.1" name="wpproatoz_gf_bulk_delete_options[pause_time]" value="' . esc_attr($pause_time) . '" min="0" />';
    echo '<p class="description">Pause time between batches to avoid server overload.</p>';
}

function wpproatoz_gf_entry_status_field() {
    $options = get_option('wpproatoz_gf_bulk_delete_options');
    $entry_status = isset($options['entry_status']) ? (array) $options['entry_status'] : array('active');
    $statuses = array('active' => 'Active', 'spam' => 'Spam', 'trash' => 'Trash');
    foreach ($statuses as $value => $label) {
        echo '<label><input type="checkbox" name="wpproatoz_gf_bulk_delete_options[entry_status][]" value="' . esc_attr($value) . '" ' . checked(in_array($value, $entry_status), true, false) . ' /> ' . esc_html($label) . '</label><br>';
    }
    echo '<p class="description">Select which entry statuses to delete.</p>';
}

// Enqueue admin scripts
add_action('admin_enqueue_scripts', 'wpproatoz_gf_bulk_delete_enqueue_scripts');
function wpproatoz_gf_bulk_delete_enqueue_scripts($hook) {
    if ($hook !== 'settings_page_wpproatoz-gf-bulk-delete') {
        return;
    }
    wp_enqueue_script('wpproatoz-gf-bulk-delete-js', plugin_dir_url(__FILE__) . 'bulk-delete.js', array(), '1.1', true);
    wp_localize_script('wpproatoz-gf-bulk-delete-js', 'wpproatoz_gf_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wpproatoz_gf_bulk_delete_nonce'),
        'pause_time' => isset(get_option('wpproatoz_gf_bulk_delete_options')['pause_time']) ? floatval(get_option('wpproatoz_gf_bulk_delete_options')['pause_time']) * 1000 : 1000
    ));
}

// Admin page
function wpproatoz_gf_bulk_delete_page() {
    if (!class_exists('GFAPI')) {
        echo '<div class="wrap"><h1>GF Bulk Delete</h1><p>Gravity Forms is not active. Please activate it to use this tool.</p></div>';
        return;
    }
    ?>
    <div class="wrap">
        <h1>GF Bulk Delete</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('wpproatoz_gf_bulk_delete_group');
            do_settings_sections('wpproatoz-gf-bulk-delete');
            submit_button('Save Settings');
            ?>
        </form>
        <div id="bulk-delete-controls">
            <button id="start-bulk-delete" class="button button-primary">Run Bulk Delete Now</button>
            <button id="stop-bulk-delete" class="button button-secondary" style="display:none;">Stop Bulk Delete</button>
        </div>
        <div id="bulk-delete-progress" style="margin-top: 20px;">
            <p id="progress-text"></p>
            <div id="progress-bar" style="width: 100%; background: #f1f1f1; height: 20px; border-radius: 5px;">
                <div id="progress-bar-fill" style="width: 0%; height: 100%; background: #4caf50; border-radius: 5px;"></div>
            </div>
        </div>
    </div>
    <?php
}

// AJAX handler for deletion process
add_action('wp_ajax_wpproatoz_gf_bulk_delete_process', 'wpproatoz_gf_bulk_delete_process');
function wpproatoz_gf_bulk_delete_process() {
    check_ajax_referer('wpproatoz_gf_bulk_delete_nonce', 'nonce');
    
    if (!class_exists('GFAPI')) {
        wp_send_json_error(array('message' => 'Gravity Forms not active.'));
    }

    $options = get_option('wpproatoz_gf_bulk_delete_options');
    $form_id = isset($options['form_id']) ? absint($options['form_id']) : 0;
    $batch_size = isset($options['batch_size']) ? absint($options['batch_size']) : 1000;
    $entry_status = isset($options['entry_status']) ? (array) $options['entry_status'] : array('active');
    $offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;

    if (!$form_id) {
        wp_send_json_error(array('message' => 'No form selected.'));
    }

    $transient_key = 'wpproatoz_gf_bulk_delete_progress_' . $form_id;
    $progress = get_transient($transient_key) ?: array('total_deleted' => 0, 'total_entries' => 0);

    if ($progress['total_entries'] == 0) {
        $search_criteria_total = array();
        if (!in_array('active', $entry_status) || !in_array('spam', $entry_status) || !in_array('trash', $entry_status)) {
            $search_criteria_total['status'] = $entry_status;
        }
        $progress['total_entries'] = GFAPI::count_entries($form_id, $search_criteria_total);
        set_transient($transient_key, $progress, HOUR_IN_SECONDS);
    }

    $search_criteria = array();
    if (!in_array('active', $entry_status) || !in_array('spam', $entry_status) || !in_array('trash', $entry_status)) {
        $search_criteria['status'] = $entry_status;
    }

    $entries = GFAPI::get_entries($form_id, $search_criteria, null, array('offset' => $offset, 'page_size' => $batch_size));
    $entry_count = count($entries);

    if ($entry_count > 0) {
        foreach ($entries as $entry) {
            GFAPI::delete_entry($entry['id']);
        }
        $progress['total_deleted'] += $entry_count;
        set_transient($transient_key, $progress, HOUR_IN_SECONDS);
    }

    $remaining = max(0, $progress['total_entries'] - $progress['total_deleted']);
    $percentage = $progress['total_entries'] > 0 ? round(($progress['total_deleted'] / $progress['total_entries']) * 100, 2) : 0;
    $is_stopped = get_option('wpproatoz_gf_bulk_delete_stop') == '1';
    $has_more = $entry_count > 0 && !$is_stopped;

    if (!$has_more) {
        delete_transient($transient_key);
    }

    wp_send_json_success(array(
        'total_deleted' => $progress['total_deleted'],
        'total_entries' => $progress['total_entries'],
        'percentage' => $percentage,
        'remaining' => $remaining,
        'offset' => $offset + $entry_count,
        'has_more' => $has_more,
        'is_stopped' => $is_stopped
    ));
}

// AJAX handler for updating options
add_action('wp_ajax_wpproatoz_gf_update_option', 'wpproatoz_gf_update_option');
function wpproatoz_gf_update_option() {
    check_ajax_referer('wpproatoz_gf_bulk_delete_nonce', 'nonce');
    $option = sanitize_text_field($_POST['option']);
    $value = sanitize_text_field($_POST['value']);
    update_option($option, $value);
    wp_send_json_success();
}

// AJAX handler for deleting options
add_action('wp_ajax_wpproatoz_gf_delete_option', 'wpproatoz_gf_delete_option');
function wpproatoz_gf_delete_option() {
    check_ajax_referer('wpproatoz_gf_bulk_delete_nonce', 'nonce');
    $option = sanitize_text_field($_POST['option']);
    delete_option($option);
    wp_send_json_success();
}