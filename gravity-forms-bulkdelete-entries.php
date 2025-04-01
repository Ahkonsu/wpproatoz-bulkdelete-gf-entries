<?php
/*
Plugin Name: Gravity Forms WPProAtoZ Bulk Delete for Individual Gravity Forms
Plugin URI: https://wpproatoz.com
Description: This plugin helps you remove bulk entries for Gravity Forms, a great way to deal with spam.
Version: 1.2
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
    
    add_settings_section('wpproatoz_gf_bulk_delete_main', 'Bulk Delete Settings', 'wpproatoz_gf_bulk_delete_section_callback', 'wpproatoz-gf-bulk-delete');
    add_settings_field('form_id', 'Select Form', 'wpproatoz_gf_form_id_field', 'wpproatoz-gf-bulk-delete', 'wpproatoz_gf_bulk_delete_main');
    add_settings_field('batch_size', 'Batch Size', 'wpproatoz_gf_batch_size_field', 'wpproatoz-gf-bulk-delete', 'wpproatoz_gf_bulk_delete_main');
    add_settings_field('pause_time', 'Pause Time (seconds)', 'wpproatoz_gf_pause_time_field', 'wpproatoz-gf-bulk-delete', 'wpproatoz_gf_bulk_delete_main');
    add_settings_field('entry_status', 'Entry Status to Delete', 'wpproatoz_gf_entry_status_field', 'wpproatoz-gf-bulk-delete', 'wpproatoz_gf_bulk_delete_main');
    add_settings_field('dry_run', 'Dry Run Mode', 'wpproatoz_gf_dry_run_field', 'wpproatoz-gf-bulk-delete', 'wpproatoz_gf_bulk_delete_main');
}

// Section callback for instructions
function wpproatoz_gf_bulk_delete_section_callback() {
    echo '<p>Use this tool to delete entries from a specific Gravity Form. Select a form, adjust the batch size and pause time, and choose which entry statuses to delete. Save your settings before running the bulk delete process.</p>';
}

// Sanitize inputs
function wpproatoz_gf_sanitize_options($input) {
    $sanitized = array();
    $sanitized['form_id'] = absint($input['form_id']);
    $sanitized['batch_size'] = absint($input['batch_size']);
    $sanitized['pause_time'] = floatval($input['pause_time']);
    $sanitized['entry_status'] = array_map('sanitize_text_field', (array) $input['entry_status']);
    $sanitized['dry_run'] = isset($input['dry_run']) ? 1 : 0;
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
    echo '<p class="description">Choose the form whose entries you want to delete.</p>';
}

function wpproatoz_gf_batch_size_field() {
    $options = get_option('wpproatoz_gf_bulk_delete_options');
    $batch_size = isset($options['batch_size']) ? $options['batch_size'] : 250;
    echo '<input type="number" name="wpproatoz_gf_bulk_delete_options[batch_size]" value="' . esc_attr($batch_size) . '" min="1" />';
    echo '<p class="description">Number of entries to process per batch. Lower this if your server struggles with large deletes.</p>';
}

function wpproatoz_gf_pause_time_field() {
    $options = get_option('wpproatoz_gf_bulk_delete_options');
    $pause_time = isset($options['pause_time']) ? $options['pause_time'] : 15;
    echo '<input type="number" step="0.1" name="wpproatoz_gf_bulk_delete_options[pause_time]" value="' . esc_attr($pause_time) . '" min="0" />';
    echo '<p class="description">Pause time between batches (in seconds) to reduce server load. Increase this if you experience timeouts or high CPU usage during deletion.</p>';
}

function wpproatoz_gf_entry_status_field() {
    $options = get_option('wpproatoz_gf_bulk_delete_options');
    $entry_status = isset($options['entry_status']) ? (array) $options['entry_status'] : array('active');
    $statuses = array('active' => 'Active', 'spam' => 'Spam', 'trash' => 'Trash');
    foreach ($statuses as $value => $label) {
        echo '<label><input type="checkbox" name="wpproatoz_gf_bulk_delete_options[entry_status][]" value="' . esc_attr($value) . '" ' . checked(in_array($value, $entry_status), true, false) . ' /> ' . esc_html($label) . '</label><br>';
    }
    echo '<p class="description">Select which entry statuses to delete. At least one must be checked.</p>';
}

function wpproatoz_gf_dry_run_field() {
    $options = get_option('wpproatoz_gf_bulk_delete_options');
    $dry_run = isset($options['dry_run']) ? $options['dry_run'] : 0;
    echo '<label><input type="checkbox" name="wpproatoz_gf_bulk_delete_options[dry_run]" value="1" ' . checked($dry_run, 1, false) . ' /> Enable Dry Run</label>';
    echo '<p class="description">Simulate the deletion process without actually removing entries. Useful for testing settings.</p>';
}

// Enqueue admin scripts and styles
add_action('admin_enqueue_scripts', 'wpproatoz_gf_bulk_delete_enqueue_scripts');
function wpproatoz_gf_bulk_delete_enqueue_scripts($hook) {
    if ($hook !== 'settings_page_wpproatoz-gf-bulk-delete') {
        return;
    }
    wp_enqueue_script('wpproatoz-gf-bulk-delete-js', plugin_dir_url(__FILE__) . 'bulk-delete.js', array(), '1.2', true);
    wp_localize_script('wpproatoz-gf-bulk-delete-js', 'wpproatoz_gf_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wpproatoz_gf_bulk_delete_nonce'),
        'pause_time' => isset(get_option('wpproatoz_gf_bulk_delete_options')['pause_time']) ? floatval(get_option('wpproatoz_gf_bulk_delete_options')['pause_time']) * 1000 : 15000,
        'form_id' => isset(get_option('wpproatoz_gf_bulk_delete_options')['form_id']) ? absint(get_option('wpproatoz_gf_bulk_delete_options')['form_id']) : 0,
        'form_title' => ($form_id = absint(get_option('wpproatoz_gf_bulk_delete_options')['form_id'])) ? GFAPI::get_form($form_id)['title'] : '',
        'dry_run' => isset(get_option('wpproatoz_gf_bulk_delete_options')['dry_run']) ? (int) get_option('wpproatoz_gf_bulk_delete_options')['dry_run'] : 0
    ));
    wp_enqueue_style('wpproatoz-gf-bulk-delete-css', plugin_dir_url(__FILE__) . 'bulk-delete.css', array(), '1.2');
}

// Admin page with tabs
function wpproatoz_gf_bulk_delete_page() {
    if (!class_exists('GFAPI')) {
        echo '<div class="wrap"><h1>GF Bulk Delete</h1><p>Gravity Forms is not active. Please activate it to use this tool.</p></div>';
        return;
    }
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings';
    ?>
    <div class="wrap">
        <h1>GF Bulk Delete</h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=wpproatoz-gf-bulk-delete&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
            <a href="?page=wpproatoz-gf-bulk-delete&tab=docs" class="nav-tab <?php echo $active_tab == 'docs' ? 'nav-tab-active' : ''; ?>">Documentation</a>
            <a href="?page=wpproatoz-gf-bulk-delete&tab=logs" class="nav-tab <?php echo $active_tab == 'logs' ? 'nav-tab-active' : ''; ?>">Logs</a>
        </h2>

        <?php if ($active_tab == 'settings') : ?>
            <form method="post" action="options.php">
                <?php
                settings_fields('wpproatoz_gf_bulk_delete_group');
                do_settings_sections('wpproatoz-gf-bulk-delete');
                submit_button('Save Settings');
                ?>
                <button type="button" id="reset-settings" class="button button-secondary">Reset to Defaults</button>
            </form>
            <div id="entry-count-preview" style="margin-top: 10px;"></div>
            <div id="bulk-delete-controls">
                <button id="start-bulk-delete" class="button button-primary">Run Bulk Delete Now</button>
                <button id="stop-bulk-delete" class="button button-secondary" style="display:none;">Stop Bulk Delete</button>
            </div>
            <div id="bulk-delete-progress" style="margin-top: 20px;">
                <p id="progress-text"><span id="loader" class="spinner" style="display:none;"></span></p>
                <div id="progress-bar">
                    <div id="progress-bar-fill"></div>
                </div>
            </div>
        <?php elseif ($active_tab == 'docs') : ?>
            <div class="documentation">
                <?php
                $docs_file = plugin_dir_path(__FILE__) . 'documentation.txt';
                if (file_exists($docs_file)) {
                    $docs_content = file_get_contents($docs_file);
                    echo '<pre>' . esc_html($docs_content) . '</pre>';
                } else {
                    echo '<p>Documentation file not found. Please ensure documentation.txt is in the plugin directory.</p>';
                }
                ?>
            </div>
        <?php elseif ($active_tab == 'logs') : ?>
            <div class="logs">
                <?php
                $log_file = plugin_dir_path(__FILE__) . 'bulk-delete-log.txt';
                if (file_exists($log_file)) {
                    $log_content = file_get_contents($log_file);
                    echo '<pre>' . esc_html($log_content) . '</pre>';
                } else {
                    echo '<p>No logs available yet. Run a bulk delete to generate logs.</p>';
                }
                ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// Logging function
function wpproatoz_gf_log($message) {
    $log_file = plugin_dir_path(__FILE__) . 'bulk-delete-log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
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
    $batch_size = isset($options['batch_size']) ? absint($options['batch_size']) : 250;
    $entry_status = isset($options['entry_status']) ? (array) $options['entry_status'] : array('active');
    $dry_run = isset($options['dry_run']) ? (int) $options['dry_run'] : 0;
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
        wpproatoz_gf_log("Started " . ($dry_run ? "dry run" : "deletion") . " for Form ID $form_id with {$progress['total_entries']} entries");
    }

    $search_criteria = array();
    if (!in_array('active', $entry_status) || !in_array('spam', $entry_status) || !in_array('trash', $entry_status)) {
        $search_criteria['status'] = $entry_status;
    }

    $entries = GFAPI::get_entries($form_id, $search_criteria, null, array('offset' => $offset, 'page_size' => $batch_size));
    $entry_count = count($entries);

    if ($entry_count > 0) {
        if (!$dry_run) {
            foreach ($entries as $entry) {
                GFAPI::delete_entry($entry['id']);
            }
            wpproatoz_gf_log("Deleted $entry_count entries from Form ID $form_id at offset $offset");
        } else {
            wpproatoz_gf_log("Dry Run: Would have deleted $entry_count entries from Form ID $form_id at offset $offset");
        }
        $progress['total_deleted'] += $entry_count;
        set_transient($transient_key, $progress, HOUR_IN_SECONDS);
    }

    $remaining = max(0, $progress['total_entries'] - $progress['total_deleted']);
    $percentage = $progress['total_entries'] > 0 ? round(($progress['total_deleted'] / $progress['total_entries']) * 100, 2) : 0;
    $is_stopped = get_option('wpproatoz_gf_bulk_delete_stop') == '1';
    $has_more = $entry_count > 0 && !$is_stopped;

    if (!$has_more) {
        wpproatoz_gf_log("Completed " . ($dry_run ? "dry run" : "deletion") . " for Form ID $form_id. Total deleted: {$progress['total_deleted']}");
        delete_transient($transient_key);
    }

    wp_send_json_success(array(
        'total_deleted' => $progress['total_deleted'],
        'total_entries' => $progress['total_entries'],
        'percentage' => $percentage,
        'remaining' => $remaining,
        'offset' => $offset + $entry_count,
        'has_more' => $has_more,
        'is_stopped' => $is_stopped,
        'dry_run' => $dry_run
    ));
}

// AJAX handler for resetting settings
add_action('wp_ajax_wpproatoz_gf_reset_settings', 'wpproatoz_gf_reset_settings');
function wpproatoz_gf_reset_settings() {
    check_ajax_referer('wpproatoz_gf_bulk_delete_nonce', 'nonce');
    $defaults = array(
        'form_id' => '',
        'batch_size' => 250,
        'pause_time' => 15,
        'entry_status' => array('active'),
        'dry_run' => 0
    );
    update_option('wpproatoz_gf_bulk_delete_options', $defaults);
    wp_send_json_success();
}

// AJAX handler for entry count preview
add_action('wp_ajax_wpproatoz_gf_get_entry_count', 'wpproatoz_gf_get_entry_count');
function wpproatoz_gf_get_entry_count() {
    check_ajax_referer('wpproatoz_gf_bulk_delete_nonce', 'nonce');
    $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
    $options = get_option('wpproatoz_gf_bulk_delete_options');
    $entry_status = isset($options['entry_status']) ? (array) $options['entry_status'] : array('active');

    if (!$form_id) {
        wp_send_json_error(array('message' => 'No form selected.'));
    }

    $search_criteria = array();
    if (!in_array('active', $entry_status) || !in_array('spam', $entry_status) || !in_array('trash', $entry_status)) {
        $search_criteria['status'] = $entry_status;
    }

    $count = GFAPI::count_entries($form_id, $search_criteria);
    wp_send_json_success(array('count' => $count));
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