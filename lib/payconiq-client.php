<?php

namespace payconiq\lib;

class Payconiq_Client {

	protected $merchant_id;
	protected $merchant_name;
	protected $payment_profile_id;
	protected $api_key;
	protected $jws;
	protected $sandbox;
	protected $invalidateCache;

	protected $endpoint = 'https://api.payconiq.com/v3';
	protected $dev_endpoint = 'https://api.ext.payconiq.com/v3';

	protected $jwksExt = 'https://ext.payconiq.com/certificates';
	protected $jwksProd = 'https://payconiq.com/certificates';

	protected $cacheExpire = 43200; // 12h
	protected $alg = 'ES256';


	/**
	 * Construct
	 *
	 * @param  string $payment_profile_id The payment profile ID registered with Payconiq.
	 * @param  string $api_key Used to secure request between merchant backend and Payconiq backend.
	 * @param bool $sandbox Used to check if sandbox or production
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function __construct( $payment_profile_id = null, $api_key = null, $sandbox = false, $merchant_name = null, $merchant_id = null ) {
		$this->payment_profile_id  = $payment_profile_id;
		$this->api_key = $api_key;
		$this->sandbox = $sandbox;
		$this->merchant_name = $merchant_name;
		$this->merchant_id = $merchant_id;
		$this->invalidateCache = true;
		$this->jws = null;
	}

	/**
	 * Set the endpoint
	 *
	 * @param  string $url The endpoint of the Payconiq API.
	 *
	 * @return self
	 *
	 * @since 1.0.0
	 */
	public function setEndpoint( $url ) {
		$this->endpoint = $url;

		return $this;
	}

	/**
     * Set the merchant id
     *
     * @param  string The merchant ID registered with Payconiq.
     *
     * @return self
     *
     * @since 1.0.0
     */
    public function setMerchantId( $merchant_id ) {
        $this->merchant_id = $merchant_id;

        return $this;
    }

	/**
	 * Set the payment profile id
	 *
	 * @param  string $payment_profile_id The payment profile ID registered with Payconiq.
	 *
	 * @return self
	 *
	 * @since 1.0.0
	 */
	public function setPaymentProfileId( $payment_profile_id ) {
		$this->payment_profile_id = $payment_profile_id;

		return $this;
	}

	/**
	 * Set the api key
	 *
	 * @param  string $api_key Used to secure request between merchant backend and Payconiq backend.
	 *
	 * @return self
	 *
	 * @since 1.0.0
	 */
	public function setApiKey( $api_key ) {
		$this->api_key = $api_key;

		return $this;
	}

	/**
	 * Create a new transaction
	 *
	 * @param  float $amount Transaction amount in cents
	 * @param  string $currency Amount currency
	 * @param  string $callbackUrl Callback where payconiq needs to send confirmation status
	 *
	 * @return array|\Exception  transaction_id
	 * @throws \Exception  If the response has no transactionid
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function createTransaction( $amount, $currency, $callbackUrl ) {
		$response = $this->makeHttpRequest( 'POST', $this->getEndpoint( '/payments' ), $this->constructHeaders(), array(
			'amount'      => $amount,
			'currency'    => $currency,
			'callbackUrl' => $callbackUrl,
		) );

		if ( empty( $response['paymentId'] ) ) {
            throw new \Exception( $response['message'] );
        }

		return $response;
	}

	/**
	 * Retrieve an existing transaction
	 *
	 * @param  string $payment_id The transaction id provided by Payconiq
	 *
	 * @return  array  Response object by Payconiq
	 * @throws \Exception  If the response has no transactionid
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function retrieveTransaction( $payment_id ) {
		$response = $this->makeHttpRequest( 'GET', $this->getEndpoint( '/payments/' . $payment_id ), $this->constructHeaders() );
		
		if ( empty( $response['paymentId'] ) ) {
			throw new \Exception( $response['message'] );
		}

		return $response;
	}

	/**
	 * Create refund in Payconiq
	 *
	 * @param $transaction_id
	 * @param $amount
	 * @param $currency
	 * @param string $paymentMethod SCT (SEPA Credit Transfer) or SDD (SEPA Direct Debit)
	 * @param string $description
	 *
	 * @return array Response object by Payconiq
	 * @throws \Exception  If the response has no transactionid
	 *
	 * @since 1.0.0
	 */
	public function createRefund( $transaction_id, $amount, $currency, $description = '' ) {
		return false;
		/**
		 * To be added in a future release
		 */
		/*
		$jwks = $this->getJWKS();
		$joseHeader = $this->joseHeader($transaction_id, $jwks);
		$payload = [
			'iss' => $this->merchant_name,
			get_bloginfo('url') . '/payconiqrefund' => true
		];
		$payload = json_encode($payload);
		
		$requestBody = [
			'amount'        => $amount,
			'currency'      => $currency,
			'description'   => $description
		];
		
		$jws = $this->base64UrlEncode($joseHeader) . '.' . $this->base64UrlEncode($payload) . '.' . $this->base64UrlEncode(hash('sha256', $this->base64UrlEncode($joseHeader).'.'.$this->base64UrlEncode(json_encode($requestBody))));
		$this->jws = $jws;
		$response = $this->makeHttpRequest( 'POST', $this->getEndpoint( '/payments/' . $transaction_id . '/refunds' ), $this->constructHeaders(true), $requestBody);

		if ( ! isset( $response['_id'] ) || empty( $response['_id'] ) ) {
			throw new \Exception( $response['message'] . ' : ' . $response['code'] );
		}

		return $response;*/
	}

	/**
	 * Get the endpoint for the call
	 *
	 * @param  string $route
	 *
	 * @return string   API url
	 *
	 * @version 1.0.0
	 * @since 1.0.0
	 */
	private function getEndpoint( $route = null ) {
		return ( $this->sandbox == true ) ? $this->dev_endpoint . $route : $this->endpoint . $route;
	}

	/**
	 * Construct the headers for the HTTP API call
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	private function constructHeaders($isRefund = false) {
		$headers = null;

		if ($isRefund && !is_null($this->jws)) {
			$headers['Signature'] = $this->jws;
			$headers['Cache-Control'] = 'no-cache';
			$headers['Content-Type'] = 'application/json';
		} else {
			$headers['Authorization'] = 'Bearer '.$this->api_key;
			$headers['Cache-Control'] = 'no-cache';
			$headers['Content-Type'] = 'application/json';
		}

		return $headers;
    }

	/**
	 * HTTP request
	 *
	 * @param  string $method
	 * @param  string $url
	 * @param  array $headers
	 * @param  array $parameters
	 *
	 * @return array response
	 *
	 * @since 1.0.0
	 */
	private function makeHttpRequest($method, $url, $headers = [], $parameters = [] )
	{
	    if( $method == 'POST' ) {
	        $args = array(
	            'method' => $method,
                'headers' => $headers,
				'timeout' => 60,
				'redirection' => 5,
				'blocking'    => true,
				'httpversion' => '1.0',
				'sslverify' => true,
                'body' => json_encode( $parameters )
            );

			$response = wp_remote_post( $url, $args );
			
	        if(! is_wp_error( $response ) ) {
	            return json_decode( wp_remote_retrieve_body( $response ), true );
            } else {
	            return array();
            }
        } else {
		    $args = array(
				'method' => $method,
			    'headers' => $headers,
				'httpversion' => '1.0',
				'sslverify' => false,
		    );

		    $response = wp_remote_get($url, $args);

		    if(! is_wp_error( $response ) ) {
			    return json_decode( wp_remote_retrieve_body( $response ), true );
		    } else {
			    return array();
		    }
	    }
	}

	/**
	 * Create JOSE + Header
	 *
	 * @param  string $data
	 *
	 * @return string encoded base64
	 *
	 * @since 1.0.0
	 */
	private function joseHeader( $transaction_id, $jwks ) {
		$typ = "JOSE+JSON";
		$kid = $jwks['kid'];
		$alg = $this->alg;
		$crit = ["https://payconiq.com/sub", "https://payconiq.com/iss", "https://payconiq.com/iat", "https://payconiq.com/jti", "https://payconiq.com/path"];
		$sub = $this->payment_profile_id;
		$iss = $this->merchant_name;
		$iat = date('Y-m-d\TH:i:s.000', time()) . 'Z';
		$jti = $this->uniqueRequestId();
		$path = $this->getEndpoint( '/payments/' . $transaction_id . '/refunds' );

		$json = array();
		$json["typ"] = $typ;
		$json["kid"] = $kid;
		$json["alg"] = $alg;
		$json["crit"] = $crit;
		$json["https://payconiq.com/sub"] = $sub;
		$json["https://payconiq.com/iss"] = $iss;
		$json["https://payconiq.com/iat"] = $iat;
		$json["https://payconiq.com/jti"] = $jti;
		$json["https://payconiq.com/path"] = $path;
		
		return json_encode($json);
	}

	private function getJWKS() {
		$args = array(
			'method' => 'GET',
			'httpversion' => '1.0',
			'sslverify' => false,
		);

		$cache = wp_cache_get( 'payconiqJWKSProfile' ) && !$this->invalidateCache;

		$cache = false;
		if (!$cache) {
			$url = '';
			if ($this->sandbox) {
				$url = $this->jwksExt;
			} else {
				$url = $this->jwksProd;
			}

			$response = wp_remote_get($url, $args);
			
			if(! is_wp_error( $response ) ) {
				$data = json_decode( wp_remote_retrieve_body( $response ), true );
				if (isset($data['keys'])) {
					$key = null;
					foreach ($data['keys'] as $jwk) {
						if ($jwk['alg'] == $this->alg) {
							$key = $jwk;
						}
					}
					$jwk = $data['keys'][0];
					wp_cache_set( 'payconiqJWKSProfile', $key, '', $this->cacheExpire );
					return $jwk;
				}
			}

			return array();
		} else {
			return $cache;
		}
	}

	/**
	 * Encode base64url
	 *
	 * @param  string $data
	 *
	 * @return string encoded base64
	 *
	 * @since 1.0.0
	 */
	private function base64UrlEncode(string $data): string
	{
		$base64Url = strtr(base64_encode($data), '+/', '-_');
		return rtrim($base64Url, '=');
	}

	/**
	 * Generate a unique request identifier
	 *
	 * @return string uniq-id
	 *
	 * @since 1.0.0
	 */
	private function uniqueRequestId() {
		$bytes = random_bytes(20);
		return bin2hex($bytes);
	}

}