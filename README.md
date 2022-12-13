# Hotfix for Trac 56970

Moves global stylesheet from transient to `WP_Object_Cache`, and resolves an inline CSS issue related to Gallery blocks when upgrading to WordPress 6.1.1. Tested with starting version of 5.9.5 and 6.0.3.

See [Trac 56970](https://core.trac.wordpress.org/ticket/56970) for additional details.

**An alternate approach to resolving this issue is proposed in https://github.com/ironprogrammer/wp-test-56970.**

## Purpose
If testing with this plugin is shown to resolve the issues reported in [Trac 56970](https://core.trac.wordpress.org/ticket/56970), then the [proposed fix](https://github.com/WordPress/wordpress-develop/pull/3712) could be included in a future release.

## Manual Installation
Copy `hotfix-56970.php` to your `wp-content/plugins/` folder, and activate it on the *Plugins > Installed Plugins* screen.

## Testing
Testing requires starting with a standard WordPress install of version 5.9 through 6.0.3. Steps adapted from [Trac 56970#comment:42](https://core.trac.wordpress.org/ticket/56970#comment:42).

1. Create a new site using WordPress 5.9 through 6.0.3.
2. Ensure that `WP_DEBUG` is not enabled. Debug mode causes caches to be skipped, so won’t replicate the issue.
3. Navigate to *Appearance > Themes* and activate **Twenty Twenty-One**.
4. Navigate to *Posts > Add New*. Insert a Gallery block and add three images.
5. Save the post and view it on the frontend. Confirm that the images are displayed in three columns.
6. [Install and activate the hotfix plugin](#manual-installation), if you have not already done so.
7. Upgrade the site to WordPress 6.1.1.
8. View the same post from Step 5, and confirm that it displays the images in three columns on the frontend.
9. Navigate to *Posts > All Posts* and edit the post. Confirm that the images in the block editor are displayed in 3 columns and no errors occur.

## Reporting Issues
Please open an issue in [this test plugin repository](https://github.com/ironprogrammer/wp-hotfix-56970/issues).
