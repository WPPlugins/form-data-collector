<?php
/**
 *  Plugin Name: Form Data Collector
 *  Plugin URI: http://prixal.eu/
 *  Description: This plugin is a developer's tookit for collecting form data from your WordPress site
 *  Version: 1.3.1
 *  Author: Prixal LLC
 *  Author URI: http://prixal.eu/
 *  License: GPL2+
 *  Text Domain: fdc
 *
 *  Hooks:
 *
 *      filter: fdc_overview_details_output
 *      filter: fdc_table_list_columns_values
 *      filter: fdc_table_list_columns
 *      filter: fdc_table_list_items_per_page
 *      filter: fdc_pre_save_meta
 *      filter: fdc_ajax_response_error
 *      filter: fdc_ajax_response_success
 *      filter: fdc_enable_email_settings
 *      filter: fdc_store_fields_as_array
 *      action: fdc_form_data_saved
 *      action: fdc_before_email_send
 *      action: restrict_manage_px_fdc
 *      action: fdc_overview_details_before_output
 *      action: fdc_overview_details_after_output
 *
 *
 *  Copyright 2015-2016  Prixal LLC  (email: info@prixal.eu)
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License, version 2, as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
**/

defined('ABSPATH') or die('No script kiddies please!');

class Form_Data_Collector
{
    var $key = '_fdc';
    var $globals_metabox_id = '_fdc_global_settings';
    var $to_client_metabox_id = '_fdc_client_email_settings';
    var $to_you_metabox_id = '_fdc_your_email_settings';

    public function __construct()
    {
        $enable_email_settings = apply_filters('fdc_enable_email_settings', true);

        if ( $enable_email_settings && ! defined('CMB2_LOADED') ) {
            require_once('libs/cmb2/init.php');
        }

        add_action('admin_init', function() {
            if(!class_exists('WP_List_Table')) { require_once( 'classes/class-wp-list-table.php' ); }
            require_once('classes/AdminView.php');
        });

        require_once('classes/AdminAjax.php');

        add_action('wp_enqueue_scripts', array($this, 'frontScripts'));
        add_action('admin_enqueue_scripts', array($this, 'styles'));
        add_action('admin_enqueue_scripts', array($this, 'scripts'));
        add_action('wp_footer', array($this, 'addFrontFooter'));
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('cmb2_admin_init', array($this, 'addSettingsMetabox') );
        add_action('fdc_form_data_saved', array($this, 'emails'), 10, 1 );

        if( apply_filters('fdc_store_fields_as_array', false) ) {
            add_filter('get_post_metadata', array($this, 'getFieldValueViaKey'), 10, 4);
        }

        $this->customPostType();
        new Form_Data_Collector_AJAX();
    }

    public function getFieldValueViaKey($value, $object_id, $meta_key, $single)
    {
        if( strpos($meta_key, '_px_fdc_') !== false )
        {
            global $wpdb;
            $meta = $wpdb->get_var( $wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = %s AND post_id = %d LIMIT 1", '_px_fdc_form_fields_data', $object_id) );
            $data = maybe_unserialize($meta);

            if( '_px_fdc_form_fields_data' == $meta_key) {
                return array($data);
            }

            if( $single ) {
                return array($data[$meta_key]);
            }

        }

        return null;
    }

    public function customPostType()
    {
        register_post_type('px_fdc',
            array(
                'public' => false,
                'exclude_from_search' => true,
                'show_in_nav_menus' => false,
                'rewrite' => false,
                'supports' => array('title')
            )
        );
    }

    public function addFrontFooter()
    {
        $fdc_vars = array(
            'nonce' => wp_create_nonce('fdc_nonce')
        );
        $output = "var fdc_vars = " . wp_json_encode( $fdc_vars ) . ';';

        echo "\n<script type='text/javascript'>\n";
        echo "$output\n";
        echo "</script>\n";
    }

    public function scripts()
    {
        wp_enqueue_script('bootstrap', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js', array('jquery'), null, false);
        wp_enqueue_script('fdc', plugins_url('/js/custom.js' , __FILE__ ), array('jquery'), null, false);
    }

    public function frontScripts()
    {
        wp_enqueue_script('fdc', plugins_url('/js/custom-front.js' , __FILE__ ), array('jquery'), null, false);
        wp_localize_script('fdc', '_fdcVars', array(
            'ajax' => array(
                'url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('fdc_nonce')
            )
        ));
    }

    public function styles()
    {
        $screen = get_current_screen();

        if ( $screen->id != 'toplevel_page_px_fdc' ) {
            return;
        }

        wp_enqueue_style('fdc', plugins_url('/css/styles.css' , __FILE__ ));
    }

    public function addSettingsMetabox()
    {
        $toClient = new_cmb2_box( array(
            'id'         => $this->to_client_metabox_id,
            'hookup'     => false,
            'cmb_styles' => false,
            'show_on'    => array(
                'key'   => 'options-page',
                'value' => array( $this->key, )
            ),
        ) );

        $toClient->add_field( array(
            'name' => esc_attr__('To Client email settings', 'fdc'),
            'type' => 'title',
            'id'   => 'wiki_test_title'
        ) );

        $toClient->add_field( array(
            'name' => esc_attr__('Email content', 'fdc'),
            'id'   => $this->key . '_client_email_content',
            'desc' => esc_attr__('You can use this content as email body. Use "fdc_before_email_send" action to add email functionality.', 'fdc'),
            'type' => 'wysiwyg',
            'options' => array(
                'teeny' => true,
                'media_buttons' => false,
                'textarea_rows' => 8
            )
        ) );

        $toYou = new_cmb2_box( array(
            'id'         => $this->to_you_metabox_id,
            'hookup'     => false,
            'cmb_styles' => false,
            'show_on'    => array(
                'key'   => 'options-page',
                'value' => array( $this->key, )
            ),
        ) );

        $toYou->add_field( array(
            'name' => 'To You email settings',
            'type' => 'title',
            'id'   => 'wiki_test_title'
        ) );

        $toYou->add_field( array(
            'name' => esc_attr__('Email address', 'fdc'),
            'desc' => esc_attr__('Use this email address if you want to send an email notification to you or to someone else. Use "fdc_before_email_send" action to add email functionality.', 'fdc'),
            'id'   => $this->key . '_you_email_address',
            'type' => 'text'
        ) );

        $toYou->add_field( array(
            'name' => esc_attr__('Email content', 'fdc'),
            'id'   => $this->key . '_you_email_content',
            'type' => 'wysiwyg',
            'options' => array(
                'teeny' => true,
                'media_buttons' => false,
                'textarea_rows' => 8
            )
        ) );
    }

    public function addAdminMenu()
    {
        if( current_user_can('manage_options') )
        {
            /**
             * Filter to enable or disable Email settings
             *
             * @since 1.2.0
             *
             */
            $enable_email_settings = apply_filters('fdc_enable_email_settings', true);

            add_menu_page('FDC', 'FDC', 'manage_options', 'px_fdc');
            add_submenu_page('px_fdc', esc_attr__('Entries', 'fdc'), esc_attr__('Entries', 'fdc'), 'manage_options', 'px_fdc', array($this, 'fdcEntriesPage'));

            if( $enable_email_settings === true ) {
                add_submenu_page('px_fdc', esc_attr__('Email settings', 'fdc'), esc_attr__('Email settings', 'fdc'), 'manage_options', 'px_fdc_emails', array($this, 'fdcEmailsPage'));
            }
        }
    }

    public function fdcEmailsPage()
    {
        echo '<div class="wrap">';
        printf('<h1>%s</h1>', __('Email settings', 'fdc'));
        echo '<hr /><br />';
        cmb2_metabox_form( $this->to_client_metabox_id, $this->key );
        echo '<hr class="divider" />';
        cmb2_metabox_form( $this->to_you_metabox_id, $this->key );
    }

    public function fdcEntriesPage()
    {
        echo '<div class="wrap">';
        printf('<h1>%s</h1>', __('Entries', 'fdc'));

        $tableList = new Form_Data_Collector_List_Table();
        $tableList->prepare_items();

        echo '<form id="px-fdc-filter" method="get">';
        echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
        $tableList->display();
        echo '</form>';

        echo '</div>';
        ?>
            <div id="pxFDCModal" class="modal fade" tabindex="-1" role="dialog" data-loading="<?php esc_attr_e('Loading content', 'fdc'); ?>...">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title"><?php _e('Entry details', 'fdc'); ?></h4>
                        </div>
                        <div class="modal-body">
                            <p><?php _e('Loading content', 'fdc'); ?>&hellip;</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php
    }

    public function emails($post_id)
    {
        if( empty($post_id) ) {
            return;
        }

        $toClient = array(
            'content' => cmb2_get_option($this->key, '_fdc_client_email_content'),
        );

        $toYou = array(
            'email' => cmb2_get_option($this->key, '_fdc_you_email_address'),
            'content' => cmb2_get_option($this->key, '_fdc_you_email_content')
        );

        do_action('fdc_before_email_send', $post_id, $toClient, $toYou);
    }
}

add_action('init', function() {
    new Form_Data_Collector();
});
