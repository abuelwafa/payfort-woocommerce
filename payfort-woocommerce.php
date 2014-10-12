<?php
/**
 * Plugin Name: Payfort Woocommerce payment gateway extension
 * Plugin URI: http://hive-ad.com
 * Description: Payfort Woocommerce payment gateway extension.
 * Version: 0.1
 * Author: Hive Advertising
 * Author URI: hhttp://hive-ad.com
*/
defined('ABSPATH') or die("No script kiddies please!");

function init_payfort_gateway_class() {

	if(!class_exists('WC_Payment_Gateway')) return;

	// localization
	load_plugin_textdomain('hive_woocommerce_payfort', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');

	class WC_Gateway_Payfort extends WC_Payment_Gateway{

		public $payfort_test_pspid;
		public $payfort_production_pspid;
		public $payfort_shain_passphrase;
		public $payfort_shaout_passphrase;

		public $payfort_accept_url;

		public $payfort_payment_page_title;

		public $payfort_payment_page_bg_color;
		public $payfort_payment_page_text_color;
		public $payfort_payment_page_btn_bg_color;
		public $payfort_payment_page_btn_text_color;
		public $payfort_payment_page_table_bg_color;
		public $payfort_payment_page_table_text_color;

		public $payfort_payment_page_logo;

		public $payfort_production_action_url;
		public $payfort_test_action_url;

		public $payfort_production_mode;

		public function __construct() {
			$this->id = 'payfort';
			$this->method_title = 'Payfort';
			$this->method_description = $this->get_option( 'description' );
			$this->has_fields = false;

			$this->payfort_production_action_url = 'https://secure.payfort.com/ncol/prod/orderstandard.asp';
			$this->payfort_test_action_url = 'https://secure.payfort.com/ncol/test/orderstandard.asp';
			// $this->payfort_test_action_url = 'http://localhost:8000/checkout';

			$this->init_form_fields();
			$this->init_settings();

			// initialize payfort attributes
			$this->title = $this->get_option('title');

			$this->payfort_test_pspid = $this->get_option('test_pspid');
			$this->payfort_production_pspid = $this->get_option('production_pspid');

			$this->payfort_test_shain_passphrase = $this->get_option('test_shain');
			$this->payfort_test_shaout_passphrase = $this->get_option('test_shaout');
			$this->payfort_production_shain_passphrase = $this->get_option('production_shain');
			$this->payfort_production_shaout_passphrase = $this->get_option('production_shaout');


			$this->payfort_payment_page_title = $this->get_option('payment_page_title');

			$this->payfort_payment_page_bg_color = $this->get_option('payment_page_bg_color');
			$this->payfort_payment_page_text_color = $this->get_option('payment_page_text_color');
			$this->payfort_payment_page_btn_bg_color = $this->get_option('payment_page_btn_bg_color');
			$this->payfort_payment_page_btn_text_color = $this->get_option('payment_page_btn_text_color');
			$this->payfort_payment_page_table_bg_color = $this->get_option('payment_page_table_bg_color');
			$this->payfort_payment_page_table_text_color = $this->get_option('payment_page_table_text_color');

			$this->payfort_payment_page_logo = $this->get_option('payment_page_logo');

			$this->payfort_production_mode = $this->get_option('production') == 'yes' ? true : false;


			$this->payfort_accept_url = WC()->api_request_url('WC_Gateway_Payfort');

			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options'));
			add_action('woocommerce_receipt_payfort', array($this, 'receipt_page'));
			add_action('woocommerce_thankyou_payfort', array($this, 'payfort_return_callback')); // i think this can be removed...

			add_action('woocommerce_api_wc_gateway_payfort', array($this, 'payfort_accept_redirection_callback'));

		}

		public function init_form_fields() {
			$this->form_fields = [
				'enabled' => [
					'title' => __('Enable/Disable', 'hive_woocommerce_payfort'),
					'type' => 'checkbox',
					'label' => __('Enable Payment through Payfort', 'hive_woocommerce_payfort'),
					'default' => 'yes'
				],
				'production' => [
					'title' => __('Production Payfort Environment', 'hive_woocommerce_payfort'),
					'type' => 'checkbox',
					'label' => __('Check to use the Production payfort PSPID/Account', 'hive_woocommerce_payfort'),
					'default' => 'no'
				],
				'title' => [
					'title' => __('Title', 'hive_woocommerce_payfort'),
					'type' => 'text',
					'description' => __('This controls the title which the user sees on the payment page.', 'hive_woocommerce_payfort'),
					'default' => __('Payfort', 'hive_woocommerce_payfort'),
					'desc_tip' => true,
				],
				'description' => [
					'title' => __('Customer Message', 'hive_woocommerce_payfort'),
					'type' => 'textarea',
					'default' => ''
				],
				'test_pspid' => [
					'title' => __(' Test PSPID', 'hive_woocommerce_payfort'),
					'type' => 'text',
					'description' => __('payfort affiliate name', 'hive_woocommerce_payfort'),
					'default' => __('', 'hive_woocommerce_payfort'),
					'desc_tip' => true,
				],
				'test_shain' => [
					'title' => __('Test SHA-IN Passphrase', 'hive_woocommerce_payfort'),
					'type' => 'text',
					'description' => __('', 'hive_woocommerce_payfort'),
					'default' => __('', 'hive_woocommerce_payfort'),
					'desc_tip' => true,
				],
				'test_shaout' => [
					'title' => __('Test SHA-OUT passphrase', 'hive_woocommerce_payfort'),
					'type' => 'text',
					'description' => __('', 'hive_woocommerce_payfort'),
					'default' => __('', 'hive_woocommerce_payfort'),
					'desc_tip' => true,
				],
				'production_pspid' => [
					'title' => __('Production PSPID', 'hive_woocommerce_payfort'),
					'type' => 'text',
					'description' => __('payfort affiliate name', 'hive_woocommerce_payfort'),
					'default' => __('', 'hive_woocommerce_payfort'),
					'desc_tip' => true,
				],
				'production_shain' => [
					'title' => __('Production SHA-IN Passphrase', 'hive_woocommerce_payfort'),
					'type' => 'text',
					'description' => __('', 'hive_woocommerce_payfort'),
					'default' => __('', 'hive_woocommerce_payfort'),
					'desc_tip' => true,
				],
				'production_shaout' => [
					'title' => __('Production SHA-OUT passphrase', 'hive_woocommerce_payfort'),
					'type' => 'text',
					'description' => __('', 'hive_woocommerce_payfort'),
					'default' => __('', 'hive_woocommerce_payfort'),
					'desc_tip' => true,
				],
				'payment_page_title' => [
					'title' => __('Payment Page Title', 'hive_woocommerce_payfort'),
					'type' => 'text',
					'description' => __('Main title to be viewed on the payment page', 'hive_woocommerce_payfort'),
					'default' => __('Payfort payment', 'hive_woocommerce_payfort'),
					'desc_tip' => true,
				],
				'payment_page_bg_color' => [
					'title' => __('Payment Page Background Color', 'hive_woocommerce_payfort'),
					'type' => 'text',
					'description' => __('Payment Page Background Color', 'hive_woocommerce_payfort'),
					'default' => __('#FFFFFF', 'hive_woocommerce_payfort'),
					'desc_tip' => true,
				],
				'payment_page_text_color' => [
					'title' => __('Payment Page text Color', 'hive_woocommerce_payfort'),
					'type' => 'text',
					'description' => __('Payment Page text Color', 'hive_woocommerce_payfort'),
					'default' => __('#121212', 'hive_woocommerce_payfort'),
					'desc_tip' => true,
				],
				'payment_page_btn_bg_color' => [
					'title' => __('Payment Page button Background Color', 'hive_woocommerce_payfort'),
					'type' => 'text',
					'description' => __('Payment Page Button Background Color', 'hive_woocommerce_payfort'),
					'default' => __('#CCCCCC', 'hive_woocommerce_payfort'),
					'desc_tip' => true,
				],
				'payment_page_btn_text_color' => [
					'title' => __('Payment Page Button text Color', 'hive_woocommerce_payfort'),
					'type' => 'text',
					'description' => __('Payment Page Button text Color', 'hive_woocommerce_payfort'),
					'default' => __('#121212', 'hive_woocommerce_payfort'),
					'desc_tip' => true,
				],
				'payment_page_table_bg_color' => [
					'title' => __('Payment Page table Background Color', 'hive_woocommerce_payfort'),
					'type' => 'text',
					'description' => __('Payment Page table Background Color', 'hive_woocommerce_payfort'),
					'default' => __('#CCCCCC', 'hive_woocommerce_payfort'),
					'desc_tip' => true,
				],
				'payment_page_table_text_color' => [
					'title' => __('Payment Page table text Color', 'hive_woocommerce_payfort'),
					'type' => 'text',
					'description' => __('Payment Page table text Color', 'hive_woocommerce_payfort'),
					'default' => __('#121212', 'hive_woocommerce_payfort'),
					'desc_tip' => true,
				],
				'payment_page_logo' => [
					'title' => __('Payment Page logo', 'hive_woocommerce_payfort'),
					'type' => 'text',
					'description' => __('Absolute HTTPS URL for Payment Page logo image', 'hive_woocommerce_payfort'),
					'default' => __('', 'hive_woocommerce_payfort'),
					'desc_tip' => true,
				]
			];
		}

		public function process_payment($order_id) {
			$order = new WC_Order( $order_id );
			return [
				'result' => 'success',
				'redirect' => $order->get_checkout_payment_url(true)
			];
		} // end of process_payment function

		// payfort form fields
		public function payfort_payment_fields($order_id){

			$order = new WC_Order($order_id);

			$argsArr = [
				// general parameters
				'PSPID' => $this->payfort_production_mode ? $this->payfort_production_pspid : $this->payfort_test_pspid,
				'AMOUNT' => $order->get_total()*100,
				'ORDERID' => $order->id,
				'CURRENCY' => 'EGP',
				'LANGUAGE' => 'en_US',
				'CN' => $order->billing_first_name . ' ' . $order->billing_last_name,
				'EMAIL' => $order->billing_email,
				'OWNERZIP' => $order->billing_postcode,
				'OWNERADDRESS' => $order->billing_address_1 . ',' . $order->billing_address_2,
				'OWNERCTY' => $order->billing_country,
				'OWNERTOWN' => $order->billing_city,
				'OWNERTELNO' => $order->billing_phone,

				// misc
				'HOMEURL' => home_url(),
				// 'CATALOGURL' => '',

				// payfort payment page presentation
				'TITLE' => $this->title,
				'BGCOLOR' => $this->payfort_payment_page_bg_color,
				'TXTCOLOR' => $this->payfort_payment_page_text_color,
				'TBLBGCOLOR' => $this->payfort_payment_page_table_bg_color,
				'TBLTXTCOLOR' => $this->payfort_payment_page_table_text_color,
				'BUTTONBGCOLOR' => $this->payfort_payment_page_btn_bg_color,
				'BUTTONTXTCOLOR' => $this->payfort_payment_page_btn_text_color,
				// 'FONTTYPE' => '',

				// post payment redirection parameters
				'ACCEPTURL' => $this->payfort_accept_url,
				'DECLINEURL' => $this->payfort_accept_url,
				'EXCEPTIONURL' => $this->payfort_accept_url,
				'CANCELURL' => $this->payfort_accept_url,

				// extra parameters
				// 'COMPLUS' => '',
				// 'PARAMPLUS' => ''


			];

			if(isset($this->payment_page_logo) && !empty($this->payfort_payment_page_logo)) {
				$argsArr['LOGO'] = $this->payfort_payment_page_logo;
			}

			return $argsArr;
		}

		// public function getPlusParameters() {
		// 	return [];
		// }

		public function receipt_page($order) {
			echo '<p>' . __('Thank you - your order is now pending payment. You should be automatically redirected to Payfort to make payment.', 'hive_woocommerce_payfort') . '</p>';
			echo $this->generate_payfort_form( $order );
		}

		public function admin_options() {
		?>
			<h2><?php _e('Payfort','hive_woocommerce_payfort'); ?></h2>
			<table class="form-table">
				<?php $this->generate_settings_html(); ?>
			</table>
		<?php
		}

		public function generate_payfort_form($order_id){

			$order = new WC_Order( $order_id );

			$payfort_args = $this->payfort_payment_fields($order->id);

				// sha calculation
				$payfort_args['SHASIGN'] = $this->calculateSHAIn($payfort_args);

			$payfort_args_array = array();

			foreach ( $payfort_args as $key => $value ) {
				$paypal_args_array[] = '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
			}

			wc_enqueue_js('
				$.blockUI({
					message: "' . esc_js(__('Thank you for your order. We are now redirecting you to Payfort to make payment.', 'woocommerce')) . '",
					baseZ: 99999,
					overlayCSS: {
						background: "#fff",
						opacity: 0.6
					},
					css: {
						padding:        "20px",
						zindex:         "9999999",
						textAlign:      "center",
						color:          "#555",
						border:         "3px solid #aaa",
						backgroundColor:"#fff",
						cursor:         "wait",
						lineHeight:		"24px",
					}
				});
				jQuery("#submit_paypal_payment_form").click();
			');

				$form_action_url = $this->payfort_production_mode ? $this->payfort_production_action_url : $this->payfort_test_action_url;

			return '<form action="' . esc_url($form_action_url) . '" method="post" id="paypal_payment_form" target="_top">
				' . implode('', $paypal_args_array) . '
				<!-- Button Fallback -->
				<div class="payment_buttons">
					<input type="submit" class="button alt" id="submit_paypal_payment_form" value="' . __('Pay via PayPal', 'woocommerce') . '" /> <a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Cancel order &amp; restore cart', 'woocommerce') . '</a>
				</div>
				<script type="text/javascript">
					jQuery(".payment_buttons").hide();
				</script>
			</form>';

		}

		public function payfort_return_callback() {
			$woocommerce->cart->empty_cart();
			wp_die('thank you action callback' . var_dump([$_GET, $_POST]));
			// wp_redirect($this->get_return_url($order));
		}

		public function payfort_accept_redirection_callback() {


			// check for the sha-out validation here...

			global $woocommerce;
			$woocommerce->cart->empty_cart();
			// die('payfort accept action callback' . var_dump([$_GET, $_POST]));
			wp_redirect($this->get_return_url($order));
		}

		public function calculateSHAIn($parameters){
			ksort($parameters);
			$passphrase = $this->payfort_production_mode ? $this->payfort_production_shain_passphrase : $this->payfort_test_shain_passphrase;

			$digestString = '';
			foreach ($parameters as $parameter => $value) {
				$digestString .= $parameter . '=' . $value . $passphrase;
			}
			return hash('sha256', $digestString);
		}

		public function calculateSHAOut($parameters){
			$actual_parameters = $parameters;
			unset($actual_parameters['SHASIGN']);
			ksort($parameters);
			$passphrase = $this->payfort_production_mode ? $this->payfort_production_shaout_passphrase : $this->payfort_test_shaout_passphrase;

			$digestString = '';
			foreach ($actual_parameters as $parameter => $value) {
				$digestString .= $parameter . '=' . $value . $passphrase;
			}

			return hash('sha256', $digestString);
		}

	} // end of class WC_Gateway_Payfort


	function addPayfortGatewayClass($methods) {
		$methods[] = 'WC_Gateway_Payfort';
		return $methods;
	}
	add_filter('woocommerce_payment_gateways', 'addPayfortGatewayClass');

} // end of init_payfort_gateway_class function


add_action('plugins_loaded', 'init_payfort_gateway_class' );

/*

	TODO:
	* add extra security parameter in the COMPLUS field
	* add query args to the return urls
	* change the payfort_payment_fields function to check for and validate admin values before appending them to the array
	* display the transaction feedback urls in the admin page for the plugin user

 */
