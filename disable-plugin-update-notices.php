<?php
/*
Plugin Name: Disable Update Notices
Description: This plugin allows you to disable update notices for selected plugins, themes and WordPress.
Version: 1.1
Author: Muhammad Ilham
Author URI: https://www.linkedin.com/in/muhammad-ilham-shogir/
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'Disable_Update_Notices' ) ) {

    class Disable_Update_Notices {

        // Current active tab.
        private $active_tab;

        // Initialization of hooks and filters
        public function __construct()
        {
            add_action('admin_menu', [$this, 'add_plugin_page']);
            add_action('admin_init', [$this, 'page_init']);
            add_action('admin_init', [$this, 'check_wp_updates']);
            add_filter('site_transient_update_plugins', [$this, 'disable_selected_plugin_updates']);
            add_filter('site_transient_update_themes', [$this, 'disable_selected_theme_updates']);
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_plugin_action_links']);
            add_filter('plugin_row_meta', [$this, 'add_plugin_row_meta_links'], 10, 2);
        }

        // Method to add the plugin page
        public function add_plugin_page()
        {
            add_options_page(
                __('Disable Update Notices', 'disable-update-notices'),
                __('Disable Update Notices', 'disable-update-notices'),
                'manage_options',
                'disable-update-notices',
                [$this, 'admin_page_content']
            );
        }

        // Method to add plugin action links
        public function add_plugin_action_links($links)
        {
            $settings_link = '<a href="options-general.php?page=disable-update-notices">' . __('Settings') . '</a>';
            array_unshift($links, $settings_link);
            return $links;
        }

        // Method to add plugin row meta links
        public function add_plugin_row_meta_links($links, $file)
        {
            $base = plugin_basename(__FILE__);
            if ($file == $base) {
                $links[] = '<a href="https://github.com/ilhamhady/disable-update-notice" target="_blank">' . __('Repo') . '</a>';
                $links[] = '<a href="https://wa.me/6281232724414" target="_blank">' . __('Contact') . '</a>';
            }
            return $links;
        }

        public function page_init()
        {
            // Plugin settings
            register_setting( 'disable_update_notices_settings', 'disabled_plugins', array( $this, 'sanitize' ) );
            add_settings_section( 'setting_section', 'Plugin Update Notices', array( $this, 'section_info' ), 'disable-update-notices' );
            add_settings_field( 'disabled_plugins', 'Disable update notices for:', array( $this, 'disabled_plugins_callback' ), 'disable-update-notices', 'setting_section' );

            // Theme settings
            register_setting( 'disable_theme_update_notices_settings', 'disabled_themes', array( $this, 'sanitize' ) );
            add_settings_section( 'theme_setting_section', 'Theme Update Notices', array( $this, 'theme_update_section_info' ), 'theme-update-notices' );
            add_settings_field( 'disabled_themes', 'Disable update notices for:', array( $this, 'disabled_themes_callback' ), 'theme-update-notices', 'theme_setting_section' );

            // WordPress settings
            register_setting( 'disable_wordpress_update_notices_settings', 'disable_wordpress_updates', array( $this, 'sanitize' ) );
            add_settings_section( 'wordpress_update_setting_section', 'WordPress Update Notices', array( $this, 'wordpress_update_section_info' ), 'wordpress-update-notices' );
            add_settings_field( 'disable_wordpress_updates', 'Disable update notices for:', array( $this, 'wordpress_updates_callback' ), 'wordpress-update-notices', 'wordpress_update_setting_section' );
        }

        public function admin_page_content()
        {
            $this->active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'plugins';
            ?>
            <div class="wrap">
                <h1>Disable Update Notices</h1>
                <h2 class="nav-tab-wrapper">
                    <a href="?page=disable-update-notices&tab=plugins" class="nav-tab <?php echo $this->active_tab == 'plugins' ? 'nav-tab-active' : ''; ?>">Plugin Updates</a>
                    <a href="?page=disable-update-notices&tab=themes" class="nav-tab <?php echo $this->active_tab == 'themes' ? 'nav-tab-active' : ''; ?>">Theme Updates</a>
                    <a href="?page=disable-update-notices&tab=wordpress" class="nav-tab <?php echo $this->active_tab == 'wordpress' ? 'nav-tab-active' : ''; ?>">WordPress Updates</a>
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

        public function sanitize( $input ) {
            return isset( $input ) ? $input : array();
        }

        public function section_info() {
            echo 'Choose the plugins for which you want to disable update notices:';
        }

        public function theme_update_section_info() {
            echo 'Choose the themes for which you want to disable update notices:';
        }

        public function wordpress_update_section_info() {
            echo 'Choose the WordPress updates for which you want to disable update notices:';
        }

        public function disabled_plugins_callback()
        {
            $disabled_plugins = get_option( 'disabled_plugins' );
            if ( false === $disabled_plugins ) {
                $disabled_plugins = array();
            }
            $all_plugins = get_plugins();

            echo '<table>';
            foreach ( $all_plugins as $plugin_path => $plugin_data ) {
                $checked = in_array( $plugin_path, $disabled_plugins ) ? 'checked="checked"' : '';
                echo '<tr>';
                echo '<td style="padding: 10px 0"><input type="checkbox" name="disabled_plugins[]" value="' . esc_attr( $plugin_path ) . '" ' . $checked . '></td>';
                echo '<td style="padding: 10px">' . esc_html( $plugin_data['Name'] ) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }

        public function disabled_themes_callback()
        {
            $disabled_themes = get_option( 'disabled_themes' );
            if ( false === $disabled_themes ) {
                $disabled_themes = array();
            }
            $all_themes = wp_get_themes();

            echo '<table>';
            foreach ( $all_themes as $theme_path => $theme_data ) {
                $checked = in_array( $theme_path, $disabled_themes ) ? 'checked="checked"' : '';
                echo '<tr>';
                echo '<td style="padding: 10px 0"><input type="checkbox" name="disabled_themes[]" value="' . esc_attr( $theme_path ) . '" ' . $checked . '></td>';
                echo '<td style="padding: 10px">' . esc_html( $theme_data->get('Name') ) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }

        public function wordpress_updates_callback()
        {
            $disable_wordpress_updates = get_option( 'disable_wordpress_updates' );
            if ( false === $disable_wordpress_updates ) {
                $disable_wordpress_updates = array();
            }

            $updates = array('core', 'major', 'minor', 'translation');

            echo '<table>';
            foreach ( $updates as $update ) {
                $checked = in_array( $update, $disable_wordpress_updates ) ? 'checked="checked"' : '';
                echo '<tr>';
                echo '<td style="padding: 10px 0"><input type="checkbox" name="disable_wordpress_updates[]" value="' . esc_attr( $update ) . '" ' . $checked . '></td>';
                echo '<td style="padding: 10px">WordPress ' . esc_html( ucwords($update) ) . ' Update</td>';
                echo '</tr>';
            }
            echo '</table>';
        }

        public function disable_selected_plugin_updates( $transient ) {
            if ( ! is_object( $transient ) || ! isset( $transient->response ) ) {
                return $transient;
            }

            $disabled_plugins = get_option( 'disabled_plugins' );

            if ( ! empty( $disabled_plugins ) ) {
                foreach ( $disabled_plugins as $plugin_path ) {
                    if ( isset( $transient->response[ $plugin_path ] ) ) {
                        unset( $transient->response[ $plugin_path ] );
                    }
                }
            }

            return $transient;
        }

        public function disable_selected_theme_updates( $transient ) {
            if ( ! is_object( $transient ) || ! isset( $transient->response ) ) {
                return $transient;
            }

            $disabled_themes = get_option( 'disabled_themes' );

            if ( ! empty( $disabled_themes ) ) {
                foreach ( $disabled_themes as $theme_path ) {
                    if ( isset( $transient->response[ $theme_path ] ) ) {
                        unset( $transient->response[ $theme_path ] );
                    }
                }
            }

            return $transient;
        }

        public function check_wp_updates() {
            $disable_wordpress_updates = get_option( 'disable_wordpress_updates', array() );
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

    new Disable_Update_Notices();
}

?>
