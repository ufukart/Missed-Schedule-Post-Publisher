<?php
/*
 * Plugin Name: Missed Schedule Post Publisher
 * Description: Publishes missed scheduled posts automatically.
 * Plugin URI: https://www.zumbo.net/missed-schedule-post-publisher-wordpress-plugin/
 * Version: 2.0
 * Author: UfukArt
 * Author URI: https://www.zumbo.net
 * Text Domain: missed-schedule-post-publisher
 * Domain Path: /languages/
 * License: GPL2
 */

namespace MissedSchedulePostPublisher;

defined('ABSPATH') || exit;

final class Missed_Schedule_Post_Publisher {

    const OPTION_EXECUTE_TIME = 'mspp_execute_time';
    const OPTION_LAST_EXECUTE = 'mspp_last_execute_time';
    const DEFAULT_INTERVAL    = 15;
    const CRON_HOOK           = 'mspp_check_posts';
    const CRON_INTERVAL_KEY   = 'mspp_custom_interval';
    const BATCH_LIMIT         = 10;

    public function __construct() {
        add_action('init', [$this, 'load_textdomain']);

        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        add_filter('cron_schedules', [$this, 'register_cron_interval']);
        add_action(self::CRON_HOOK, [$this, 'publish_missed_posts']);

        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'failsafe_cron']);
    }

    /* -------------------------------------------------------------------------
     * Initialization & I18n
     * ---------------------------------------------------------------------- */

    public function load_textdomain() {
        load_plugin_textdomain(
            'missed-schedule-post-publisher',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /* -------------------------------------------------------------------------
     * Activation / Deactivation
     * ---------------------------------------------------------------------- */

    public function activate() {
        update_option(self::OPTION_EXECUTE_TIME, self::DEFAULT_INTERVAL);
        update_option(self::OPTION_LAST_EXECUTE, time());

        wp_clear_scheduled_hook(self::CRON_HOOK);
        
        wp_schedule_event(
            time() + 60,
            self::CRON_INTERVAL_KEY,
            self::CRON_HOOK
        );
    }

    public function deactivate() {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    /* -------------------------------------------------------------------------
     * Cron
     * ---------------------------------------------------------------------- */

    public function register_cron_interval($schedules) {
        $interval = max(1, (int) get_option(self::OPTION_EXECUTE_TIME, self::DEFAULT_INTERVAL));

        $schedules[self::CRON_INTERVAL_KEY] = [
            'interval' => $interval * 60,
            'display'  => sprintf(
                /* translators: %d: number of minutes */
                __('Every %d Minutes (MSPP)', 'missed-schedule-post-publisher'),
                $interval
            ),
        ];

        return $schedules;
    }

    public function failsafe_cron() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(
                time() + 60,
                self::CRON_INTERVAL_KEY,
                self::CRON_HOOK
            );
        }
    }

    /* -------------------------------------------------------------------------
     * Core Logic
     * ---------------------------------------------------------------------- */

    public function publish_missed_posts() {
		
		$lock_key = 'mspp_running_lock';
		if ( get_transient( $lock_key ) ) {
			return;
		}
		set_transient( $lock_key, 1, 60 );
		
        global $wpdb;

        $now = current_time('mysql', true); // GMT

        // (post, page, product, event vb.)
        $args = [
            'public' => true,
        ];
        $post_types = get_post_types($args, 'names');
         
        if (empty($post_types)) {
            $post_types = ['post', 'page'];
        }

        $placeholders = implode(', ', array_fill(0, count($post_types), '%s'));

        $sql = "SELECT ID
                FROM {$wpdb->posts}
                WHERE post_status = 'future'
                AND post_date_gmt <= %s
                AND post_type IN ($placeholders)
                ORDER BY post_date_gmt ASC
                LIMIT %d";

        $params = array_merge([$now], array_values($post_types), [self::BATCH_LIMIT]);
        
        $post_ids = $wpdb->get_col($wpdb->prepare($sql, $params));

        if (!empty($post_ids)) {
            foreach ($post_ids as $post_id) {
                $result = wp_publish_post( (int) $post_id );

                if (is_wp_error($result)) {
                    error_log('MSPP Error: Failed to publish post ' . $post_id . ' - ' . $result->get_error_message());
                }
            }
        }

        update_option(self::OPTION_LAST_EXECUTE, time());
		
		delete_transient( $lock_key );
    }

    /* -------------------------------------------------------------------------
     * Admin UI
     * ---------------------------------------------------------------------- */

    public function add_admin_menu() {
        add_options_page(
            __('Missed Schedule Post Publisher', 'missed-schedule-post-publisher'),
            __('Missed Schedule', 'missed-schedule-post-publisher'),
            'manage_options',
            'missed-schedule-post-publisher',
            [$this, 'render_admin_page']
        );
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'missed-schedule-post-publisher'));
        }

        if (isset($_POST['action']) && $_POST['action'] === 'update') {
            $this->handle_settings_update();
        }

        $interval = (int) get_option(self::OPTION_EXECUTE_TIME, self::DEFAULT_INTERVAL);
        $last_run = (int) get_option(self::OPTION_LAST_EXECUTE, 0); // Varsayılan 0
        $next_run = wp_next_scheduled(self::CRON_HOOK);
        
        settings_errors('mspp_messages');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Missed Schedule Post Publisher Settings', 'missed-schedule-post-publisher'); ?></h1>
				<?php if ( defined('DISABLE_WP_CRON') && DISABLE_WP_CRON ) {
					echo '<div class="notice notice-warning"><p>';
					esc_html_e("The site's WP-Cron is disabled. For the plugin to function correctly, you need to periodically run wp-cron.php using your system's cron job.", 'missed-schedule-post-publisher');
					echo '</p></div>';
				} ?>
            <div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
                <p>
                    <strong><?php esc_html_e('Current Status:', 'missed-schedule-post-publisher'); ?></strong>
                </p>
                <ul>
                    <li>
                        <?php printf(
                            esc_html__('Check Interval: Every %d minutes', 'missed-schedule-post-publisher'),
                            $interval
                        ); ?>
                    </li>
                    <li>
                        <?php 
                        if ($last_run > 0) {
                            printf(
                                esc_html__('Last check run (Local Time): %s', 'missed-schedule-post-publisher'),
                                date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_run + (get_option('gmt_offset') * 3600))
                            );
                        } else {
                            esc_html_e('Last check run: Never', 'missed-schedule-post-publisher');
                        }
                        ?>
                    </li>
                    <li>
                        <?php if ($next_run): ?>
                            <?php printf(
                                esc_html__('Next scheduled run (Local Time): %s', 'missed-schedule-post-publisher'),
                                date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_run + (get_option('gmt_offset') * 3600))
                            ); ?>
                        <?php else: ?>
                            <span style="color: red;"><?php esc_html_e('Cron is not scheduled!', 'missed-schedule-post-publisher'); ?></span>
                        <?php endif; ?>
                    </li>
                </ul>

                <hr>

                <form method="post">
                    <?php wp_nonce_field('mspp_settings_update', 'mspp_nonce'); ?>
                    <input type="hidden" name="action" value="update">

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="mspp_execute_time">
                                    <?php esc_html_e('Select Interval', 'missed-schedule-post-publisher'); ?>
                                </label>
                            </th>
                            <td>
                                <select name="mspp_execute_time" id="mspp_execute_time">
                                    <?php $this->render_interval_options($interval); ?>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('How often should the plugin check for missed posts?', 'missed-schedule-post-publisher'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(__('Save Settings', 'missed-schedule-post-publisher')); ?>
                </form>
            </div>
        </div>
        <?php
    }

    private function render_interval_options($selected) {
        $options = [5, 10, 15, 20, 30, 60];

        foreach ($options as $value) {
            printf(
                '<option value="%d" %s>%d %s</option>',
                (int) $value,
                selected($value, $selected, false),
                (int) $value,
                esc_html__('Minutes', 'missed-schedule-post-publisher')
            );
        }
    }

    private function handle_settings_update() {
        if (
            !isset($_POST['mspp_nonce']) ||
            !wp_verify_nonce($_POST['mspp_nonce'], 'mspp_settings_update')
        ) {
            wp_die(__('Security check failed', 'missed-schedule-post-publisher'));
        }

        $interval = isset($_POST['mspp_execute_time'])
            ? absint($_POST['mspp_execute_time'])
            : self::DEFAULT_INTERVAL;

        update_option(self::OPTION_EXECUTE_TIME, max(1, $interval));

        wp_clear_scheduled_hook(self::CRON_HOOK);
        wp_schedule_event(
            time() + 60,
            self::CRON_INTERVAL_KEY,
            self::CRON_HOOK
        );

        add_settings_error(
            'mspp_messages',
            'mspp_message',
            __('Settings saved and schedule updated.', 'missed-schedule-post-publisher'),
            'updated'
        );
    }
}

add_action('plugins_loaded', static function () {
    new Missed_Schedule_Post_Publisher();
});