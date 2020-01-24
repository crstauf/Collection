<?php

class Collection_Info_Test extends WP_UnitTestCase {

	protected static $info = null;

	static function setUpBeforeClass() {
		$file = trailingslashit( dirname( __DIR__ ) ) . 'collection.php';
		static::$info = get_plugin_data( $file );
	}

	function test_name() {
		$this->assertEquals( static::$info['Name'], 'Collection' );
	}

	function test_version() {
		$this->assertEquals( static::$info['Version'], '1.0' );
	}

	function test_url() {
		$this->assertEquals( static::$info['PluginURI'], 'https://github.com/crstauf/Collection' );
	}

	function test_author() {
		$url = 'https://develop.calebstauffer.com';
		$this->assertEquals( static::$info['Author'], '<a href="' . $url . '">Caleb Stauffer</a>' );
		$this->assertEquals( static::$info['AuthorURI'], $url );
	}

}
