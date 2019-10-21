<?php
// Mapbox Map Provider
class Map_Provider_Mapbox extends Map_Provider {

	public function __construct( $args = array() ) {
		$this->name = __( 'Mapbox', 'simple-location' );
		$this->slug = 'mapbox';
		if ( ! isset( $args['api'] ) ) {
			$args['api'] = get_option( 'sloc_mapbox_api' );
		}

		if ( ! isset( $args['user'] ) ) {
			$args['user'] = get_option( 'sloc_mapbox_user' );
		}

		if ( ! isset( $args['style'] ) ) {
			$args['style'] = get_option( 'sloc_mapbox_style' );
		}
		add_action( 'admin_init', array( get_called_class(), 'admin_init' ) );
		parent::__construct( $args );
	}

	public static function admin_init() {
		add_settings_field(
			'mapboxuser', // id
			__( 'Mapbox User', 'simple-location' ),
			array( 'Loc_Config', 'string_callback' ),
			'sloc_providers',
			'sloc_providers',
			array(
				'label_for' => 'sloc_mapbox_user',

			)
		);
		add_settings_field(
			'mapboxstyle', // id
			__( 'Mapbox Style', 'simple-location' ),
			array( 'Loc_Config', 'style_callback' ),
			'sloc_providers',
			'sloc_providers',
			array(
				'label_for' => 'sloc_mapbox_style',
				'provider'  => new Map_Provider_Mapbox(),

			)
		);
	}

	public function default_styles() {
		return array(
			'streets-v11'                  => 'Streets',
			'outdoors-v11'                 => 'Outdoor',
			'light-v10'                    => 'Light',
			'dark-v10'                     => 'Dark',
			'satellite-v9'                 => 'Satellite',
			'satellite-streets-v11'        => 'Satellite Streets',
			'navigation-preview-day-v4'    => 'Navigation Preview Day',
			'navigation-preview-night-v4'  => 'Navigation Preview Night',
			'navigation-guidance-day-v4'   => 'Navigation Guidance Day',
			'navigation-guidance-night-v4' => 'Navigation Guidance Night',
		);
	}

	public function get_styles() {
		if ( empty( $this->user ) ) {
			return array();
		}
		$return = $this->default_styles();
		if ( 'mapbox' === $this->user ) {
			return $return;
		}
		$url          = 'https://api.mapbox.com/styles/v1/' . $this->user . '?access_token=' . $this->api;
				$args = array(
					'headers'             => array(
						'Accept' => 'application/json',
					),
					'timeout'             => 10,
					'limit_response_size' => 1048576,
					'redirection'         => 1,
					// Use an explicit user-agent for Simple Location
					'user-agent'          => 'Simple Location for WordPress',
				);
				$request = wp_remote_get( $url, $args );
				if ( is_wp_error( $request ) ) {
					return $request; // Bail early.
				}
				$body = wp_remote_retrieve_body( $request );
				$data = json_decode( $body );
				if ( 200 !== wp_remote_retrieve_response_code( $request ) ) {
					return new WP_Error( '403', $data->message );
				}
				foreach ( $data as $style ) {
					if ( is_object( $style ) ) {
						$return[ $style->id ] = $style->name;
					}
				}
				return $return;
	}


	public function get_the_map_url() {
		return sprintf( 'https://www.openstreetmap.org/?mlat=%1$s&mlon=%2$s#map=%3$s/%1$s/%2$s', $this->latitude, $this->longitude, $this->map_zoom );
	}

	public function get_the_map( $static = true ) {
		if ( $static ) {
			$map  = sprintf( '<img src="%s">', $this->get_the_static_map() );
			$link = $this->get_the_map_url();
			return '<a target="_blank" href="' . $link . '">' . $map . '</a>';
		}
	}

	public function get_the_static_map() {
		if ( empty( $this->api ) || empty( $this->style ) ) {
			return '';
		}
		$user   = $this->user;
		$styles = $this->default_styles();
		if ( array_key_exists( $this->style, $styles ) ) {
			$user = 'mapbox';
		}
		$map = sprintf( 'https://api.mapbox.com/styles/v1/%1$s/%2$s/static/pin-s(%3$s,%4$s)/%3$s,%4$s, %5$s,0,0/%6$sx%7$s?access_token=%8$s', $user, $this->style, $this->longitude, $this->latitude, $this->map_zoom, $this->width, $this->height, $this->api );
		return $map;

	}

}

register_sloc_provider( new Map_Provider_Mapbox() );
