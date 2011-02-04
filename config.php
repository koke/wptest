<?php
// Check latest WP version
$version_data = file_get_contents( "http://api.wordpress.org/core/version-check/1.5/" );
$version_data = explode( "\n", $version_data );
$latest = $version_data[3];
$download_url = $version_data[2];
$wpdir = "wordpress-$latest";

$ROOT = dirname(__FILE__);
$DOMAIN="koke.me";
$DBUSER="wptest";
$DBPASS="ieshoh2M";
$SITES = array(
	// "name" : method | auth | subdir | mu | desc
	"wpa" => array("zip", true, false, false, "WP $latest with HTTP Auth"),
	"wp3" => array("zip", false, false, false, "WP $latest"),
	"wpp" => array("zip", false, false, false, "WP $latest plugin-loaded"),
	"wpm" => array("zip", false, false, true, "WP $latest multisite"),
	"wpx" => array("svn", false, false, true, "WP trunk multisite"),
	"wpt" => array("svn", false, false, false, "WP trunk"),
	"sub" => array("zip", false, true, false, "WP $latest in a subdirectory"),
);
// Adds automattic plugins from http://automattic.com/wordpress-plugins/
// and some more
$AUTOMATTIC_PLUGINS = array(
    "akismet",
    "video", 
    "polldaddy", 
    "intensedebate", 
    "after-the-deadline", 
    "wickett-twitter-widget", 
    "geolocation",
    // "safecss", // Readme.txt tag is broken
);
$OTHER_PLUGINS = array(
    "wptouch", 
    "wordpress-video-plugin", 
);
$EXTRA_PLUGINS = array_merge($AUTOMATTIC_PLUGINS, $OTHER_PLUGINS);
$INSTALL_PLUGINS = array("wpp");