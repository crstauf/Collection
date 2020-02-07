<?php

class Collection_Plugin_Test extends WP_UnitTestCase {

	protected static $info = null;

	static function setUpBeforeClass() {
		$file = trailingslashit( dirname( __DIR__ ) ) . 'collection.php';
		static::$info = get_plugin_data( $file );
	}

	function test_info() {
		$this->assertEquals( static::$info['Name'], 'Collection' );
		$this->assertEquals( static::$info['Version'], '2.0' );
		$this->assertEquals( static::$info['PluginURI'], 'https://github.com/crstauf/Collection' );

		$url = 'https://develop.calebstauffer.com';
		$this->assertEquals( static::$info['Author'], '<a href="' . $url . '">Caleb Stauffer</a>' );
		$this->assertEquals( static::$info['AuthorURI'], $url );
	}

	function test_class() {
		$this->assertTrue( class_exists( 'Collection' ) );

		$implements = class_implements( 'Collection' );
		$this->assertContains( 'ArrayAccess', $implements );
		$this->assertContains(   'Countable', $implements );
		$this->assertContains(    'Iterator', $implements );
	}

	function test_helpers() {
		$this->assertTrue( function_exists( 'register_collection' ) );
		$this->assertTrue( function_exists(      'get_collection' ) );
	}

	function test_action() {
		$this->assertTrue( ( bool ) did_action( 'collections_available' ) );
	}

}
