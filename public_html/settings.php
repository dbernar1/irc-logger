<?php

define( 'LOG_DIR', dirname( dirname( __FILE__ ) ) . '/rez2/logs/sqlite/' );

date_default_timezone_set( 'UTC' );

foreach ( array( 'search', 'channel', 'day', 'sort' ) as $_var ) {
	if ( isset( $_GET[$_var] ) && strlen( $_GET[$_var] ) ) {
		$_GET[$_var] = (string) $_GET[$_var];
	} else {
		unset( $_GET[$_var] );
	}
}
unset( $_var );
