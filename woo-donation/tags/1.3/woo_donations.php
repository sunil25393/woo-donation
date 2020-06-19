<?php
/*
  Plugin Name: Woo Donation Plugin
  Plugin URI: www.sunilprajapati.in/plugins/woo_donation/
  Description: This plugin adds a donation field on the cart page. You can make donation as mandatory with this plugin.
  Version: 1.3
  Author: Sunil Prajapati
  Author URI: www.sunilprajapati.in
 */


/* * *********************** constant ddefine ************************ */
define('SPWD_VERSION', '1.2.1');

define('SPWD_REQUIRED_WP_VERSION', '3.2');

define('SPWD_PLUGIN', __FILE__);

define('SPWD_PLUGIN_BASENAME', plugin_basename(SPWD_PLUGIN));

define('SPWD_PLUGIN_NAME', trim(dirname(SPWD_PLUGIN_BASENAME), '/'));

define('SPWD_PLUGIN_DIR', untrailingslashit(dirname(SPWD_PLUGIN)));

add_action('admin_menu', 'register_woo_donation_submenu');

/* * *********************** add style ************************ */
add_action('admin_init', 'sp_edp_enqueued_style');

function sp_edp_enqueued_style() {
    if (is_admin()) {
        wp_enqueue_style('spwd_custom', spwd_plugin_url('css/sp_edp_custom.css'), array(), SPWD_VERSION, 'all');
    }
}

/* * *********************** load translatable files ************************ */
add_action('plugins_loaded', 'sp_wc_donations_language');

function sp_wc_donations_language() {
    load_plugin_textdomain('sp-wc-donations', false, dirname(plugin_basename(__FILE__)) . '/language/');
}

/* * *********************** Register menu ************************ */

function register_woo_donation_submenu() {
    add_submenu_page('woocommerce', 'Donation', 'Donation', 'manage_options', 'donation-settings-page', 'woo_donation_submenu_callback');
}

/* * *********************** TO check woocommerce plugin existance ************************ */
add_action('admin_init', 'sp_check_woocommerce_activation');

function sp_check_woocommerce_activation() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'sp_my_error_notice');
    }
}

/* * *********************** Error message to show when woocommerce is not installed in your website ************************ */

function sp_my_error_notice() {
    ?>
    <div class="error notice">
        <p><?php _e(' In order to use <b>Easy Donaion Plugin</b>, You need to install <a href="www.wordpress.org/plugins/woocommerce/" alt="wocommerce">woocommerce</a> plugin.', 'my_plugin_textdomain'); ?></p>
    </div>
    <?php
}

/* * ***********************admin Setting page ************************ */

function woo_donation_submenu_callback() {

    //Create new product START
    if (isset($_POST['woo_donations_add_new_product_form'])) {

        if ($_POST['woo_donations_new_product_title'] != "") {
            $new_product_title = $_POST['woo_donations_new_product_title'];
        }


        $add_new_donation_product_array = array(
            'post_title' => $new_product_title,
            'post_status' => 'publish',
            'post_type' => 'product'
        );
        $id_of_new_donation_product = wp_insert_post($add_new_donation_product_array);

        //update_post_meta($id_of_new_donation_product , '_visibility','hidden');		
        update_post_meta($id_of_new_donation_product, '_sku', 'checkout-donation-product');
        update_post_meta($id_of_new_donation_product, '_tax_class', 'zero-rate');
        update_post_meta($id_of_new_donation_product, '_tax_status', 'none');
        update_post_meta($id_of_new_donation_product, '_sold_individually', 'yes');
        update_post_meta($id_of_new_donation_product, '_virtual', 'yes');
        update_post_meta($id_of_new_donation_product, '_regular_price', (woo_donations_get_saved_strings_admin('donation_minimum_amount') != 0) ? woo_donations_get_saved_strings_admin('donation_minimum_amount') : 0);
        update_post_meta($id_of_new_donation_product, '_sale_price', (woo_donations_get_saved_strings_admin('donation_minimum_amount') != 0) ? woo_donations_get_saved_strings_admin('donation_minimum_amount') : 0);
        Generate_Featured_Image(spwd_plugin_url('img/donation.png'), $id_of_new_donation_product);

        if ($id_of_new_donation_product != "") {

            //save selected product ID
            $donation_product_new_option_value = $id_of_new_donation_product;

            if (get_option('woo_donations_product_id') !== false) {
                update_option('woo_donations_product_id', $donation_product_new_option_value);
            } else {
                // there is still no options on the database
                add_option('woo_donations_product_id', $donation_product_new_option_value, null, 'no');
            }
        }
    }
    //Create new product ENDS
    //save string translations 
    if (isset($_POST['woo_donations_save_string_translation'])) {
        if ($_POST['woo_donations_translations']['use_custom_translation']) {
            update_option('woo_donations_translations', $_POST['woo_donations_translations']);
        }
        if ($_POST['woo_donations_select_product_id'] != "") {

            //save selected product ID
            $donation_product_new_option_value = $_POST['woo_donations_select_product_id'];

            if (get_option('woo_donations_product_id') !== false) {
                update_option('woo_donations_product_id', $donation_product_new_option_value);
            } else {
                // there is still no options on the database
                add_option('woo_donations_product_id', $donation_product_new_option_value, null, 'no');
            }
        }
    }

    $get_saved_translation = get_option('woo_donations_translations');
    ?>	
    <div class="donation_admin"><h3>Woo Donation</h3>
        <div class="clearfix">
            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <th scope="row" class="titledesc"><label for="woo_donations_select_product_id"><?php echo __("Create Donation product"); ?></label></th>
                        <td class="forminp">
                            <form  action="" method="post">
                                <input name="woo_donations_new_product_title" class="text" type="text" placeholder="<?php echo __("Enter Product Title"); ?> ">
                                <input name="woo_donations_add_new_product_form" class="button btn-create" type="submit" value="Create Product">

                            </form>
                        </td>
                    </tr>			
                </tbody>
            </table>		
        </div>
        <form  action="" method="post">
            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <th scope="row" class="titledesc"><label for="woo_donations_select_product_id"><?php echo __("Select Donation product"); ?></label></th>
                        <td class="forminp">
                            <select name="woo_donations_select_product_id" id="woo_donations_select_product_id" style="" class="select email_type" >
                                <option value=""><?php echo __("Select Donation product"); ?></option>

                                <?php
                                $query_existing_hidden_products = new WP_Query(array(
                                    'posts_per_page' => -1,
                                    'post_type' => array('product')
                                ));


                                while ($query_existing_hidden_products->have_posts()) {

                                    $query_existing_hidden_products->the_post();
                                    ?>

                                    <option value="<?php echo get_the_ID(); ?>" <?php
                                    if (get_option('woo_donations_product_id') == get_the_ID()) {
                                        echo 'selected="selected"';
                                    }
                                    ?>> <?php echo get_the_title(); ?> </option>

                                    <?php
                                }
                                wp_reset_postdata();
                                ?>
                            </select>
                            <p class="description"> <?php echo __("Select Donation Product if you have created it."); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row" class="titledesc"><label for="woo_donations_use_custom_translation"><?php echo __("Use custom text"); ?></label></th>
                        <td class="forminp">
                            <select name="woo_donations_translations[use_custom_translation]" class="text" type="text" style="width:80%">
                                <option value="no" <?php selected($get_saved_translation['use_custom_translation'], 'no'); ?>><?php echo __("No , use default text for now"); ?> </option>
                                <option value="yes" <?php selected($get_saved_translation['use_custom_translation'], 'yes'); ?>><?php echo __("Yes ,I want to use custom texts as defined below"); ?> </option>
                            </select>
                        </td>
                    </tr>				

                    <tr valign="top">
                        <th scope="row" class="titledesc"><label for="woo_donations_single_product_text"><?php echo __("Single Product page title"); ?> </label></th>
                        <td class="forminp">
                            <input name="woo_donations_translations[single_product_text]" class="text" type="text" style="width:80%" value="<?php echo woo_donations_get_saved_strings_admin('single_product_text'); ?>">
                            <p class="description"><?php echo __("EX : Enter the amount you wish to donate."); ?></p>
                        </td>
                    </tr>			

                    <tr valign="top">
                        <th scope="row" class="titledesc"><label for="woo_donations_single_product_text"><?php echo __("Single product confirmation"); ?> </label></th>
                        <td class="forminp">
                            <input name="woo_donations_translations[donation_added_single_product_text]" class="text" type="text" style="width:80%" value="<?php echo woo_donations_get_saved_strings_admin('donation_added_single_product_text'); ?>">
                            <p class="description"><?php echo __("EX : Donation added! Thanks for supporting us."); ?></p>
                        </td>
                    </tr>				



                    <tr valign="top">
                        <th scope="row" class="titledesc"><label for="woo_donations_cart_header_text"><?php echo __("Cart Text"); ?> </label></th>
                        <td class="forminp">
                            <input name="woo_donations_translations[cart_header_text]" class="text" type="text"  style="width:80%" value="<?php echo woo_donations_get_saved_strings_admin('cart_header_text'); ?>">
                            <p class="description"><?php echo __("EX : Add a donation to your order."); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row" class="titledesc"><label for="cart_header_subtitle"><?php echo __("Cart donation Subtitle"); ?> </label></th>
                        <td class="forminp">
                            <input name="woo_donations_translations[cart_header_subtitle]" class="text" type="text"  style="width:80%" value="<?php echo woo_donations_get_saved_strings_admin('cart_header_subtitle'); ?>">
                            <p class="description"><?php echo __("EX : Raise your hand to support Us."); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row" class="titledesc"><label for="woo_donations_cart_button_text"><?php echo __("Cart Donation Button Text"); ?> </label></th>
                        <td class="forminp">
                            <input name="woo_donations_translations[cart_button_text]" class="text" type="text"  style="width:80%" value="<?php echo woo_donations_get_saved_strings_admin('cart_button_text'); ?>">
                            <p class="description"><?php echo __("EX : Add Donation."); ?></p>
                        </td>
                    </tr>			


                    <tr valign="top">
                        <th scope="row" class="titledesc"><label for="woo_donations_checkout_title_text"><?php echo __("Checkout Title Text"); ?> </label></th>
                        <td class="forminp">
                            <input name="woo_donations_translations[checkout_title_text]" class="text" type="text"  style="width:80%" value="<?php echo woo_donations_get_saved_strings_admin('checkout_title_text'); ?>">
                            <p class="description"><?php echo __("EX : Add a donation to your order."); ?></p>
                        </td>
                    </tr>				


                    <tr valign="top">
                        <th scope="row" class="titledesc"><label for="woo_donations_checkout_text"><?php echo __("Checkout  Text"); ?> </label></th>
                        <td class="forminp">
                            <input name="woo_donations_translations[checkout_text]" class="text" type="text"  style="width:80%" value="<?php echo woo_donations_get_saved_strings_admin('checkout_text'); ?>">
                            <p class="description"><?php echo __("EX : If you wish to add a donation you can do so.."); ?></p>
                        </td>
                    </tr>				
                    <tr valign="top">
                        <th scope="row" class="titledesc"><label for="hide_donation_product_on_shop"><?php echo __("Hide Donation Product On shop"); ?> </label></th>
                        <td class="forminp">
                            <select name="woo_donations_translations[hide_donation_product_on_shop]" class="text" type="text" style="width:80%">
                                <option value="no" <?php selected($get_saved_translation['hide_donation_product_on_shop'], 'no'); ?>><?php echo __("No, Let it display"); ?> </option>
                                <option value="yes" <?php selected($get_saved_translation['hide_donation_product_on_shop'], 'yes'); ?>><?php echo __("Yes , Hide It"); ?></option>
                            </select>
                            <p class="description"><?php echo __("if yes, It will Hide donation proct from shop page."); ?> </p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row" class="titledesc"><label for="donation_minimum_amount"><?php echo __("Set Minimum Donation Amount"); ?> </label></th>
                        <td class="forminp">
                            <input name="woo_donations_translations[donation_minimum_amount]" class="text" type="text"  style="width:80%" value="<?php echo woo_donations_get_saved_strings_admin('donation_minimum_amount'); ?>">
                            <p class="description"><?php echo __("Enter Donation  Minimum Amount."); ?> </p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row" class="titledesc"><label for="donation_mandatory"><?php echo __("Make Donation Mandatory"); ?> </label></th>
                        <td class="forminp">
                            <select name="woo_donations_translations[donation_mandatory]" class="text" type="text" style="width:80%">
                                <option value="no" <?php selected($get_saved_translation['donation_mandatory'], 'no'); ?>><?php echo __("No, Let it be optional"); ?> </option>
                                <option value="yes" <?php selected($get_saved_translation['donation_mandatory'], 'yes'); ?>><?php echo __("Yes , Make it Mandatory"); ?> </option>
                            </select>
                            <p class="description"><?php echo __("if yes, Donation will be Required."); ?> </p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row" class="titledesc"><label for="donation_automatic"><?php echo __("Automatically Add Donation to cart"); ?> </label></th>
                        <td class="forminp">
                            <select name="woo_donations_translations[donation_automatic]" class="text" type="text" style="width:80%">
                                <option value="no" <?php selected($get_saved_translation['donation_automatic'], 'no'); ?>><?php echo __("No, Let customer to do it"); ?> </option>
                                <option value="yes" <?php selected($get_saved_translation['donation_automatic'], 'yes'); ?>><?php echo __("Yes , Add it Automatically"); ?> </option>
                            </select>
                            <p class="description"><?php echo __("If Yes, Donation will automatically added in cart with minimum amount when customer visit cart page."); ?></p>
                        </td>       
                    </tr>
                    <tr valign="top">
                        <th scope="row" class="titledesc"><label for="donation_table_position"><?php echo __("Donation Form Position"); ?> </label></th>
                        <td class="forminp">
                            <select name="woo_donations_translations[donation_table_position]" class="text" type="text" style="width:80%">
                                <option value="before" <?php selected($get_saved_translation['donation_table_position'], 'before'); ?>><?php echo __("Before Cart Form"); ?> </option>
                                <option value="after" <?php selected($get_saved_translation['donation_table_position'], 'after'); ?>><?php echo __("After Cart Form"); ?> </option>
                            </select>

                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row" class="titledesc"><label for="donation_css"><?php echo __("Custom Css"); ?> </label></th>
                        <td class="forminp">
                            <textarea name="woo_donations_translations[donation_css]" rows="10" ><?php echo $get_saved_translation['donation_css']; ?></textarea>
                        </td>
                    </tr>
                </tbody>

            </table>		

            <p class="submit">
                <input name="woo_donations_save_string_translation" class="button-primary" type="submit" value="Save Settings">        			        
            </p>

        </form>
    </div>
    <div class="spwd_promo clearfix">
        <div class="w50">
            <p class="description"><b><?php echo __("NOTE"); ?> :</b><?php echo __("Donation Product should be non taxable,not shippable product. Also Keep in mind that the new product title will be visible on the cart, the checkout page and invoice so name it something like DONATIONS"); ?> </p>
            <p class="description"><h2><?php echo __("For any kind of query, You can leave"); ?> <a href="http://sunilprajapati.in/woo-donation/"><?php echo __("Comment here"); ?></a></h2></p>
            <p class="description"><h2><?php echo __("For full documation of Plugin"); ?> <a href="http://sunilprajapati.in/woo-donation/"><?php echo __("Click here"); ?></a></h2></p>
            <p class="description"><h2> Rate Us :  <a href="https://wordpress.org/support/view/plugin-reviews/woo-donation">Click Here</a></h2></p>
            <p class="description"><h2> For Plugin demo :  <a href="http://sunilprajapati.in/woo-donation-demo/">Click Here</a></h2></p>
            <p class="description">Admin Access : demo / demo</p>
            <p></p>
        </div>
        <div class="w50">
            <h2>
                <a href="https://wordpress.org/plugins/easy-custom-cssjs/"> Easy Custom Css/Js</a></h2>
            <a href="https://wordpress.org/plugins/easy-custom-cssjs/">
                <img src="http://sunilprajapati.in/wp-content/uploads/2016/07/banner-772x250-Recovered-1-e1468772793646.png" alt="woo-donation" style="    width: 100%;">
            </a>
        </div>
    </div>

    <?php
}

//woocommerce_donation_submenu_callback
//current product ID 
if (get_option('woo_donations_product_id') !== false) {

//defines the ID of the product to be used as donation
    define('DONATE_PRODUCT_ID', get_option('woo_donations_product_id'));
}

/* * ***********************  check if donation exist or not ************************ */
if (!function_exists('donation_exists')) {

    function donation_exists() {

        global $woocommerce;

        if (sizeof($woocommerce->cart->get_cart()) > 0) {

            foreach ($woocommerce->cart->get_cart() as $cart_item_key => $values) {

                $_product = $values['data'];

                if ($_product->id == DONATE_PRODUCT_ID)
                    return true;
            }
        }
        return false;
    }

}

/* * ***********************  Add donation form in front ************************ */
$get_saved_options = get_option('woo_donations_translations');
if ($get_saved_options['donation_table_position'] == 'after') {
    add_action('woocommerce_after_cart', 'sp_woocommerce_after_cart_table');
} else {
    add_action('woocommerce_before_cart', 'sp_woocommerce_after_cart_table');
}
add_shortcode('woo_donation_form', 'sp_woocommerce_after_cart_table');

if (!function_exists('sp_woocommerce_after_cart_table')) {

    function sp_woocommerce_after_cart_table() {

        global $woocommerce;
        $donate = isset($woocommerce->session->sp_donation) ? floatval($woocommerce->session->sp_donation) : 0;

        if (!donation_exists()) {
            unset($woocommerce->session->sp_donation);
        }

        if (!donation_exists()) {
            ?>
            <script>function isNumber(evt) {
                    evt = (evt) ? evt : window.event;
                    var charCode = (evt.which) ? evt.which : evt.keyCode;
                    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                        return false;
                    }
                    return true;
                }
                function donationvalidationEvent() {
                    var amount = document.getElementById("sp-donation").value;
                    var min =<?php echo woo_donations_get_saved_strings_admin('donation_minimum_amount'); ?>;
                    if (amount < min) {
                        document.getElementById("sp-donation").className = "error";
                        document.getElementById("mandatory_error").innerHTML = "Minimum Allowed donation is " + min;
                        return false;
                    } else {
                        document.getElementById("sp-donation").className = "success";
                        document.getElementById("donation_form").submit();
                        ;
                        return true;
                    }
                }
            </script>
            <style>
                #sp-donation.error{
                    border-color:red;
                }
                #sp-donation.success{
                    border-color:green;
                }
                <?php echo woo_donations_get_saved_strings_admin('donation_css'); ?>
            </style>
            <p>&nbsp;</p>
            <tr class="donation-block">
                <td colspan="6">

                    <p class="message"><h2>

                        <?php
                        if (woocommerce_donations_get_saved_strings('cart_header_text')) {
                            echo woocommerce_donations_get_saved_strings('cart_header_text');
                        } else {
                            _e('Add A Donation&#63;', 'sp-wc-donations');
                        }
                        ?>

                    </h2></p>

                <p><?php
                    if (woocommerce_donations_get_saved_strings('cart_header_subtitle')) {
                        echo woocommerce_donations_get_saved_strings('cart_header_subtitle');
                    } else {
                        _e('Raise your hand to support us!', 'sp-wc-donations');
                    }
                    ?></p>

                <?php if (woo_donations_get_saved_strings_admin('donation_minimum_amount') != '') { ?>
                    <form action="" method="post" id="donation_form" onsubmit="return donationvalidationEvent()">
                    <?php } else { ?>
                        <form action=""method="post">   
                        <?php } ?>

                        <div class="input text">

                            <input type="text" name="sp-donation" id="sp-donation"  onkeypress="return isNumber(event)" class="input-text" value placeholder="Enter An Amount &#40;Number Only&#41;"/>
                            &nbsp;
                            <?php if (woocommerce_donations_get_saved_strings('cart_button_text')) { ?>
                                <input type="submit" name="donate-btn"  class="fusion-button fusion-button-default fusion-button-small button default small" value="<?php echo woocommerce_donations_get_saved_strings('cart_button_text'); ?>"/>
                            <?php } else { ?>
                                <input type="submit" name="donate-btn"  class="fusion-button fusion-button-default fusion-button-small button default small" value="<?php _e('Donate', 'sp-wc-donations'); ?>"/>
                            <?php } ?>

                            <br>  <span id="mandatory_error" style="color:red;"></span>
                        </div>
                    </form>

            </td>
            </tr>
            <?php
        }
    }

}

/* * *********************** Process the donation ************************ */

add_action('template_redirect', 'sp_process_donation');

if (!function_exists('sp_process_donation')) {

    function sp_process_donation() {

        global $woocommerce;

        $donation = isset($_POST['sp-donation']) && !empty($_POST['sp-donation']) ? floatval($_POST['sp-donation']) : false;

        if ($donation && isset($_POST['donate-btn'])) {

            // add item to basket
            $found = false;

            // add to session
            if ($donation > 0) {
                $woocommerce->session->sp_donation = $donation;

                //check if product already in cart
                if (sizeof($woocommerce->cart->get_cart()) > 0) {


                    foreach ($woocommerce->cart->get_cart() as $cart_item_key => $values) {

                        $_product = $values['data'];

                        if ($_product->id == DONATE_PRODUCT_ID) {
                            $found = true;
                        }
                    }

                    // if product not found, add it
                    if (!$found) {

                        $woocommerce->cart->add_to_cart(DONATE_PRODUCT_ID);
                    }
                } else {
                    // if no products in cart, add it
                    $woocommerce->cart->add_to_cart(DONATE_PRODUCT_ID);
                }
            }
        }
    }

}

/* * *********************** Get donation price ************************ */
add_filter('woocommerce_get_price', 'sp_get_price', 10, 2);

if (!function_exists('sp_get_price')) {

    function sp_get_price($price, $product) {

        global $woocommerce;

        if ($product->id == DONATE_PRODUCT_ID) {

            if (isset($_POST['sp-donation'])) {
                return isset($woocommerce->session->sp_donation) ? floatval($woocommerce->session->sp_donation) : 0;
            }

            if (isset($_POST['sp_wc_donation_from_single_page'])) {

                return ($_POST['sp_wc_donation_from_single_page'] > 0) ? floatval($_POST['sp_wc_donation_from_single_page']) : 0;
            }

            return isset($woocommerce->session->sp_donation) ? floatval($woocommerce->session->sp_donation) : 0;
        }

        return $price;
    }

}

/* * *********************** Change free text  ************************ */
add_filter('woocommerce_free_price_html', 'sp_change_free_text', 12, 2);

if (!function_exists('sp_change_free_text')) {

    function sp_change_free_text($price, $product_object) {

        global $woocommerce;

        if (!is_admin()) {

            if (isset($product_object->id)) {

                if (defined('DONATE_PRODUCT_ID')) {

                    if ($product_object->id == DONATE_PRODUCT_ID) {

                        if (isset($woocommerce->session->sp_donation)) {
                            if ($woocommerce->session->sp_donation) {
                                return 'Donation  added';
                            }
                        }
//return 'Enter the amount you wish to donate' . print_r($woocommerce->session);

                        if (woocommerce_donations_get_saved_strings('single_product_text')) {
                            return '<span class="enter_donation_amount_single_page">' . woocommerce_donations_get_saved_strings('single_product_text') . '</span>';
                        }

                        return '<span class="enter_donation_amount_single_page">' . _e('Enter the amount you wish to donate', 'sp-wc-donations') . '</span>';
                    }
                }
            }
        }

        return $price;
    }

}

/* * *********************** Add daontion input on sigle product page ************************ */
add_action('woocommerce_before_add_to_cart_button', 'sp_add_input_on_single_product_page');

if (!function_exists('sp_add_input_on_single_product_page')) {

    function sp_add_input_on_single_product_page() {

        global $woocommerce, $post;

        $current_donation_value = 0;

        if (defined('DONATE_PRODUCT_ID')) {

            if ($post->ID == DONATE_PRODUCT_ID) {


                if (!donation_exists()) {
                    unset($woocommerce->session->sp_donation);
                }

                if (!isset($woocommerce->session->sp_donation)) {
                    ?>
                    <input name="sp_wc_donation_from_single_page" value="<?php echo $current_donation_value; ?>">

                    <?php
                } else {
                    ?>
                    <p class="sp_wc_donation_from_single_page_added">

                        <?php
                        if (woocommerce_donations_get_saved_strings('single_product_text')) {

                            echo woocommerce_donations_get_saved_strings('donation_added_single_product_text');
                        } else {

                            printf(__('Donation added . Check it on the  <a href="%s"> cart page </a>', 'sp-wc-donations'), $woocommerce->cart->get_cart_url());
                        }
                        ?>

                    </p>
                    <?php
                }
            }
        } // if defined
    }

}

/* * ***********************Change "add to cart" on single page ************************ */
add_filter('woocommerce_product_single_add_to_cart_text', 'sp_custom_cart_button_text_single_page');

if (!function_exists('sp_custom_cart_button_text_single_page')) {

    function sp_custom_cart_button_text_single_page($text) {

        global $post, $woocommerce;

        if (defined('DONATE_PRODUCT_ID')) {

            if ($post->ID == DONATE_PRODUCT_ID) {
                if (isset($woocommerce->session->sp_donation)) {
                    $text = _e('Donation added', 'sp-wc-donations');
                }
            }
        }

        return $text;
    }

}

/* * *********************** Hide  the "ADD TO CART" on single page if donations already added ************************ */
add_action('wp_head', 'sp_wc_donation_hide_add_to_cart_on_single_product');

if (!function_exists('sp_wc_donation_hide_add_to_cart_on_single_product')) {

    function sp_wc_donation_hide_add_to_cart_on_single_product() {

        global $woocommerce;

        if (defined('DONATE_PRODUCT_ID')) {

            if (isset($woocommerce->session->sp_donation)) {
                echo '<style>
						.woocommerce div.product.post-' . DONATE_PRODUCT_ID . ' form.cart .button {
							display:none;
						}
					 </style>';
            }
        }
    }

}

/* * *********************** Add Donation on cart ************************ */
add_filter('woocommerce_add_cart_item', 'sp_wc_donation_add_cart_item_data', 14, 2);

if (!function_exists('sp_wc_donation_add_cart_item_data')) {

    function sp_wc_donation_add_cart_item_data($cart_item) {
        global $woocommerce;

        if (defined('DONATE_PRODUCT_ID')) {

            if ($cart_item['product_id'] == DONATE_PRODUCT_ID) {

                //if the user is adding from single product page 
                if (isset($_POST['sp_wc_donation_from_single_page'])) {

                    $woocommerce->session->sp_donation = floatval($_POST['sp_wc_donation_from_single_page']);
                }
            }
        }

        return $cart_item;
    }

}

/* * *********************** Add Donation Link on checkout page ************************ */
add_action('woocommerce_review_order_before_payment', 'sp_donations_add_link_on_checkout');

if (!function_exists('sp_donations_add_link_on_checkout')) {

    function sp_donations_add_link_on_checkout() {

        global $woocommerce;

        $products_ids_in_cart = false;

        //check if donation is already in cart 
        foreach ($woocommerce->cart->get_cart() as $cart_item_key => $values) {

            $_product = $values['data'];

            $products_ids_in_cart[$_product->id] = $_product->id;
        }

        //if no donation found on cart ... show a link on checkout page
        if (is_array($products_ids_in_cart)) {

            if (!in_array(DONATE_PRODUCT_ID, $products_ids_in_cart)) {
                ?>
                <div style="margin: 0 -1px 24px 0;">
                    <h3>  

                        <?php
                        if (woocommerce_donations_get_saved_strings('checkout_title_text')) {
                            echo woocommerce_donations_get_saved_strings('checkout_title_text');
                        } else {
                            _e('Add a donation to your order', 'sp-wc-donations');
                        }
                        ?>

                    </h3> 


                    <?php
                    if (woocommerce_donations_get_saved_strings('checkout_text')) {
                        echo woocommerce_donations_get_saved_strings('checkout_text');
                    } else {
                        printf(__('If you wish to add a donation you can do so on the <a href="%s"> cart page </a>', 'sp-wc-donations'), $woocommerce->cart->get_cart_url());
                    }
                    ?>					


                </div>
                <?php
            } //end if "no donation found on cart"
        } //end if is array $products_ids_in_cart
    }

}



/* * ***********************  Get translated texts for backend plugin options ************************ */

function woo_donations_get_saved_strings_admin($key) {
    $saved_strings_array = get_option('woo_donations_translations');
    if (isset($saved_strings_array[$key])) {
        return stripcslashes(esc_html($saved_strings_array[$key]));
    }

    return false;
}

/* * *********************** Get translated texts for frontend ************************ */

function woocommerce_donations_get_saved_strings($key) {

    $saved_strings_frontend_array = get_option('woo_donations_translations');

    if ($saved_strings_frontend_array['use_custom_translation'] == 'yes') {

        if ($saved_strings_frontend_array[$key]) {
            return stripcslashes($saved_strings_frontend_array[$key]);
        }
    }

    return false;
}

/* * *********************** Hide donation product in shop page ************************ */
add_action('wp_head', 'sp_wc_donation_prdocut_hide_on_shop');

if (!function_exists('sp_wc_donation_prdocut_hide_on_shop')) {

    function sp_wc_donation_prdocut_hide_on_shop() {

        global $woocommerce;

        if (defined('DONATE_PRODUCT_ID')) {
            $hide = woo_donations_get_saved_strings_admin('hide_donation_product_on_shop');
            if ($hide == 'yes') {
                echo '<style>
						.post-' . DONATE_PRODUCT_ID . '{
							display:none!important;
						}
					 </style>';
            }
        }
    }

}

/* * *********************** Make Donation Mandatory ************************ */
add_action('woocommerce_before_checkout_form', 'sp_checkout_field_process');

function sp_checkout_field_process() {
    // Check if set, if its not set add an error.
    $get_saved_translation = get_option('woo_donations_translations');
    if ($get_saved_translation['donation_mandatory'] == 'yes') {
        if (!donation_exists()) {
            global $woocommerce;
            $cart_url = $woocommerce->cart->get_cart_url();
            wc_add_notice(__('Please add Donation!'), 'error');
            wp_redirect($cart_url);
            exit;
        }
    }
}

/* * *********************** add item to cart on visit ************************ */
add_action('template_redirect', 'add_product_to_cart');

function add_product_to_cart() {
    if (!is_admin()) {
        $am = woo_donations_get_saved_strings_admin('donation_automatic');
        if ($am == 'yes') {
            $product_id = DONATE_PRODUCT_ID;
            $found = false;
            //check if product already in cart
            if (sizeof(WC()->cart->get_cart()) > 0) {
                foreach (WC()->cart->get_cart() as $cart_item_key => $values) {
                    $_product = $values['data'];
                    if ($_product->id == $product_id)
                        $found = true;
                }
                // if product not found, add it
                if (!$found)
                    WC()->cart->add_to_cart($product_id);
            } else {
                // if no products in cart, add it
                WC()->cart->add_to_cart($product_id);
            }
        }
    }
}

function spwd_plugin_url($path = '') {
    $url = plugins_url($path, SPWD_PLUGIN);

    if (is_ssl() && 'http:' == substr($url, 0, 5)) {
        $url = 'https:' . substr($url, 5);
    }
    return $url;
}

function Generate_Featured_Image($image_url, $post_id) {
    $upload_dir = wp_upload_dir();
    $image_data = file_get_contents($image_url);
    $filename = basename($image_url);
    if (wp_mkdir_p($upload_dir['path']))
        $file = $upload_dir['path'] . '/' . $filename;
    else
        $file = $upload_dir['basedir'] . '/' . $filename;
    file_put_contents($file, $image_data);

    $wp_filetype = wp_check_filetype($filename, null);
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name($filename),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    $attach_id = wp_insert_attachment($attachment, $file, $post_id);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    $res1 = wp_update_attachment_metadata($attach_id, $attach_data);
    $res2 = set_post_thumbnail($post_id, $attach_id);
}
