<?php

Test::load();

class Test {
	
	public static function load() {
		add_action( 'admin_post_jpi', array( __CLASS__, 'jpi' ) );
		//add_action( 'admin_footer', array( __CLASS__, 'admin_footer' ) );
	}

	public static function jpi() {

		if ( !defined( 'STATS_VERSION' ) or !function_exists( 'stats_get_option' ) ) {
			echo 'This requires the Jetpack Stats module to be enabled.';
			wp_die();
		}

		$q = array(
			'noheader' => 'true',
			'proxy' => '',
			'page' => 'stats',
			'blog' => stats_get_option( 'blog_id' ),
			'charset' => get_option( 'blog_charset' ),
			'color' => get_user_option( 'admin_color' ),
			'ssl' => is_ssl(),
			'j' => sprintf( '%s:%s', JETPACK__API_VERSION, JETPACK__VERSION ),
			'blog_subscribers' => 0,
			'type' => 'email',
		);

		$url = add_query_arg( $q, 'http://' . STATS_DASHBOARD_SERVER . "/wp-admin/index.php" );

		$method = 'GET';
		$timeout = 90;
		$user_id = JETPACK_MASTER_USER;
		
		$get = Jetpack_Client::remote_request( compact( 'url', 'method', 'timeout', 'user_id' ) );

		if ( is_wp_error( $get ) ) {
			echo $get->get_error_message();
			wp_die();
		}

		if ( 200 != $get['response']['code'] ) {
			echo $get['body'];
			wp_die();
		}

		$dom = new DOMDocument();
		$dom->loadHTML( $get['body'] );

		$xml = simplexml_import_dom( $dom );

		$page_count = 1;

		$email_followers = $xml->xpath( "//ul[contains(concat(' ',normalize-space(@class),' '), ' subsubsub ')]/li[2]" );
		preg_match( '/\((\d+)\)$/', $email_followers[0]->__toString(), $user_count_matches );
		$user_count = intval( $user_count_matches[1] );
		var_dump( compact( 'user_count' ) );

		$page_links = $xml->xpath( "//a[contains(concat(' ',normalize-space(@class),' '), ' page-numbers ')]" );

		if ( count( $page_links ) > 1 ) {
			$next_link = array_pop( $page_links );
			$last_link = array_pop( $page_links );
			$page_count = intval( $last_link->__toString() );
		}

		var_dump( $page_count );

		$rows = $xml->xpath( "//table/tbody/tr" );

		echo count( $rows );
		foreach( $rows as $row ) {
			echo $row->td[1] . ' ' . $row->td[3]->span['title'] . '<br/>';
		}

		wp_die();
	}
}
