<?php
/******************************************************************************
 * Copyright (c) 2010 Jevon Wright and others.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html
 *
 * or
 *
 * LGPL which is available at http://www.gnu.org/licenses/lgpl.html
 *
 *
 * Contributors:
 *    Jevon Wright - initial API and implementation
 ****************************************************************************/

namespace Html2Text;

class Html2Text {

	/**
	 * Replaces URLs from <a> tags with a reference number (eg: "[1]") and moves the URL itself to
	 * the end of the document. Makes the resulting text much easier to read if your HTML contains
	 * many long URLs. The downside being the user has to scroll to the bottom of the document in
	 * order to find (and click on) the URL. It's a trade off and a decision you can make per
	 * document.
	 */
	const OPT_FOOTER_URLS = 1;

	/**
	 * If you use the OPT_FOOTER_URLS option, this variable will keep track of which indexes point
	 * to which URLs, so they can be inserted at the end of the converted text.
	 * @var array Associative array, where the key is a URL, and the value is an associative array
	 *     of properties (currently "index" and "text").
	 */
	static $_indexedUrls = array();

	/**
	 * Tries to convert the given HTML into a plain text format - best suited for
	 * e-mail display, etc.
	 *
	 * <p>In particular, it tries to maintain the following features:
	 * <ul>
	 *   <li>Links are maintained, with the 'href' copied over
	 *   <li>Information in the &lt;head&gt; is lost
	 * </ul>
	 *
	 * @param string html the input HTML
	 * @param array An array of options of the Html2Text::OPT_* variety
	 * @return string the HTML converted, as best as possible, to text
	 * @throws Html2TextException if the HTML could not be loaded as a {@link DOMDocument}
	 */
	static function convert($html,$options=array()) {

		// reset
		Html2Text::$_indexedUrls = array();

		// DOMDocument doesn't support empty value and throws an error
		if (!$html) {
			return '';
		}

		// replace &nbsp; with spaces
		$html = str_replace("&nbsp;", " ", $html);

		$html = static::fixNewlines($html);

		$doc = new \DOMDocument();
		$doc->strictErrorChecking = FALSE;
		$doc->recover = TRUE;
		$doc->xmlStandalone = true;
		$prevValue = libxml_use_internal_errors(true); //prevent $doc to trhow any warnings
		$loaded = $doc->loadHTML($html,LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET);
		libxml_use_internal_errors($prevValue); //restore original setting

		if (!$loaded) {
			throw new Html2TextException("Could not load HTML - badly formed?", $html);
		}

		$output = static::iterateOverNode($doc,$options);

		// remove leading and trailing spaces on each line
		$output = preg_replace("/[ \t]*\n[ \t]*/im", "\n", $output);

		// remove leading and trailing whitespace
		$output = trim($output);

		// if they want URLs at the end of the document instead of inline, append them here
		if (in_array(static::OPT_FOOTER_URLS,$options) && Html2Text::$_indexedUrls) {
			$output .= "\n\n------\n\n";
			foreach (Html2Text::$_indexedUrls as $url=>$info) {
				$output .= "[".$info['index']."] ".($info['text']?$info['text']." ":"").$url."\n";
			}
		}

		return $output;
	}

	/**
	 * Unify newlines; in particular, \r\n becomes \n, and
	 * then \r becomes \n. This means that all newlines (Unix, Windows, Mac)
	 * all become \ns.
	 *
	 * @param string text text with any number of \r, \r\n and \n combinations
	 * @return string the fixed text
	 */
	static function fixNewlines($text) {
		// replace \r\n to \n
		$text = str_replace("\r\n", "\n", $text);
		// remove \rs
		$text = str_replace("\r", "\n", $text);

		return $text;
	}

	static function nextChildName($node) {
		// get the next child
		$nextNode = $node->nextSibling;
		while ($nextNode != null) {
			if ($nextNode instanceof \DOMElement) {
				break;
			}
			$nextNode = $nextNode->nextSibling;
		}
		$nextName = null;
		if ($nextNode instanceof \DOMElement && $nextNode != null) {
			$nextName = strtolower($nextNode->nodeName);
		}

		return $nextName;
	}

	static function prevChildName($node) {
		// get the previous child
		$nextNode = $node->previousSibling;
		while ($nextNode != null) {
			if ($nextNode instanceof \DOMElement) {
				break;
			}
			$nextNode = $nextNode->previousSibling;
		}
		$nextName = null;
		if ($nextNode instanceof \DOMElement && $nextNode != null) {
			$nextName = strtolower($nextNode->nodeName);
		}

		return $nextName;
	}

	static function iterateOverNode($node,&$options) {
		if ($node instanceof \DOMText) {
		  // Replace whitespace characters with a space (equivilant to \s)
			return preg_replace("/[\\t\\n\\f\\r ]+/im", " ", $node->wholeText);
		}
		if ($node instanceof \DOMDocumentType) {
			// ignore
			return "";
		}

		$nextName = static::nextChildName($node);
		$prevName = static::prevChildName($node);

		$name = strtolower($node->nodeName);

		// start whitespace
		switch ($name) {
			case "hr":
				return "------\n";

			case "style":
			case "head":
			case "title":
			case "meta":
			case "script":
				// ignore these tags
				return "";

			case "h1":
			case "h2":
			case "h3":
			case "h4":
			case "h5":
			case "h6":
			case "ol":
			case "ul":
				// add two newlines, second line is added below
				$output = "\n";
				break;

			case "td":
			case "th":
				// add tab char to separate table fields
			   $output = "\t";
			   break;

			case "tr":
			case "p":
			case "div":
				// add one line
				$output = "\n";
				break;

			case "li":
				$output = "- ";
				break;

			case "img":
				$output = $node->getAttribute("alt");
				break;

			default:
				// print out contents of unknown tags
				$output = "";
				break;
		}

		// debug
		//$output .= "[$name,$nextName]";

		if (isset($node->childNodes)) {
			for ($i = 0; $i < $node->childNodes->length; $i++) {
				$n = $node->childNodes->item($i);

				$text = static::iterateOverNode($n,$options);

				$output .= $text;
			}
		}

		// end whitespace
		switch ($name) {
			case "style":
			case "head":
			case "title":
			case "meta":
			case "script":
				// ignore these tags
				return "";

			case "h1":
			case "h2":
			case "h3":
			case "h4":
			case "h5":
			case "h6":
				$output .= "\n";
				break;

			case "p":
			case "br":
				// add one line
				if ($nextName != "div")
					$output .= "\n";
				break;

			case "div":
				// add one line only if the next child isn't a div
				if ($nextName != "div" && $nextName != null)
					$output .= "\n";
				break;

			case "a":
				// links are returned in [text](link) format
				$href = $node->getAttribute("href");
				if ($href == null) {
					// it doesn't link anywhere
					if ($node->getAttribute("name") != null) {
						$output = "[$output]";
					}
				} else {
					if ($href == $output || $href == "mailto:$output" || $href == "http://$output" || $href == "https://$output") {
						// link to the same address: just use link
						$output;
					} elseif (in_array(static::OPT_FOOTER_URLS,$options)) {
						$output = $output."[".static::_indexUrl($href,$output)."]";
					} else {
						// replace it
						$output = "[$output]($href)";
					}
				}

				// does the next node require additional whitespace?
				switch ($nextName) {
					case "h1": case "h2": case "h3": case "h4": case "h5": case "h6":
						$output .= "\n";
						break;
				}
				break;

			case "li":
				$output .= "\n";
				break;

			default:
				// do nothing
		}

		return $output;
	}

	/**
	 * Accepts a URL (and optionally the link text) and returns a unique index number for that URL.
	 * @param  string $url  The URL you want an index number for.
	 * @param  string $text The text of the link (associated with the above URL).
	 * @return integer      The index number that will refer to the URL passed.
	 */
	static function _indexUrl($url,$text=null) {
		if (!isset(static::$_indexedUrls[$url])) {
			static::$_indexedUrls[$url] = array(
				'index'=>count(static::$_indexedUrls),
				'text'=>$text,
			);
		}
		return static::$_indexedUrls[$url]['index'];
	}

}
