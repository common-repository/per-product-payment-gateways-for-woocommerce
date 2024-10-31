<?php
/**
 * Plugin Name: Per Product Payment Gateways for WooCommerce
 * Plugin URI: http://woogang.com/ 
 * Version: 1.0.0
 * Author: WooGang
 * Author URI: http://woogang.com/
 * Description: Set different payment gateways for each product
 * Requires at least: 3.7
 * Tested up to: 4.3.1
  */
//require_once ABSPATH . WPINC . '/pluggable.php';;
//require_once dirname(dirname(__FILE__)).'/woocommerce/classes/class-wc-payment-gateways.php';
//require_once dirname(dirname(__FILE__)).'/woocommerce/classes/class-wc-cart.php';

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))))
{
    add_action( 'admin_enqueue_scripts', 'woogang_product_payments_enqueue' );
    add_action('admin_menu','woogang_product_payments_submenu_page');

    function woogang_product_payments_enqueue() {
        wp_enqueue_style( 'woogang_pd_payments_enqueue', plugin_dir_url( __FILE__ ) . '/css/style.css' );
    }

    function woogang_product_payments_submenu_page() {
        add_submenu_page('woocommerce', __('Product Payments', 'woogang'), __('Product Payments', 'woogang'), 'manage_options', 'woogang-product-payments', 'woogang_product_payments_settings');
    }

    function woogang_product_payments_footer_text( $text ) {
        if(! empty( $_GET['page'] ) && strpos( $_GET['page'], 'woogang-product-payments' ) === 0 ) {
            $text = sprintf('If you enjoy using <strong>Per Product Payment Gateways for Woocommerce</strong>, please <a href="%s" target="_blank">leave us a ★★★★★ rating</a>. A <strong style="text-decoration: underline;">huge</strong> thank you in advance!','http://woogang.com/' );
        }
        return $text;
    }

    function woogang_product_payments_update_footer($text) {
        if(! empty( $_GET['page'] ) && strpos( $_GET['page'], 'woogang-product-payments' ) === 0 ) {
            $text = 'Version 1.0.0';
        }
        return $text;   
    }

    function woogang_product_payments_settings()
    {   
       add_filter( 'admin_footer_text', 'woogang_product_payments_footer_text' );
       add_filter( 'update_footer', 'woogang_product_payments_update_footer' );
        ?>

        <?php 
        echo '<div class="wrap "><div id="icon-tools" class="icon32"></div>';
        echo '<h2 style="padding-bottom:15px; margin-bottom:20px; border-bottom:1px solid #ccc">' . __('Per Product Payment Gatways for WooCommerce', 'woogang') . '</h2>';
         ?>
        <div class="left-mc-setting">
            <p>Now you can have power of selecting payment gateway for each individual product.<br>
 This plugin lets you select the available payment method in the product add/edit screen itself.<br>
For example if you select only paypal, only paypal will available for that product by checking out.</p>
This plugin is developed by modifying free Dreamfox plugin-woocommerce payment gateway per product but is without any limitation.
           
        </div>
        
            <?php $user = wp_get_current_user(); ?>         

       
        <?php
    }

    add_action('add_meta_boxes', 'wpp_meta_box_add');

    /**
     * 
     */
    function wpp_meta_box_add() {
            add_meta_box('payments', 'Payments', 'wpp_payments_form', 'product', 'side', 'core');
    }

    /**
     * 
     * @global type $post
     * @global WC_Payment_Gateways $woo
     * @return type
     */
    function wpp_payments_form() {
            global $post, $woo;

            $productIds = get_option('woocommerce_product_apply');
            if (is_array($productIds)) {
                    foreach ($productIds as $key => $product) {
                            if (!get_post($product) || !count(get_post_meta($product, 'payments', true))) {
                                    unset($productIds[$key]);
                            }
                    }
            }
            update_option('woocommerce_product_apply', $productIds);

            $postPayments = count(get_post_meta($post->ID, 'payments', true)) ? get_post_meta($post->ID, 'payments', true) : array();
            if (count($productIds) >= 1000000 && !count($postPayments)) {
                    echo 'No limits';
                    return;
            }

            $woo = new WC_Payment_Gateways();
            $payments = $woo->payment_gateways;
            foreach ($payments as $pay) {
                    /**
                     *  skip if payment in disbled from admin
                     */
                    if ($pay->enabled == 'no') {
                            continue;
                    }
                    $checked = '';
                    if (is_array($postPayments) && in_array($pay->id, $postPayments)) {
                            $checked = ' checked="yes" ';
                    }
                    ?>  
                    <input type="checkbox" <?php echo $checked; ?> value="<?php echo $pay->id; ?>" name="pays[]" id="payment_<?php echo $pay->id; ?>" />
                    <label for="payment_<?php echo $pay->id; ?>"><?php echo $pay->title; ?></label>  
                    <br />  
                    <?php
            }
    }

    add_action('save_post', 'wpp_meta_box_save', 10, 2);

    /**
     * 
     * @param type $post_id
     * @param type $post
     * @return type
     */
    function wpp_meta_box_save($post_id, $post) {
            // Restrict to save for autosave
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                    return $post_id;
            }

            // Restrict to save for revisions
            if (isset($post->post_type) && $post->post_type == 'revision') {
                    return $post_id;
            }

            if (isset($_POST['post_type']) && $_POST['post_type'] == 'product' && isset($_POST['pays'])  ) {

                    $productIds = get_option('woocommerce_product_apply');
                    if (is_array($productIds) && !in_array($post_id, $productIds) && count($productIds) <= 1000000) {
                            $productIds[] = $post_id;
                            update_option('woocommerce_product_apply', $productIds);
                    }

                    //delete_post_meta($post_id, 'payments');    
                    $payments = array();
                    if ($_POST['pays']) {
                            foreach ($_POST['pays'] as $pay) {
                                    $payments[] = $pay;
                            }
                    }
                    update_post_meta($post_id, 'payments', $payments);
            }elseif (isset($_POST['post_type']) && $_POST['post_type'] == 'product'  ) {
                    update_post_meta($post_id, 'payments', array());
            }
    }

    /**
     * 
     * @global type $woocommerce
     * @param type $available_gateways
     * @return type
     */
    function wpppayment_gateway_disable_country($available_gateways) {
            global $woocommerce;
            $arrayKeys = array_keys($available_gateways);
            if (count($woocommerce->cart)) {
                    $items = $woocommerce->cart->cart_contents;
                    $itemsPays = '';
                    if (is_array($items)) {
                            foreach ($items as $item) {
                                    $itemsPays = get_post_meta($item['product_id'], 'payments', true);
                                    if (is_array($itemsPays) && count($itemsPays)) {
                                            foreach ($arrayKeys as $key) {
                                                    if (array_key_exists($key, $available_gateways) && !in_array($available_gateways[$key]->id, $itemsPays)) {
                                                            unset($available_gateways[$key]);
                                                    }
                                            }
                                    }
                            }
                    }
            }
            return $available_gateways;
    }

    add_filter('woocommerce_available_payment_gateways', 'wpppayment_gateway_disable_country');

}







?>