#!/usr/bin/php
<?php

date_default_timezone_set( 'UTC' );

// Settings

define( 'BOT_NICKNAME',      'testloggerbot' );
define( 'BOT_SERVER',        'chat.freenode.net' );
define( 'BOT_PORT',          6667 );
define( 'CHANSERV_PASSWORD', false );
$bot_channels = array( '#channel1', '#channel2' );

// More, obscure, settings
define( 'BOT_UID', false );
define( 'BOT_GID', false );

/** Unix daemonizing stuff not specific to the bot (boilerplate) **/
if ( true && function_exists('pcntl_fork') ) {
	// If we can the preferred method is to fork, and kill the parent process
	// leaving the controlling terminal free to move on, and exit if need be
	$pid = pcntl_fork();
	if ($pid == -1) {
		// if I should have been able to fork... but couldnt for some reason
		// I'll just complain until someone fixes me
		die();
	} else if ($pid) {
		// Even if I did manage to fork... I'm gonna commit suicide anyway...
		// It's depressing being a daemon... I'll go talk to Marvin for a while...
		die();
	}
}

// Drop privileges if we can
if ( function_exists( 'posix_setgid' ) ) {
	if ( BOT_GID ) {
		if ( !posix_setgid( BOT_GID ) )
			die( "Could not setgid (" . BOT_GID . ")\n" );
	}
	if ( BOT_UID ) {
		if ( !posix_setuid( BOT_UID ) )
			die( "Could not setuid (" . BOT_UID . ")\n" );
	}
}

// Lock and run ( this needs to be done AFTER the fork, now, see: http://bugs.php.net/47227 )
$pidfile	= dirname( __FILE__ ) . '/.' . basename( __FILE__ ) . '.pid';

// If these things arent kept in the global scope then they MAY be released on scope change
$GLOBALS['pidfp'] = fopen( $pidfile, 'a+' );
$GLOBABS['lock'] = flock( $pidfp, LOCK_EX | LOCK_NB, $wouldblock );
$pidfp = &$GLOBALS['pidfp'];
$lock = &$GLOBABS['lock'];
if ( !$lock || $wouldblock )
	die("Could not lock PIDFILE\n" );
ftruncate( $pidfp, 0 );
fwrite( $pidfp, getmypid() );

// If we don't do this then	 we can't log out after launching the bot (ssh terminal hangs because of open FDs)
fclose( STDIN );
fclose( STDOUT );
fclose( STDERR );

/** Begin the actual bot source code here **/

class mybot {	

	// SQLite3 logging
	function chan_log_sql( $channel, $user, $type, $message ) {
		global $irc;
		
		$microtime = $irc->_microint();
		$timestamp = gmdate("D M d H:i:s O Y", $microtime);
		$daystamp = gmdate( "Y-m-d", $microtime);
		$filename = sprintf( "%s/logs/sqlite/%s.sqlite.db", dirname( __FILE__ ), substr( $channel, 1 ) );
		if ( file_exists( $filename ) ) {
			$sql = array();
		} else {
			$sql = array(
				'CREATE TABLE IF NOT EXISTS chanlog ( i INTEGER PRIMARY KEY, t VARCHAR(32), u VARCHAR(32), m TEXT )',
				'CREATE TABLE IF NOT EXISTS daylog ( d VARCHAR(32) PRIMARY KEY, n INTEGER )',
				'CREATE TABLE IF NOT EXISTS userlog ( u VARCHAR(32) PRIMARY KEY, n INTEGER )',
				'CREATE TABLE IF NOT EXISTS userdaylog ( u VARCHAR(32), d VARCHAR(32), n INTEGER )',
				'CREATE UNIQUE INDEX IF NOT EXISTS udl ON userdaylog ( u, d )',
				'CREATE UNIQUE INDEX IF NOT EXISTS d ON daylog ( d )',
				'CREATE UNIQUE INDEX IF NOT EXISTS u ON userlog ( u )',
			);
		}
		try {
			$db = new PDO( 'sqlite:' . $filename );
		} catch( PDOException $Exception ) {
			print_r( $Exception );
			unset( $db );
			return;
		}
		$user	 = $db->quote( $user );
		$message = $db->quote( $message );
		$sql[]	 = "INSERT INTO chanlog (t,u,m) VALUES('$timestamp',$user,$message)";
		$sql[]	 = "INSERT OR IGNORE INTO userlog (u,n) VAlUES($user,0)";
		$sql[]	 = "UPDATE userlog SET n=n+1 WHERE u=$user";
		$sql[]	 = "INSERT OR IGNORE INTO daylog (d,n) VAlUES('$daystamp',0)";
		$sql[]	 = "UPDATE daylog SET n=n+1 WHERE d='$daystamp'";
		$sql[]	 = "INSERT OR IGNORE INTO userdaylog (u,d,n) VAlUES($user,'$daystamp',0)";
		$sql[]	 = "UPDATE userdaylog SET n=n+1 WHERE u=$user AND d='$daystamp'";
		$sql = implode( ";\r\n", $sql );
		try {
			$db->exec( $sql );
		} catch( PDOException $Exception ) {
			print_r( $Exception );
			unset( $db );
			return;
		}
		unset( $db );
		return;
	}

	// Handler functions for logging IRC events
	function chan_msg_log( &$irc, &$data ) {   $this->chan_log_sql( $data->channel, $data->nick, 'MESSAGE', $data->message ); }
	function chan_act_log( &$irc, &$data ) {   $this->chan_log_sql( $data->channel, $data->nick, 'ACTION',	"::" . substr( $data->message, 8 ) ); }
	function chan_join_log( &$irc, &$data ) {  $this->chan_log_sql( $data->channel, $data->nick, 'JOIN',	":: User Joined Channel ::" ); }
	function chan_quit_log( &$irc, &$data ) {  $this->chan_log_sql( $data->channel, $data->nick, 'QUIT',	":: User Quit :: " ); }
	function chan_part_log( &$irc, &$data ) {  $this->chan_log_sql( $data->channel, $data->nick, 'PART',	":: User Left Channel :::" ); }
	
	function identify(&$irc, &$data = null) {
		global $idid;
		if ( defined( 'CHANSERV_PASSWORD' ) && CHANSERV_PASSWORD )
			$irc->message( SMARTIRC_TYPE_QUERY, 'nickserv', 'identify ' . CHANSERV_PASSWORD );
		$irc->unregisterTimeid($idid);
	}
}

require dirname( __FILE__ ) . '/Net/SmartIRC.php';

// Normally this would not be necessary, but we want him to join all chans
// And this is kind of a strange use case...
class Net_SmartIRC_Logger extends Net_SmartIRC {

	// Handle rpl_list (send event to bot)
	function event_rpl_list( $data ) {
		global $bot, $irc;
		$bot->process_list( $irc, $data );
	}

}

// Create our objects
$irc = &new Net_SmartIRC_logger();
$bot = new mybot();

$irc->setDebug( false );
// Keep track of channels that we know about
$irc->setChannelSyncing( true );
// Keep track of users that we know about
$irc->setUserSyncing( true );

// IRC Connection Info
$irc->connect( BOT_SERVER, BOT_PORT );
$irc->login( BOT_NICKNAME, 'Net_SmartIRC Client '.SMARTIRC_VERSION.' ('.basename(__FILE__).')', 0, BOT_NICKNAME );

// Attach methods from the bot to events from IRC
$irc->registerActionhandler( SMARTIRC_TYPE_JOIN,		'.*', $bot, 'chan_join_log'	 );
$irc->registerActionhandler( SMARTIRC_TYPE_CHANNEL,		'.*', $bot, 'chan_msg_log'	 );
$irc->registerActionhandler( SMARTIRC_TYPE_ACTION,		'.*', $bot, 'chan_act_log'	 );
$irc->registerActionhandler( SMARTIRC_TYPE_NICKCHANGE,	'.*', $bot, 'chan_nick_log'	 );
$irc->registerActionhandler( SMARTIRC_TYPE_KICK,		'.*', $bot, 'chan_part_log'	 );
$irc->registerActionhandler( SMARTIRC_TYPE_PART,		'.*', $bot, 'chan_part_log'	 );
$irc->registerActionhandler( SMARTIRC_TYPE_QUIT,		'.*', $bot, 'chan_quit_log'	 );

// Setup
$idid = $irc->registerTimehandler(10, $bot, 'identify');
$irc->join( $bot_channels );

// Idle...
$irc->listen();
$irc->disconnect();

