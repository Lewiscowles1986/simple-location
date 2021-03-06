<?php
/**
 * Base Provider Class.
 *
 * @package Simple_Location
 */

/**
 * Abstract Class to Provide Basic Functionality for Providers.
 *
 * @since 1.0.0
 */
abstract class Sloc_Provider {

	 /**
	  * Provider Slug.
	  *
	  * @since 1.0.0
	  * @var string
	  */
	protected $slug;

	 /**
	  * Provider Name.
	  *
	  * @since 1.0.0
	  * @var string
	  */
	protected $name;

	 /**
	  * Provider Description.
	  *
	  * @since 1.0.0
	  * @var string
	  */
	protected $description;

	 /**
	  * Provider API Key.
	  *
	  * @since 1.0.0
	  * @var string
	  */
	protected $api;


	 /**
	  * Latitude.
	  *
	  * @since 1.0.0
	  * @var float
	  */
	protected $latitude;

	 /**
	  * Longitude.
	  *
	  * @since 1.0.0
	  * @var float
	  */
	protected $longitude;

	 /**
	  * Altitude.
	  *
	  *  Denotes the height of the position, specified in meters above the [WGS84] ellipsoid. If the implementation cannot provide altitude information, the value of this attribute must be null.
	  *
	  * @since 1.0.0
	  * @var float
	  */
	protected $altitude;

	/**
	 * Constructor for the Abstract Class.
	 *
	 * The default version of this just sets the parameters.
	 *
	 * @param array $args {
	 *  Arguments.
	 *  @type string $api API Key.
	 *  @type float $latitude Latitude.
	 *  @type float $longitude Longitude.
	 *  @type float $altitude Altitude.
	 */
	public function __construct( $args = array() ) {
		$defaults  = array(
			'api'       => null,
			'latitude'  => null,
			'longitude' => null,
			'altitude'  => null,
		);
		$r         = wp_parse_args( $args, $defaults );
		$this->api = $r['api'];
		$this->set( $r['latitude'], $r['longitude'] );
	}


	/**
	 * Fetches JSON from a remote endpoint.
	 *
	 * @param string $url URL to fetch.
	 * @param array  $query Query parameters.
	 * @return WP_Error|array Either the associated array response or error.
	 *
	 * @since 4.0.6
	 */
	public function fetch_json( $url, $query ) {
		$fetch = add_query_arg( $query, $url );
		$args  = array(
			'headers'             => array(
				'Accept' => 'application/json',
			),
			'timeout'             => 10,
			'limit_response_size' => 1048576,
			'redirection'         => 1,
			// Use an explicit user-agent for Simple Location.
			'user-agent'          => 'Simple Location for WordPress',
		);

		$response = wp_remote_get( $fetch, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		if ( ( $code / 100 ) !== 2 ) {
			return new WP_Error( 'invalid_response', $body, array( 'status' => $code ) );
		}
		$json = json_decode( $body, true );
		if ( empty( $json ) ) {
			return new WP_Error( 'not_json_response', $body, array( 'type' => wp_remote_retrieve_header( $response, 'Content-Type' ) ) );
		}
		return $json;
	}

	/**
	 * Given a list of keys returns the first matching one.
	 *
	 * @param array $array Array of associative data.
	 * @param array $keys List of keys to search for.
	 * @return mixed|null Return either null or the value of the first key found.
	 *
	 * @since 4.0.0
	 */
	public static function ifnot( $array, $keys ) {
		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $array ) ) {
				return $array[ $key ];
			}
		}
		return null;
	}

	/**
	 * Returns the name property.
	 *
	 * @return string $name Returns name.
	 *
	 * @since 1.0.0
	 */
	public function get_name() {
		return $this->name;
	}


	/**
	 * Returns the desciption property.
	 *
	 * @return string $description Returns description.
	 *
	 * @since 1.0.0
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Returns the slug property.
	 *
	 * @return string $slug Slug.
	 *
	 * @since 1.0.0
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Set and Validate Coordinates.
	 *
	 * @param array|float $lat Latitude or array of all three properties.
	 * @param float       $lng Longitude. Optional if first property is an array.
	 * @param float       $alt Altitude. Optional.
	 * @return boolean Return False if Validation Failed
	 */
	public function set( $lat, $lng = null, $alt = null ) {
		if ( ! $lng && is_array( $lat ) ) {
			if ( isset( $lat['latitude'] ) && isset( $lat['longitude'] ) ) {
				$this->latitude  = $lat['latitude'];
				$this->longitude = $lat['longitude'];
				if ( isset( $lat['altitude'] ) && is_numeric( $lat['altitude'] ) ) {
					$this->altitude = $lat['altitude'];
				}
				return true;
			} else {
				return false;
			}
		}
		// Validate inputs.
		if ( ( ! is_numeric( $lat ) ) && ( ! is_numeric( $lng ) ) ) {
			return false;
		}
		$this->latitude  = $lat;
		$this->longitude = $lng;
		if ( is_numeric( $alt ) ) {
			$this->altitude = $altt;
		}
		return true;
	}

	/**
	 * Get Coordinates.
	 *
	 * @return array|boolean Array with Latitude and Longitude false if null
	 */
	public function get() {
		$return              = array();
		$return['latitude']  = $this->latitude;
		$return['longitude'] = $this->longitude;
		$return['altitude']  = $this->altitude;
		$return              = array_filter( $return );
		if ( ! empty( $return ) ) {
			return $return;
		}
		return false;
	}

	/**
	 * Converts millimeters to inches.
	 *
	 * @param float $mm Millimeters.
	 * @return float Inches.
	 */
	public static function mm_to_inches( $mm ) {
		return floatval( $mm ) / 25.4;
	}

	/**
	 * Converts inches to millimeters.
	 *
	 * @param float $inch Inches.
	 * @return float Millimeters.
	 */
	public static function inches_to_mm( $inch ) {
		return floatval( $inch ) * 25.4;
	}


	/**
	 * Converts feet to meters.
	 *
	 * @param float $feet Feet.
	 * @return float Meters.
	 */
	public static function feet_to_meters( $feet ) {
		return floatval( $feet ) / 3.2808399;
	}


	/**
	 * Converts meters to feet.
	 *
	 * @param float $meters Meters.
	 * @return float Feet.
	 */
	public static function meters_to_feet( $meters ) {
		return floatval( $meters ) * 3.2808399;
	}

	/**
	 * Converts meters to miles.
	 *
	 * @param float $meters Meters.
	 * @return float Miles.
	 */
	public static function meters_to_miles( $meters ) {
		return floatval( $meters ) / 1609;
	}

	/**
	 * Converts miles to meters.
	 *
	 * @param float $miles Miles.
	 * @return float Meters.
	 */
	public static function miles_to_meters( $miles ) {
		return floatval( $miles ) * 1609;
	}

	/**
	 * Converts miles per hour to meters per second.
	 *
	 * @param float $miles Miles per hour.
	 * @return float Meters per second.
	 */
	public static function miph_to_mps( $miles ) {
		return round( $miles * 0.44704 );
	}
}
