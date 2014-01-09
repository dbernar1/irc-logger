<?php 

	require dirname( __FILE__ ) . '/settings.php';		  
	require dirname( __FILE__ ) . '/sqlitedb.php';

	global $db;
	$db = false;

	if ( isset( $_GET['channel'] ) && !preg_match( '/[^a-zA-z0-9_.-]/', $_GET['channel'] ) ) {
		$channel = $_GET['channel'];
		$dbfile = preg_replace('/\.\.+/', '.', LOG_DIR . $channel . '.sqlite.db');
		$dbfile = str_replace('/./', '/', $dbfile);
		if ( file_exists($dbfile) ) {
			$db = new sqlite_wpdb($dbfile, 3);
		} else {
			$channel = '';
		}
	} else {
		$channel = '';
	}

	require dirname(__FILE__) . '/functions.php';

?><html>
	<head>
		<style>
			body { font-family: 'Lucida Grande', Arial, sans-serif; font-size: 80%; }
			body div div { -moz-border-radius: 10px; -webkit-border-radius: 10px;
			padding: 10px !important; }
			h1 { text-align: center; font-family: Georgia, 'Times New Roman',
			serif; color: #21759B; }
			h1 span.highlight { background: white; padding: 0; }
			hr { color: #ddd; }
			span.highlight { background: yellow; padding-left: 3px; padding-right:
			3px; padding-top: 1px; padding-bottom: 1px;}
			ul.entry { list-style: none; margin: 0; }
			ul.entry li { float: left; margin-right: 3px; padding: 4px 3px; }
			ul:hover { background: #fff5af; }
			ul.entry li.msg:hover {	 }
			ul.entry li.ts { font-size: 70%; float: right; margin-right: 40px; }
			ul.entry li.ts a { text-decoration: none; color: #808080; }
			ul.entry li.nick a { color: #f90; text-decoration: none; font-weight: bold; float: left; }
			ul.entry li.msg { float: none; }
		</style>
		<script type="text/javascript" src="searchhi_slim.js"></script>
	</head>
<body>
<div>

	<div style="float: left; padding: 2px; border: 1px solid #aaa; background: #ddd; margin: 2px; height: 22px;">
		<?php if ( $db ) { ?>
			<strong><u>#<?php echo htmlentities($channel); ?></u></strong> 
		<?php } else { ?>
			please select a channel
		<?php } ?>
	</div>

	<?php if ( $db ) { ?>
	<div style="float: left; padding: 2px; margin: 2px; height: 22px;"> actions: </div>
	<?php } ?>

	<div style="float: left; padding: 2px; border: 1px solid #aaa; background: #ddd; margin: 2px; height: 22px;">
		<form method="GET">
		<select name="channel">
		<?php
		foreach ( glob( LOG_DIR . '*.db' ) as $_db ) {
			if ( time() - filemtime($_db) > ( 86400 * 7 ) ) 
				continue;
			$file = basename( $_db, '.sqlite.db' );
			echo "<option ";
			if ( $channel == $file )
				echo " selected ";
			echo " value='" . rawurlencode($file) . "'>".htmlentities($file)."</option>\r\n";
		}
		?>
		</select>
		<?php if ( $db ) { ?>
			<input type="submit" value="switch channels" />
		<?php } else { ?>
			<input type="submit" value="select channel" />
		<?php } ?>
		</form>
	</div>

	<?php if ( $db ) { ?>

	<div style="float: left; padding: 2px; margin: 2px; height: 22px;"> or </div>

		<div style="float: left; padding: 2px; border: 1px solid #aaa; background: #ddd; margin: 2px; height: 22px;">
			<form method="GET"/>
			<input type="hidden" name="channel" value="<?php echo htmlentities($channel); ?>" />
			<select name="day" id="day" />
			<?php foreach ( list_days() as $day ) {
				$day = htmlspecialchars( $day, ENT_QUOTES );
				echo "<option ";
				if ( isset( $_GET['day'] ) && $_GET['day'] == $day )
					echo " selected ";
				echo "value='$day' >$day</option>\r\n";
			} ?>
			</select>
			<select name="sort">
			<?php $sort = get_sort_query_parameter( false ); ?>
			<option <?php if ( 'asc'  == $sort ) echo "SELECTED"; ?>>asc</option>
			<option <?php if ( 'desc' == $sort ) echo "SELECTED"; ?>>desc</option>
			</select>
			<input type="submit" value="view this day" />
			</form>
		</div>

		<div style="float: left; padding: 2px; margin: 2px; height: 22px;"> or </div>

		<div style="float: left; padding: 2px; border: 1px solid #aaa; background: #ddd; margin: 2px; height: 22px;">
			<form method="GET"/>
			<input type="hidden" name="channel" value="<?php echo htmlentities($channel); ?>" />
			<input type="text" name="search" value="<?php if ( isset( $_GET['search'] ) ) echo htmlentities( $_GET['search'] ); ?>"/>
			<select name="sort">
			<option <?php if ( 'asc'  == $sort ) echo "SELECTED"; ?>>asc</option>
			<option <?php if ( 'desc' == $sort ) echo "SELECTED"; ?>>desc</option>
			</select>
			<input type="submit" value="search all days" />
			</form>
		</div>

	<?php } ?>

	<br style="clear: both;" />
	<hr />
</div>
