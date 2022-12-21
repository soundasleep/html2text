<?php

require(__DIR__ . "/../src/Html2Text.php");

class Html2TextTest extends \PHPUnit\Framework\TestCase {

	// delete all failures before we run
	public static function setUpBeforeClass(): void {
		foreach (new DirectoryIterator(__DIR__ . '/failures') as $fileInfo) {
			if ($fileInfo->getFileName()[0] != '.') {
				unlink($fileInfo->getPathname());
			}
		}
	}

	/**
	 * @dataProvider providerFiles
	 */
	public function testFile(string $test): void {
		$this->doTestWithResults($test, $test, []);
	}

	/** @param bool | array<string, bool | string> $options */
	function doTestWithResults(string $test, string $result, $options = []): void {
		$html = __DIR__ . "/html/$test.html";
		$txt = __DIR__ . "/txt/$result.txt";
		$this->assertTrue(file_exists($html), "File '{$html}' does not exist");
		$this->assertTrue(file_exists($txt), "File '{$txt}' does not exist");
		$input = file_get_contents($html);
		$expected = \Soundasleep\Html2Text::fixNewlines(file_get_contents($txt));

		$output = \Soundasleep\Html2Text::convert($input, $options);

		if ($output != $expected) {
			file_put_contents(__DIR__ . "/failures/$result.output", $output);
		}
		$this->assertEquals($expected, $output, "{$html} file failed to convert to {$txt}");
	}

	/** @return array<array<string>> */
	public function providerFiles(): array {
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
			['msoffice'],
			['dom-processing'],
			['empty'],
			['huge-msoffice'],
			['zero-width-non-joiners'],
		];
	}

	public function testInvalidXML(): void {
		$this->expectWarning();
		$this->doTestWithResults("invalid", "invalid", ['ignore_errors' => false]);
	}

	public function testInvalidXMLIgnore(): void {
		$this->doTestWithResults("invalid", "invalid", ['ignore_errors' => true]);
	}

	public function testInvalidXMLIgnoreOldSyntax(): void {
		// for BC, allow old #convert(text, bool) syntax
		$this->doTestWithResults("invalid", "invalid", true);
	}

	public function testInvalidOption(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->doTestWithResults("basic", "basic", ['invalid_option' => true]);
	}

	public function testBasicDropLinks(): void {
		$this->doTestWithResults("basic", "basic.no-links", ['drop_links' => true]);
	}

	public function testAnchorsDropLinks(): void {
		$this->doTestWithResults("anchors", "anchors.no-links", ['drop_links' => true]);
	}

	public function testWindows1252(): void {
		$this->doTestWithResults("windows-1252-example", "windows-1252-example", ['char_set' => 'windows-1252']);
	}
}