<?php

require(__DIR__ . "/../src/Html2Text.php");

class Html2TextTest extends \PHPUnit\Framework\TestCase {

	function doTest($test, $options = array()) {
		return $this->doTestWithResults($test, $test, $options);
	}

	function doTestWithResults($test, $result, $options = array()) {
		$this->assertTrue(file_exists(__DIR__ . "/$test.html"), "File '$test.html' did not exist");
		$this->assertTrue(file_exists(__DIR__ . "/$result.txt"), "File '$result.txt' did not exist");
		$input = file_get_contents(__DIR__ . "/$test.html");
		$expected = \Soundasleep\Html2Text::fixNewlines(file_get_contents(__DIR__ . "/$result.txt"));

		$output = \Soundasleep\Html2Text::convert($input, $options);

		if ($output != $expected) {
			file_put_contents(__DIR__ . "/$result.output", $output);
		}
		$this->assertEquals($output, $expected);
	}

	function testBasic() {
		$this->doTest("basic");
	}

	function testAnchors() {
		$this->doTest("anchors");
	}

	function testMoreAnchors() {
		$this->doTest("more-anchors");
	}

	function test3() {
		$this->doTest("test3");
	}

	function test4() {
		$this->doTest("test4");
	}

	function testTable() {
		$this->doTest("table");
	}

	function testNbsp() {
		$this->doTest("nbsp");
	}

	function testLists() {
		$this->doTest("lists");
	}

	function testPre() {
		$this->doTest("pre");
	}

	function testNewLines() {
		$this->doTest("newlines");
	}

	function testNestedDivs() {
		$this->doTest("nested-divs");
	}

	function testBlockQuotes() {
		$this->doTest("blockquotes");
	}

	function testFullEmail() {
		$this->doTest("full_email");
	}

	function testImages() {
		$this->doTest("images");
	}

	function testNonBreakingSpaces() {
		$this->doTest("non-breaking-spaces");
	}

	function testUtf8Example() {
		$this->doTest("utf8-example");
	}

	function testWindows1252Example() {
		$this->doTest("windows-1252-example");
	}

	function testMsoffice() {
		$this->doTest("msoffice");
	}

	function testDOMProcessing() {
		$this->doTest("dom-processing");
	}

	function testEmpty() {
		$this->doTest("empty");
	}

	function testHugeMsoffice() {
		$this->doTest("huge-msoffice");
	}

	function testZeroWidthNonJoiners() {
		$this->doTest("zero-width-non-joiners");
	}

    function testInvalidXML() {
        $this->expectWarning();
        $this->doTest("invalid", array('ignore_errors' => false));
	}

	function testInvalidXMLIgnore() {
		$this->doTest("invalid", array('ignore_errors' => true));
	}

	function testInvalidXMLIgnoreOldSyntax() {
		// for BC, allow old #convert(text, bool) syntax
		$this->doTest("invalid", true);
	}

    function testInvalidOption() {
        $this->expectException(InvalidArgumentException::class);
        $this->doTest("basic", array('invalid_option' => true));
	}

	function testBasicDropLinks() {
		$this->doTestWithResults("basic", "basic.no-links", array('drop_links' => true));
	}

	function testAnchorsDropLinks() {
		$this->doTestWithResults("anchors", "anchors.no-links", array('drop_links' => true));
	}

}