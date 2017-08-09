<?php

defined('ABSPATH') or die('No script kiddies please!');

class Form_Data_Collector_AJAX
{
    var $prefix = '_px_fdc_';

    function __construct()
    {
        add_action('wp_ajax_fdc_action', array($this, 'ajax') );
        add_action('wp_ajax_nopriv_fdc_action', array($this, 'ajax') );
    }

    public function ajax()
    {
        $cmd = $_POST['cmd'];

        switch( $cmd )
        {
            case 'view'  : $this->view();   break;
            case 'delete': $this->delete(); break;
            case 'save'  : $this->save();   break;
        }

        die();
    }

    private function view()
    {
        if( current_user_can('manage_options') )
        {
            $output = array();
            $store_fields_as_array = apply_filters('fdc_store_fields_as_array', false);

            if( $store_fields_as_array ) {
                $meta = get_post_meta( (int) $_POST['id'], $this->prefix . 'form_fields_data', true);
            } else {
                $meta = get_metadata('post', (int) $_POST['id']);
            }

            $data = apply_filters('fdc_overview_details_output', $meta);

            /**
             * Fires before Entry details output
             *
             * @since 1.3.0
             *
             * @param array     $meta       Metadata associated with the entry
             * @param int       $entry_id   Entry ID
             */
            do_action('fdc_overview_details_before_output', $meta, (int) $_POST['id']);

            if( !empty($data) && is_array($data) )
            {
                $output[]= '<table class="px-table px-table-striped"><tbody>';

                foreach( $data as $key => $value )
                {
                    array_push($output,
                        sprintf(
                            '<tr>
                                <th>%s</th>
                                <td>%s</td>
                            </tr>',

                            $key,
                            ( is_array($value) ) ? $value[0] : $value
                        )
                    );
                }

                $output[]= '</tbody></table>';

                echo implode("\n", $output);
            }

            /**
             * Fires after Entry details output
             *
             * @since 1.3.0
             *
             * @param array     $meta       Metadata associated with the entry
             * @param int       $entry_id   Entry ID
             */
            do_action('fdc_overview_details_after_output', $meta, (int) $_POST['id']);

        }
    }

    private function delete()
    {
        if( current_user_can('manage_options') ) {
            wp_delete_post($_POST['id'], true);
            echo '#' . $_POST['id'] . ' deleted';
        }
    }

    private function save()
    {
        check_ajax_referer('fdc_nonce', 'check');

        if( !isset($_POST['data']) )
        {
            if( !isset($_POST['fdcUtility']) ) {
                return apply_filters('fdc_ajax_response_error', '');
            }

            /**
             * Filter the error Ajax response
             *
             * @since 1.2.0
             *
             * @param string $response Response text
             *
             */
            wp_send_json_error( apply_filters('fdc_ajax_response_error', __('Form data missing', 'fdc')));
        }

        /**
         * Filter how values should be stored.
         * By default all fields are stored as separated meta_keys.
         * Using this filter you can disable this feature and start to store fields as one Array.
         *
         * @since 1.3.0
         *
         */
        $store_fields_as_array = apply_filters('fdc_store_fields_as_array', false);

        $data = array();
        $prefixed_data = array();
        parse_str($_POST['data'], $data);

        $args = array(
            'post_type' => 'px_fdc',
            'post_content' => '',
            'post_title' => '',
            'post_status' => 'publish',
            'post_author' => 1
        );
        $id = wp_insert_post($args);
        $fields = array();

        if( ! is_wp_error($id) )
        {
            $data = apply_filters('fdc_pre_save_meta', $data);

            if( is_array($data) && !empty($data) )
            {
                foreach( $data as $key => $value )
                {
                    $fields[$this->prefix . $key] = $value;

                    if( $store_fields_as_array ) {
                        continue;
                    }

                    add_post_meta($id, $this->prefix . $key, $value);
                }
            }

            if( $store_fields_as_array ) {
                add_post_meta($id, $this->prefix . 'form_fields_data', $fields);
            }

            /**
             * Fires after new Entry with all its metadata is stored
             *
             * @since 1.0.0
             * @since 1.2.0 The `$fields` variable was added.
             *
             * @param int     $id       Entry ID
             * @param array   $fields   Metadata associated with the entry
             */
            do_action('fdc_form_data_saved', $id, $fields);

            if( !isset($_POST['fdcUtility']) ) {
                return apply_filters('fdc_ajax_response_success', '#' . $id . ' saved', $id, $fields);
            }

            /**
             * Filter the success Ajax response
             *
             * @since 1.2.0
             *
             * @param string $response Response text
             * @param int    $id       Entry ID
             * @param array  $fields   Metadata associated with the entry
             *
             */
            wp_send_json_success( apply_filters('fdc_ajax_response_success', '#' . $id . ' saved', $id, $fields) );
        }

        wp_send_json_error( $id->get_error_message() );
    }
}
