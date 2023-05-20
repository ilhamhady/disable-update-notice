<?php
/*
Plugin Name: Disable Plugin Update Notices
Description: This plugin allows you to disable update notices for selected plugins.
Version: 1.0.3
Author: Muhammad Ilham
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'Disable_Plugin_Update_Notices' ) ) {

    class Disable_Plugin_Update_Notices {

        public function __construct() {
            add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
            add_action( 'admin_init', array( $this, 'page_init' ) );
            add_filter( 'site_transient_update_plugins', array( $this, 'disable_selected_plugin_updates' ) );
            add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'add_plugin_action_links' ) );
            add_filter( 'plugin_row_meta', array( $this, 'add_plugin_row_meta_links' ), 10, 2 );
        }

        public function add_plugin_page() {
            add_options_page( 'Disable Plugin Update Notices', 'Disable Plugin Update Notices', 'manage_options', 'disable-plugin-update-notices', array( $this, 'admin_page_content' ) );
        }

        public function add_plugin_action_links( $links ) {
            $settings_link = '<a href="options-general.php?page=disable-plugin-update-notices">' . __( 'Settings' ) . '</a>';
            array_unshift( $links, $settings_link, $repo_link );
            return $links;
        }

        public function add_plugin_row_meta_links( $links, $file ) {
            $base = plugin_basename(__FILE__);
            if ($file == $base) {
                $links[] = '<a href="https://github.com/ilhamhady/disable-update-notice" target="_blank">' . __( 'Repo' ) . '</a>';
                $links[] = '<a href="https://wa.me/6281232724414" target="_blank">' . __( 'Contact' ) . '</a>';
            }
            return $links;
        }

        public function page_init() {
            register_setting( 'disable_plugin_update_notices_settings', 'disabled_plugins', array( $this, 'sanitize' ) );
            add_settings_section( 'setting_section', 'Disable Plugin Update Notices', array( $this, 'section_info' ), 'disable-plugin-update-notices' );
            add_settings_field( 'disabled_plugins', 'Disable update notices for:', array( $this, 'disabled_plugins_callback' ), 'disable-plugin-update-notices', 'setting_section' );
        }

        public function admin_page_content() {
            ?>
            <div class="wrap">
                <h1>Disable Plugin Update Notices</h1>
                <form method="post" action="options.php">
                    <?php
                        settings_fields( 'disable_plugin_update_notices_settings' );
                        do_settings_sections( 'disable-plugin-update-notices' );
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

        public function disabled_plugins_callback() {
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
    }

    new Disable_Plugin_Update_Notices();
}
