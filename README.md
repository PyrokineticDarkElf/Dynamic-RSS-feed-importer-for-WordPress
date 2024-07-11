# Dynamic-RSS-feed-importer-for-WordPress
Work in progress. Use at your own risk!

This PHP script is designed to import RSS feed items into WordPress as posts. It includes functionality to modify field values, update or insert posts, handle custom fields (Advanced Custom Fields - ACF), process tags, and upload images to the media library.

## Configuration

The configuration is done through the `$js_config` array at the beginning of the script. It defines input fields, their types, paths, and any modifications needed.

The configuration provided in this script is tailored for a Spotify podcast. They offer an RSS feed for episodes, and this script aims to be dynamic enough to work with most RSS feeds due to its dynamic field mapping.

Input Fields
```
'input_fields' => array(
    'explicit' => array(
        'path' => 'itunes:explicit',
        'type' => 'acf',
    ),
    // ... (other fields)
    'featured_image' => array(
        'path' => 'itunes:image',
        'type' => 'image',
        'attribute' => 'href',
    ),
),
```
- Key: A unique identifier for the field.
- Path: The XPath expression or RSS feed element path where the data is located. This allows dynamic navigation of the XML structure.
- Type: Specifies the type of data and how it should be processed ('acf' for Advanced Custom Fields, 'wp' for WordPress fields, 'tags' for tags, 'image' for images, etc.).
- Attribute (Optional): If the data is an attribute of an XML element, specify the attribute name. Useful for extracting attributes like 'href' from an image element.

Dynamic Modification
```
// Apply modifications to specified fields based on global config
$input_data = modifyFieldValues($input_data);
```
The modifyFieldValues function allows for unified modifications. It iterates through each field, applying specific rules. You can add more cases to handle various modifications according to your needs.

In summary, the dynamic mapping approach allows the script to be highly adaptable, making it suitable for a wide range of RSS feeds with different structures and content types. Users only need to configure the $js_config array to match their specific RSS feed structure.

Current issues include:

- No category mapping yet.
- Multiple images can be uploaded, but there is no mapping for images other than the featured image yet.

## Usage

1. Download the `dynamic-rss-feed-importer.php` file and add it to your child theme.
2. Add the following to your functions.php file in your child theme:
```
// Anchor RSS Sync
require_once( __DIR__ . '/dynamic-rss-feed-importer.php');
```
3. **Admin Menu:** A custom menu item 'RSS Import' is added to the WordPress admin menu.
4. **Manual Trigger:** Visit the 'RSS Import' page in the admin dashboard. Enter the RSS Feed URL and click 'Trigger RSS Import'.

## Functions
- modifyFieldValues($input_data): A unified function to modify field values based on predefined rules. Add more cases as needed for specific modifications.
- import_rss_episodes(): The main function to initiate the RSS feed import. Can be triggered manually from the admin dashboard.
- process_feed($feed): Function to process the items from the RSS feed. It utilizes the configuration defined in $js_config.
- update_or_insert_post($existing_post, $input_data): Function to update or insert posts based on the processed feed items. It handles both WordPress fields and Advanced Custom Fields (ACF).
- filterInputData($input_data, $type, $fields_config): Function to extract items from the input_data array based on the specified type (e.g., 'wp', 'acf', 'tags').
- extractAndMergeTags($input_data, $fields_config): Function to extract and merge tags from multiple fields. Useful for handling tags from different sources.
- process_image_upload($post_id, $input_data): Function to save the image to the media library. It checks if a featured image is set and uploads it if necessary.

## Dependencies

- SimpleXML library is required for processing RSS feeds.

<a href="https://www.buymeacoffee.com/Invulnerable.Orc"><img src="https://img.buymeacoffee.com/button-api/?text=Buy me a coffee&emoji=&slug=Invulnerable.Orc&button_colour=FFDD00&font_colour=000000&font_family=Cookie&outline_colour=000000&coffee_colour=ffffff" /></a>
