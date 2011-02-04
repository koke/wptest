<?php

// curl -s http://wordpress.org/extend/plugins/browse/popular/ | grep '<h3><a href' | sed -e 's/.*extend\/plugins\///;s/\/".*//'

function plugin_url($plugin_name)
{
	$readme = file_get_contents("https://plugins.svn.wordpress.org/$plugin_name/trunk/readme.txt");
	if (preg_match('/Stable tag: (.*)$/mi', $readme, $matches)) {
		$tag = trim($matches[1]);
	} else {
	    $tag = "trunk";
	}

	if ($tag == "trunk")
		return "https://plugins.svn.wordpress.org/$plugin_name/$tag";
	else
		return "https://plugins.svn.wordpress.org/$plugin_name/tags/$tag";
}

function plugins_popular()
{
	$plugins = array();
	$pop = file_get_contents('http://wordpress.org/extend/plugins/browse/popular/');
	$lines = explode("\n", $pop);
	foreach ($lines as $line) {
		if (preg_match('/<h3><a href/', $line)) {
			$plugin_name = preg_replace('/^.*extend\/plugins\/(.*)\/".*$/', '$1', $line);
			$plugins[$plugin_name] = plugin_url($plugin_name);
		}
	}
	return $plugins;
}

if (basename(__FILE__) == $argv[0])
    var_dump(plugins_popular());