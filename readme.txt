=== Form Data Collector ===
Contributors: taunoh
Donate link: http://prixal.eu
Tags: form, email, forms, input, ajax, database
Requires at least: 4.4
Tested up to: 4.8
Stable tag: 1.3.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin will help you to collect and store form data.


== Description ==

This plugin is a developer's tookit for collecting form data from your WordPress site. It provides the necessary hooks for you to manage how data is stored and displayed later.

NOTE: Plugin will add "_px_fdc_" prefix to all form field names before they are stored in database. **Since 1.3.0** you can store all fields as one array (read the Changelog).

In HTML
<code>
<form action="#" method="post" id="form">
    <div style="position: absolute; left: -1000em;"><input type="text" name="pxnotempty" value="" /></div>
    <input type="text" name="itemID" />
    <input type="text" name="cname" />
    <input type="text" name="address2" />
    <button type="button" class="js-submit">Submit</button>
</form>
</code>

Send form data via Ajax.
<code>
$('.js-submit').on('click', function(e) {
    e.preventDefault();

    fdc.ajax.post('#form', {
        success: function(data) {
            console.log(data);
        },
        error: function(data) {
            console.log(data);
        }
    });
});
</code>

Use filter to remove fields that you don't want to store in database
<code>
function px_fdc_filter_data($data)
{
    if( !empty($data['pxnotempty']) )
        die();

    $allowed_fields = array('itemID', 'cname', 'cemail', 'address2');
    return array_intersect_key($data, array_flip($allowed_fields));
}
add_filter('fdc_pre_save_meta', 'px_fdc_filter_data', 10, 1);
</code>

Add columns to the Entries table in the Admin area.
<code>
function px_fdc_table_list_columns($columns)
{
    $columns = array(
        'item' => 'Item',
        'name' => 'Name'
    );
    return $columns;
}
add_filter('fdc_table_list_columns', 'px_fdc_table_list_columns');
</code>

Add data to the Entries table columns
<code>
function px_fdc_table_list_values($column_name, $item)
{
    $post_id = $item->ID;

    switch($column_name)
    {
        case 'item':
            return get_post_meta($post_id, '_px_fdc_itemID', true);
        break;
        case 'name':
            return get_post_meta($post_id, '_px_fdc_cname', true);
        break;
    }
}
add_filter('fdc_table_list_columns_values', 'px_fdc_table_list_values', 10, 2);
</code>

Control how data is displayed in the Entry Details modal.
<code>
function px_fdc_overview_output($meta)
{
    $new_meta = array();
    $new_keys = array(
        '_px_fdc_itemID' => 'Car',
        '_px_fdc_cname' => 'Name'
    );

    if( ! empty($meta) )
    {
        foreach( $meta as $key => $value )
        {
            if( isset($new_keys[$key]) ) {
                $new_meta[$new_keys[$key]]= $value;
                continue;
            }

            $new_meta[$key]= $value;
        }
    }

    return $new_meta;
}
add_filter('fdc_overview_details_output', 'px_fdc_overview_output');
</code>

Want to send email after form submit? You can use the `fdc_before_email_send` hook for that.
<code>
function px_fdc_send_email($post_id, $toClient, $toYou)
{
    $toClientContent = $toClient['content'];
    $toClientEmail = get_post_meta($post_id, '_px_fdc_CLIENT_EMAIL_FIELD_NAME', true);
    $toAdminEmail = $toYou['email'];
    $toAdminContent = $toYou['content'];

    wp_mail($toClientEmail, 'Email from my Website', $toClientContent);
}
add_action('fdc_before_email_send', 'px_fdc_send_email', 10, 3);
</code>


== Installation ==

1. Go to your admin area and select Plugins -> Add new from the menu.
2. Search for "Form Data Collector".
3. Click install.
4. Click activate.
5. A new menu item called "FDC" will be available in Admin menu.

== Changelog ==

= 1.3.1 =
* Updated CMB2 code

= 1.3.0 =
* NEW: Now you can store all fields as one meta_key value and still use get_post_meta() to access them. Use the `fdc_store_fields_as_array` filter to enable this feature (Default: false).
* Added action `fdc_overview_details_before_output`
* added action `fdc_overview_details_after_output`
* Updated usage info

= 1.2.0 =
* Introduced AJAX utility `fdc.ajax.post()` to send POST request to WordPress
* Added filter `fdc_ajax_response_error` to filter AJAX error response
* Added filter `fdc_ajax_response_success` to filter AJAX success response
* Added filter `fdc_enable_email_settings` to enable or disable email settings subpage (Default: true)
* Code clean up

= 1.1.3 =
* Added 'CMB2_LOADED' constant check

= 1.1.2 =
* WP_List_Table Class check is now in admin_init hook
* Minor updates

= 1.1.1 =
* Updated usage info and some text in code.
* Added loading state to Entry modal.

= 1.1 =
* Added `restrict_manage_px_fdc` action hook. Now you can add restriction filters to the Entries view. Combine this hook with `parse_query` filter to manage the output of Entries list.
* Added date column in the Entries view is now displayed by default.

= 1.0 =
* Initial release.
