<html>
<head>
	<style>
		body { padding: 30px; }
		a { color: steelblue; font-size: 1.5em; text-decoration: none; }
		a:hover { text-decoration: underline; }
		ul { list-style: none; margin: none; padding: none; }
		li { background: #ccc; border: 1px solid #aaa; margin: 5px; padding: 3px; width: 360px; text-align: center;}
		li:hover { background: #ddd; }
	</style>
</head>
<body>
<div style="margin-left: auto; margin-right: auto; width: 460px;">
<h1>Please Choose A Logged Channel</h1>
<ul>
<?php

require dirname( __FILE__ ) . '/settings.php';

foreach ( glob( LOG_DIR . '*.db' ) as $db ) {
	if ( strtolower(substr(basename($db), 0, 4)) == "test" )
		continue;
	if ( (time() - filemtime($db)) > 86400 )
		continue;
	$file = basename( $db, '.sqlite.db' );
	$name = ucwords(str_replace('-', ' ', $file));
	$name = str_ireplace('press', 'Press', $name);
	$name = str_ireplace('bbpress', 'BBPress', $name);
	echo "<li><a href='chanlog.php?channel=" . rawurlencode($file) . "'>" . htmlspecialchars( $name ) . "</a></li>";
}

?>
</ul>
</div>
</body>
</html>
