
<?php
function cms_register_shortcodes() {
    add_shortcode('custom_middle_section', 'cms_display_section');
}

function cms_display_section($atts) {
    global $wpdb;
    $section_table_name = $wpdb->prefix . 'custom_sections';
    $template_table_name = $wpdb->prefix . 'custom_templates';

    // Extract the ID from the shortcode attributes
    $atts = shortcode_atts(array(
        'id' => '0'
    ), $atts);
    
    $id = intval($atts['id']);
    $section = $wpdb->get_row($wpdb->prepare("SELECT * FROM $section_table_name WHERE id = %d", $id));
    
    if (!$section) {
        return 'Section not found';
    }
    $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM $template_table_name WHERE status=1 AND id = %d" , $section->template_id));

    if (!$template) {
        return 'Template not found';
    }
    $keys_array=[];
    $values_array=[];
    $section_fields = json_decode($section->section_fields);
    $template_fields = json_decode($template->template_fields);
    for($i=0; $i<count($template_fields); $i++){
        $field = $template_fields[$i];
        $name_of_field=$field->name;
        $value = $section_fields->{$name_of_field} ?? '';
        $keys_array[] = '{' . $name_of_field . '}';
        $values_array[] = esc_html($value);
    }
    return str_replace(
        $keys_array,
        $values_array,
        wp_kses_post($template->template_html)
    );
}

add_action('init', 'cms_register_shortcodes');
?>

