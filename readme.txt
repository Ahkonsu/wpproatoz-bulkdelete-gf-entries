=== Gravity Forms WPProAtoZ Bulk Delete for Individual Gravity Forms ===
Contributors: wpproatoz
Tags: gravity forms, bulk delete, spam cleanup, form entries, wordpress plugin
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires Plugins: gravityforms

A powerful tool to bulk delete Gravity Forms entries for a specific form, ideal for cleaning up spam or unwanted submissions.

== Description ==

The **Gravity Forms WPProAtoZ Bulk Delete** plugin provides an easy-to-use interface to remove large numbers of entries from individual Gravity Forms in your WordPress site. Whether you're dealing with spam overload or simply need to clear out old submissions, this plugin lets you target a specific form, choose entry statuses (active, spam, trash), and delete entries in manageable batches with real-time progress tracking and a stop option.

### Features
- Select a specific Gravity Form to delete entries from.
- Choose which entry statuses to delete: Active, Spam, Trash, or any combination.
- Adjustable batch size and pause time to optimize server performance.
- Real-time progress bar and status updates via AJAX with a loading spinner.
- Stop the deletion process mid-run with confirmation.
- Dry Run mode to simulate deletion without removing entries.
- Logging of deletion activities for auditing.
- Entry count preview before starting the process.
- Reset settings to defaults with a single click.
- Requires Gravity Forms plugin to function.

Developed by WPProAtoZ, this plugin is perfect for site admins managing high-volume forms.

== Installation ==

1. Upload the `wpproatoz-bulkdelete-gf-entries` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure the Gravity Forms plugin is installed and active.
4. Navigate to **Settings > GF Bulk Delete** in your WordPress admin dashboard to configure and use the tool.

== Frequently Asked Questions ==

= Does this plugin delete entries from all forms? =
No, it only deletes entries from the form you select in the settings.

= Can I stop the deletion process once it starts? =
Yes, a "Stop Bulk Delete" button appears during the process, allowing you to halt it after the current batch completes with a confirmation prompt.

= What is Dry Run mode? =
Dry Run mode simulates the deletion process without actually removing entries, letting you test settings safely.

= Where can I see the logs? =
Logs are available in the "Logs" tab, showing details of each deletion run.

= What happens if Gravity Forms is not installed? =
The plugin will display a message indicating that Gravity Forms is required and won’t function without it.

= Is there a risk of server overload? =
The plugin processes entries in batches with a configurable pause time between batches to minimize server load. Adjust these settings based on your server’s capacity.

== Screenshots ==

1. The GF Bulk Delete settings page with form selection and options.
2. The bulk delete process in action with progress bar and stop button.

== Changelog ==

= 1.2 =
* Added logging functionality to track deletion activities.
* Introduced Dry Run mode to simulate deletions.
* Enhanced visual feedback with a loading spinner and stop confirmation.
* Added a Reset Settings button to revert to defaults.
* Included an entry count preview before starting the process.
* Released: April 01, 2025

= 1.1 =
* Added AJAX-based deletion with real-time progress bar and stop functionality.
* Improved performance for large entry sets.
* Released: April 01, 2025

= 1.0 =
* Initial release with basic bulk delete functionality.
* Released: March 2025

== Upgrade Notice ==

= 1.2 =
This update adds logging, dry run mode, improved visuals, a reset button, and entry count preview for better control and usability. Highly recommended for all users.

= 1.1 =
This update introduces AJAX processing, a progress bar, and a stop feature for better control and performance. Update recommended for all users.

== Support ==
For support, visit [WPProAtoZ.com](https://wpproatoz.com) or raise an issue on [GitHub](https://github.com/Ahkonsu/wpproatoz-bulkdelete-gf-entries).