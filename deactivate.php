<?php
function cms_deactivate_plugin() {
    // Nothing to do on deactivation
    global $wpdb;
    $table_name_sections = $wpdb->prefix . 'custom_sections';
    $table_name_templates = $wpdb->prefix . 'custom_templates';
    $wpdb->query("DROP TABLE IF EXISTS $table_name_sections");
    $wpdb->query("DROP TABLE IF EXISTS $table_name_templates");
}
?>
