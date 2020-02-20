<?php

class Collection_Plugin_Test extends WP_UnitTestCase {

	function test_info() {
		$file = trailingslashit( dirname( __DIR__ ) ) . 'collection.php';
		$info = get_plugin_data( $file );

		$this->assertEquals( $info['Name'], 'Collection' );
		$this->assertEquals( $info['Version'], '2.0' );
		$this->assertEquals( $info['PluginURI'], 'https://github.com/crstauf/Collection' );

		$url = 'https://develop.calebstauffer.com';
		$this->assertEquals( $info['Author'], '<a href="' . $url . '">Caleb Stauffer</a>' );
		$this->assertEquals( $info['AuthorURI'], $url );
	}

	function test_helpers() {
		$this->assertTrue( function_exists( 'register_collection' ) );
		$this->assertTrue( function_exists(      'get_collection' ) );
	}

	function test_action() {
		$this->assertEquals( 1, did_action( 'collections_available' ) );
	}

}
