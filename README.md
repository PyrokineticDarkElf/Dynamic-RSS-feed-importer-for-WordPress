# Dynamic-RSS-feed-importer-for-WordPress
Work in progress.

This PHP script is designed to import RSS feed items into WordPress as posts. It includes functionality to modify field values, update or insert posts, handle custom fields (Advanced Custom Fields - ACF), process tags, and upload images to the media library.

## Configuration

The configuration is done through the `$js_config` array at the beginning of the script. It defines input fields, their types, paths, and any modifications needed.

The config applied is for a Spotify podcast. They offer an RSS feed for your episodes. This script should be dynamic enough to work with most RSS feeds since all the field mapping is dynamic.

Current issues include:
No category mapping yet.
Multiple images can be uploaded, but there is no mapping for images other than the featured image yet.

## Usage

1. **Admin Menu:** A custom menu item 'RSS Import' is added to the WordPress admin menu.
2. **Manual Trigger:** Visit the 'RSS Import' page in the admin dashboard. Enter the RSS Feed URL and click 'Trigger RSS Import'.

## Functions

- **`modifyFieldValues`**: Unified function to modify field values based on predefined rules.
- **`import_rss_episodes`**: Main function to initiate the RSS feed import. Can be triggered manually.
- **`process_feed`**: Function to process the items from the RSS feed.
- **`update_or_insert_post`**: Function to update or insert posts based on the processed feed items.
- **`filterInputData`**: Function to extract items from `input_data` array based on type.
- **`extractAndMergeTags`**: Function to extract and merge tags from multiple fields.
- **`process_image_upload`**: Function to save the image to the media library.

## Dependencies

- SimpleXML library is required for processing RSS feeds.

