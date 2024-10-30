<?php

// Ajax functions
add_action('wp_ajax_imgMD_dbAddPresetEntry', 'imgMD_dbAddPresetEntry');
add_action('wp_ajax_imgMD_dbReturnPresetEntry', 'imgMD_dbReturnPresetEntry');
add_action('wp_ajax_imgMD_dbPresetEntryExists', 'imgMD_dbPresetEntryExists');
add_action('wp_ajax_imgMD_dbRemovePresetEntry', 'imgMD_dbRemovePresetEntry');
add_action('wp_ajax_imgMD_dbAddLocationEntry', 'imgMD_dbAddLocationEntry');
add_action('wp_ajax_imgMD_dbLocationEntryExists', 'imgMD_dbLocationEntryExists');
add_action('wp_ajax_imgMD_dbRemoveLocationEntry', 'imgMD_dbRemoveLocationEntry');

function imgMD_dbAddPresetEntry() 
{
    global $wpdb;
    $presets_table_name = $wpdb->prefix . 'imgMD_presets';
    $links_table_name = $wpdb->prefix . 'imgMD_links';

    $entry_name = sanitize_text_field($_POST['imgMD_entry_name']);
    $entry_alt_txt = sanitize_text_field($_POST['imgMD_entry_alt_txt']);
    $entry_phone = sanitize_text_field($_POST['imgMD_entry_phone']);
    $entry_location = sanitize_text_field($_POST['imgMD_entry_location']);

    // Grabs all the links.
    $links = preg_split("/\r\n|\n|\r/", sanitize_textarea_field($_POST['imgMD_links']));

    // Inserts the new entry into the preset database.
    $wpdb->insert($presets_table_name, array(
        'imgMD_entry_name' => $entry_name,
        'imgMD_entry_alt_txt' => $entry_alt_txt,
        'imgMD_entry_phone' => $entry_phone,
        'imgMD_entry_location' => $entry_location
    ));

    // Inserts all the links into the link database with 
    // the corresponding preset id.
    foreach($links as $link) 
    {
        $sql = "INSERT INTO $links_table_name (imgMD_link_text, imgMD_entry_id) VALUES 
            ('$link', (SELECT imgMD_entry_id FROM $presets_table_name WHERE 
            imgMD_entry_name='".$entry_name."'));";

        $wpdb->query($sql);
    };

    // This is required to terminate immediately and 
    // return a proper response for ajax calls.
    wp_die(); 
}

function imgMD_dbReturnPresetEntry() 
{
    global $wpdb;
    $presets_table_name = $wpdb->prefix . 'imgMD_presets';
    $links_table_name = $wpdb->prefix . 'imgMD_links';

    $entry_name = sanitize_text_field($_POST['imgMD_entry_name']);

    // Grabs the desired preset entry along with the links associated.
    $sql = "SELECT * FROM $presets_table_name LEFT JOIN $links_table_name ON 
        $presets_table_name.imgMD_entry_id = $links_table_name.imgMD_entry_id WHERE 
        imgMD_entry_name = '".$entry_name."';";
    $results = $wpdb->get_results($sql);

    // Return it in a friendly json format.
    echo json_encode($results);

    wp_die();
}

function imgMD_dbPresetEntryExists() 
{
    global $wpdb;
    $presets_table_name = $wpdb->prefix . 'imgMD_presets';

    $entry_name = sanitize_text_field($_POST['imgMD_entry_name']);

    // Checks to see if the preset entry exists in the database.
    $sql = "SELECT * FROM $presets_table_name WHERE imgMD_entry_name = 
        '".$entry_name."';";
    echo($wpdb->query($sql));

    wp_die();
}

function imgMD_dbRemovePresetEntry() {
    global $wpdb;
    $presets_table_name = $wpdb->prefix . 'imgMD_presets';
    $links_table_name = $wpdb->prefix . 'imgMD_links';

    $entry_name = sanitize_text_field($_POST['imgMD_entry_name']);

    // Delete entry links first since they are the foreign key.
    $sql = "DELETE FROM $links_table_name WHERE imgMD_entry_id = (SELECT imgMD_entry_id 
        FROM $presets_table_name WHERE imgMD_entry_name='".$entry_name."');";
    $wpdb->query($sql);

    // Delete the preset entry.
    $wpdb->delete($presets_table_name, 
                   array('imgMD_entry_name' => $entry_name));
    
    wp_die();
}

function imgMD_dbAddLocationEntry() {
    global $wpdb;
    $locations_table_name = $wpdb->prefix . 'imgMD_locations';

    $location_name = sanitize_text_field($_POST['imgMD_location_name']);

    $wpdb->insert($locations_table_name, array(
        'imgMD_location_name' => $location_name,
        'imgMD_latitude' => (int) $_POST['imgMD_latitude'],
        'imgMD_longitude' => (int) $_POST['imgMD_longitude']
    ));

    // Once it inserts the new location it returns an updated list of locations.
    $results = $wpdb->get_col(
        "SELECT imgMD_location_name FROM $locations_table_name;");
    echo json_encode($results);

    wp_die();
}

function imgMD_dbLocationEntryExists() {
    global $wpdb;
    $locations_table_name = $wpdb->prefix . 'imgMD_locations';

    $location_name = sanitize_text_field($_POST['imgMD_location_name']);

    // Checks to see if the location entry exists.
    $sql = "SELECT * FROM $locations_table_name WHERE imgMD_location_name =
        '".$location_name."';";
    echo($wpdb->query($sql));

    wp_die();
}

function imgMD_dbRemoveLocationEntry() {
    global $wpdb;
    $locations_table_name = $wpdb->prefix . 'imgMD_locations';

    $location_name = sanitize_text_field($_POST['imgMD_location_name']);

    // Remove a location from the database.
    $wpdb->delete($locations_table_name, 
                  array('imgMD_location_name' => $location_name));

    // Return an array of locations still in the database.
    $results = $wpdb->get_col(
        "SELECT imgMD_location_name FROM $locations_table_name;");
    echo json_encode($results);
    
    wp_die();
}

?>