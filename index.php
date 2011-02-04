<?php 
if (!isset($_SERVER['PHP_AUTH_PW']) || ($_SERVER['PHP_AUTH_PW'] != "wpiosrocks")) {
    header('WWW-Authenticate: Basic realm="Test Blogs Admin"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Sorry, not allowed here';
    exit;
}
require 'config.php';
$creds = unserialize(file_get_contents("auth.txt"));
if ($_POST && $_POST["authpw"]) {
  $newpw = preg_replace('/[^a-zA-Z0-9]/', '', $_POST["authpw"]);
  if ($newpw != "") {
    $creds[1] = $newpw;
    file_put_contents("auth.txt", serialize($creds));
    system( "htpasswd -bc $ROOT/passwd q $newpw" );
  }
}
$authpw = $creds[1];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>WordPress Test blogs for mobile apps</title>
        <style type="text/css">
        body { font-family: "Myriad Pro", Helvetica, sans-serif; }
        td,th { text-align: left; padding: 0 1em; border-top: 1px solid #333; border-bottom: 1px solid #333; }
        table { border-collapse: collapse }
        #access { background: #eee; font-style: italic; margin: 1em 0; padding: 1em; width: auto }
        #wrap, #warning { width: 960px; margin: 0 auto; }
        #warning { font-size: 1.5em; color: #c00;}
        #warning a {color:inherit;}
        #bloglist { width: 640px; float: left; margin: 0 }
        #auth, #plugins { width: 300px; float: left; margin: 0 0 0 20px }
        ul {padding-left: 1em; color: #56e; }
        #plugins li {list-style-image:url("wordpress.gif"); }
        #plugins li.automattic {list-style-image:url("automattic.gif");}
        .dash { font-size: 0.75em; padding-left: 0.4em}
        </style>
    </head>
    <body>
    <?php if (is_file(".deploy")): ?>
        <div id="warning"><blink>Deploy in progress</blink>(<a href="">Reload</a>)</div>
    <?php endif; ?>
    <div id='wrap'>
      <div id="bloglist">
      <h1>Test blog collection</h1>
        <table>
        <thead>
          <tr>
            <th>URL</th>
            <th>Version</th>
            <th>Auth</th>
            <th>MS</th>
            <th>Description</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($SITES as $name => $info): ?>
          <tr>
            <?php
            $url = ($info[2]) ? "wp.$DOMAIN/$name" : "$name.$DOMAIN";
            $version = ($info[0] == "svn") ? "trunk" : $latest;
            ?>
            <td><a href="http://<?php echo $url ?>"><?php echo $url ?></a> <a class="dash" href="http://<?php echo $url ?>/wp-admin/">(Dashboard)</a></td>
            <td><?php echo $version ?></td>
            <td><?php echo ($info[1]) ? "Y" : "N" ?></td>
            <td><?php echo ($info[3]) ? "Y" : "N" ?></td>
            <td><?php echo $info[4] ?></td>
          </tr>
          <?php if ($info[3]): ?>
            <tr><td colspan="5">- <a href="http://a.<?php echo $url ?>">a.<?php echo $url ?></a> <a class="dash" href="http://a.<?php echo $url ?>/wp-admin/">(Dashboard)</a></td></tr>
            <tr><td colspan="5">- <a href="http://b.<?php echo $url ?>">b.<?php echo $url ?></a> <a class="dash" href="http://b.<?php echo $url ?>/wp-admin/">(Dashboard)</a></td></tr>
            <tr><td colspan="5">- <a href="http://c.<?php echo $url ?>">c.<?php echo $url ?></a> <a class="dash" href="http://c.<?php echo $url ?>/wp-admin/">(Dashboard)</a></td></tr>
            <?php endif; ?>
          <?php endforeach; ?>
        </tbody>
        </table>
        <div id="access">For all the blogs, the admin user is "q" and password is "q"</div>
        <div id="support">Some blog not working as expected? <a href="mailto:jorge@automattic.com">jorge@automattic.com</a> or ping me on IRC (koke)</div>
      </div>
      <div id='auth'>
        <h2>HTTP Auth</h2>
        <p><strong>Current user:</strong> q</p>
        <p><strong>Current password:</strong> <?php echo $authpw ?></p>
        <form action="" method="POST">
          <label for="authpw">New password:</label><br />
          <input type="text" value="<?php echo $authpw ?>" id="authpw" name="authpw" /><br />
          <input type="submit" value="Change Password" />
        </form>
      </div>
      <div id="plugins">
        <h2>Plugins installed</h2>
        <?php if ($INSTALL_PLUGINS && !empty($INSTALL_PLUGINS)): ?>
        <p>Installed in <?php echo join(", ", $INSTALL_PLUGINS) ?></p>
        <ul>
            <?php
            $blog = $INSTALL_PLUGINS[0];
            $dir = opendir("$blog/wp-content/plugins");
            foreach (scandir("$blog/wp-content/plugins") as $file) {
                if (($file[0] == '.') || (FALSE !== strpos($file, '.php')))
                    continue;
                if (in_array($file, $AUTOMATTIC_PLUGINS)) {
                    $class = "automattic";                    
                } else {
                    $class = "";
                }
                echo "<li class='$class'><a href='http://wordpress.org/extend/plugins/$file/'>$file</a></li>\n";
            }
            ?>
        </ul>
        <?php else: ?>
        <p>None</p>
        <?php endif; ?>
      </div>
     </div>
    </body>
</html>

