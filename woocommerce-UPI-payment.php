<?php
/**
 * Plugin Name: Kiasa UPI Payment plugin for WooCommerce
 * Plugin URI: https://www.kiasa.in/plugin
 * Description: It enables a WooCommerce site to accept payments through UPI apps like BHIM, Google Pay, PhonePe or any Banking UPI app. Avoid payment gateway charges.
 * Author: Kiasa
 * Author URI: http://kiasa.in/
 * Version: 1.2
 *
 * Copyright: (c) 2018, Kiasa LLP
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * @package   WC-Gateway-UPI
 * 
 * Warranty information
 *      
 */


 
/**
 * Prevent direct calling
 */
 
defined( 'ABSPATH' ) or exit;

/* Make sure WooCommerce is active */

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}


function kiasa_upi_scripts_method() {
    wp_enqueue_script( 'newscript', plugins_url( '/woocommerce-UPI-payment.js' , __FILE__ ) );
}
add_action( 'wp_enqueue_scripts', 'kiasa_upi_scripts_method' );

/**
 * Following code is from example offline gateway from skyverge. the code is used as is
 * its only use is to add a new type of payment in woocommerce->settings->payments. It 
 * shows UPI as an option to customers when paying
 */
 
 register_activation_hook( __FILE__, 'woocommerce_UPI_payment_activate' );
 
 register_deactivation_hook( __FILE__, 'woocommerce_UPI_payment_deactivate' );
 
 register_uninstall_hook(__FILE__, 'woocommerce_UPI_payment_uninstall');
 
 
 function woocommerce_UPI_payment_activate()
 {
     
 }
 
 function woocommerce_UPI_payment_deactivate()
 {
     
 }
 
 function woocommerce_UPI_payment_uninstall()
 {
 }

 

/**
 * Add the gateway to WC Available Gateways
 * 
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + offline gateway
 */
function woocommerce_UPI_payment_add_to_gateways( $gateways ) {
	$gateways[] = 'WC_Gateway_offline';
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'woocommerce_UPI_payment_add_to_gateways' );

/**
 * Adds plugin page links
 * 
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function woocommerce_UPI_payment_gateway_plugin_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=offline_gateway' ) . '">' . __( 'Configure', 'wc-gateway-offline' ) . '</a>'
	);
	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'woocommerce_UPI_payment_gateway_plugin_links' );


/**
 *
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class 		WC_Gateway_Offline
 * @extends		WC_Payment_Gateway
 * @version		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		SkyVerge
 */
 
add_action( 'plugins_loaded', 'woocommerce_upi_payment_gateway_init', 11 );

function woocommerce_upi_payment_gateway_init() {
	class WC_Gateway_Offline extends WC_Payment_Gateway {
		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
	  
			$this->id                 = 'offline_gateway';
			$this->icon               = apply_filters('woocommerce_offline_icon', '');
			$this->has_fields         = false;
			$this->method_title       = __( 'UPI', 'wc-gateway-UPI' );
			$this->method_description = __( 'Allows customers to use UPI mobile app like Google Pay/ BHIM/ PhonePe to pay to your bank account directly using UPI.', 'wc-gateway-offline' );
		  
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
		  
			// Define user set variables
			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->instructions = $this->get_option( 'instructions', $this->description );
			$this->name 	 = $this->get_option( 'name' );
		    $this->vpa 		 = $this->get_option( 'vpa' );
		  
			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		  
			// Customer Emails
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
		}
	
	
		/**
		 * Initialize Gateway Settings Form Fields
		 */
		public function init_form_fields() {
	  
			$this->form_fields = apply_filters( 'woocommerce_upi_payment_form_fields', array(
		  
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'wc-gateway-offline' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable UPI Payment', 'wc-gateway-offline' ),
					'default' => 'yes'
				),
				
				'title' => array(
					'title'       => __( 'Title', 'wc-gateway-offline' ),
					'type'        => 'text',
					'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-offline' ),
					'default'     => __( 'UPI Payment', 'wc-gateway-offline' ),
					'desc_tip'    => true,
				),
				
				'description' => array(
					'title'       => __( 'Description', 'wc-gateway-offline' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc-gateway-offline' ),
					'default'     => __( 'It uses UPI apps like BHIM, Google Pay, PhonePe or any Banking UPI app to make payment', 'wc-gateway-offline' ),
					'desc_tip'    => true,
				),
				
				'instructions' => array(
					'title'       => __( 'Instructions', 'wc-gateway-offline' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wc-gateway-offline' ),
					'default'     => 'Please pay through UPI application like BHIM, Google Pay, PhonePe etc',
					'desc_tip'    => true,
				),
				'name' => array(
				'title'       => __( 'Your Store Name', 'wc-gateway-offline' ),
				'type'        => 'text',
				'description' => __( 'Please enter Your Store name', 'wc-gateway-offline' ),
				'default'     => '',
				'desc_tip'    => true,
			    ),
			'vpa' => array(
				'title'       => __( 'UPI VPA', 'wc-gateway-offline' ),
				'type'        => 'email',
				'description' => __( 'Please enter Your UPI VPA', 'wc-gateway-offline' ),
				'default'     => '',
				'desc_tip'    => true,
			    ),
			) );
		}


		/**
		 * Output for the order received page.
		 */
		public function thankyou_page() {
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) );
			}
		}
	
	
		/**
		 * Add content to the WC emails.
		 *
		 * @access public
		 * @param WC_Order $order
		 * @param bool $sent_to_admin
		 * @param bool $plain_text
		 */
		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		
			if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
		}
	
	
		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment( $order_id ) {
	
			$order = wc_get_order( $order_id );
			
			// Mark as on-hold (we're awaiting the payment)
			$order->update_status( 'on-hold', __( 'Awaiting offline payment', 'wc-gateway-offline' ) );
			
			// Reduce stock levels
			$order->reduce_order_stock();

			// Remove cart
			WC()->cart->empty_cart();
			
			// Return thankyou redirect
			return array(
				'result' 	=> 'success',
				'redirect'	=> $this->get_return_url( $order )
			);
		}
	
  } // end \WC_Gateway_Offline class
}

/**
 * This is the UPI payment code. Basically it creates a URL with payment link
 * and shows in checkout page
 */
 
/**
 * This gets called during thankyou screen in Woocommerce.
 * we read the saved store details and populate a HTML link for payments.
 */

add_action( "woocommerce_thankyou", 'woocommerce_UPI_payment_pay', 10, 1 ); 

function woocommerce_UPI_payment_pay($orderid) {
  
    $order = wc_get_order( $orderid );
    $order_data = $order->get_data(); // The Order data

/** 
 * proceed only if it is UPI payment.else stay silent
 */
 
    $payment_method_title = $order_data['payment_method_title'];
 //  error_log("payment method:" . $payment_method_title);
    if(!strcmp($payment_method_title, 'UPI Payment')) {
        
/**
 * get the total value of woocommerce order
 */
   
    $order_id = $order_data['id'];
    $order_billing_first_name = $order_data['billing']['first_name'];
    $grand_total = $order_data['total'];


    $payment_gateway = wc_get_payment_gateway_by_order( $orderid );

    $shopvpa = $payment_gateway->vpa;
    $shopname = $payment_gateway->name;

    ob_start();
    echo '<h2 class="woocommerce-order-details__title">Payment</h2>';
    if ( ! wp_is_mobile() ) :
    echo '<div id="qrcode" style="width:350px; height:350px;align:center" align="center"></div>';
    echo '<script>var qrcode = new QRCode(document.getElementById("qrcode"),{width : 350,height : 350});function makeCode () {var elText = "upi://pay?pa=' . $shopvpa . '&pn=' . $shopname . '&am=' . $grand_total . '&cu=INR&tn=OrderID ' . $orderid . '";qrcode.makeCode(elText);}makeCode();$("#text").on("blur", function () {makeCode();	}).on("keydown", function (e) {if (e.keyCode == 13) {makeCode();}});</script>';
    else :
    $a = '<a href="upi://pay?pa=' . $shopvpa . '&pn=' . $shopname . '&am=' . $grand_total . '&cu=INR&tn=OrderID ' . $orderid . ' "> Click here to pay through UPI </a> ';
    echo $a;
    endif;
    }
}
?>
