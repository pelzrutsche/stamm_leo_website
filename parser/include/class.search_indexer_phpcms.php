<?php
/* $Id: class.search_indexer_phpcms.php,v 1.5.2.19 2006/06/18 18:07:32 ignatius0815 Exp $ */
/*
   +----------------------------------------------------------------------+
   | phpCMS Content Management System - Version 1.2
   +----------------------------------------------------------------------+
   | phpCMS is Copyright (c) 2001-2006 by the phpCMS Team
   +----------------------------------------------------------------------+
   | This program is free software; you can redistribute it and/or modify
   | it under the terms of the GNU General Public License as published by
   | the Free Software Foundation; either version 2 of the License, or
   | (at your option) any later version.
   |
   | This program is distributed in the hope that it will be useful, but
   | WITHOUT ANY WARRANTY; without even the implied warranty of
   | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
   | General Public License for more details.
   |
   | You should have received a copy of the GNU General Public License
   | along with this program; if not, write to the Free Software
   | Foundation, Inc., 59 Temple Place - Suite 330, Boston,
   | MA  02111-1307, USA.
   +----------------------------------------------------------------------+
   | Contributors:
   |    Michael Brauchl (mcyra)
   |    Beate Paland (beate76)
   |    Henning Poerschke (hpoe)
   |    Markus Richert (e157m369)
   +----------------------------------------------------------------------+
*/
if (!defined('PHPCMS_RUNNING')) die('Hacking attempt...');

// --------------------------------------
// set some basic vars
// --------------------------------------

$PHPCMS_DOC_ROOT = $DEFAULTS->DOCUMENT_ROOT;
$SEARCHDATADIR   = $PHPCMS_DOC_ROOT.$session->vars['datadir'];
$MAX_BYTE_SIZE   = $session->vars['maxbytesize'];
$MIN_WORD_SIZE   = $session->vars['minwordsize'];

$e = 0;
if(isset($session->vars['excludepath1'])) {
	$EXDIR[$e++] = $session->vars['excludepath1'];
}
if(isset($session->vars['excludepath2'])) {
	$EXDIR[$e++] = $session->vars['excludepath2'];
}
if(isset($session->vars['excludepath3'])) {
	$EXDIR[$e++] = $session->vars['excludepath3'];
}
if(isset($session->vars['excludepath4'])) {
	$EXDIR[$e++] = $session->vars['excludepath4'];
}
if(isset($session->vars['excludepath5'])) {
	$EXDIR[$e++] = $session->vars['excludepath5'];
}
if(isset($session->vars['excludepath6'])) {
	$EXDIR[$e++] = $session->vars['excludepath6'];
}
if(isset($session->vars['excludepath7'])) {
	$EXDIR[$e++] = $session->vars['excludepath7'];
}
if(isset($session->vars['excludepath8'])) {
	$EXDIR[$e++] = $session->vars['excludepath8'];
}
unset($e);

// patterns for documents
// ======================
// to get the content from HTML-title and HTML-body change the regex
// for each extension. you can easily add new extensions in the
// same way as for phpCMS here. these values work for me.
$EXTENSION[0]['type']        = 'htm';
$EXTENSION[0]['doc_start']   = '<';
$EXTENSION[0]['name']        = '.htm';
$EXTENSION[0]['title_start'] = "<[:space:]*title[^>]*>";
$EXTENSION[0]['title_stop']  = "<[:space:]*/title[^>]*>";
$EXTENSION[0]['body_start']  = "<body[^>]*>";
$EXTENSION[0]['body_stop']   = "</body[^>]*>";

$EXTENSION[1]['type']        = 'html';
$EXTENSION[1]['doc_start']   = '<';
$EXTENSION[1]['name']        = '.html';
$EXTENSION[1]['title_start'] = "<[:space:]*title[^>]*>";
$EXTENSION[1]['title_stop']  = "<[:space:]*/title[^>]*>";
$EXTENSION[1]['body_start']  = "<body[^>]*>";
$EXTENSION[1]['body_stop']   = "</body[^>]*>";

$PEXTENSION['doc_start']     = $DEFAULTS->START_FIELD;
$PEXTENSION['name']          = $DEFAULTS->PAGE_EXTENSION;

// --------------------------------------
// --------------------------------------

function addIndexField($fieldtoindex) {
	global $DEFAULTS, $PEXTENSION;

	$fieldname = $fieldtoindex;
	$fieldcount = count($PEXTENSION['FieldsToIndex']);
	//echo($fieldcount);
	$PEXTENSION['FieldsToIndex'][($fieldcount)]['field_start']	= '\\'.$DEFAULTS->START_FIELD.$fieldname.'\\'.$DEFAULTS->STOP_FIELD;
	$PEXTENSION['FieldsToIndex'][($fieldcount)]['field_stop']	= '\\'.$DEFAULTS->START_FIELD;
}

// --------------------------------------
// optimize stop words file
// --------------------------------------

function optimize_stopdb($db) {
	global $session;

	if(is_writable($db)) {
		$STOP = file($db);
		$i=0;$c=count($STOP);
		for($i; $i<$c; $i++) {
			$STOP[$i] = string_tolower($STOP[$i]);
		}
		$STOP = array_unique($STOP);
		sort($STOP);

		$fp = @fopen($db, "w+b");
		$i=0;$c=count($STOP);
		for ($i; $i<$c; $i++) {
			fwrite($fp,$STOP[$i]);
		}
		fclose($fp);
		$session->set_var('optimized', true);
		return true;
	}
	return false;
} //function optimize_stopdb($db)

// -----------------------------------------------
// this is the main-function of the file-indexer
// -----------------------------------------------

function index_entry($title, &$body, $page) {
	global
		$SEARCHDATADIR,
		$STOP,
		$STOP_MAX,
		$MIN_WORD_SIZE;

	$body = $title.' '.$body;

	// jump back, if the content of the
	// body-field is shorter than the
	// minimum-word-size.
	if(strlen($body) < $MIN_WORD_SIZE) {
		return;
	}
	// remove some unwanted chars
	$body = cleanChars($body);

	// make all words lowercase. search is not casesensitive
	// at this time. this increases performance a lot.
	$body = string_tolower($body);
	$body = trim($body);
	$wordAr = explode(' ', $body);
	$old_val = '';
	$doit = true;
	$indexer = 0;

	sort($wordAr);
	reset($wordAr);
	while(list($key, $val) = each($wordAr)) {
		$val = trim($val);

		if(strlen($val) == 0) {
			continue;
		}

		if($val == $old_val) {
			if($doit == false) {
				continue;
			}
			// same as bevore so only increment the word-counter for this page.
			$ResultArray[$indexer-1]['c']++;
		} else {
			// we dont need to make all checks on every word.
			// if there are two same words, we could also memory the checks.
			$old_val = $val;
			$doit = true;

			// removing some unwanted chars at beginning and end of a word.
			while(substr($val, 0, 1) == '-' OR substr($val, 0, 1) == '_') {
				$val = substr($val, 1);
			}
			while(substr($val, -1) == '-' OR substr($val, -1) == '_') {
				$val = substr($val, 0, -1);
			}

			// checking minimum word-size
			if(!is_numeric($val)) {
				if(strlen($val) < $MIN_WORD_SIZE) {
				$doit = false;
				continue;
				}
			}

			// only check stop-words, if the length of the word to index is shorter then the longest stop-word.
			// checking the stop-word-array is very time-consuming so check only if really nessesary.
			if(strlen($val) < $STOP_MAX) {
				if(CheckArray($STOP, $val)) {
					$doit = false;
					continue;
				}
			}
			if($doit) {
				// all checks passed => add this word to the index.
				$doit = true;
				$ResultArray[$indexer]['n'] = $val;
				$ResultArray[$indexer]['u'] = $page;
				$ResultArray[$indexer]['c'] = 1;
				$indexer++;
			}
		}
	}

	// return the result-array, wich looks like this:
	// $indexer = Array-Index 0-x
	// $ResultArray[$indexer]['n'] = the word to index
	// $ResultArray[$indexer]['u'] = the index of the page in which the word was found
	// $ResultArray[$indexer]['c'] = the occurences of the word in the page
	if(isset($ResultArray)) {
		return $ResultArray;
	} else {
		return;
	}
}

// --------------------------------------
// checks, if an entry is in a array.
// used for checking the stop-word-array
// --------------------------------------

function CheckArray($sarray, $entry) {
	global $PHP;

	$ac = count($sarray);
	for($i = 0; $i < $ac; $i++) {
		if(trim($sarray[$i]) == trim($entry)) {
			return true;
		}
	}
	return false;
}

// --------------------------------------------------
// reduce the length of the body-field to be
// displayed in the search-result. You can change the
// length with the variable $MaxChar.
// --------------------------------------------------

function MakeShortWords($words) {
	global $session;

	$MaxChar = $session->vars['textsize'];

	$words = substr($words, 0, $MaxChar * 2);
	$words = str_replace('  ', ' ', $words);
	$words = substr(trim(strip_tags($words)), 0, $MaxChar);
	$lpos  = strrpos($words, ' ');
	$words = substr($words, 0, $lpos);
	$words = str_replace(';', '##', $words);
	return $words;
}

// --------------------------------------
// trimming the title-string and replace
// all occurences of ";", because we use
// this for splitting the entry later.
// --------------------------------------

function TrimTitle($words) {
	$words = trim($words);
	$words = str_replace(';', '##', $words);
	return $words;
}

// --------------------------------------
// remove unwanted characters
// --------------------------------------

function cleanChars($body) {
	$strip_array = array(
	"!"  , ":"  , '"', "\'", "@",
	"&lt", "&gt", "$", "%" , "(",
	")"  , "["  , "]", "{" , "}",
	"?"  , "*"  , "+", "|" , "^",
	"'"  , "�"  , "`", "~" , "\\",
	"�"  , "�"  , "=", "\n", "/",
	".." , "-"  , "	", "  ",
	"&#8220;", "&#8221;", "&#8222;",
	"&#8217;", "&#8216;",
	//   non-breaking space
	"&nbsp;", "&#160;",
	// � inverted exclamation mark
	"&iexcl;", "&#161;", "�",
	// � cent sign
	"&cent;", "&#162;", "�",
	// � pound sign
	"&pound;", "&#163;", "�",
	// � currency sign
	"&curren;", "&#164;", "�",
	// � yen sign
	"&yen;", "&#165;", "�",
	// � broken bar
	"&brvbar;", "&#166;", "�",
	// � section sign
	"&sect;", "&#167;", "�",
	// � diaeresis
	"&uml;", "&#168;", "�",
	// � copyright sign
	"&copy;", "&#169;", "�",
	// � feminine ordinal indicator
	"&ordf;", "&#170;", "�",
	// � left-pointing double angle quotation mark
	"&laquo;", "&#171;", "�",
	// � not sign
	"&not;", "&#172;", "�",
	// � soft hyphen
	"&shy;", "&#173;",
	// � registered sign
	"&reg;", "&#174;", "�",
	// � macron
	"&macr;", "&#175;", "�",
	// � degree sign
	"&deg;", "&#176;", "�",
	// � plus-minus sign
	"&plusmn;", "&#177;", "�",
	// � superscript two
	"&sup2;", "&#178;", "�",
	// � superscript three
	"&sup3;", "&#179;", "�",
	// � acute accent
	"&acute;", "&#180;", "�",
	// � micro sign
	"&micro;", "&#181;", "�",
	// � pilcrow sign
	"&para;", "&#182;", "�",
	// � middle dot
	"&middot;", "&#183;", "�",
	// � cedilla
	"&cedil;", "&#184;", "�",
	// � superscript one
	"&sup1;", "&#185;", "�",
	// � masculine ordinal indicator
	"&ordm;", "&#186;", "�",
	// � right-pointing double angle quotation mark
	"&raquo;", "&#187;", "�",
	// � vulgar fraction one quarter
	"&frac14;", "&#188;", "�",
	// � vulgar fraction one half
	"&frac12;", "&#189;", "�",
	// � vulgar fraction three quarters
	"&frac34;", "&#190;", "�",
	// � inverted question mark
	"&iquest;", "&#191;", "�",
	// � multiplication sign
	"&times;", "&#215", "�",
	// � latin capital letter O with stroke
	"&Oslash;", "&#216;", "�",
	// � division sign
	"&divide;", "&#247;", "�",
	// � latin small letter o with stroke
	"&oslash;", "&#248;", "�",
	// � horizontal ellipse
	"�", "&#8230;", "&hellip;",	";",
	"&#",
	"#",
	"&",
	"  ",
	"  ");

	$body = str_replace($strip_array, ' ', $body);

	// index numbers w/o separators?
	//$body = preg_replace("/(\d+)(\.)?(\d+)/","\\1\\3",$body);
	//$body = preg_replace("/(\d+)(\,)?(\d+)/","\\1\\3",$body);

	//	disallow indexing of numbers and seperators?
	//$strip_array = array(".", ",", "1", "2", "3", "4", "5", "6", "7", "8", "9", "0");
	//$body = str_replace($strip_array, ' ', $body);

	$body = preg_replace("/([a-zA-Z]+)(\.) /si","\\1 ",$body);
	$body = preg_replace("/([a-zA-Z]+)(\,) /si","\\1 ",$body);

	return $body;
}

// --------------------------------------
// i know. :-)
// --------------------------------------

/*
function make_german(&$body) {
	$body = str_replace('>', '> ', $body);
	$body = str_replace('&Auml;', '�', $body);
	$body = str_replace('&auml;', '�', $body);
	$body = str_replace('&ouml;', '�', $body);
	$body = str_replace('&Ouml;', '�', $body);
	$body = str_replace('&uuml;', '�', $body);
	$body = str_replace('&Uuml;', '�', $body);
	$body = str_replace('&szlig;', '�', $body);
	$body = strip_tags($body);
}
*/

// --------------------------------------
// a better strtolower()
// --------------------------------------

function string_tolower($string) {

	$replacement = array(
	// � latin capital letter A with grave
	"�"		=>		"�",
	// � latin capital letter A with acute
	"�"		=>		"�",
	// � latin capital letter A with circumflex
	"�"		=>		"�",
	// � latin capital letter A with tilde
	"�"		=>		"�",
	// � latin capital letter A with diaeresis
	"�"		=>		"�",
	// � latin capital letter A with ring above
	"�"		=>		"�",
	// � latin capital letter AE
	"�"		=>		"�",
	// � latin capital letter C with cedilla
	"�"		=>		"�",
	// � latin capital letter E with grave
	"�"		=>		"�",
	// � latin capital letter E with acute
	"�"		=>		"�",
	// � latin capital letter E with circumflex
	"�"		=>		"�",
	// � latin capital letter E with diaeresis
	"�"		=>		"�",
	// � latin capital letter I with grave
	"�"		=>		"�",
	// � latin capital letter I with acute
	"�"		=>		"�",
	// � latin capital letter I with circumflex
	"�"		=>		"�",
	// � latin capital letter I with diaeresis
	"�"		=>		"�",
	// � latin capital letter ETH
	"�"		=>		"�",
	// � latin capital letter N with tilde
	"�"		=>		"�",
	// � latin capital letter O with grave
	"�"		=>		"�",
	// � latin capital letter O with acute
	"�"		=>		"�",
	// � latin capital letter O with circumflex
	"�"		=>		"�",
	// � latin capital letter O with tilde
	"�"		=>		"�",
	// � latin capital letter O with diaeresis
	"�"		=>		"�",
	// � latin capital letter U with grave
	"�"		=>		"�",
	// � latin capital letter U with acute
	"�"		=>		"�",
	// � latin capital letter U with circumflex
	"�"		=>		"�",
	// � latin capital letter U with diaeresis
	"�"		=>		"�",
	// � latin capital letter Y with acute
	"�"		=>		"�",
	// � latin capital letter THORN
	"�"		=>		"�",
	);

	foreach($replacement as $key=>$value) {
		$string = str_replace ($key, $value, $string);
	}
	$string = strtolower($string);
	return $string;
}

// --------------------------------------
// --------------------------------------

function revertDiacritical($string) {
	$string = str_replace('>','> ',$string);
	$string = str_replace('<',' <',$string);

	// (\&[a-zA-Z0-9]+;)(.)*:=(.)*(\&#[0-9]+;)
	// \t$string = str_replace ('\1', ' ', $string);\n\t$string = str_replace ('\4', ' ', $string);

	$replacement = array(

	// � latin capital letter A with grave
	"&Agrave;"		=>		"�",
	"&#192;"		=>		"�",
	// � latin capital letter A with acute
	"&Aacute;"		=>		"�",
	"&#193;"		=>		"�",
	// � latin capital letter A with circumflex
	"&Acirc;"		=>		"�",
	"&#194;"		=>		"�",
	// � latin capital letter A with tilde
	"&Atilde;"		=>		"�",
	"&#195;"		=>		"�",
	// � latin capital letter A with diaeresis
	"&Auml;"		=>		"�",
	"&#196;"		=>		"�",
	// � latin capital letter A with ring above
	"&Aring;"		=>		"�",
	"&#197;"		=>		"�",
	// � latin capital letter AE
	"&AElig;"		=>		"�",
	"&#198;"		=>		"�",
	// � latin capital letter C with cedilla
	"&Ccedil;"		=>		"�",
	"&#199;"		=>		"�",
	// � latin capital letter E with grave
	"&Egrave;"		=>		"�",
	"&#200;"		=>		"�",
	// � latin capital letter E with acute
	"&Eacute;"		=>		"�",
	"&#201;"		=>		"�",
	// � latin capital letter E with circumflex
	"&Ecirc;"		=>		"�",
	"&#202;"		=>		"�",
	// � latin capital letter E with diaeresis
	"&Euml;"		=>		"�",
	"&#203;"		=>		"�",
	// � latin capital letter I with grave
	"&Igrave;"		=>		"�",
	"&#204;"		=>		"�",
	// � latin capital letter I with acute
	"&Iacute;"		=>		"�",
	"&#205;"		=>		"�",
	// � latin capital letter I with circumflex
	"&Icirc;"		=>		"�",
	"&#206;"		=>		"�",
	// � latin capital letter I with diaeresis
	"&Iuml;"		=>		"�",
	"&#207;"		=>		"�",
	// � latin capital letter ETH
	"&ETH;"		=>		"�",
	"&#208;"		=>		"�",
	// � latin capital letter N with tilde
	"&Ntilde;"		=>		"�",
	"&#209;"		=>		"�",
	// � latin capital letter O with grave
	"&Ograve;"		=>		"�",
	"&#210;"		=>		"�",
	// � latin capital letter O with acute
	"&Oacute;"		=>		"�",
	"&#211;"		=>		"�",
	// � latin capital letter O with circumflex
	"&Ocirc;"		=>		"�",
	"&#212;"		=>		"�",
	// � latin capital letter O with tilde
	"&Otilde;"		=>		"�",
	"&#213;"		=>		"�",
	// � latin capital letter O with diaeresis
	"&Ouml;"		=>		"�",
	"&#214;"		=>		"�",
	// � latin capital letter U with grave
	"&Ugrave;"		=>		"�",
	"&#217;"		=>		"�",
	// � latin capital letter U with acute
	"&Uacute;"		=>		"�",
	"&#218;"		=>		"�",
	// � latin capital letter U with circumflex
	"&Ucirc;"		=>		"�",
	"&#219;"		=>		"�",
	// � latin capital letter U with diaeresis
	"&Uuml;"		=>		"�",
	"&#220;"		=>		"�",
	// � latin capital letter Y with acute
	"&Yacute;"		=>		"�",
	"&#221;"		=>		"�",
	// � latin capital letter THORN
	"&THORN;"		=>		"�",
	"&#222;"		=>		"�",
	// � latin small letter sharp s
	"&szlig;"		=>		"�",
	"&#223;"		=>		"�",
	// � latin small letter a with grave
	"&agrave;"		=>		"�",
	"&#224;"		=>		"�",
	// � latin small letter a with acute
	"&aacute;"		=>		"�",
	"&#225;"		=>		"�",
	// � latin small letter a with circumflex
	"&acirc;"		=>		"�",
	"&#226;"		=>		"�",
	// � latin small letter a with tilde
	"&atilde;"		=>		"�",
	"&#227;"		=>		"�",
	// � latin small letter a with diaeresis
	"&auml;"		=>		"�",
	"&#228;"		=>		"�",
	// � latin small letter a with ring above
	"&aring;"		=>		"�",
	"&#229;"		=>		"�",
	// � latin small letter ae
	"&aelig;"		=>		"�",
	"&#230;"		=>		"�",
	// � latin small letter c with cedilla
	"&ccedil;"		=>		"�",
	"&#231;"		=>		"�",
	// � latin small letter e with grave
	"&egrave;"		=>		"�",
	"&#232;"		=>		"�",
	// � latin small letter e with acute
	"&eacute;"		=>		"�",
	"&#233;"		=>		"�",
	// � latin small letter e with circumflex
	"&ecirc;"		=>		"�",
	"&#234;"		=>		"�",
	// � latin small letter e with diaeresis
	"&euml;"		=>		"�",
	"&#235;"		=>		"�",
	// � latin small letter i with grave
	"&igrave;"		=>		"�",
	"&#236;"		=>		"�",
	// � latin small letter i with acute
	"&iacute;"		=>		"�",
	"&#237;"		=>		"�",
	// � latin small letter i with circumflex
	"&icirc;"		=>		"�",
	"&#238;"		=>		"�",
	// � latin small letter i with diaeresis
	"&iuml;"		=>		"� ",
	"&#239;"		=>		"� ",
	// � latin small letter eth
	"&eth;"		=>		"�",
	"&#240;"		=>		"�",
	// � latin small letter n with tilde
	"&ntilde;"		=>		"�",
	"&#241;"		=>		"�",
	// � latin small letter o with grave
	"&ograve;"		=>		"�",
	"&#242;"		=>		"�",
	// � latin small letter o with acute
	"&oacute;"		=>		"�",
	"&#243;"		=>		"�",
	// � latin small letter o with circumflex
	"&ocirc;"		=>		"�",
	"&#244;"		=>		"�",
	// � latin small letter o with tilde
	"&otilde;"		=>		"�",
	"&#245;"		=>		"�",
	// � latin small letter o with diaeresis
	"&ouml;"		=>		"�",
	"&#246;"		=>		"�",
	// � latin small letter u with grave
	"&ugrave;"		=>		"�",
	"&#249;"		=>		"�",
	// � latin small letter u with acute
	"&uacute;"		=>		"�",
	"&#250;"		=>		"�",
	// � latin small letter u with circumflex
	"&ucirc;"		=>		"�",
	"&#251;"		=>		"�",
	// � latin small letter u with diaeresis
	"&uuml;"		=>		"�",
	"&#252;"		=>		"�",
	// � latin small letter y with acute
	"&yacute;"		=>		"�",
	"&#253;"		=>		"�",
	// � latin small letter thorn
	"&thorn;"		=>		"�",
	"&#254;"		=>		"�",
	// � latin small letter y with diaeresis
	"&yuml;"		=>		"�",
	"&#255;"		=>		"�"
	);

	foreach($replacement as $key=>$val) {
		$string = str_replace ($key, $val, $string);
	}

	return $string;
}

// --------------------------------------
// A better strip_tags()
// --------------------------------------

function mstrip_tags($tostrip) {

	//$tostrip = preg_replace ("'<!-- PHPCMS_IGNORE --[^>]*?".">.*?<!-- /PHPCMS_IGNORE -->'si", " ", $tostrip);
	while (preg_match("'<phpcms:ignore[^>]*?>.*?</phpcms:ignore>'si", $tostrip)) {
		$tostrip = preg_replace ("'<phpcms:ignore[^>]*?>.*?</phpcms:ignore>'si", " ", $tostrip); // <?
	}

	//$tostrip = preg_replace ("'<!-- PHPCMS_NOINDEX --[^>]*?".">.*?<!-- /PHPCMS_NOINDEX -->'si", " ", $tostrip);
	while (preg_match("'<phpcms:noindex[^>]*?>.*?</phpcms:noindex>'si", $tostrip)) { // <?
		$tostrip = preg_replace ("'<phpcms:noindex[^>]*?>.*?</phpcms:noindex>'si", " ", $tostrip); // <?
	}

	$search = '/<!--(.*)-->/Uis';
	$tostrip = preg_replace($search,' ',$tostrip);

	$search = '/<[^>]+>/s';
	$tostrip = preg_replace($search,' ',$tostrip);

	$search = '/(&nbsp;)|(&copy;)/is';
	$tostrip = preg_replace($search,' ',$tostrip);

	$search = '/"[<|>]"/is';
	$tostrip = preg_replace($search,' ',$tostrip);

	return $tostrip;
}

// --------------------------------------
// this function does the main work.
// Writing the file-index, writing the
// word-index etc.
// --------------------------------------

function write_file_entry($FileIndex, $actual_entry, $titel, &$body, &$written) {
	global
	$SEARCHDATADIR,
	$PHPCMS_DOC_ROOT,
	$MIN_WORD_SIZE;

	// throw out parts that should be ignored
	$body = mstrip_tags($body);

	// revert unicode and entities to ISO-8859-1 chars
	$body = revertDiacritical($body);

	// throw out parts that should be ignored
	$titel = mstrip_tags($titel);

	// revert unicode and entities to ISO-8859-1 chars
	$titel = revertDiacritical($titel);

	// we need this later for the status-display
	$written.= substr(trim($actual_entry), strlen($PHPCMS_DOC_ROOT)).'<br />';

	$fp = fopen($SEARCHDATADIR.'/files.db', 'ab+');

	// the entry in the file.db looks like this:
	// index_of_page;title-text_of_page;body-text_of_page
	// eg: 33;the title;the body
	$entry = substr(trim($actual_entry), strlen($PHPCMS_DOC_ROOT));
	$entry .=';'.TrimTitle($titel).';'.MakeShortWords($body)."\n";
	fputs($fp, $entry, strlen($entry));
	fclose($fp);

	$ResultArray = index_entry($titel, $body, $FileIndex);
	$indexer = count($ResultArray);

	// for write-performance, we write all
	// index-entrys in one string and append
	// this string to the index.
	$words_to_write = '';
	for($i = 0; $i < $indexer; $i++) {
		unset($entry);
		$entry = $ResultArray[$i]['n'].'#'.$ResultArray[$i]['u'].'#'.$ResultArray[$i]['c'];
		$words_to_write = $words_to_write."\n".$entry;
	}
	if(strlen($words_to_write) > $MIN_WORD_SIZE) {
		$fp = fopen($SEARCHDATADIR.'/words.tmp', 'ab+');
		fwrite($fp, $words_to_write, strlen($words_to_write));
		fclose($fp);
	}
}

// --------------------------------------
// --------------------------------------

function get_dir_list($dire) {
	global
		$DEFAULTS,
		$EXTENSION,
		$PEXTENSION,
		$EXDIR,
		$PHPCMS_DOC_ROOT;

	$d = dir($dire);
	while($entry = $d->read()) {
		$test = substr($dire.'/'.$entry, strlen($PHPCMS_DOC_ROOT));
		if($entry == '.' OR $entry == '..') {
			continue;
		}
		if(CheckArray($EXDIR,$test)) {
			continue;
		}
		if(is_dir($dire.'/'.$entry)) {
			$add_array = get_dir_list($dire.'/'.$entry);
			if(!isset($add_array)) {
				continue;
			}
			for($i = 0; $i < count($add_array); $i++) {
				if(!isset($ReturnArray)) {
					$ReturnArray[0] = $add_array[$i];
				} else {
					$ReturnArray[count($ReturnArray)] = $add_array[$i];
				}
			}
			continue;
		}
		$extension = substr($entry, strrpos($entry, '.'));
		$doit = false;
		if($extension == $DEFAULTS->PAGE_EXTENSION) {
			$doit = true;
		}
		for($i = 0; $i < count($EXTENSION); $i++) {
			if($extension != $EXTENSION[$i]['name']) {
				continue;
			} else {
				$doit = true;
				break;
			}
		}
		if(!$doit) {
			continue;
		}
		if(!isset($ReturnArray)) {
			$ReturnArray[0] = $dire.'/'.$entry;
		} else {
			$ReturnArray[count($ReturnArray)] = $dire.'/'.$entry;
		}
	}
	if(isset($ReturnArray)) {
		return $ReturnArray;
	} else {
		return;
	}
}

// --------------------------------------
// now looping through the files which
// should be indexed
// --------------------------------------

function doindex() {
	global
	$DEFAULTS,
	$SEARCHDATADIR,
	$EXTENSION,
	$PEXTENSION,
	$MESSAGES,
	$MAX_BYTE_SIZE,
	$session;

	// because we make more reloads, this checks if we are ready.
	if(!file_exists($SEARCHDATADIR.'/files_to_index.txt') OR filesize($SEARCHDATADIR.'/files_to_index.txt') < 3) {
		unlink($SEARCHDATADIR.'/files_indexed.txt');
		unlink($SEARCHDATADIR.'/files_to_index.txt');
		$session->set_var('start', '0');
		$session->set_var('task', 'MERGER1');
		merger1();
		soft_exit();
	}
	// read the files we have to process
	$dirarray = file($SEARCHDATADIR.'/files_to_index.txt');

	// get the actual fileindex
	if(file_exists($SEARCHDATADIR.'/files_indexed.txt')) {
		$readyarray = file($SEARCHDATADIR.'/files_indexed.txt');
		$filecounter = count($readyarray);
		unset($readyarray);
	} else {
		$filecounter = 0;
	}

	// init some counters
	$entry_counter = 0;
	$continue_index = 1;
	$actual_filesize = 0;
	$written = '';

	while($continue_index == 1) {
		unset($body);
		unset($titel);

		$actual_entry = trim($dirarray[$entry_counter]);

		$raw_value = file($actual_entry);
		$value = join('', $raw_value);
		$value = str_replace("\n", ' ', $value);
		$value = str_replace("\r", ' ', $value);
		$value = str_replace("\t", ' ', $value);
		$epos = strrpos($actual_entry, '.');
		$extension = substr($actual_entry, $epos);

		// if it is an phpCMS-file, use this
		if($extension == $DEFAULTS->PAGE_EXTENSION AND substr($value, 0, strlen($PEXTENSION['doc_start'])) == $PEXTENSION['doc_start']) {
			eregi($PEXTENSION['FieldsToIndex'][0]['field_start']."([^".$PEXTENSION['FieldsToIndex'][0]['field_stop']."]*)", $value, $titel);

			for($i=1; $i<count($PEXTENSION['FieldsToIndex']); $i++) {
				eregi($PEXTENSION['FieldsToIndex'][$i]['field_start']."([^".$PEXTENSION['FieldsToIndex'][$i]['field_stop']."]*)", $value, $bodytemp);
				$body[1] .= $bodytemp[1];
			}
			unset($bodytemp);

			write_file_entry($filecounter, $actual_entry, $titel[1], $body[1], $written);
		} else {
			// walk through all other extensions, which are defined
			for($i = 0; $i < count($EXTENSION); $i++) {
				if($extension != $EXTENSION[$i]['name']) {
					continue;
				}
				eregi($EXTENSION[$i]['title_start']."(.*)".$EXTENSION[$i]['title_stop'], $value, $titel);
				eregi($EXTENSION[$i]['body_start']."(.*)".$EXTENSION[$i]['body_stop'], $value, $body);
				write_file_entry($filecounter, $actual_entry, $titel[1], $body[1], $written);
			}
		}

		$entry_counter++;
		$filecounter++;
		// stop if no more entries are available
		if($entry_counter == count($dirarray)) {
			$continue_index = -1;
		} else {
			// calculate the size of the next file, because we have a
			// time-limit on the server.
			// if the size is bigger as the limit, force a reload of the page.
			$actual_filesize = $actual_filesize + filesize($actual_entry);
			$next_entry = trim($dirarray[$entry_counter]);
			$next_filesize = filesize($next_entry);
			if(($actual_filesize + $next_filesize) > $MAX_BYTE_SIZE) {
				$continue_index = -1;
			}
		}
	}
	// writing the process for the files
	if(file_exists($SEARCHDATADIR.'/files_indexed.txt')) {
		$readyarray = file($SEARCHDATADIR.'/files_indexed.txt');
		$fc = count($readyarray);
		for($i = 0; $i < $entry_counter; $i++) {
			$readyarray[$fc+$i] = $dirarray[$i];
		}
	} else {
		for($i = 0; $i < $entry_counter; $i++) {
			$readyarray[$i] = $dirarray[$i];
		}
	}
	$fp = fopen($SEARCHDATADIR.'/files_indexed.txt', 'w');
	$files_indexed = count($readyarray);
	for($i = 0; $i < $files_indexed; $i++) {
		fputs($fp, trim($readyarray[$i])."\n");
	}
	fclose($fp);

	$fp = fopen($SEARCHDATADIR.'/files_to_index.txt', 'w');
	$files_to_index = count($dirarray);
	for($i = $entry_counter; $i < $files_to_index; $i++) {
		fputs($fp, trim($dirarray[$i])."\n");
	}
	fclose($fp);

	// force the reload of this page
	b_write('<html>');
	b_write('<head>');
	b_write('<meta http-equiv="refresh" content="0; URL='.make_link('&select=INDEXER').'" />');
	b_write('</head>');
	b_write('<body>');
	// write some status information, collected in write_file_entry()
	b_write('<br /><font face="Arial, Helvetica, Verdana" size=3><b>');
	b_write($MESSAGES['FILE_SRC'][26].'</b></font><br />');
	b_write('<font face="Arial, Helvetica, Verdana" size=2>');
	b_write($MESSAGES['FILE_SRC'][27].$files_indexed.$MESSAGES['FILE_SRC'][28].'<br />');
	b_write($MESSAGES['FILE_SRC'][29].$files_to_index.$MESSAGES['FILE_SRC'][30].'<br />');
	b_write($MESSAGES['FILE_SRC'][31].'<hr />');
	b_write($written);
	b_write('</font></body>');
	b_write('</html>');

	soft_exit();
}

// --------------------------------------
// --------------------------------------

function index() {
	global
		$DEFAULTS,
		$session,
		$SEARCHDATADIR;

	// clean data directory of old index-files
	$stopdb = $session->vars['stopwords'];
	$dire = $SEARCHDATADIR;
	$d = dir($dire);
	while($entry = $d->read()) {
		$entry = trim($entry);
		if(is_dir($SEARCHDATADIR.'/'.$entry)) {
			continue;
		}
		if($entry{0} == '.') {
			continue;
		}
		if($entry == $stopdb) {
			continue;
		}
		if($entry == 'nono.db') {
			continue;
		}
		if($entry == 'log.txt') {
			unlink($SEARCHDATADIR.'/'.$entry);
		}
		if(substr($entry, strlen($entry)-3, 3) == '.db') {
			unlink($SEARCHDATADIR.'/'.$entry);
		}
		elseif(substr($entry, strlen($entry)-4, 4) == '.tmp') {
			unlink($SEARCHDATADIR.'/'.$entry);
		}
	}

	$session->set_var('globaltime', time());

	if(trim($session->vars['startpath']) == '/') {
		$indexdir = $DEFAULTS->DOCUMENT_ROOT;
	} else {
		$indexdir = $DEFAULTS->DOCUMENT_ROOT.$session->vars['startpath'];
	}

	$dirarray = get_dir_list($indexdir);
	chdir($SEARCHDATADIR);
	$fp = fopen('files_to_index.txt', 'w');
	for($i = 0; $i < count($dirarray); $i++) {
		fputs($fp, $dirarray[$i]."\n");
	}
	fclose($fp);
	$session->set_var('task', 'INDEX');
	doindex();
}

// --------------------------------------
// merger-functions
// --------------------------------------

function merger1() {
	global $SEARCHDATADIR, $MAX_BYTE_SIZE, $session, $MESSAGES;

	$starttime = time();
	$TempWordsCount = 0;
	$index = 0;
	$start = $session->vars['start'];

	$fp = fopen($SEARCHDATADIR.'/words.tmp', 'rb');
	fseek($fp, $start);

	while((($TempWordsCount + 100) < $MAX_BYTE_SIZE) AND !feof($fp)) {
		$TempWords[$index] = trim(fgets($fp, 4096));
		if(strlen($TempWords[$index]) == 0) {
			continue;
		}
		$TempWordsCount = $TempWordsCount + strlen($TempWords[$index]) + 1;
		$index++;
	}
	if(feof($fp)) {
		$session->set_var('task', 'MERGER2');
	}
	fclose ($fp);

	$next_start = $start + $TempWordsCount;
	$session->set_var('start', $next_start);

	for($i = 0; $i < $index; $i++) {
		list($word, $file) = explode('#', $TempWords[$i]);
		$word_len = strlen($word);

		$fp = fopen($SEARCHDATADIR.'/t'.$word_len.'.db', 'ab+');
		$entry = $TempWords[$i]."\n";
		fputs($fp, $entry, strlen($entry));
		fclose($fp);
	}

	b_write('<html>');
	b_write('<head>');
	b_write('<META http-equiv="refresh" content="0; URL='.make_link('&select=INDEXER').'">');
	b_write('</head>');
	b_write('<body>');
	// writing some status infos, collected in write_file_entry()
	b_write('<br /><font face="Arial, Helvetica, Verdana" size=3><b>');
	b_write($MESSAGES['FILE_SRC'][32].'</b></font><br />');
	b_write('<font face="Arial, Helvetica, Verdana" size=2>');
	b_write($MESSAGES['FILE_SRC'][33].$next_start.$MESSAGES['FILE_SRC'][34].filesize($SEARCHDATADIR.'/words.tmp').$MESSAGES['FILE_SRC'][35].'<hr />');
	b_write($MESSAGES['FILE_SRC'][36].date("H:i:s", $starttime).'<br />');
	b_write($MESSAGES['FILE_SRC'][37].date("H:i:s", time()).'<br />');
	b_write($MESSAGES['FILE_SRC'][38].date("i:s", (time()-$starttime)).'<br />');
	b_write('</font></body>');
	b_write('</html>');
}

// --------------------------------------
// --------------------------------------

function merger2() {
	global $SEARCHDATADIR, $session, $MESSAGES;

	$starttime = time();

	// open directory,
	$dire = $SEARCHDATADIR;
	$d = dir($dire);
	while($entry = $d->read()) {
		if(substr($entry, 0, 1) == 't') {
			$current_file = $SEARCHDATADIR.'/'.$entry;
			break;
		}
	}

	// get first temp-file
	if(isset($current_file)) {
		$TempArray = file($current_file);
		$index = 0;
		for($i = 0; $i < count($TempArray); $i++) {
			$TempArray[$i] = trim($TempArray[$i]);
			if(strlen($TempArray[$i]) < 1) {
				continue;
			}

			list($word, $seite, $anzahl) = explode('#', $TempArray[$i]);

			if(isset($WordArray[$word])) {
				$DataArray[$WordArray[$word]] = $DataArray[$WordArray[$word]].'+'.$seite.'*'.$anzahl;
			} else {
				$WordArray[$word] = $index;
				$IndexArray[$index] = $word;
				$DataArray[$index] = $seite.'*'.$anzahl;
				$index++;
			}
		}

		$output_file = $SEARCHDATADIR.'/words.db';

		$fp = fopen($output_file, 'ab+');
		foreach($IndexArray as $k=>$v) {
			$v = $v."\n";
			$size = strlen($v);
			fputs($fp, $v, $size);
		}
		fclose($fp);

		$output_file = $SEARCHDATADIR.'/data.db';

		$fp = fopen($output_file, 'ab+');
		foreach($DataArray as $k=>$v) {
			$v = $v."\n";
			$size = strlen($v);
			fputs($fp, $v, $size);
		}
		fclose($fp);

		unlink($current_file);

		// write timing
		$log_entry = 'current wordlength:'.substr($entry, 1, strrpos($entry, '.') - 1);
		$log_entry .= ' time: '.date("H:i:s", (time() - $starttime))."\n";
		$fp = fopen($SEARCHDATADIR.'/log.txt', 'a+');
		$size = strlen($log_entry);
		fputs($fp, $log_entry, $size);
		fclose($fp);

		b_write('<html>');
		b_write('<head>');
		b_write('<META http-equiv="refresh" content="0; URL='.make_link('&select=INDEXER').'">');
		b_write('</head>');
		b_write('<body>');
		// writing som status infos, collected in write_file_entry()
		b_write('<br /><font face="Arial, Helvetica, Verdana" size=3><b>');
		b_write($MESSAGES['FILE_SRC'][39].'</b></font><br />');
		b_write('<font face="Arial, Helvetica, Verdana" size=2>');
		b_write($MESSAGES['FILE_SRC'][40].substr($entry, 1, strrpos($entry, '.')-1).'<hr />');
		b_write($MESSAGES['FILE_SRC'][36].date("H:i:s", $starttime).'<br />');
		b_write($MESSAGES['FILE_SRC'][37].date("H:i:s", time()).'<br />');
		b_write($MESSAGES['FILE_SRC'][38].date("i:s", (time()-$starttime)).'<br />');
		b_write('</font></body>');
		b_write('</html>');
	} else {
		if(trim($session->vars['gzipcompr']) == $MESSAGES['ON'] AND extension_loaded('zlib')) {
			$WordIndex = file($SEARCHDATADIR.'/words.db');
			$WordToWrite = implode("", $WordIndex);
			$gp1 = gzopen($SEARCHDATADIR.'/words.gz', 'wb');
			gzwrite($gp1, $WordToWrite);
			gzclose($gp1);
			unlink($SEARCHDATADIR.'/words.db');

			$FileDB = file($SEARCHDATADIR.'/files.db');
			$FileToWrite = implode("", $FileDB);
			$gp2 = gzopen($SEARCHDATADIR.'/files.gz', 'wb');
			gzwrite($gp2, $FileToWrite);
			gzclose($gp2);
			unlink($SEARCHDATADIR.'/files.db');

			$DataArray = file($SEARCHDATADIR.'/data.db');
			$DataToWrite = implode("", $DataArray);
			$gp3 = gzopen($SEARCHDATADIR.'/data.gz', 'wb');
			gzwrite($gp3, $DataToWrite);
			gzclose($gp3);
			unlink($SEARCHDATADIR.'/data.db');

			unlink($SEARCHDATADIR.'/words.tmp');
			$globaltime = date("i:s", (time() - $session->vars['globaltime']));
			$session->destroy();

			b_write('<html>');
			b_write('<head><title></title></head>');
			b_write('<body>');
			b_write('<br /><font face="Arial, Helvetica, Verdana" size=3><b>');
			b_write($MESSAGES['FILE_SRC'][41].'</b></font><br />');
			b_write('<font face="Arial, Helvetica, Verdana" size=2>');
			b_write($MESSAGES['FILE_SRC'][42].$globaltime);
			b_write('</font></body>');
			b_write('</html>');
			exit;
		} else {
			unlink($SEARCHDATADIR.'/words.tmp');
			$globaltime = date("i:s", (time() - $session->vars['globaltime']));
			$session->destroy();

			b_write('<html>');
			b_write('<head><title></title></head>');
			b_write('<body>');
			b_write('<br /><font face="Arial, Helvetica, Verdana" size=3><b>');
			b_write($MESSAGES['FILE_SRC'][43].'</b></font><br />');
			b_write('<font face="Arial, Helvetica, Verdana" size=2>');
			b_write($MESSAGES['FILE_SRC'][42].$globaltime);
			b_write('</font></body>');
			b_write('</html>');
			exit;
		}
	}
}

// --------------------------------------
// M A I N
// --------------------------------------

// add index fields:
$indexfields =  explode(":", $session->vars['fieldstoindex']);
for($i=0; $i<count($indexfields); $i++) {
	if($indexfields[$i] != '') {
		addIndexField($indexfields[$i]);
	}
}

// optimize stop word db
$stopdb = $session->vars['stopwords'];

if($session->vars['sortstopdb'] == $MESSAGES['ON'] AND $session->vars['optimized'] === false) {
	optimize_stopdb($stopdb);
}

$STOP = file($stopdb);

$STOP_MAX = 0;
$STOP_COUNT = count($STOP);
for($i = 0; $i < $STOP_COUNT; $i++) {
	if(strlen($STOP[$i]) > $STOP_MAX) {
		$STOP_MAX = strlen($STOP[$i]);
	}
}
unset($STOP_COUNT);

// decide, which task has to be performed
if(isset($session->vars['task'])) {
	$task = $session->vars['task'];
} else {
	$task = '';
}

switch($task) {
	case 'MERGER1':
		merger1();
		soft_exit();
	case 'MERGER2':
		merger2();
		soft_exit();
	case 'INDEX':
		doindex();
		soft_exit();
	case 'STARTINDEX':
		index();
		soft_exit();
	default:
		index();
		soft_exit();
}

?>