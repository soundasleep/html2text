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
	 * @return string the HTML converted, as best as possible, to text
	 * @throws Html2TextException if the HTML could not be loaded as a {@link DOMDocument}
	 */
	static function convert($html) {
		// replace &nbsp; with spaces
		$html = str_replace("&nbsp;", " ", $html);

		$html = static::fixNewlines($html);

		$doc = new \DOMDocument();
		if (!$doc->loadHTML($html)) {
			throw new Html2TextException("Could not load HTML - badly formed?", $html);
		}

		$output = static::iterateOverNode($doc);

		// remove leading and trailing spaces on each line
		$output = preg_replace("/[ \t]*\n[ \t]*/im", "\n", $output);

		// remove leading and trailing whitespace
		$output = trim($output);

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

	static function iterateOverNode($node) {
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

				$text = static::iterateOverNode($n);

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

}
