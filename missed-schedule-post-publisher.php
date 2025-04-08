<?php
/*
 * Plugin Name: Missed Schedule Post Publisher
 * Description: Publishes missed scheduled posts automatically.
 * Plugin URI: https://www.zumbo.net/missed-schedule-post-publisher-wordpress-plugin/
 * Version: 1.0.5
 * Author: UfukArt
 * Author URI: https://www.zumbo.net
 * Text Domain: missed-schedule-post-publisher
 * Domain Path: /languages/
 * License: GPL2
 */
namespace MissedSchedulePostPublisher;

defined('ABSPATH') || exit;

if (!class_exists(__NAMESPACE__ . '\Missed_Schedule_Post_Publisher')) {

    class Missed_Schedule_Post_Publisher {

        const OPTION_EXECUTE_TIME = 'mspp_execute_time';
        const OPTION_LAST_EXECUTE = 'mspp_last_execute_time';
        const DEFAULT_INTERVAL = 20;

        public function __construct() {
            register_activation_hook(__FILE__, [$this, 'activate']);
            register_deactivation_hook(__FILE__, [$this, 'deactivate']);

            add_action('admin_menu', [$this, 'add_admin_menu']);
            add_action('mspp_check_posts', [$this, 'publish_missed_posts']);

            // Custom interval filter
            add_filter('cron_schedules', [$this, 'add_custom_cron_interval']);
        }

        public function activate() {
            update_option(self::OPTION_EXECUTE_TIME, self::DEFAULT_INTERVAL);
            update_option(self::OPTION_LAST_EXECUTE, time());

            if (!wp_next_scheduled('mspp_check_posts')) {
                wp_schedule_event(time(), 'mspp_custom_interval', 'mspp_check_posts');
            }
        }

        public function deactivate() {
            wp_clear_scheduled_hook('mspp_check_posts');
            delete_option(self::OPTION_EXECUTE_TIME);
            delete_option(self::OPTION_LAST_EXECUTE);
        }

        public function add_custom_cron_interval($schedules) {
            $interval = get_option(self::OPTION_EXECUTE_TIME, self::DEFAULT_INTERVAL);

            $schedules['mspp_custom_interval'] = [
                'interval' => $interval * 60,
                'display' => sprintf(__('Every %d Minutes (MSPP)', 'missed-schedule-post-publisher'), $interval)
            ];

            return $schedules;
        }

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
                wp_die(__('You do not have sufficient permissions to access this page.', 'missed-schedule-post-publisher'));
            }

            if (isset($_POST['action']) && 'update' === $_POST['action']) {
                $this->handle_settings_update();
            }

            $current_interval = get_option(self::OPTION_EXECUTE_TIME, self::DEFAULT_INTERVAL);
            $last_run = get_option(self::OPTION_LAST_EXECUTE, time());
            ?>
            <div class="wrap">
                <h1 class="wp-heading-inline"><?php esc_html_e('Missed Schedule Post Publisher', 'missed-schedule-post-publisher'); ?></h1>
                <p>
                    <?php
                    printf(
                        __('Running every <strong>%d</strong> minutes. Last run time (GMT): %s', 'missed-schedule-post-publisher'),
                        $current_interval,
                        gmdate('Y-m-d H:i:00', $last_run)
                    );
                    ?>
                </p>
                <form method="post">
                    <?php wp_nonce_field('mspp_settings_update', 'mspp_nonce'); ?>
                    <label for="mspp_execute_time">
                        <?php esc_html_e('Run Every', 'missed-schedule-post-publisher'); ?>
                        <select name="mspp_execute_time" id="mspp_execute_time" required>
                            <?php $this->render_interval_options($current_interval); ?>
                        </select>
                    </label>
                    <input type="hidden" name="action" value="update">
                    <?php submit_button(__('Update', 'missed-schedule-post-publisher')); ?>
                </form>
            </div>
            <?php
        }

        private function render_interval_options($selected) {
            $intervals = [
                5  => __('5 Minutes', 'missed-schedule-post-publisher'),
                10 => __('10 Minutes', 'missed-schedule-post-publisher'),
                15 => __('15 Minutes', 'missed-schedule-post-publisher'),
                20 => __('20 Minutes (Recommended)', 'missed-schedule-post-publisher'),
                30 => __('30 Minutes', 'missed-schedule-post-publisher'),
                60 => __('Every Hour', 'missed-schedule-post-publisher'),
            ];

            foreach ($intervals as $value => $label) {
                printf(
                    '<option value="%d" %s>%s</option>',
                    $value,
                    selected($value, $selected, false),
                    esc_html($label)
                );
            }
        }

        private function handle_settings_update() {
            if (!isset($_POST['mspp_nonce']) || !wp_verify_nonce($_POST['mspp_nonce'], 'mspp_settings_update')) {
                wp_die(__('Security check failed', 'missed-schedule-post-publisher'));
            }

            if (isset($_POST['mspp_execute_time'])) {
                $interval = absint($_POST['mspp_execute_time']);
                if ($interval > 0) {
                    update_option(self::OPTION_EXECUTE_TIME, $interval);
                    wp_clear_scheduled_hook('mspp_check_posts');
                    wp_schedule_event(time(), 'mspp_custom_interval', 'mspp_check_posts');

                    add_settings_error(
                        'mspp_messages',
                        'mspp_message',
                        __('Settings Saved', 'missed-schedule-post-publisher'),
                        'updated'
                    );
                }
            }
        }

        public function publish_missed_posts() {
            global $wpdb;

            $now = current_time('mysql', 1);
            $post_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT ID FROM $wpdb->posts WHERE post_status = 'future' AND post_date_gmt <= %s",
                $now
            ));

            if (!empty($post_ids)) {
                foreach ($post_ids as $post_id) {
                    wp_publish_post($post_id);
                }
                update_option(self::OPTION_LAST_EXECUTE, time());
            }
        }
    }

    add_action('plugins_loaded', function() {
        new Missed_Schedule_Post_Publisher();
    });
}