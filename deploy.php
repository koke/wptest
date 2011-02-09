<?php

require 'config.php';
require 'plugins.php';

chdir($ROOT);
if (is_file(".deploy"))
    die("Deploy already in progress");

system("touch .deploy");
ini_set('implicit_flush', 1);
header("Content-Type: text/plain");

// Download tarball and svn
if ( !is_dir( "$ROOT/$wpdir" ) ) {
	echo "Donwloading $wpdir.tar.gz...\n";
	system( "wget -qO $wpdir.zip $download_url" );
	system( "unzip -q $wpdir.zip" );
	system( "mv wordpress $wpdir" );
}

if ( !is_dir( "$ROOT/trunk" ) ) {
	echo "Donwloading from svn...\n";
	system( "svn co -q http://core.svn.wordpress.org/trunk" );
} else {
	echo "Updating svn...\n";
	system( "svn up -q trunk" );
}

$conf = "
<VirtualHost *:80>
	ServerAdmin jorge@automattic.com
	ServerName wp.$DOMAIN
	DocumentRoot /srv/apache/wptest
	LogFormat \"%t %v %u %h %>s %r\" common
	ErrorLog /var/log/apache2/wp.$DOMAIN-error.log
	CustomLog /var/log/apache2/wp.$DOMAIN-access.log combined
</VirtualHost>
";
// Create directories
foreach ( $SITES as $name => $info ) {
	list( $method, $auth, $subdir, $mu, $desc ) = $info;
	
	echo "Deploying $name...\n";
	if ( is_dir( $name ) ) {
		system( "rm -rf $name" );
	}
	
	mkdir( $name, 0770, true );
	if ( $method == "zip" )
		system( "rsync -a $wpdir/ $name/" );
	else
		system( "rsync -a trunk/ $name/" );
	system( "mkdir -p $name/wp-content/uploads" );
	system( "chmod a+rwX $name/wp-content/uploads" );
	
	// Create databases
	system( "mysqladmin -f drop $name > /dev/null" );
	system( "mysqladmin -f create $name" );
	system( "mysql -e \"GRANT ALL ON $name.* TO ${DBUSER}@localhost IDENTIFIED BY '$DBPASS'\"" );
	
	// Add apache virtual host
	if ( !$subdir ) {
		$conf .= "
<VirtualHost *:80>
	ServerAdmin jorge@automattic.com
	ServerName $name.$DOMAIN
	DocumentRoot $ROOT/$name
	LogFormat \"%t %v %u %h %>s %r\" common
	ErrorLog /var/log/apache2/wp.$DOMAIN-error.log
	CustomLog /var/log/apache2/wp.$DOMAIN-access.log combined";
	if ( $mu ) {
		$conf .= "
	ServerAlias *.$name.$DOMAIN";
	}
	if ( $auth ) {
		$conf .= "
<Location />
AuthType Basic
Require valid-user
AuthName \"$desc\"
AuthUserFile $ROOT/passwd
</Location>";
	}
		$conf .= "\n</VirtualHost>";
	}
	
	// Create wp-config.php
	if ( $subdir ) {
		$siteurl = "http://wp.$DOMAIN/$name";
	} else {
		$siteurl = "http://$name.$DOMAIN";
	}
	
	if ( $mu ) {
		$muconf = "define('WP_ALLOW_MULTISITE', true);";
	} else {
		$muconf = '';
	}
	
	$wpconf = "<?php
define('DB_NAME', '$name');
define('DB_USER', '$DBUSER');
define('DB_PASSWORD', '$DBPASS');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');
define('AUTH_KEY',         'si1ShaiHu1aiz6fois5rohle0eePiech4Koo1ezei4chu9baiTah7chaiceemuo1');
define('SECURE_AUTH_KEY',  'Haivahzai0aPhiVeiv8ou0yaethietheimieQu0ookieSh2ais6choamahqu8kei');
define('LOGGED_IN_KEY',    'aGoo6quuulae0oor8faepar1Ooyo7Oi5eethi3Ungo0eic1aipeiK6yeech9eiWe');
define('NONCE_KEY',        'jielie3ahph8ushai2riaquaing6ohsh8duv7hah0ha2jee0oom3OrabeeChegh6');
define('AUTH_SALT',        'aeyeeQu2xuGo4cae9kaeQuie0ep4wahvei6YieVoi4Uz0eu0eiv0eigh5yen3the');
define('SECURE_AUTH_SALT', 'Nohpiebaep1eu1aiwago6eiNaeyohLeeweegh3aelaequ9go7muchaisah4taiph');
define('LOGGED_IN_SALT',   'gah3naeza0eeXakoo5eiz4Ees9leiNoosha7thaeyeiBaidaePhifi4Pa7die2th');
define('NONCE_SALT',       'dei1qui5aeh2uQuaecooy5uDoM6aice4coo4eequexaiN0wiokienaechahghae8');
define('WP_SITEURL','$siteurl');
define('WP_HOME','$siteurl');
$muconf
\$table_prefix  = 'wp_';
define ('WPLANG', '');
define('WP_DEBUG', false);
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');
require_once(ABSPATH . 'wp-settings.php');";

	$wpf = fopen( "$name/wp-config.php", "w" );
	fwrite( $wpf, $wpconf );
	fclose( $wpf );
	
	// Run install
	$install = "<?php
	define( 'WP_INSTALLING', true );
	require_once( dirname( __FILE__ ) . '/wp-load.php' );
	require_once( dirname( __FILE__ ) . '/wp-admin/includes/upgrade.php' );
	require_once( dirname( __FILE__ ) . '/wp-includes/wp-db.php');
	wp_install('$desc', 'q', 'jorge+wptest@automattic.com', 0, '', 'q');
	update_option('enable_xmlrpc', true);
	update_option('enable_app', true);";
	$install_path = "$name/installer.php";
	$installf = fopen( $install_path, "w" );
	fwrite( $installf, $install );
	fclose( $installf );
	system( "php $install_path" );
	unlink( $install_path );
	
	if ( $mu ) {
		$install = "<?php
		define( 'WP_NETWORK_ADMIN_PAGE', true );
		require_once( dirname( __FILE__ ) . '/wp-load.php' );
		require_once( dirname( __FILE__ ) . '/wp-includes/ms-functions.php' );
		require_once( dirname( __FILE__ ) . '/wp-includes/ms-blogs.php' );
		require_once( dirname( __FILE__ ) . '/wp-admin/includes/upgrade.php' );
		foreach ( \$wpdb->tables( 'ms_global' ) as \$table => \$prefixed_table )
			\$wpdb->\$table = \$prefixed_table;
		function is_subdomain_install() { return true; }
		install_network();
		populate_network( 1, '$name.$DOMAIN', 'jorge+wptest@automattic.com', '$desc', '/', true );
		";
		$install_path = "$name/installer.php";
		$installf = fopen( $install_path, "w" );
		fwrite( $installf, $install );
		fclose( $installf );
		system( "php $install_path" );
		unlink( $install_path );

		$newmuconf = "define( 'MULTISITE', true );
define( 'SUBDOMAIN_INSTALL', true );
\$base = '/';
define( 'DOMAIN_CURRENT_SITE', '$name.$DOMAIN' );
define( 'PATH_CURRENT_SITE', '/' );
define( 'SITE_ID_CURRENT_SITE', 1 );
define( 'BLOG_ID_CURRENT_SITE', 1 );";
		$wpconf = file_get_contents( "$name/wp-config.php" );
		$wpconf = str_replace( $muconf, $newmuconf, $wpconf );
		file_put_contents( "$name/wp-config.php", $wpconf );

		echo "Creating secondary blogs...\n";
		$blogs = array('a', 'b', 'c');
		foreach ($blogs as $blog) {
			$install = "<?php
			define('WP_INSTALLING', true);
			require_once( dirname( __FILE__ ) . '/wp-load.php' );
			require_once( dirname( __FILE__ ) . '/wp-includes/ms-functions.php' );
			require_once( dirname( __FILE__ ) . '/wp-includes/ms-blogs.php' );
			echo \"Creating $blog.$name.$DOMAIN...\\n\";
			wpmu_create_blog( '$blog.$name.$DOMAIN', '/', strtoupper('$blog').' $desc', 1 , array( 'public' => 1 ), 1 );
			";
			$install_path = "$name/installer.php";
			$installf = fopen( $install_path, "w" );
			fwrite( $installf, $install );
			fclose( $installf );
			system( "php $install_path" );
			unlink( $install_path );
		}
	}
	
	// Create htaccess
	$htaccess = "RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]

# uploaded files
RewriteRule ^files/(.+) wp-includes/ms-files.php?file=$1 [L]

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule . index.php [L]";
	$htf = fopen( "$name/.htaccess", "w" );
	fwrite( $htf, $htaccess );
	fclose( $htf );
}

// Install plugins
if ( !empty( $INSTALL_PLUGINS ) ) {
	echo "Installing plugins...\n";
	system( "rm -rf $name/wp-content/plugins/*" );
	$plugins = plugins_popular();
	foreach ( $EXTRA_PLUGINS as $plugin ) {
		$url = plugin_url( $plugin );
		if ( $url )
			$plugins[$plugin] = $url;
	}
	foreach ( $plugins as $plugin => $url ) {
	    if ( is_dir( "plugins/$plugin" ) ) {
	        // Tags shouldn't be updated so skip checking
	        if (FALSE !== strpos($url, 'trunk')) {
    	        echo "Updating $plugin...\n";
    	        system( "svn -q up plugins/$plugin" );	            
	        }
	    } else {
	        echo "Downloading $plugin...\n";
	        system( "svn -q co $url plugins/$plugin" );	        
	    }
	}
	foreach ( $INSTALL_PLUGINS as $name ) {
		foreach ( $plugins as $plugin => $url ) {
			$file = str_replace('-','_',$plugin);
			echo "Installing $plugin in $name...\n";
			system("rsync -a plugins/$plugin/ $name/wp-content/plugins/$plugin/");
			$install = "<?php
			require_once( dirname( __FILE__ ) . '/wp-load.php' );
			require_once( dirname( __FILE__ ) . '/wp-admin/includes/plugin.php' );
			\$plugins = get_plugins();
			foreach (\$plugins as \$key => \$info) {
			    if (0 === strpos(\$key, '$plugin')) {
        			echo \"Activating \$key in $name...\\n\";
        			activate_plugin(\$key);			        
			    }
			}
			";
			$install_path = "$name/installer.php";
			$installf = fopen( $install_path, "w" );
			fwrite( $installf, $install );
			fclose( $installf );
			system( "php $install_path" );
			unlink( $install_path );
		}
	}
}

// Add HTTP auth
$passinfo = serialize( array( "q", "q" ) );
$plain = fopen( "auth.txt", "w" );
fwrite( $plain, $passinfo );
fclose( $plain );
system( "htpasswd -bc passwd q q" );

$apache = fopen( "apache.conf", "w" );
fwrite( $apache, $conf );
fclose( $apache );

echo "\nAll hosts deployed.

Make sure apache includes the hosts configuration:

Include $ROOT/apache.conf

You should probably restart apache now\n\n";

system("rm .deploy");