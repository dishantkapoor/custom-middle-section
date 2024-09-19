<?php
function cms_add_admin_menu()
{
    add_menu_page('Custom Section', 'Custom Section', 'manage_options', 'custom_section', 'cms_template_list', 'dashicons-editor-insertmore');
    add_submenu_page('custom_section', 'Templates', 'Templates', 'manage_options', 'custom_middle_section_templates', 'cms_admin_template_page');
}

function show_error()
{
    echo '<div class="notice notice-error is-dismissible"><p>Fields are required.</p></div>';
}

function cms_template_list()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_templates';
    $templates = $wpdb->get_results("SELECT * FROM $table_name");
    $is_editing = false;
    $fielsList = array();
    if (isset($_GET['edit_section'])) {
        $section_id = intval($_GET['edit_section']);
        $edit_section = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}custom_sections WHERE id = %d", $section_id));
        if ($edit_section) {
            $fielsList = json_decode($edit_section->section_fields);
            $is_editing = true;
        }
    }

    if (isset($_GET['delete_section'])) {
        $section_id = intval($_GET['delete_section']);
        $wpdb->delete("{$wpdb->prefix}custom_sections", array('id' => $section_id));
        echo '<div class="notice notice-success is-dismissible"><p>Section deleted successfully.</p></div>';
        echo '<script>window.location.href = "admin.php?page=custom_section";</script>';
    }

    if (isset($_GET['template_id'])) {
        $template_id = intval($_GET['template_id']);
        $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}custom_templates WHERE id = %d", $template_id));
        if ($template) {
            $fields = json_decode($template->template_fields);
        ?>
            <h2><?php if ($is_editing) {
                    echo 'Edit Section';
                } else {
                    echo  'Add Section';
                } ?> for Template: <?php echo esc_html($template->template_name); ?></h2>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="section_name">Section Name</label></th>
                        <td><input name="section_name" type="text" value="<?php echo $is_editing ? esc_attr($edit_section->section_name) : ''; ?>" id="section_name" placeholder="Enter Section Name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="section_description">Section Description</label></th>
                        <td><input name="section_description" type="text" value="<?php echo $is_editing ? esc_attr($edit_section->section_description) : ''; ?>" placeholder="Enter Section Description" id="section_description" class="regular-text" required></td>
                    </tr>
                    <?php
                    if ($is_editing && $fielsList) {
                        foreach ($fielsList as $name => $value) { ?>
                            <tr>
                                <th scope="row"><label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($name); ?></label></th>
                                <td><input name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" type="text" id="<?php echo esc_attr($name); ?>" class="regular-text" required></td>
                            </tr>
                        <?php }
                    } else {
                        foreach ($fields as $field) { ?>
                            <tr>
                                <th scope="row"><label for="<?php echo esc_attr($field->name); ?>"><?php echo esc_html($field->description); ?></label></th>
                                <td><input name="<?php echo esc_attr($field->name); ?>" placeholder="{<?php echo esc_attr($field->name); ?>}" type="text" id="<?php echo esc_attr($field->name); ?>" class="regular-text" required></td>
                            </tr>
                    <?php }
                    } ?>
                </table>
                <p class="submit">
                    <input type="submit" name="submit_section" id="submit_section" class="button button-primary" value="<?php echo $is_editing ? "Update Section" : "Save Section" ?>">
                    <a href="<?php echo admin_url('admin.php?page=custom_section'); ?>" class="button">Back</a>
                </p>
            </form>
            <?php
        }
    }

    if (isset($_POST['submit_section'])) {
        $template_id = intval($_GET['template_id']);
        $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}custom_templates WHERE id = %d", $template_id));
        if ($template) {
            $fields = json_decode($template->template_fields);
            $section_data = array();
            foreach ($fields as $field) {
                $section_data[$field->name] = sanitize_text_field($_POST[$field->name]);
            }
            if ($is_editing) {
                $wpdb->update("{$wpdb->prefix}custom_sections", array(
                    'section_name' => $_POST['section_name'],
                    'section_description' => $_POST['section_description'],
                    'section_fields' => json_encode($section_data),
                ), array('id' => $section_id));
                echo '<div class="notice notice-success is-dismissible"><p>Section updated successfully.</p></div>';
                echo '<script>window.location.href = "admin.php?page=custom_section&template_id=' . $template_id . '";</script>';
            } else {
                $wpdb->insert("{$wpdb->prefix}custom_sections", array(
                    'section_name' => $_POST['section_name'],
                    'section_description' => $_POST['section_description'],
                    'template_id' => $template_id,
                    'section_fields' => json_encode($section_data),
                    'created_at' => current_time('mysql')
                ));
                echo '<div class="notice notice-success is-dismissible"><p>Section added successfully.</p></div>';
            }
        }
    }
    if (!isset($_GET['template_id'])) {
        echo "<div style='display:flex; justify-content:space-between; padding:4px;'><h2>Sections for Template</h2> <a href='/wp-admin/admin.php?page=custom_middle_section_templates' style='padding-top:30px;margin-right:40px;'> <button class='button'>Add Template</button></a></div>";
        if ($templates) {

            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Template Name</th><th>Template Fields</th><th>Created At</th><th>Number of Sections</th><th>Actions</th></tr></thead>';
            echo '<tbody>';
            foreach ($templates as $template) {
                $fields = json_decode($template->template_fields);
                $field_names = array_map(function ($field) {
                    return $field->name;
                }, $fields);
                $field_list = implode(', ', $field_names);

                // Get the number of sections using this template
                $section_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}custom_sections WHERE template_id = %d", $template->id));

                echo '<tr>';
                echo '<td>' . esc_html($template->template_name) . '</td>';
                echo '<td>' . esc_html($field_list) . '</td>';
                echo '<td>' . esc_html($template->created_at) . '</td>';
                echo '<td>' . esc_html($section_count) . '</td>';
                echo '<td><a href="' . admin_url('admin.php?page=custom_section&template_id=' . $template->id) . '" class="button">Manage Section</a></td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>No templates found.</p>';
        }
    } else {

        $template_id = intval($_GET['template_id']);
        $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}custom_templates WHERE id = %d", $template_id));
        $sections = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}custom_sections WHERE template_id = %d", $template_id));

        if ($sections) {
            echo '<h2>Sections for Template : ' . esc_html($template->template_name) . '(' . esc_html($template_id) . ')</h2>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Section Name</th><th>Section Description</th><th>Short Code</th><th>Created At</th><th>Actions</th></tr></thead>';
            echo '<tbody>';
            foreach ($sections as $section) {
                echo '<tr>';
                echo '<td>' . esc_html($section->section_name) . '</td>';
                echo '<td>' . esc_html($section->section_description) . '</td>';
                echo '<td><pre>[custom_middle_section id="' . esc_html($section->id) . '"]</pre></td>';
                echo '<td>' . esc_html($section->created_at) . '</td>';
                echo '<td>';
                echo '<a href="' . admin_url('admin.php?page=custom_section&edit_section=' . $section->id . '&template_id=' . $section->template_id) . '" class="button">Edit</a> ';
                echo '<a href="' . admin_url('admin.php?page=custom_section&delete_section=' . $section->id) . '" class="button">Delete</a>';
                echo "<button class='button copy-shortcode' style='margin-left:4px;' data-shortcode='[" . "custom_middle_section id=" . '"' . esc_html($section->id) . '"' . "]'>Copy Shortcode</button>";
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';

            echo '<style>.copy-shortcode { background-color: #0073aa; color: #fff; border: none; padding: 5px 10px; cursor: pointer; }</style>';
            echo '<script>
                document.querySelectorAll(".copy-shortcode").forEach(function(button) {
                    button.addEventListener("click", function() {
                        var textarea = document.createElement("textarea");
                        textarea.value = button.dataset.shortcode;
                        document.body.appendChild(textarea);
                        textarea.select();
                        document.execCommand("copy");
                        document.body.removeChild(textarea);
                        alert("Shortcode copied: " + button.dataset.shortcode);
                    });
                });
            </script>';
        } else {
            echo '<p>No sections found for this template.</p>';
        }
    }
}

function cms_admin_template_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_templates';

    // Check if we are in edit mode
    $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
    $editing = false;
    $template = null;

    if ($edit_id > 0) {
        $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id));
        if ($template) {
            $editing = true;
        }
    }

    // Handle form submission
    if (isset($_POST['submit'])) {
        if (empty($_POST['fields'])) {
            echo '<div class="notice notice-error is-dismissible"><p>Fields are required.</p></div>';
            echo '<button class="button button-primary"  onclick="window.history.back();">Go Back</button>';
            return; // Exit processing to avoid further output.
        } else {
            $template_name = sanitize_text_field($_POST['template_name']);
            $template_fields = ($_POST['fields']);
            $html_template = wp_kses_post($_POST['template_html']);
            $status = sanitize_text_field($_POST['status']);
            $random_string = wp_generate_password(20, false);

            if ($editing) {
                $wpdb->update($table_name, array(
                    'template_name' => $template_name,
                    'template_code' => $random_string,
                    'template_fields' => json_encode($template_fields),
                    'template_html' => $html_template,
                    'status' => $status,
                ), array('id' => $edit_id));
            } else {
                $wpdb->insert($table_name, array(
                    'template_name' => $template_name,
                    'template_code' => $random_string,
                    'template_fields' => json_encode($template_fields),
                    'template_html' => $html_template,
                    'status' => $status,
                ));
            }
            echo '<script>window.location.href = "admin.php?page=custom_middle_section_templates";</script>';
            exit;
        }
    }

    // Handle deletion
    if (isset($_GET['delete'])) {
        $delete_id = intval($_GET['delete']);
        if ($delete_id > 0) {
            $wpdb->delete($table_name, array('id' => $delete_id));
            echo '<script>window.location.href = "admin.php?page=custom_middle_section_templates";</script>';
            exit;
        }
    }

    $sections = $wpdb->get_results("SELECT * FROM $table_name");
    ?>

    <div class="wrap">
        <h1><?php echo $editing ? 'Edit Template' : 'Add New Template'; ?></h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="template_name">Template Name</label></th>
                    <td><input name="template_name" type="text" placeholder="Enter Template Name" id="template_name" value="<?php echo $editing ? esc_attr($template->template_name) : ''; ?>" class="regular-text" required></td>
                </tr>

                <tr>
                    <th scope="row"><label for="fields">Fields</label></th>
                    <td>
                        <div id="fields-container">
                            <?php if ($editing && $template->template_fields) { ?>
                                <?php $fields = json_decode($template->template_fields); ?>
                                <?php foreach ($fields as $index => $field) { ?>
                                    <div class="field-row " style="margin-bottom:10px;">
                                        <div style="display: flex;">
                                            <input name="fields[<?php echo $index; ?>][name]" type="text" placeholder="Field Name for name attribute" class="regular-text " value="<?php echo esc_attr($field->name); ?>" required>
                                            <input name="fields[<?php echo $index; ?>][description]" type="text" placeholder="Field Label" style="margin-left:5px;" class="regular-text " value="<?php echo esc_attr($field->description); ?>" required>
                                            <button type="button" class="button remove-field " style="margin-left: 10px;">&times;</button>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>

                                <div class="field-row " style="margin-bottom:10px;">
                                    <div style="display: flex;">
                                        <input name="fields[0][name]" type="text" placeholder="Field Name for name attribute" class="regular-text " required>
                                        <input name="fields[0][description]" type="text" placeholder="Field Label" style="margin-left:5px;" class="regular-text " required>
                                        <button type="button" class="button remove-field " style="margin-left: 10px;">&times;</button>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        <button type="button" class="button add-field" style="margin-top: 10px;">+</button>
                    </td>
                </tr>



                <tr>
                    <th scope="row"><label for="template_html">HTML Template</label></th>
                    <td><textarea name="template_html" id="template_html" placeholder="Your EML template with placeholder variables, like:for field name 'title' use '{title}'" rows="10" class="large-text" required><?php echo $editing ? esc_textarea($template->template_html) : ''; ?></textarea>
                    <p>Use placeholder variables within curly braces to dynamically insert field values into the HTML template. For example, if you have a field named 'title', you can use '{title}' as a placeholder in your HTML template. When the template is rendered, '{title}' will be replaced with the actual value of the 'title' field.</p>
                    <p>Here is a step-by-step guide on how to use placeholder variables:</p>
                    <ol>
                        <li><strong>Define Fields:</strong> When creating or editing a template, define the fields you need. Each field should have a unique name and a description.</li>
                        <li><strong>Use Placeholders:</strong> In the HTML Template textarea, use the field names within curly braces as placeholders. For example, if you have a field named 'title', you can use '{title}' in your HTML template.</li>
                        <li><strong>Save Template:</strong> Save the template. The placeholders will be stored as part of the template.</li>
                        <li><strong>Render Template:</strong> When the template is rendered, the placeholders will be replaced with the actual values provided for each field.</li>
                    </ol>
                    <p>Example:</p>
                    <pre>
                        <code>
                            &lt;div class="section"&gt;
                                &lt;h1&gt;{title}&lt;/h1&gt;
                                &lt;p&gt;{description}&lt;/p&gt;
                            &lt;/div&gt;
                        </code>
                    </pre>
                    <p>In this example, '{title}' and '{description}' will be replaced with the values of the 'title' and 'description' fields, respectively.</p>
                </td>

                </tr>
                <tr>
                    <th scope="row"><label for="status">Status</label></th>
                    <td>
                        <select name="status" id="status">
                            <option value="1" <?php selected($editing && $template->status == 1); ?>>Active</option>
                            <option value="0" <?php selected($editing && $template->status == 0); ?>>Inactive</option>
                        </select>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $editing ? 'Update Section' : 'Save Section'; ?>">
            </p>
        </form>

        <h2>Created Templates</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Created Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sections as $section) { ?>
                    <tr>
                        <td><?php echo esc_html($section->template_name); ?></td>
                        <td><?php echo $section->status ? "Active" : "Inactive" ?></td>
                        <td><?php echo esc_html($section->created_at); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=custom_middle_section_templates&edit=' . $section->id); ?>" class="button">Edit</a>
                            <a href="<?php echo admin_url('admin.php?page=custom_middle_section_templates&delete=' . $section->id); ?>" class="button">Delete</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <script>
        (function($) {
            $(document).ready(function() {
                let fieldIndex = <?php if ($editing && $template->template_fields) {
                                        echo count(json_decode($template->template_fields));
                                    } else { ?> 1 <?php } ?>;

                $('.add-field').on('click', function() {
                    const newFieldRow = `
                                    <div class="field-row " style="margin-bottom:10px;">
                                        <div style="display: flex;">
                                            <input name="fields[${fieldIndex}][name]" type="text" placeholder="Field Name for name attribute" class="regular-text " required>
                                            <input name="fields[${fieldIndex}][description]" type="text" placeholder="Field Label" style="margin-left:5px;" class="regular-text " required>
                                            <button type="button" class="button remove-field " style="margin-left: 10px;">&times;</button>
                                        </div>
                                    </div>
                                `;
                    $('#fields-container').append(newFieldRow);
                    fieldIndex++;
                });

                $('#fields-container').on('click', '.remove-field', function() {
                    $(this).closest('.field-row').remove();
                });
            });
        })(jQuery);
    </script>
<?php
}
?>