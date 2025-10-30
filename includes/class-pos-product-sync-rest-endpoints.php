<?php

defined( 'ABSPATH' ) || exit;

/**
 * Class Pos_Product_Sync_Rest_Endpoints
 *
 * Registers REST API endpoints for POS product synchronization.
 */
class Pos_Product_Sync_Rest_Endpoints extends WP_REST_Controller {
	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'pos/v1';

	/**
	 * REST API base route for products.
	 *
	 * @var string
	 */
	protected $rest_base = 'products';


	/**
	 * REST API base route for product.
	 *
	 * @var string
	 */
	protected $rest_base_single = 'product';

	/**
	 * Consumer key for products.
	 *
	 * @var string
	 */
	protected $consumer_key = 'ck_0b73733738aff804dcef4cd4166060215d61492d';

	/**
	 * Consumer secret for products.
	 *
	 * @var string
	 */
	protected $consumer_secret = 'cs_e061a52eace9e0530dd78adacfd882db85c956f8';

	/**
	 * Constructor.
	 * Hooks the endpoint registration into rest_api_init.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
	}

	/**
	 * Returns a new instance of this class.
	 *
	 * @return self
	 */
	public static function get_instance() {
		return new self();
	}

	/**
	 * Register REST API endpoints.
	 */
	public function register_endpoints() {
		// Collection routes
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_products' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				)
			)
		);

		// Single item routes

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base_single,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_product' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base_single . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_product' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE, // covers PUT & PATCH
					'callback'            => array( $this, 'update_product' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_product' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);

	}

	/**
	 * Check permissions for the REST API requests.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return bool True if the current user has permission, false otherwise.
	 */
	public function permissions_check( $request ) {
		return true;
	}

	/**
	 * Get the synced product ID by table row ID.
	 *
	 * @param int $id The ID of the row in the pos_product_sync table.
	 *
	 * @return int|null Returns the product_id if found, or null if not found.
	 */


	public function get_product_id_by_pos( $pos_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pos_product_sync';

		// Prepare the query to get product_id by pos_id
		$query = $wpdb->prepare( "SELECT product_id FROM $table_name WHERE pos_id = %d", intval( $pos_id ) );

		$result = $wpdb->get_row( $query, ARRAY_A );

		return $result['product_id'] ?? null;
	}

	public function get_products( WP_REST_Request $request ) {

		$url = home_url() . '/wp-json/wc/v3/products/';

		// Use basic auth headers
		$headers = [
			'Authorization' => 'Basic ' . base64_encode( $this->consumer_key . ':' . $this->consumer_secret ),
			'Content-Type'  => 'application/json',
		];

		$response = wp_remote_get( $url, [
			'headers' => $headers,
			'timeout' => 20,
		] );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', $response->get_error_message(), [ 'status' => 500 ] );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Handle WooCommerce API errors

		if ( isset( $data['code'] ) && isset( $data['message'] ) ) {
			return new WP_REST_Response(
				array(
					'status'     => 'error',
					'error_code' => $data['code'],
					'message'    => $data['message'],
				),
				isset( $data['data']['status'] ) ? intval( $data['data']['status'] ) : 400
			);
		}

		return new WP_REST_Response(
			array(
				'status'   => 'success',
				'home_url' => home_url(),
				'data'     => $data,
			),
			200
		);
	}

	public function get_product( WP_REST_Request $request ) {

		$id = $request->get_param( 'id' );

		$product_id = $this->get_product_id_by_pos( $id );

		if ( ! $product_id ) {
			return new WP_REST_Response(
				array(
					'status'  => 'false',
					'message' => 'Product not found',
				), 404
			);
		}

		$url = home_url() . '/wp-json/wc/v3/products/' . $product_id;

		// Use basic auth headers
		$headers = [
			'Authorization' => 'Basic ' . base64_encode( $this->consumer_key . ':' . $this->consumer_secret ),
			'Content-Type'  => 'application/json',
		];

		$response = wp_remote_get( $url, [
			'headers' => $headers,
			'timeout' => 20,
		] );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', $response->get_error_message(), [ 'status' => 500 ] );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );


		// Handle WooCommerce API errors

		if ( isset( $data['code'] ) && isset( $data['message'] ) ) {
			return new WP_REST_Response(
				array(
					'status'     => 'error',
					'error_code' => $data['code'],
					'message'    => $data['message'],
				),
				isset( $data['data']['status'] ) ? intval( $data['data']['status'] ) : 400
			);
		}

		return new WP_REST_Response( array(
			'status' => 'success',
			'data'   => $data,
		), 200 );
	}

	public function create_product( WP_REST_Request $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pos_product_sync';

		$body   = $request->get_json_params();
		$pos_id = isset( $body['pos_id'] ) ? intval( $body['pos_id'] ) : '';
		$data   = isset( $body['data'] ) ? $body['data'] : '';

		$pos_id_exists = $this->get_product_id_by_pos( $pos_id );

		if ( $pos_id_exists ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'Product already exists',
				), 409
			);
		}

		// Validate input
		if ( ! $pos_id ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'POS id is required for product creation',
				),
				400
			);
		}

		if ( ! $data ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'Product data is required',
				),
				400
			);
		}

		$api_url = 'https://shop.dorabd.com/wp-json/wc/v3/products/';

		// Prepare request args
		$args = array(
			'body'    => wp_json_encode( $data ),
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $this->consumer_key . ':' . $this->consumer_secret ),
				'Content-Type'  => 'application/json',
			),
			'timeout' => 20,
		);

		// Call WooCommerce API
		$response = wp_remote_post( $api_url, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', $response->get_error_message(), [ 'status' => 500 ] );
		}

		$body         = wp_remote_retrieve_body( $response );
		$product_data = json_decode( $body, true );

		// Handle WooCommerce API errors

		if ( isset( $product_data['code'] ) && isset( $product_data['message'] ) ) {
			return new WP_REST_Response(
				array(
					'status'     => 'error',
					'error_code' => $product_data['code'],
					'message'    => $product_data['message'],
				),
				isset( $product_data['data']['status'] ) ? intval( $product_data['data']['status'] ) : 400
			);
		}

		$update_pos_meta = update_post_meta( intval( $product_data['id'] ), 'pos_id', $pos_id );

		// Insert POS sync record
		$inserted = $wpdb->insert(
			$table_name,
			array(
				'pos_id'     => $pos_id,
				'product_id' => intval( $product_data['id'] )
			),
			array( '%d', '%d' )
		);

		if ( ! $inserted ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'Failed to insert POS product record',
					'pos_id'  => $pos_id,
				),
				500
			);
		}

		// Success response
		return new WP_REST_Response(
			array(
				'status'   => 'success',
				'pos_id'   => $pos_id,
				'pos_meta' => $update_pos_meta,
				'data'     => $product_data,
			),
			201
		);
	}

	public function update_product( WP_REST_Request $request ) {
		$body   = $request->get_json_params();
		$pos_id = isset( $body['pos_id'] ) ? intval( $body['pos_id'] ) : '';
		$data   = isset( $body['data'] ) ? $body['data'] : '';

		// Validate input
		if ( ! $pos_id ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'POS id is required for product update',
				),
				400
			);
		}

		$product_id = $this->get_product_id_by_pos( $pos_id );

		if ( ! $product_id ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'Product not found',
				),
				404
			);
		}

		if ( ! $data ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'Product data is required',
				),
				400
			);
		}

		$api_url = home_url() . '/wp-json/wc/v3/products/' . $product_id;

		// Prepare request args
		$args = array(
			'method'  => 'PUT', // Update method
			'body'    => wp_json_encode( $data ),
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $this->consumer_key . ':' . $this->consumer_secret ),
				'Content-Type'  => 'application/json',
			),
			'timeout' => 20,
		);

		// Call WooCommerce API
		$response = wp_remote_request( $api_url, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', $response->get_error_message(), [ 'status' => 500 ] );
		}

		$product_data = json_decode( wp_remote_retrieve_body( $response ), true );

		// Handle WooCommerce API errors
		if ( isset( $product_data['code'] ) && isset( $product_data['message'] ) ) {
			return new WP_REST_Response(
				array(
					'status'     => 'error',
					'error_code' => $product_data['code'],
					'message'    => $product_data['message'],
				),
				isset( $product_data['data']['status'] ) ? intval( $product_data['data']['status'] ) : 400
			);
		}

		return new WP_REST_Response(
			array(
				'status' => 'success',
				'data'   => $product_data,
			),
			200
		);
	}

	public function delete_product( WP_REST_Request $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pos_product_sync';

		$body   = $request->get_json_params();
		$pos_id = isset( $body['pos_id'] ) ? intval( $body['pos_id'] ) : '';

		if ( ! $pos_id ) {
			return new WP_REST_Response( [
				'status'  => 'error',
				'message' => 'POS id is required for product deletion',
			], 400 );
		}

		$product_id = $this->get_product_id_by_pos( $pos_id );

		if ( ! $product_id ) {
			return new WP_REST_Response( [
				'status'  => 'error',
				'message' => 'Product not found',
			], 404 );
		}

		$api_url = home_url() . '/wp-json/wc/v3/products/' . $product_id . '?force=true';

		$args = [
			'method'  => 'DELETE',
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode( $this->consumer_key . ':' . $this->consumer_secret ),
				'Content-Type'  => 'application/json',
			],
			'timeout' => 20,
		];

		$response = wp_remote_request( $api_url, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', $response->get_error_message(), [ 'status' => 500 ] );
		}

		$response_data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $response_data['code'] ) && isset( $response_data['message'] ) ) {
			return new WP_REST_Response( [
				'status'     => 'error',
				'error_code' => $response_data['code'],
				'message'    => $response_data['message'],
			], isset( $response_data['data']['status'] ) ? intval( $response_data['data']['status'] ) : 400 );
		}

		// Delete POS meta
		delete_post_meta( intval( $product_id ), 'pos_id', $pos_id );

		// Delete from custom table
		$deleted = $wpdb->delete( $table_name, [ 'pos_id' => $pos_id ], [ '%d' ] );

		if ( $deleted === false ) {
			return new WP_REST_Response( [
				'status'  => 'error',
				'message' => 'Failed to delete POS record from database',
			], 500 );
		}

		return new WP_REST_Response( [
			'status'  => 'success',
			'message' => 'Product deleted successfully',
			'data'    => $response_data,
		], 200 );
	}

}
