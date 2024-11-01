<?php
/**
 * Settings for Payconiq Gateway.
 *
 * @package payconiq/Classes/Payment
 */

defined( 'ABSPATH' ) || exit;

return array(
	'enabled'	=> array(
		'title'   => esc_html__( 'Enable/Disable', 'payconiq' ),
		'type'    => 'checkbox',
		'label'   => esc_html__( 'Enable Payconiq', 'payconiq' ),
		'default' => 'no',
	),

	'testmode'	=> array(
		'title'       => esc_html__( 'Sandbox', 'payconiq' ),
		'type'        => 'checkbox',
		'label'       => esc_html__( 'Enable Payconiq sandbox', 'payconiq' ),
		'default'     => 'no',
		'description' => esc_html__( 'Payconiq sandbox can be used to test payments.', 'payconiq' ),
	),

	'debug'	=> array(
		'title'       => esc_html__( 'Debug log', 'payconiq' ),
		'type'        => 'checkbox',
		'label'       => esc_html__( 'Enable logging', 'payconiq' ),
		'default'     => 'no',
		'description' => sprintf( esc_html__( 'Log Payconiq events, inside %s Note: this may log personal information. We recommend using this for debugging purposes only and deleting the logs when finished.', 'payconiq' ), '<code>' . WC_Log_Handler_File::get_log_file_path( 'payconiq' ) . '</code>' ),
	),

	'api_details'	=> array(
		'title'       => esc_html__( 'API credentials', 'payconiq' ),
		'type'        => 'title',
		'description' => esc_html__( 'Enter your Payconiq API credentials to process refunds via Payconiq.', 'payconiq' ),
	),

	'api_merchant_name' => array(
		'title'       => esc_html__( 'Merchant Name', 'payconiq' ),
		'type'        => 'text',
		'description' => esc_html__( 'Get the same merchant name as given from Payconiq.', 'payconiq' ),
		'default'     => get_bloginfo('name'),
		'desc_tip'    => true,
		'placeholder' => esc_html__( 'Optional', 'payconiq' ),
	),

	'api_payment_profile_id' => array(
		'title'       => esc_html__( 'Payment Profile ID', 'payconiq' ),
		'type'        => 'text',
		'description' => esc_html__( 'Get your API credentials from Payconiq.', 'payconiq' ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => esc_html__( 'Optional', 'payconiq' ),
	),

	'api_key'	=> array(
		'title'       => esc_html__( 'Live API key', 'payconiq' ),
		'type'        => 'text',
		'description' => esc_html__( 'Get your API credentials from Payconiq.', 'payconiq' ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => esc_html__( 'Optional', 'payconiq' ),
	),

	'sandbox_api_payment_profile_id' => array(
		'title'       => esc_html__( 'Sandbox Payment Profile ID', 'payconiq' ),
		'type'        => 'text',
		'description' => esc_html__( 'Get your API credentials from Payconiq.', 'payconiq' ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => esc_html__( 'Optional', 'payconiq' ),
	),

	'sandbox_api_key' => array(
		'title'       => esc_html__( 'Sandbox API key', 'payconiq' ),
		'type'        => 'text',
		'description' => esc_html__( 'Get your API credentials from Payconiq.', 'payconiq' ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => esc_html__( 'Optional', 'payconiq' ),
	),

	'api_callback_url' => array(
		'title'       => esc_html__( 'Callback URL', 'payconiq' ),
		'type'        => 'text',
		'description' => esc_html__( 'Set the callback URL for the payment confirmation of Woocommerce (ex. ' . str_replace( 'http://', 'https://', add_query_arg( 'wc-api', 'gateway_payconiq', home_url( '/' ) ) ) . ')', 'payconiq' ),
		'default'     => '',
		'desc_tip'    => false,
		'placeholder' => esc_html__( 'Optional', 'payconiq' ),
	),
);