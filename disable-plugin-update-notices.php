<?php
/*
Plugin Name: Disable Plugin Update Notices
Description: This plugin allows you to disable update notices for selected plugins.
Version: 1.0
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
        }

        public function add_plugin_page() {
            add_options_page( 'Disable Plugin Update Notices', 'Disable Plugin Update Notices', 'manage_options', 'disable-plugin-update-notices', array( $this, 'admin_page_content' ) );
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
                echo '<td><input type="checkbox" name="disabled_plugins[]" value="' . esc_attr( $plugin_path ) . '" ' . $checked . '></td>';
                echo '<td>' . esc_html( $plugin_data['Name'] ) . '</td>';
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
