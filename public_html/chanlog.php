<?php

require dirname(__FILE__) . '/header.php';

if ( isset( $_GET['search'] ) ) {
	echo "<h1>#".htmlentities($_GET['channel'])." search results for &quot;".htmlentities($_GET['search'])."&quot;</h1><hr />"; 
	$rx = $_GET['search'];
	foreach ( search_log('%'.$_GET['search'].'%') as $row ) {
	    display_log_row($row, $rx);
	}
} elseif ( isset( $_GET['day'] ) ) {
	echo "<h1>#".htmlentities($_GET['channel'])." logs for ".htmlentities($_GET['day'])."</h1><hr />";
	show_day_log($_GET['day']);
}

if ( isset( $_GET['search'] ) ) {
?>
	<script type="text/javascript">localSearchHighlight(<?php echo json_encode( $_GET['search'] ); ?>);</script>

<?php
}
require dirname(__FILE__) . '/footer.php';
