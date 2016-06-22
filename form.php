<?php 
/**
 * output news sync result in a nice way
 *
 * @return void
 * @author ews
 **/
function output_sync_result( $result = array() )
{
	if( empty($result) ) return;

	if( $result['type'] == 'success' ){
			
		print '<div class="updated notice notice-success is-dismissible">
				<p>
					There was <b>'.$result['synced'].'</b> news synced
				</p>
				<button type="button" class="notice-dismiss">
				<span class="screen-reader-text">Dismiss this notice.</span>
				</button>
			</div><br/>';
			
	}elseif( $result['type'] == 'no-change' ){
		print '<div class="update-nag">
				<p>
					There is no change since last sync <b>['.$result['sync_date'].']</b>
				</p>
				<ul>
					<li>Title: <b>'.$result['last_item_title'].'</b></li>
					<li>Date: <b>'.$result['last_item_date'].'</b></li>
				</ul>
				<button type="button" class="notice-dismiss">
				<span class="screen-reader-text">Dismiss this notice.</span>
				</button>
			</div><br/>';
	}
}
 ?>
<form name="Form" method="POST" action="" >
	<?php 

	if( isset($_POST['sync_news']) ){
		// syncing news
		require_once('sync-news.php');

		output_sync_result($result);

	}

	?>
	<br>
	<br>
	<br>
	<br>
	<br>
	<input class="button button-primary button-large" type="submit" name="sync_news" value="Sync News">
</form>
