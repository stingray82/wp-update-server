<?php

// Function to extract metadata from the plugin ZIP file (locally, not via web)
function get_plugin_metadata_from_zip($zip_path, $plugin_file_name) {
    // Open the zip file from the local file system
    $zip = new ZipArchive;
    $plugin_data = array(
        'Version' => 'Unknown',
        'Name' => 'Unknown',
        'Author' => 'Unknown',
        'Description' => 'No description available',
        'RequiresWP' => 'Unknown',
        'TestedWP' => 'Unknown',
    );

    if ($zip->open($zip_path) === TRUE) {
        // Search for the main plugin file in the zip
        if (($index = $zip->locateName($plugin_file_name)) !== false) {
            $plugin_file = $zip->getFromIndex($index);

            // Parse the headers from the plugin file
            if (!empty($plugin_file)) {
                $headers = array(
                    'Version'       => 'Version',
                    'Name'          => 'Plugin Name',
                    'Author'        => 'Author',
                    'Description'   => 'Description',
                    'RequiresWP'    => 'Requires at least',
                    'TestedWP'      => 'Tested up to'
                );

                foreach ($headers as $key => $header) {
                    if (preg_match('/' . preg_quote($header, '/') . ':\s*(.+)$/mi', $plugin_file, $matches)) {
                        $plugin_data[$key] = trim($matches[1]);
                    }
                }
            }
        }
        $zip->close();
    } else {
        error_log("Failed to open ZIP file: " . $zip_path);
    }

    return $plugin_data; // Return an array of metadata
}

// Define the custom plugins list
$custom_plugins = array(
   'my-custom' => array(                               // Slug can be anything (Should be what wordpress will be looking for)
        'zip_path'  => __DIR__ . '/packages/my-custom-plugin.zip', // Path to the new plugin's ZIP file
        'main_file' => 'my-custom-plugin/my-custom-plugin.php',    // Path to the new plugin’s main file inside the ZIP
    ),
    // Add more plugins here
);


// Get the slug from the request
$slug = isset($_GET['slug']) ? $_GET['slug'] : null;

if ($slug && array_key_exists($slug, $custom_plugins)) {
    $plugin_info = $custom_plugins[$slug];

    // Get dynamic metadata from the local plugin ZIP
    $plugin_data = get_plugin_metadata_from_zip($plugin_info['zip_path'], $plugin_info['main_file']);

    // Make sure homepage is dynamically fetched or set to a default value
    $homepage = $plugin_data['homepage'] ?? 'https://default-homepage.com';

    // Serve the JSON response with dynamic metadata
    echo json_encode(array(
        'slug'        => $slug,
        'name'        => $plugin_data['Name'], // Dynamically fetched name
        'new_version' => $plugin_data['Version'], // Dynamically fetched version
        'package'     => '[Your site here]' . basename($plugin_info['zip_path']), // remplace with site url
        'url'         => $homepage,  // Dynamically fetched or default homepage
        'requires'    => $plugin_data['RequiresWP'], // WordPress version required
        'tested'      => $plugin_data['TestedWP'], // Last tested WordPress version
        'version'     => $plugin_data['Version'], // Dynamically fetched version
        'author'      => $plugin_data['Author'], // Dynamically fetched author
        'download_url'=> '[Your site here]' . basename($plugin_info['zip_path']), // Correct download URL for the zip
        'sections'    => array(
            'description' => $plugin_data['Description'], // Dynamically fetched description
        ),
    ));
} else {
    // Return a 404 or appropriate error if not found
    header("HTTP/1.0 404 Not Found");
    echo "Plugin not found.";
}
exit;
