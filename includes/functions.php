<?php
/*
 * Add my new menu to the Admin Control Panel
 */
// Hook the 'admin_menu' action hook, run the function named 'mfp_Add_My_Admin_Link()'
// Add a menu item
function csv_menu() {
  add_menu_page(
    'CSV Upload', // Title of the page
    'CSV Price Update', // Text to show on the menu link
    'manage_options', // Capability requirement to see the link
    'my-custom-plugin',
    'csv_upload_page'
  );
}
add_action('admin_menu', 'csv_menu');

// Display the settings page
function csv_upload_page() {
  ?>
  <div class="wrap">
      <h1>Role Price Setting</h1>
      <form method="post" enctype="multipart/form-data">
          <?php
          settings_fields('my_custom_plugin_settings_group');
          do_settings_sections('my-custom-plugin');
          ?>
          <h2>Upload Files</h2>
          <input type="file" name="my_custom_files[]" multiple />
          <p class="description">Upload CSV.</p>
          <?php
          submit_button('Upload Files');
          ?>
      </form>
  </div>
  <?php

  // Handle file uploads after form submission
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['my_custom_files'])) {
    my_custom_plugin_handle_file_uploads();
  }
}

function my_custom_plugin_handle_file_uploads() {
  $uploaded_files = $_FILES['my_custom_files'];
  $myJson = new stdClass(); 

  if (empty($uploaded_files['name'][0])) {
      echo "<p style='color: red;'>No files uploaded.</p>";
      return;
  }

  $upload_dir = wp_upload_dir();
  $allowed_types = ['text/csv'];

  foreach ($uploaded_files['name'] as $key => $filename) {
    if ($uploaded_files['error'][$key] !== UPLOAD_ERR_OK) {
        echo "<p style='color: red;'>Error uploading file: $filename.</p>";
        continue;
    }

    $file_type = $uploaded_files['type'][$key];
    if (!in_array($file_type, $allowed_types)) {
        echo "<p style='color: red;'>Invalid file type: $filename. Allowed type only CSV.</p>";
        continue;
    }

    $tmp_name = $uploaded_files['tmp_name'][$key];
    $destination = $upload_dir['path'] . '/' . basename($filename);

    if (move_uploaded_file($tmp_name, $destination)) {
      echo "<p style='color: green;'>File uploaded successfully: $filename.</p>";
      if (($handle = fopen($destination, "r")) !== FALSE) {
        $rowCount = 0;
        $iSKU = '';
        $iPrice = '';
        while (($data = fgetcsv($handle, 1000, ",", "\"", "\\")) !== FALSE) {
          if ($iSKU == '' || $iPrice == '') {
            for($i = 0; $i < count($data); $i++) {
              if (strtolower($data[$i]) == 'sku') {
                $iSKU = $i;
              }
    
              if (strtolower($data[$i]) == 'reseller price' || strtolower($data[$i]) == 'harga reseller') {
                $iPrice = $i;
              }
            }    
          }

          if ($rowCount > 4) {
            $sku = $data[$iSKU];
            $retailPrice = $data[$iPrice];
            
            if ($sku && $retailPrice) {
              $finalPrice = intval(str_replace(".", "", str_replace("Rp","", $retailPrice)));
              if ($finalPrice > 0) {
                $myJson->$sku = intval($finalPrice);
              }
            }  
          }    
          $rowCount++;
        }
        fclose($handle);
        wp_delete_file( $destination );
      } else {
        echo "<p style='color: red;'>Failed to check csv file: $filename.</p>";
      }
    } else {
        echo "<p style='color: red;'>Failed to upload file: $filename.</p>";
    }
  }

  $rolePrice = new stdClass(); 
  $rolePrice->reseller = $myJson; 

  $json_data = json_encode($rolePrice, JSON_PRETTY_PRINT);
  $json_file_path = plugin_dir_path( __DIR__ ) . 'includes/result.json';

  if (file_put_contents($json_file_path, $json_data)) {
      echo '<p style="color: green;">JSON file created successfully</p>';
  } else {
      echo '<p style="color: red;">Failed to create JSON file.</p>';
  }
}


// // Register settings
// function my_custom_plugin_settings() {
//   register_setting('my_custom_plugin_settings_group', 'my_custom_option');

//   add_settings_section(
//       'my_custom_plugin_settings_section',
//       'Settings',
//       null,
//       'my-custom-plugin'
//   );

// }
// add_action('admin_init', 'my_custom_plugin_settings');
