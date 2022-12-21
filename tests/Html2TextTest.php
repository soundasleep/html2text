<?php

require(__DIR__ . "/../src/Html2Text.php");

class Html2TextTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider providerFiles
	 */
	public function testFile(string $test) {
		$this->doTestWithResults($test, $test, []);
	}

	function doTestWithResults(string $test, string $result, $options = []) {
		$this->assertTrue(file_exists(__DIR__ . "/$test.html"), "File '$test.html' did not exist");
		$this->assertTrue(file_exists(__DIR__ . "/$result.txt"), "File '$result.txt' did not exist");
		$input = file_get_contents(__DIR__ . "/$test.html");
		$expected = \Soundasleep\Html2Text::fixNewlines(file_get_contents(__DIR__ . "/$result.txt"));

		$output = \Soundasleep\Html2Text::convert($input, $options);

		if ($output != $expected) {
			file_put_contents(__DIR__ . "/$result.output", $output);
		}
		$this->assertEquals($expected, $output, __DIR__ . '/' . $test . '.html test failed to convert to ' . __DIR__ . '/' . $result . '.txt');
	}

	public function providerFiles() {
		return [
			['basic'],
			['anchors'],
			['more-anchors'],
			['test3'],
			['test4'],
			['table'],
			['nbsp'],
			['lists'],
			['pre'],
			['newlines'],
			['nested-divs'],
			['blockquotes'],
			['full_email'],
			['images'],
			['non-breaking-spaces'],
			['utf8-example'],
			['windows-1252-example'],
			['msoffice'],
			['dom-processing'],
			['empty'],
			['huge-msoffice'],
			['zero-width-non-joiners'],
		];
	}

	public function testInvalidXML() {
		$this->expectWarning();
		$this->doTestWithResults("invalid", "invalid", ['ignore_errors' => false]);
	}

	public function testInvalidXMLIgnore() {
		$this->doTestWithResults("invalid", "invalid", ['ignore_errors' => true]);
	}

	public function testInvalidXMLIgnoreOldSyntax() {
		// for BC, allow old #convert(text, bool) syntax
		$this->doTestWithResults("invalid", "invalid", true);
	}

	public function testInvalidOption() {
		$this->expectException(InvalidArgumentException::class);
		$this->doTestWithResults("basic", "basic", ['invalid_option' => true]);
	}

	public function testBasicDropLinks() {
		$this->doTestWithResults("basic", "basic.no-links", ['drop_links' => true]);
	}

	public function testAnchorsDropLinks() {
		$this->doTestWithResults("anchors", "anchors.no-links", ['drop_links' => true]);
	}

}