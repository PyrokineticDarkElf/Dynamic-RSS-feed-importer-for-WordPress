<?php

// Config
$js_config = array(
    // Input Fields found in the RSS feed on the right, mapped name on the left.
    'input_fields' => array(
        'explicit' => array(
            'path' => 'itunes:explicit',
            'type' => 'acf',
        ),
        'listen_time' => array(
            'path' => 'itunes:duration',
            'type' => 'acf',
        ),
        'season' => array(
            'path' => 'itunes:season',
            'type' => 'acf',
        ),
        'episode' => array(
            'path' => 'itunes:episode',
            'type' => 'acf',
        ),
        'embed_code' => array(
            'path' => 'link',
            'type' => 'acf',
        ),
        //'enclosure_url' => array(
        //    'path' => 'enclosure',
        //    'type' => 'acf',
        //),
        'post_title' => array(
            'path' => 'title',
            'type' => 'wp',
        ),
        'post_content' => array(
            'path' => 'description',
            'type' => 'wp',
        ),
        'post_date' => array(
            'path' => 'pubDate',
            'type' => 'wp',
        ),
        'post_date_gmt' => array(
            'path' => 'pubDate',
            'type' => 'wp',
        ),
        'post_type' => array(
            'path' => null,
            'type' => 'wp',
            'overwrite' => 'podcast'
        ),
        'tags_a' => array(
            'path' => 'itunes:episodeType',
            'type' => 'tags',
        ),
        //'tags_b' => array(
        //    'path' => 'itunes:episodeType',
        //    'type' => 'tags',
        //),
        //'category_a' => array(
        //    'path' => 'itunes:episodeType',
        //    'type' => 'category',
        //),
        //'category_b' => array(
        //    'path' => 'itunes:episodeType',
        //    'type' => 'category',
        //),
        'featured_image' => array(
            'path' => 'itunes:image',
            'type' => 'image',
            'attribute' => 'href',
        ),
    ),
);


// Unified modification function
function modifyFieldValues($input_data) {
    foreach ($input_data as $field => $value) {
        switch ($field) {
            case 'type':
                // Titlecase the field 'type'
                $input_data[$field] = ucwords($value);
                break;
            case 'post_date':
                // strtotime the date string
                $input_data[$field] = date("Y-m-d H:i:s", strtotime($value));
                break;
            case 'post_date_gmt':
                // strtotime the date string
                $input_data[$field] = get_gmt_from_date(date("Y-m-d H:i:s", strtotime($value)));
                break;
            case 'explicit':
                // bool the explicit value
                $input_data[$field] = ($value === "yes") ? 1 : 0;
                break;
            case 'embed_code':
                // add iframe wrapper and F&R the string for the 'link' value and store it as 'embed_code'
                $input_data[$field] = '<iframe src="' . str_replace("/episodes/", "/embed/episodes/", $value) . '" height="auto" width="100%" frameborder="0" scrolling="no"></iframe>';
                break;
            // Add more cases as needed
        }
    }
    return $input_data;
}


// Sync Anchor RSS Feed
global $import_rss_episodes;
function import_rss_episodes() {
   	echo "Starting Import...</br>";

    // Check that the function is being called from the right place (admin dashboard)
    if (!is_admin()) {
        echo "'Import RSS episodes' can only be run from the admin dashboard.</br>";
        return;
    }

    // Check that the SimpleXML library is available
    if (!function_exists('simplexml_load_file')) {
        echo "Error: The SimpleXML library is not available.</br>";
        return;
    }
	
	// Override the saved value if a new URL is provided in the form
    if (isset($_POST['rss_feed_url'])) {
        $feed_url = esc_url_raw($_POST['rss_feed_url']);
        update_option('js_rss_feed_url', $feed_url);
    }
	// Get the saved feed URL or use the default URL
	$saved_feed_url = get_option('js_rss_feed_url');

	if (!$saved_feed_url) {
		echo "Error: No 'RSS Feed URL' detected.</br>";
		return;
	}

	$feed = simplexml_load_file($saved_feed_url);
    if (!$feed) {
        echo "Error: Problem loading RSS feed.";
        return;
    }

    process_feed($feed);
}


// Function to process feed
function process_feed($feed) {
    global $js_config;

    foreach ($feed->channel->item as $item) {
        $input_data = array();

        // Process input fields
        foreach ($js_config['input_fields'] as $input_field => $field_config) {
            $feed_key = $field_config['path'];
            $field_type = $field_config['type'];
            $overwrite_key = $field_config['overwrite'] ?? null;
            $attribute = $field_config['attribute'] ?? null;

            // Check if the input_field has a namespace or if it's an overwrite
            if ($feed_key === null && $overwrite_key !== null) {
                // Use the value from 'overwrites' array if it's set
                $input_data[$input_field] = $overwrite_key;
                //echo "Field: $input_field set to: " . $input_data[$input_field] . "<br>";
            } elseif ($attribute != null) {
                $result = $item->xpath($feed_key);
                $input_data[$input_field] = isset($result[0][$attribute]) ? (string) $result[0][$attribute] : '';
                //echo "Field: $input_field set to: " . $input_data[$input_field] . "<br>";
            } elseif (strpos($feed_key, ':') !== false) {
                list($namespace, $key) = explode(':', $feed_key);
                $input_data[$input_field] = (string) $item->children($namespace, true)->$key;
                //echo "Field: $input_field set to: " . $input_data[$input_field] . "<br>";
            } else {
                $input_data[$input_field] = (string) $item->$feed_key;
                //echo "Field: $input_field set to: " . $input_data[$input_field] . "<br>";
            }
        }

        // Apply modifications to specified fields based on global config
        $input_data = modifyFieldValues($input_data);

        // check if the post already exists by matching title
        $existing_post_query = new WP_Query(array(
            'post_type' => $input_data['post_type'],
            'title' => $input_data['post_title'],
        ));

        // Retrieve the post object if it exists, otherwise set to false
        $existing_post = $existing_post_query->have_posts() ? $existing_post_query->posts[0] : false;
        update_or_insert_post($existing_post, $input_data); 
        //echo 'Existing Post' . var_dump($existing_post) . '<br>';
    }
}


// Function to update or insert posts
function update_or_insert_post($existing_post, $input_data) {
    global $js_config;

    // Loop through WP fields and add them to post_data if they exist in input_data
    $wp_data = filterInputData($input_data, 'wp', $js_config['input_fields']);

    // Check if an existing post is available
    $action = $existing_post != false ? 'updated' : 'added';
    // Set the ID if the post exists, otherwise keep it undefined for new post insertion
    $wp_data['ID'] = $existing_post != false ? $existing_post->ID : null;
    // Update or insert the post
    $post_id = $existing_post != false ? wp_update_post($wp_data) : wp_insert_post($wp_data);

    // Loop through ACF fields and add them to custom_fields if they exist in input_data
    $acf_data = filterInputData($input_data, 'acf', $js_config['input_fields']);
    // Update ACF fields
    foreach ($acf_data as $field_key => $field_value) {
        update_field($field_key, $field_value, $post_id);
    }

    // Tags
    $tags_data = filterInputData($input_data, 'tags', $js_config['input_fields']);
    $tags = extractAndMergeTags($tags_data, $js_config['input_fields']);
    // Check if there are tags to process
    if (!empty($tags)) {
        // Convert the tags array to a CSV-like string
        $tags_csv = implode(', ', $tags);

        // Apply tags to the post
        $existing_tags = wp_get_post_terms($post_id, 'post_tag', array('fields' => 'names'));
        $new_tags = array_merge($tags, $existing_tags);
        wp_set_post_terms($post_id, $new_tags, 'post_tag', true);
    }

    // Image upload
    process_image_upload($post_id, $input_data);

    // Log screen
    echo '<img src="' . esc_url($input_data['featured_image']) . '" style="max-width:50px;"></br>';
    echo $input_data['post_title'] . ' - <span class="' . $action . '">Item ' . $action . '.</br>';
}


// Extract items from input_data array based on type
function filterInputData($input_data, $type, $fields_config) {
    $filtered_data = array();
    foreach ($fields_config as $field => $field_config) {
        if ($field_config['type'] === $type) {
            $filtered_data[$field] = isset($input_data[$field]) ? $input_data[$field] : null;
        }
    }
    return $filtered_data;
}


// Function to extract and merge tags from multiple fields
function extractAndMergeTags($input_data, $fields_config) {
    $tags = array();
    foreach ($fields_config as $field => $field_config) {
        if ($field_config['type'] === 'tags' && isset($input_data[$field])) {
            $tags = array_merge($tags, explode(',', $input_data[$field]));
        }
    }
    return array_unique(array_map('trim', $tags));
}


// Function to process images
function process_image_upload($post_id, $input_data) {
    global $js_config;

    // Save the image to the media library
    if ($input_data['featured_image']) {
        if (get_post_thumbnail_id($post_id) == '') {
            $file = array();
            $file['name'] = basename($image);
            $file['tmp_name'] = download_url($image);

            if (is_wp_error($file['tmp_name'])) {
                echo 'Error: Problem downloading image: ' . $file['tmp_name']->get_error_message() . "</br>";
                return; // Stop processing if there's an error
            }
            $image_id = media_handle_sideload($file, $post_id);
            if (is_wp_error($image_id)) {
                echo 'Error: Problem sideloading image: ' . $image_id->get_error_message() . "</br>";
                return; // Stop processing if there's an error
            }
            // Set the image as the featured image for the post
            $result = set_post_thumbnail($post_id, $image_id);
            if ($result === false) {
                echo "Error: Problem setting featured image: " . $result . "</br>";
            }            
        }
    }
}


// Add a custom menu item to the admin menu
add_action('admin_menu', 'register_custom_menu_page');

function register_custom_menu_page() {
    add_menu_page(
        'RSS Import',               // Page title
        'RSS Import',               // Menu title
        'manage_options',           // Capability
        'manual-rss-import',        // Menu slug
        'display_custom_menu_page'  // Callback function to display the page content
    );
}

// Callback function to display the admin page content
function display_custom_menu_page() {
    ?>
    <div class="wrap">
        <h1>RSS Import</h1>
        <p>Click the button below to manually trigger the RSS import.</p>
        <form method="post">
            <?php
            // Use a WordPress nonce for security
            wp_nonce_field('manual_rss_import_nonce', 'manual_rss_import_nonce');
            ?>
			<label for="rss_feed_url">RSS Feed URL:</label>
            <input type="text" name="rss_feed_url" id="rss_feed_url" value="<?php echo esc_attr(get_option('js_rss_feed_url')); ?>">

            <br>
			<br>
            <input type="submit" name="manual_rss_import_button" class="button button-primary" value="Trigger RSS Import">
        </form>

        <?php
        // Check if the button is clicked and the nonce is valid
        if (isset($_POST['manual_rss_import_button']) && wp_verify_nonce($_POST['manual_rss_import_nonce'], 'manual_rss_import_nonce')) {
            // Call your import function
            import_rss_episodes();
        }
        ?>
    </div>
    <?php
}
