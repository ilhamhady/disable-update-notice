<?php
/*
Plugin Name: Disable Update Notices
Description: This plugin allows you to disable update notices for selected plugins, themes and WordPress.
Version: 1.1.2
Author: Muhammad Ilham
Author URI: https://www.linkedin.com/in/muhammad-ilham-shogir/
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'Disable_Update_Notices' ) ) {

    class Disable_Update_Notices {

        // Singleton instance
        private static $instance = null;

        // Option keys
        private const DISABLED_PLUGINS = 'disabled_plugins';
        private const DISABLED_THEMES = 'disabled_themes';
        private const DISABLE_WORDPRESS_UPDATES = 'disable_wordpress_updates';

        // Prevent from creating multiple instances
        private function __construct() {
            // Initialize hooks and filters
            $this->initHooks();
        }

        // Initialize hooks and filters
        private function initHooks() {
            add_action('admin_menu', [$this, 'add_plugin_page']);
            add_action('admin_init', [$this, 'admin_init_tasks']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']); // Enqueue styles
            add_filter('site_transient_update_plugins', [$this, 'disable_selected_plugin_updates']);
            add_filter('site_transient_update_themes', [$this, 'disable_selected_theme_updates']);
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_plugin_action_links']);
            add_filter('plugin_row_meta', [$this, 'add_plugin_row_meta_links'], 10, 2);
        }

        // Method to get the instance of the class
        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new Disable_Update_Notices();
            }

            return self::$instance;
        }

        // Method to add the plugin page
        public function add_plugin_page() {
            add_options_page(
                __('Disable Update Notices', 'disable-update-notices'),
                __('Disable Update Notices', 'disable-update-notices'),
                'manage_options',
                'disable-update-notices',
                [$this, 'admin_page_content']
            );
        }

        // Method to add plugin action links
        public function add_plugin_action_links($links) {
            $settings_link = '<a href="options-general.php?page=disable-update-notices">' . __('Settings') . '</a>';
            array_unshift($links, $settings_link);

            return $links;
        }

        // Method to add plugin row meta links
        public function add_plugin_row_meta_links($links, $file) {
            $base = plugin_basename(__FILE__);
            if ($file == $base) {
                $links[] = '<a href="https://github.com/ilhamhady/disable-update-notice" target="_blank">' . __('Repo', 'disable-update-notices') . '</a>';
                $links[] = '<a href="https://wa.me/6281232724414" target="_blank">' . __('Contact', 'disable-update-notices') . '</a>';
            }

            return $links;
        }

        // Method for admin_init tasks
        public function admin_init_tasks() {
            $this->page_init();
            $this->check_wp_updates();
        }

        // Method to enqueue styles
        public function enqueue_styles() {
            wp_enqueue_style('disable-update-notices-style', plugin_dir_url(__FILE__) . 'style.css');
        }

        // Register settings and define sections
        public function page_init() {
            // Plugin settings
            register_setting('disable_update_notices_settings', self::DISABLED_PLUGINS, [$this, 'sanitize']);
            add_settings_section('setting_section', 'Plugin Update Notices', [$this, 'section_info'], 'disable-update-notices');
            add_settings_field(self::DISABLED_PLUGINS, 'Disable update notices for:', [$this, 'disabled_plugins_callback'], 'disable-update-notices', 'setting_section');

            // Theme settings
            register_setting('disable_theme_update_notices_settings', self::DISABLED_THEMES, [$this, 'sanitize']);
            add_settings_section('theme_setting_section', 'Theme Update Notices', [$this, 'theme_update_section_info'], 'theme-update-notices');
            add_settings_field(self::DISABLED_THEMES, 'Disable update notices for:', [$this, 'disabled_themes_callback'], 'theme-update-notices', 'theme_setting_section');

            // WordPress settings
            register_setting('disable_wordpress_update_notices_settings', self::DISABLE_WORDPRESS_UPDATES, [$this, 'sanitize']);
            add_settings_section('wordpress_update_setting_section', 'WordPress Update Notices', [$this, 'wordpress_update_section_info'], 'wordpress-update-notices');
            add_settings_field(self::DISABLE_WORDPRESS_UPDATES, 'Disable update notices for:', [$this, 'wordpress_updates_callback'], 'wordpress-update-notices', 'wordpress_update_setting_section');
        }

        // Admin page content
        public function admin_page_content() {
            $this->active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'plugins';
            ?>
            <div class="wrap">
                <h1><?php esc_html_e('Disable Update Notices', 'disable-update-notices'); ?></h1>
                <h2 class="nav-tab-wrapper">
                    <a href="?page=disable-update-notices&tab=plugins" class="nav-tab <?php echo $this->active_tab == 'plugins' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Plugin Updates', 'disable-update-notices'); ?></a>
                    <a href="?page=disable-update-notices&tab=themes" class="nav-tab <?php echo $this->active_tab == 'themes' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Theme Updates', 'disable-update-notices'); ?></a>
                    <a href="?page=disable-update-notices&tab=wordpress" class="nav-tab <?php echo $this->active_tab == 'wordpress' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('WordPress Updates', 'disable-update-notices'); ?></a>
                </h2>
                <form method="post" action="options.php">
                    <?php
                        if ($this->active_tab == 'plugins') {
                            settings_fields( 'disable_update_notices_settings' );
                            do_settings_sections( 'disable-update-notices' );
                        } else if ($this->active_tab == 'themes') {
                            settings_fields( 'disable_theme_update_notices_settings' );
                            do_settings_sections( 'theme-update-notices' );
                        } else if ($this->active_tab == 'wordpress') {
                            settings_fields( 'disable_wordpress_update_notices_settings' );
                            do_settings_sections( 'wordpress-update-notices' );
                        }
                        submit_button();
                    ?>
                </form>
            </div>
            <?php
        }

        // Data sanitization
        public function sanitize( $input ) {
            return isset( $input ) ? $input : array();
        }

        // Plugin section info
        public function section_info() {
            esc_html_e('Choose the plugins for which you want to disable update notices:', 'disable-update-notices');
        }

        // Theme section info
        public function theme_update_section_info() {
            esc_html_e('Choose the themes for which you want to disable update notices:', 'disable-update-notices');
        }

        // WordPress section info
        public function wordpress_update_section_info() {
            esc_html_e('Choose the WordPress updates for which you want to disable update notices:', 'disable-update-notices');
        }

        // Disabled plugin checkboxes
        public function disabled_plugins_callback() {
            $disabled_plugins = get_option(self::DISABLED_PLUGINS);
            if ( !is_array($disabled_plugins) ) {
                $disabled_plugins = array();
            }
            $all_plugins = get_plugins();

            foreach ( $all_plugins as $plugin_path => $plugin_data ) {
                $checked = in_array( $plugin_path, $disabled_plugins ) ? 'checked="checked"' : '';
                echo '<div class="checklist-item">';
                echo '<div class="checkbox"><input type="checkbox" name="disabled_plugins[]" value="' . esc_attr( $plugin_path ) . '" ' . $checked . '></div>';
                echo '<div class="item-name">' . esc_html( $plugin_data['Name'] ) . '</div>';
                echo '</div>';
            }
        }

        // Disabled themes checkboxes
        public function disabled_themes_callback() {
            $disabled_themes = get_option(self::DISABLED_THEMES);
            if ( false === $disabled_themes ) {
                $disabled_themes = array();
            }
            $all_themes = wp_get_themes();

            foreach ( $all_themes as $theme_path => $theme_data ) {
                $checked = in_array( $theme_path, $disabled_themes ) ? 'checked="checked"' : '';
                echo '<div class="checklist-item">';
                echo '<div class="checkbox"><input type="checkbox" name="disabled_themes[]" value="' . esc_attr( $theme_path ) . '" ' . $checked . '></div>';
                echo '<div class="item-name">' . esc_html( $theme_data->get('Name') ) . '</div>';
                echo '</div>';
            }
        }

        // WordPress update checkboxes
        public function wordpress_updates_callback() {
            $disable_wordpress_updates = get_option(self::DISABLE_WORDPRESS_UPDATES);
            if ( false === $disable_wordpress_updates ) {
                $disable_wordpress_updates = array();
            }

            $updates = array('core', 'major', 'minor', 'translation');

            foreach ( $updates as $update ) {
                $checked = in_array( $update, $disable_wordpress_updates ) ? 'checked="checked"' : '';
                echo '<div class="checklist-item">';
                echo '<div class="checkbox"><input type="checkbox" name="disable_wordpress_updates[]" value="' . esc_attr( $update ) . '" ' . $checked . '></div>';
                echo '<div class="item-name">WordPress ' . esc_html( ucwords($update) ) . ' Update</div>';
                echo '</div>';
            }
        }

        // Disable selected plugin updates
        public function disable_selected_plugin_updates( $transient ) {
            if ( ! is_object( $transient ) || ! isset( $transient->response ) ) {
                return $transient;
            }

            $disabled_plugins = get_option(self::DISABLED_PLUGINS);

            if ( ! empty( $disabled_plugins ) ) {
                foreach ( $disabled_plugins as $plugin_path ) {
                    if ( isset( $transient->response[ $plugin_path ] ) ) {
                        unset( $transient->response[ $plugin_path ] );
                    }
                }
            }

            return $transient;
        }

        // Disable selected theme updates
        public function disable_selected_theme_updates( $transient ) {
            if ( ! is_object( $transient ) || ! isset( $transient->response ) ) {
                return $transient;
            }

            $disabled_themes = get_option(self::DISABLED_THEMES);

            if ( ! empty( $disabled_themes ) ) {
                foreach ( $disabled_themes as $theme_path ) {
                    if ( isset( $transient->response[ $theme_path ] ) ) {
                        unset( $transient->response[ $theme_path ] );
                    }
                }
            }

            return $transient;
        }

        // Check for and disable WordPress updates
        public function check_wp_updates() {
            $disable_wordpress_updates = get_option(self::DISABLE_WORDPRESS_UPDATES, array());
            if (in_array('core', $disable_wordpress_updates)) {
                add_filter('pre_site_transient_update_core', '__return_null');
            }
            if (in_array('major', $disable_wordpress_updates)) {
                add_filter('allow_major_auto_core_updates', '__return_false');
            }
            if (in_array('minor', $disable_wordpress_updates)) {
                add_filter('allow_minor_auto_core_updates', '__return_false');
            }
            if (in_array('translation', $disable_wordpress_updates)) {
                add_filter('auto_update_translation', '__return_false');
            }
        }
    }

    // Initialize the plugin
    Disable_Update_Notices::getInstance();
}

?>
