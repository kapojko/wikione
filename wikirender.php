<?php
	# Èç Wabasabi
	// WabiSabi - a fast experimental wiki engine
	// Copyright (C) 2009-2011 Felix PleÈ™oianu <felixp7@yahoo.com>
	// If you are asking what license this software is released under,
	// you are asking the wrong question.
	define('URL', '[a-z\\+]+://[\\w\\.-]+(:\\d+)?/[\\w$-.+!*\'(),\\?#%&;=~:\\/]*');
	$wiki_patterns = array(
		'/^\\{\\{\\{(.*?)\\}\\}\\}/mse' => 'wiki_preserve(\'<pre>$1</pre>\');',
		'/\s*==+\s*$/m' => '',
		'/^======(.*)/m' => '<h6>$1</h6>',
		'/^=====(.*)/m' => '<h5>$1</h5>',
		'/^====(.*)/m' => '<h4>$1</h4>',
		'/^===(.*)/m' => '<h3>$1</h3>',
		'/^==(.*)/m' => '<h2>$1</h2>',
		'/^\s*$/m' => '<p>',
		'/^----+/m' => "<hr>\n",
		'/^:(.*)/m' => '<blockquote>$1</blockquote>',
		'/^\\*+(.*)/m' => '<ul><li>$1</li></ul>',
		'/^#+(.*)/m' => '<ol><li>$1</li></ol>',
		'/^;([^:]+):(.*)/m' => '<dl><dt>$1</dt><dd>$2</dd></dl>',
		'!(</ul>\s<ul>)|(</ol>\s<ol>)|(</dl>\s<dl>)!m' => "\n",
		'/^\\{\\|(.*?)\\|\\}/mse'
			=> '"<table><tr>".wiki_render_table("$1")."</tr></table>";',
	
		'|\\{\\{(' . URL . ')(.*?)\\}\\}|e'
			=> 'wiki_preserve(\'<img src="$1" alt="$3">\');',
		'|\\[(' . URL . ')(.+?)\\]|e'
			=> 'wiki_preserve(\'<a href="$1">$3</a>\');',
		'|(' . URL . ')|e' => 'wiki_preserve(\'<a href="$1">$1</a>\');',
		#'/' . WIKIWORD . '/' => '<a href="?$1">$1</a>',
	
		'/\\{\\{\\{(.*?)\\}\\}\\}/e' => 'wiki_preserve(\'<code>$1</code>\');',
		'/\\*\\*(.*?)\\*\\*/' => '<b>$1</b>',
		'|//(.*?)//|' => '<i>$1</i>',
		'/\\\\\\\\/' => "<br>\n",
		'/\\^\\^(.*?)\\^\\^/' => '<sup>$1</sup>',
		'/,,(.*?),,/' => '<sub>$1</sub>');

	$wiki_table_patterns = array(
		'/^\s*\\|-/m' => '</tr><tr>',
		'/^\s*\\|\\+(.*)/m' => '<th>$1</th>',
		'/^\s*\\|(.*)/m' => '<td>$1</td>');
	
	$preserved_strings = array();

	function wiki_preserve($text) {
		global $preserved_strings;
		$gensym = '_' . count($preserved_strings);
		$preserved_strings[$gensym] = $text;
		return "\$$gensym";
	}
	
	function wiki_render($text) {
		global $wiki_patterns,$preserved_strings;
		$text=preg_replace(
			array_keys($wiki_patterns),
			array_values($wiki_patterns),
			htmlspecialchars($text, ENT_QUOTES, "UTF-8"));
		return preg_replace('/\\$(\\w+)/e',
			'isset($preserved_strings["$1"]) ? $preserved_strings["$1"] : "";',
			$text);
	}
	
	function wiki_render_table($text) {
		global $wiki_table_patterns;
		return preg_replace(
			array_keys($wiki_table_patterns),
			array_values($wiki_table_patterns),
			$text);
	}
	
?>