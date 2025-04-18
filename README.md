# Bulk Delete Gravity Forms Entries
# Gravity Forms WPProAtoZ Bulk Delete

![Plugin Version](https://img.shields.io/badge/version-1.3-blue.svg) ![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg) ![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue.svg) ![License](https://img.shields.io/badge/license-GPLv2-green.svg)

A powerful WordPress plugin to bulk delete entries from individual Gravity Forms, perfect for cleaning up spam or unwanted submissions.

---

## Overview

The **Gravity Forms WPProAtoZ Bulk Delete** plugin provides an intuitive tool for WordPress administrators to remove large volumes of entries from a specific Gravity Form. Whether you're tackling a spam flood or clearing out old data, this plugin offers customizable batch processing, real-time progress tracking, a stop feature, dry run simulation, and detailed logging�all accessible from your WordPress dashboard.

Developed by [WPProAtoZ](https://wpproatoz.com), this plugin integrates seamlessly with Gravity Forms to streamline form management.

---

## Features

- **Targeted Deletion**: Delete entries from a single, selected Gravity Form without affecting others.
- **Status Selection**: Choose to delete Active, Spam, Trash entries, or any combination.
- **Customizable Processing**: Adjust batch size (default: 250) and pause time (default: 15 seconds) to suit your server�s capacity.
- **Real-Time Monitoring**: Track progress with a visual progress bar, loading spinner, and detailed status updates via AJAX.
- **Stop Control**: Halt the deletion process mid-run with a confirmation prompt.
- **Dry Run Mode**: Simulate deletions without removing entries for safe testing.
- **Logging**: Record deletion activities in a log file (`bulk-delete-log.txt`) for auditing.
- **Entry Count Preview**: See the total entries to be deleted before starting.
- **Reset Settings**: Revert to default settings with one click.
- **Robust Error Handling**: Includes AJAX retries (up to 3 attempts) and extended execution time (5 minutes per batch) for large datasets.
- **Dependency**: Requires the Gravity Forms plugin.

---

## Installation

1. **Download**: Get the latest release from the [Releases](https://github.com/Ahkonsu/wpproatoz-bulkdelete-gf-entries/releases) page.
2. **Upload**: In your WordPress admin, go to `Plugins > Add New > Upload Plugin`, and upload the `.zip` file.
3. **Activate**: Activate the plugin via the `Plugins` menu.
4. **Verify Dependency**: Ensure Gravity Forms is installed and active.
5. **Configure**: Navigate to `Settings > GF Bulk Delete` to start using the tool.

Alternatively, clone this repository into your `/wp-content/plugins/` directory and activate it:
```bash
git clone https://github.com/Ahkonsu/wpproatoz-bulkdelete-gf-entries.git wpproatoz-bulkdelete-gf-entries
cd wpproatoz-bulkdelete-gf-entries