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
		$this->assertEquals( static::$info['PluginURI'], 'https://gist.github.com/crstauf/7f25b9be2eaea72952fee770432ba27e' );
	}

	function test_author() {
		$this->assertEquals( static::$info['Author'], '<a href="http://develop.calebstauffer.com">Caleb Stauffer</a>' );
		$this->assertEquals( static::$info['AuthorURI'], 'http://develop.calebstauffer.com' );
	}

}
