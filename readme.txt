=== Missed Schedule Post Publisher ===
Contributors: ufukart
Donate link: https://www.paypal.com/donate/?business=53EHQKQ3T87J8&no_recurring=0&currency_code=USD
Tags: schedule, missed schedule, trigger, scheduled post, missed scheduled posts
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 5.6
Stable tag: 1.0.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically detects and publishes missed scheduled posts. Set your preferred run time and let it handle the rest.

== Description ==
= WordPress Missed Scheduled Posts Publisher =

Never miss a scheduled post again! This plugin ensures your scheduled posts are published on time, even on low-traffic websites.

== Why Use This Plugin? ==
By default, WordPress relies on visitor activity to trigger scheduled posts. If your site has low traffic or long gaps between visits, some posts may end up with a "Missed Schedule" status instead of being published.

Missed Scheduled Posts Publisher runs automatically at your chosen interval, ensuring all scheduled posts go live as expected—without requiring manual intervention.

== Key Features: ==
✅ Automatically publishes missed scheduled posts
✅ Customizable execution time—set it and forget it
✅ Ideal for low-traffic blogs and websites
✅ Lightweight—won’t impact bandwidth or analytics

Simply install, configure, and let the plugin handle the rest! 🚀

== Installation ==
= Download & Install: =
1. Go to Plugins > Add New in your WordPress dashboard.
1. Search for "Missed Scheduled Posts Publisher" or upload the plugin ZIP file manually.
1. Click Install Now, then Activate the plugin.
= Configure Settings: =
1. Navigate to Settings > Missed Schedule Post Publisher.
1. Choose how often the plugin should check for missed posts (5, 10, 15, 20, 30, or 60 minutes).
1. Click Save Settings.
= That’s it! =
The plugin will now automatically check and publish any missed scheduled posts.
You can update the settings anytime from the plugin settings page.


== Frequently Asked Questions ==

= What does this plugin do? =
This plugin detects and automatically publishes scheduled posts that WordPress failed to publish due to the "Missed Schedule" issue.

= Why do scheduled posts sometimes fail to publish? =
WordPress relies on site visits to trigger scheduled tasks. If there’s low traffic or long gaps between visits, scheduled posts may be missed. This plugin ensures posts are published on time by running at a set interval.

= How often does the plugin check for missed posts? =
You can set the execution interval from the plugin settings. Options include 5, 10, 15, 20, 30, or 60 minutes.

= Does this plugin slow down my website? =
No. The plugin is lightweight and only runs a quick database check at the defined interval. It does not affect site performance or analytics.

= Does it work with custom post types? =
Currently, the plugin only checks and publishes standard WordPress posts (post post type). Future updates may include support for custom post types.

= Can I use this plugin on a high-traffic website? =
Yes, but it is primarily designed for low-traffic websites where WordPress’s default scheduling system may fail.

= How do I configure the plugin? =
Go to Settings > Missed Schedule Post Publisher, choose your preferred interval, and save. That’s it! The plugin will take care of the rest.

= Does this plugin require WP-Cron? =
No, it does not rely on WP-Cron. Instead, it triggers based on visitor activity, ensuring missed posts are checked and published when a user visits your site.

= Is this plugin compatible with caching plugins? =
Yes. However, if your caching plugin aggressively caches pages, ensure that database queries are not cached, so the plugin can detect missed posts in real-time.

= What should I do if the plugin isn’t working? =
* Ensure your scheduled posts have a "Missed Schedule" status.
* Check the plugin settings to confirm the execution time.
* Disable any conflicting plugins that may interfere with post publishing.
* If the issue persists, try deactivating and reactivating the plugin.


== Changelog ==

= 1.0.5 =
### Major Improvements
- Complete code refactoring using OOP architecture
- Enhanced security with proper input sanitization and nonce verification
- Implemented WordPress cron integration for reliable scheduling
- Added full internationalization support (i18n)
- Improved admin interface with better user feedback
- Tested up to PHP 8.4

### Security
- Fixed potential SQL injection vulnerability using `$wpdb->prepare()`
- Added capability checks for admin operations
- Strengthened nonce verification process

### Performance
- Optimized database queries for checking scheduled posts
- Reduced unnecessary hook registrations
- Improved memory usage with better variable handling

### Bug Fixes
- Fixed timezone handling for post scheduling
- Resolved potential race conditions in post publishing
- Fixed admin notice display issues

= 1.0.4 =
- Add Settings shortcut to plugins page
- Added donate link

= 1.0.3 =
- Fully compatible with PHP 5.6 and above. Tested up to: PHP 8.2
- Few minor improvements

= 1.0.2 =
- Bug Fixed. (Undefined Index Warning)

= 1.0.1 =
- Removed from Toolbar Menu
- Menu moved under the setting

= 1.0 =
- Initial release