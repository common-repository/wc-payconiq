<?php

namespace payconiq\model;

use payconiq\lib\Payconiq_Client;

class Gateway_Payconiq extends \WC_Payment_Gateway {

	/**
	 * Whether or not logging is enabled
	 *
	 * @var bool
	 *
	 * @since 1.0.0
	 */
	public static $log_enabled = false;

	/**
	 * Logger instance
	 *
	 * @var \WC_Logger
	 *
	 * @since 1.0.0
	 */
	public static $log = false;

	/**
	 * Is it Sandbox (false) or Production (true)
	 * @var bool
	 *
	 * @since 1.0.0
	 */
	protected $testmode;

	/**
	 * Is it a mobile
	 * @var bool
	 * 
	 * @since 1.0.0
	 */
	protected $mobile;

	/**
	 * Is endpoint
	 * @var bool
	 * 
	 * @since 1.0.0
	 */
	protected $endpoint;

	/**
	 * Check type
	 * @var string
	 * 
	 * @since 1.0.2
	 */
	protected $type;

	/**
	 * Constructor for the gateway.
	 *
	 * @since 1.0.0
	 */
	public function __construct($endpoint = true, $type = 'start') {
		$this->id                 = strtolower( get_class( $this ) );
		$this->has_fields         = false;
		$this->order_button_text  = esc_html__( 'Pay with Payconiq', 'payconiq' );
		$this->method_title       = esc_html__( 'Payconiq', 'payconiq' );
		$this->method_description = esc_html__( 'Take payments through the Payconiq app.', 'payconiq' );
		$this->supports           = array(
			'products',
			'refunds',
		);
		$this->endpoint = $endpoint;

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title          = esc_html__('Pay with Payconiq', 'payconiq');
		$this->description    = esc_html__('Pay fast and simple with the Payconiq App.', 'payconiq');
		$this->testmode       = 'yes' === $this->get_option( 'testmode', 'no' );
		$this->debug          = 'yes' === $this->get_option( 'debug', 'no' );
		$this->email          = $this->get_option( 'email' );
		$this->receiver_email = $this->get_option( 'receiver_email', $this->email );
		$this->identity_token = $this->get_option( 'identity_token' );
		$this->mobile		  = wp_is_mobile() ?: false;
		$this->icon           = PAYCONIQ_URL.'assets/images/payconiq_mark.svg';
		$this->type			  = $type;
		self::$log_enabled    = $this->debug;

		if ( $this->testmode ) {
			$this->description .= '<br><strong><u>' . esc_html__( 'SANDBOX ENABLED.', 'payconiq') . '</u></strong> ' . esc_html__( 'You can use sandbox testing accounts only.', 'payconiq' );
			$this->description = trim( $this->description );
		}

		/**
		 * Callback handler
		 */
		add_action( 'woocommerce_api_' . $this->id, array( $this, 'check_response' ) );

		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'render_receipt_page' ) );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options',
		) );

		add_action( 'woocommerce_admin_order_data_after_billing_address', array(
			$this,
			'show_transaction_id_in_backend',
		), 10, 1 );
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields() {
		$this->form_fields = include PAYCONIQ_DIR . 'view/admin/settings-payconiq.php';
	}

	/**
	 * Processes and saves options.
	 * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
	 *
	 * @return bool was anything saved?
	 *
	 * @since 1.0.0
	 */
	public function process_admin_options() {
		$saved = parent::process_admin_options();

		// Maybe clear logs.
		if ( 'yes' !== $this->get_option( 'debug', 'no' ) ) {
			if ( empty( self::$log ) ) {
				self::$log = wc_get_logger();
			}
			self::$log->clear( 'payconiq' );
		}

		return $saved;
	}

	/**
	 * Logging method.
	 *
	 * @param  string  $message  Log message.
	 * @param  string  $level  Optional. Default 'info'. Possible values:
	 *                      emergency|alert|critical|error|warning|notice|info|debug.
	 *
	 * @since 1.0.0
	 */
	public static function log( $message, $level = 'info' ) {
		if ( self::$log_enabled ) {
			if ( empty( self::$log ) ) {
				self::$log = wc_get_logger();
			}
			self::$log->log( $level, $message, array( 'source' => 'payconiq' ) );
		}
	}

	/**
	 * Redirect from checkout page to payment page
	 *
	 * @param  int  $order_id
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}

	/**
	 * * Renders the receipt page.
	 *
	 * @param  int  $order_id  WooCommerce Order ID.
	 *
	 * @throws \WC_Data_Exception
	 *
	 * @since 1.0.0
	 * @version 1.0.2
	 */
	public function render_receipt_page( $order_id ) {
		$order = wc_get_order( $order_id );

		/**
		 * If currency is not EUR
		 */
		if ( $order->get_currency() != 'EUR' ) {
			wp_die( 'The payconiq works only with EUR ', array(
				'response',
				403,
			) );
			exit;
		}

		/**
		 * Create callback url - default woocommerce gateway endpoint (or get it from option page)
		 */
		$callbackUrl = '';
		if ( $this->get_option( 'callback_url' ) && !empty($this->get_option( 'callback_url' )) ) {
			$callbackUrl = $this->get_option( 'callback_url' );
		} else {
			$callbackUrl = str_replace( 'http://', 'https://', add_query_arg( 'wc-api', 'gateway_payconiq', home_url( '/' ) ) );
		}

		/**
		 * Add order ID to callback url
		 */
		$callbackUrl = add_query_arg( 'webhookId', $order_id, $callbackUrl );

		$payconiq = $this->get_payconiq_client();

		if ( $payconiq == false ) {
			$this->log( 'Payconiq credentials are not filled in', 'error' );
			wp_die( 'Payconiq credentials are not filled in!' );
		}

		/**
		 * Create Transaction ID
		 */
		try {
			/**  
			 * Transcation ID already exists ?
			 */
			$transaction_id = $order->get_transaction_id();
			$transaction = null;

			if ( ! $transaction_id ) {
				$transaction = $payconiq->createTransaction( $order->get_total() * 100, $order->get_currency(), $callbackUrl, true );
				$order->add_order_note( 'Payconiq transaction ID ' . $transaction['paymentId'] . ' is created.' );
				$this->log( 'Transaction ID( ' . $transaction['paymentId'] . ' ) is created for the order id ' . $order_id, 
				'info' );
			} else {
				$transaction = $payconiq->retrieveTransaction($transaction_id);
			}

			$links = $transaction['_links'];
		} catch ( \Exception $e ) {
			$this->log( 'Transaction is not created in Payconiq: ' . $e->getMessage(), 'error' );
			wp_die( 'Payconiq Request Failure: ' . $e->getMessage(), 'Payconiq transaction',
				array( 'response' => 500 ) );
		}

		/**
		 * Save Transaction ID in the order
		 */
		$order->set_transaction_id( $transaction['paymentId'] );
		$order->save();

		update_post_meta( $order_id, '_payconiq_transaction_id', $transaction['paymentId'] );

		if ($this->mobile) {
			/**
			 * Display a clickable QR code (mobile)
			 */
			echo '<a href="' . esc_url($links['deeplink']['href']) . '" />';
			echo '	<div class="payconiq-container" style="height:50px; margin-top:25px;">';
			echo '		<img src="' . esc_url(PAYCONIQ_URL .'/assets/images/payconiq_logo.svg?v=1') . '" style="width: 300px; height: 50px">';
			echo '	</div>';
			echo '</a>';

			/**
			 * Show Deeplink (mobile)
			 */
			echo '<a class="payconiq_mobile_button" href="' . esc_url($links['deeplink']['href']) . '" />'. esc_html__('Click to pay', 'payconiq') . '</a>';
		} else {
			/**
			 * Display QR code (desktop)
			 */
			echo '<div class="payconiq-container">';
			echo '	<div class="qr_code" style="background-image:url(' . esc_url(PAYCONIQ_URL . '/assets/images/payconiq_frame.svg') . ')">';
			echo '		<img src="' . esc_url($links['qrcode']['href']) . '" />';
			echo '	</div>';
			echo '</div>';
		}

		/**
		 * Save Order ID for javascript
		 */
		echo '<input type="hidden" id="order_id" value="' . esc_attr($order_id) . '">';
		

		/**
		 * Cancel order
		 */
		//echo '<a href="' . esc_url($links['cancel']['href']) . '" />' . esc_html__('Cancel payment on click here', 'payconiq') . '</a>';

		/**
		 * Show more information about the Payconiq App.
		 */
		echo '<p id="payconiq_message">' . $this->description . '</p>';

		wp_enqueue_script( 'payconiq-js', PAYCONIQ_URL . 'assets/js/payconiq-transaction.js', array( 'jquery' ), PAYCONIQ_VERSION, true );
		wp_enqueue_style( 'payconiq-css', PAYCONIQ_URL . 'assets/css/payconiq.css', false, '1.0.0', 'all');
	}

	/**
	 * Show Payconiq Transaction ID in the backend.
	 *
	 * @param $order \WC_Order
	 *
	 * @since 1.0.0
	 */
	public function show_transaction_id_in_backend( $order ) {
		echo '<p><strong>' . esc_html__( 'Payconiq Transaction ID', 'payconiq' ) . ':</strong> <br/>' . $order->get_transaction_id() . '</p>';
	}

	/**
	 * Process Payconiq callback Response.
	 *
	 * @since 1.0.0
	 * @version 1.0.2
	 */
	public function check_response($orderID = null) {

		$order_id = ( isset( $_GET['webhookId'] ) ) ? sanitize_text_field( $_GET['webhookId'] ) : false;

		if ( !$order_id) {
			// since user can change callback, set order id.
			$order_id = $orderID;
		}

		if (  !$order_id ) {
			$this->log( 'The order ID is not provided.', 'error' );
			$this->p_die();
			return;
		}

		$order = wc_get_order( $order_id );

		if ( $order == false ) {
			$this->log( 'The order ID is not valid: ' . $order_id, 'error' );
			$this->p_die();
			return;
		}

		$payconiq = $this->get_payconiq_client();

		if ( $payconiq == false ) {
			$this->log( 'Payconiq credentials are not filled in', 'error' );
			wp_die( 'Payconiq credentials are not filled in!' );
		}

		// Retrieve the order status on WooCommerce
		try {
			$order_status = $order->get_status();
		} catch ( \Exception $e ) {
			$this->log( 'Something went wrong while retrieving the order status: ' . $e->getMessage(), 'error' );
			$this->p_die();
			return;
		}

		$transaction_id = $order->get_transaction_id();

		if ( empty( $transaction_id ) ) {
			$this->log( 'Transaction ID is not found.', 'error' );
			$this->p_die();
			return;
		}
	
		// Retrieve transaction status on Payconiq
		try {
			$response = $payconiq->retrieveTransaction( $transaction_id );
		} catch ( \Exception $e ) {
			$this->log( 'Something went wrong with retrieving the transaction: ' . $e->getMessage(), 'error' );
			$this->p_die();
			return;
		}

		$status = isset($response['status']) ? sanitize_text_field( $response['status'] ) : 'FAILED';
		$web_order_id = isset($response['_id']) ? sanitize_text_field( $response['_id'] ) : 0;

		switch ( $status ) {
			case 'SUCCEEDED':
				if ($order_status == 'pending') {
					$order->payment_complete( $web_order_id );
					$order->add_order_note( 'The order is payed in Payconiq.' );
					$this->log( 'The order(ID: ' . $order_id . ' ) is payed in Payconiq' );
				}
				break;
			case 'CREATION':
			case 'PENDING':
			case 'CONFIRMED':
				if ($this->type == 'start') {
					$order->update_status( 'pending' );
					$order->add_order_note( 'Order is pending due to payconiq order status: ' . $status );
					$this->log( 'The order(ID: ' . $order_id . ' ) is pending due to payconiq order status: ' . $status );
				}
				break;
			case 'CANCELED':
			case 'CANCELED_BY_MERCHANT':
			case 'FAILED':
			case 'TIMEDOUT':
			case 'BLOCKED':
				$order->update_status( 'cancelled' );
				$order->add_order_note( 'Order is cancelled due to payconiq order status: ' . $status );
				$this->log( 'The order(ID: ' . $order_id . ' ) is pending due to payconiq order status: ' . $status,
					'error' );
				break;
		}

		$this->p_die();
	}

	/**
	 * Can the order be refunded via Payconiq?
	 *
	 * @param  \WC_Order  $order  Order object.
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public function can_refund_order( $order ) {
		$has_api_creds = false;

		if ( $this->testmode ) {
			$has_api_creds = $this->get_option( 'sandbox_api_payment_profile_id' ) && $this->get_option( 'sandbox_api_key' ) && $this->get_option( 'api_merchant_name' );
		} else {
			$has_api_creds = $this->get_option( 'api_payment_profile_id' ) && $this->get_option( 'api_key' ) && $this->get_option( 'api_merchant_name' );
		}

		return $order && $order->get_transaction_id() && $has_api_creds;
	}

	/**
	 * Process a refund if supported.
	 *
	 * @param  int  $order_id  Order ID.
	 * @param  float  $amount  Refund amount.
	 * @param  string  $reason  Refund reason.
	 *
	 * @return bool|\WP_Error
	 *
	 * @since 1.0.0
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		/**
		 * If currency is not EUR
		 */
		if ( $order->get_currency() != 'EUR' ) {
			$this->log( __( 'payconiq works only with EUR', 'payconiq' ), 'error' );

			return new \WP_Error( 'error', esc_html__( 'payconiq works only with EUR', 'payconiq' ) );
		}

		if ( ! $this->can_refund_order( $order ) ) {
			$this->log( __( 'Refund failed due to invalid credentials.', 'payconiq' ), 'error' );

			return new \WP_Error( 'error', esc_html__( 'Refund failed due to invalid credentials.', 'payconiq' ) );
		}

		$transaction_id = $order->get_transaction_id();

		if ( ! $transaction_id ) {
			$this->log( __( 'Transaction ID is not found.', 'payconiq' ), 'error' );

			return new \WP_Error( 'error', esc_html__( 'Transaction ID is not found.', 'payconiq' ) );
		}

		$payconiq = $this->get_payconiq_client();

		if ( $payconiq == false ) {
			$this->log( __( 'Payconiq credentials are not filled in', 'payconiq' ), 'error' );

			return new \WP_Error( 'error', esc_html__( 'Payconiq credentials are not filled in', 'payconiq' ) );
		}


		try {
			$result = $payconiq->createRefund( $transaction_id, $amount * 100, $order->get_currency(), 'SDD', $reason );

			$this->log( 'Refund Result: ' . wc_print_r( $result, true ) );

			$order->add_order_note(
				sprintf( esc_html__( 'Refunded %1$s - Refund ID: %2$s', 'payconiq' ), $result['amount'],
					$result['_id'] )
			);

			return true;
		} catch ( \Exception $e ) {
			$this->log( __( 'Refund Failed: ', 'payconiq' ) . $e->getMessage(), 'error' );

			return new \WP_Error( 'error', __( 'Refund Failed: ', 'payconiq' ) . $e->getMessage() );
		}
	}

	/**
	 * Returns Payconiq client - which connects to Payconiq API
	 *
	 * @return bool|Payconiq_Client
	 *
	 * @since 1.0.0
	 */
	protected function get_payconiq_client() {
		$payconiq = false;

		if ( $this->testmode ) {
			if ( $this->get_option( 'sandbox_api_payment_profile_id' ) && $this->get_option( 'sandbox_api_key' ) ) {
				$payconiq = new Payconiq_Client( $this->get_option( 'sandbox_api_payment_profile_id' ),
					$this->get_option( 'sandbox_api_key' ), true, $this->get_option( 'api_merchant_name' ) );
			}
		} else {
			if ( $this->get_option( 'api_payment_profile_id' ) && $this->get_option( 'api_key' ) ) {
				$payconiq = new Payconiq_Client( $this->get_option( 'api_payment_profile_id' ), $this->get_option( 'api_key' ),
					false, $this->get_option( 'api_merchant_name' ) );
			}
		}

		return $payconiq;
	}

	/**
	 * Die function if it's endpoint
	 *
	 * @return bool|Payconiq_Client
	 *
	 * @since 1.0.0
	 */
	protected function p_die() {
		if ($this->endpoint) {
			die();
		}
	}
}