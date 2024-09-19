<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;
$table_name_sections = $wpdb->prefix . 'custom_sections';
$table_name_templates = $wpdb->prefix . 'custom_templates';
$wpdb->query("DROP TABLE IF EXISTS $table_name_sections");
$wpdb->query("DROP TABLE IF EXISTS $table_name_templates");
?>
