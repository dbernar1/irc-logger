<?php

function list_days() {
	global $db;
	return array_reverse($db->get_col( "SELECT d FROM `daylog`" ));
}

function list_users($limit=25, $offset=0) {
	global $db;
	$limit = (int)$limit;
	$offset = (int)$offset;
	return $db->get_col( "SELECT u FROM `userlog` LIMIT $limit OFFSET $offset ASC" );
}

function search_log($text) {
	global $db;
	$text = $db->db->escapeString($text);
	$sort = $db->escape( strtoupper( get_sort_query_parameter() ) );

	return sort_results($db->get_results( "SELECT * FROM chanlog WHERE LIKE('$text',m) ORDER BY t $sort"), $sort);
}

function sort_results($results, $method="ksort") {
	switch ( strtolower( $method ) ) {
		case 'desc' :
		case 'krsort' :
			$method = 'custom_desc_sort';
			break;
		default :
			$method = 'custom_asc_sort';
	}

	uasort( $results, $method );
	return $results;
}

function custom_asc_sort( $a, $b ) {
	$a_time = strtotime( $a->t );
	$b_time = strtotime( $b->t );

	if ( $a_time < $b_time ) {
		return -1;
	} elseif ( $a_time > $b_time ) {
		return +1;
	} elseif ( $a->i < $b->i ) {
		return -1;
	} else {
		return +1;
	}
}

function custom_desc_sort( $a, $b ) {
	return custom_asc_sort( $b, $a );
}

function get_sort_query_parameter( $default = 'desc' ) {
	$sort = isset( $_GET['sort'] ) ? strtolower( $_GET['sort'] ) : false;
	switch ( $sort ) {
	case 'desc' :
	case 'asc'  :
		return $sort;
		break;
	}

	return $default;
}

function show_day_log($day) {
	global $db;
	$day = $db->escape(
		date("D M d % Y", strtotime($day))
	);

	$sort = $db->escape( strtoupper( get_sort_query_parameter() ) );

	foreach ( sort_results( $db->get_results( "SELECT * FROM chanlog WHERE LIKE('$day%', t) ORDER BY t $sort" ), $sort ) as $row ) {
		display_log_row($row);
	}
}

function display_log_row($row, $rx=false) {
	$id = (int) $row->i;
	$channel_arg = rawurlencode( $_GET['channel'] );
	$day = date('Y-m-d', strtotime($row->t));
	$t = htmlspecialchars(preg_replace('/^([a-zA-Z]+| \+[0-9]+)/', '', $row->t));
	$user = htmlspecialchars( $row->u );
	$msg = clickable_link(htmlspecialchars(preg_replace('/('.chr(3).'+([0-9,]+)?)/', '', $row->m)));
	$sort = get_sort_query_parameter( '' );
	if ( $sort ) {
		$sort = '&sort=' . rawurlencode( $sort );
	}
	echo "
		<a name='m{$id}'><ul class='entry'>
			<li class='ts'><a href='chanlog.php?channel={$channel_arg}&day={$day}{$sort}#m{$id}'>{$t}</a></li>
			<li class='nick'><a href='chanlog.php?channel={$channel_arg}&day={$day}{$sort}#m{$id}'>{$user}</a></li>
			<li class='msg'>{$msg}</li>
		</ul>
	";
}

function clickable_link($text) {
	#stolen from http://www.wallpaperama.com/forums/how-to-make-clickable-text-url-links-from-text-links-change-to-clicking-t641.html
	$text = preg_replace('#(script|about|applet|activex|chrome):#is', "\\1:", $text);
	$ret = ' ' . $text;
	$ret = preg_replace("#(^|[\n ])([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*)#is", "\\1<a href=\"\\2\" target=\"_blank\">\\2</a>", $ret);
	$ret = preg_replace("#(^|[\n ])([^\#](www|ftp\.)?[\w\#$%&~/.\-;:=,?@\[\]+]*(\.com|\.net|\.org|\.edu))#is", "\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>", $ret);
	$ret = preg_replace("#(^|[\n ])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i", "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>", $ret);
	$ret = substr($ret, 1);
	return $ret;
}
