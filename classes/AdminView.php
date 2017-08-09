<?php

defined('ABSPATH') or die('No script kiddies please!');

class Form_Data_Collector_List_Table extends WP_List_Table
{
    function __construct()
    {
        global $status, $page;
        parent::__construct( array(
            'singular'  => 'fdc',
            'plural'    => 'fdcs',
            'ajax'      => false
        ) );
    }

    function display_tablenav($which)
    {
        echo '<div class="tablenav ' . esc_attr( $which ) . '">';

        if ( $this->has_items() )
        {
            echo '<div class="alignleft actions bulkactions">';
            $this->bulk_actions($which);
            echo '</div>';
        }

        $this->extra_tablenav($which);
        $this->pagination($which);
        echo '</div>';
    }

    function extra_tablenav($which)
    {
        if ( 'top' === $which ) {
            do_action('restrict_manage_px_fdc');
        }
    }

    function column_default($item, $column_name)
    {
        $actions = array(
            'view'      => sprintf('<a href="javascript:void(0);" data-id="%d" data-action="view">%s</a>', $item->ID, __('View', 'fdc')),
            'delete'    => sprintf('<a href="javascript:void(0);" data-id="%d" data-action="delete">%s</a>', $item->ID, __('Delete', 'fdc'))
        );

        switch($column_name)
        {
            case 'ID':
                printf('<a href="javascript:void(0)" data-id="%d" data-action="view">%d</a>', $item->ID, $item->ID);
                echo $this->row_actions($actions);
            break;
            case 'added':
                echo date_i18n( get_option( 'date_format' ) . ' ' . get_option('time_format'), strtotime($item->post_date) );
            break;
            default:
                echo apply_filters('fdc_table_list_columns_values', $column_name, $item);
        }
    }

    function get_columns()
    {
        $columns = array();

        return array_merge(
            array(
                'ID' => esc_attr__('Entry ID', 'fdc')
            ),
            apply_filters('fdc_table_list_columns', $columns),
            array(
                'added' => esc_attr__('Added', 'fdc'),
            )
        );
    }

    function prepare_items()
    {
        $per_page = apply_filters('fdc_table_list_items_per_page', 25);
        $columns = $this->get_columns();
        $hidden = array();
        $this->_column_headers = array($columns, $hidden);

        $query = new WP_Query(array(
            'post_type' => 'px_fdc',
            'posts_per_page' => -1
        ));

        $data = $query->posts;

        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        $this->items = $data;

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items/$per_page)
        ) );

        wp_reset_postdata();
    }
}
