<?php
function cms_activate_plugin() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_sections';
    $template_table_name = $wpdb->prefix . 'custom_templates';
    $charset_collate = $wpdb->get_charset_collate();

    $sql_for_template = "CREATE TABLE $template_table_name (
        id bigint(9) NOT NULL AUTO_INCREMENT,
        template_name tinytext NOT NULL,
        template_code tinytext NOT NULL,
        template_fields text NOT NULL,
        template_html text NOT NULL,
        status boolean  NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $sql_for_section = "CREATE TABLE $table_name (
        id bigint(9) NOT NULL AUTO_INCREMENT,
        section_name tinytext NOT NULL,
        section_description text NOT NULL,
        template_id bigint NOT NULL,
        section_fields text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_for_template);
    dbDelta($sql_for_section);
}
?>
