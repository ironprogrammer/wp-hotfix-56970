# Hotfix for Trac 56970

Cleans `theme.json` cache, and resolves an inline CSS issue related to Gallery blocks when upgrading to WordPress 6.1.1 from 5.8 through 6.0.3. Global styles transient-based caching was introduced in 5.8.

See <a href="https://core.trac.wordpress.org/ticket/56970">Trac 56970</a> for additional details.

## Installation
Copy `hotfix-56970.php` to your `wp-content/plugins/` folder, and activate it on the *Plugins > Installed Plugins* screen.

## Testing
Testing requires starting with a standard WordPress install of version 5.8 through 6.0.3. Steps adapted from [Trac 56970#comment:42](https://core.trac.wordpress.org/ticket/56970#comment:42).

1. Create a new site using WordPress 5.9 through 6.0.3.
2. Navigate to *Appearance > Themes* and activate **Twenty Twenty-One**.
3. Navigate to *Settings > Permalinks* and select anything *except for* the "Plain" structure.
4. Navigate to *Posts > Add New*. Insert a Gallery block and add three images.
5. Save the post and view it on the frontend. Confirm that the images are displayed in 3 columns.
6. [Install and activate the hotfix plugin](#installation).
7. Upgrade the site to WordPress 6.1.1.
8. View the same post from Step 4, and confirm that it displays the images in 3 columns on the frontend.
9. Navigate to *Posts > All Posts* and edit the post. Confirm that the images in the block editor are displayed in 3 columns and no errors occur.

## Reporting Issues
Please open an issue in [this hotfix plugin repository](https://github.com/ironprogrammer/wp-hotfix-56970/issues).
