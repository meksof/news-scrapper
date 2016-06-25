<?php 
set_time_limit ( 1200 );

define('BASE_URL_WEBSITE', 'http://www.commercialguru.com.sg');
//http://www.commercialguru.com.sg/property-management-news?page=1
define('NEWS_PAGE', '/property-management-news/');
define('LAST_SYNCED_FILENAME', dirname(__FILE__) . '/last_synced_item.ini');

if( !function_exists('file_get_html') ) :
	include_once('libs/simple_html_dom.php');
endif;



/**
 * get news url from website
 * news : [ 'url' => '',
 * 			'date' => '',
 *			'title' => '' ]
 *
 * @return array of news
 * @author ews
 **/
function get_news_urls()
{

	$html = file_get_html(BASE_URL_WEBSITE . NEWS_PAGE);
	$news = array();
	$i = 0;
	foreach($html->find('#homeleft .bottom10') as $element){
	    $title = $element->find('a.bluelink', 0);
	    $url = $title->href;
	    $title = trim ( strip_html_spaces( $title->plaintext) );
	    $date = trim( strip_html_spaces( $element->find('text', 3)->plaintext ) );


		// check if it is the last synced news
		if ( is_last_synced( $title ) ){
			// echo " >> this post '$title' was synced . END OF SCRAPE";
			break;
		}

	    $news[] = array(
	    	'title' => $title,
	    	'url' => $url,
	    	'date' => $date
	    );
	    // test
	    // if( $i == 6 ) break;
	    $i++;
	}


	return $news;
}


/**
 * get news data
 * [
 *			'url' => '',
 * 			'date' => '',
 *			'title' => '',
 *			'content' => '',
 *			'image_url' => ''
 * ]
 *
 * @return array of data
 * @author ews
 **/
function get_news_data($news = array())
{
	$news_data = array();
	

	// iterate tab from last item
	for( $i = ( count( $news ) - 1 ); $i >= 0; $i-- ){

		$k = 0;
		$html = file_get_html( BASE_URL_WEBSITE . $news[$i]['url'] );
		$content = $image_url = '';
		foreach( $html->find('#homeleft div.left10 p') as $text ) :

			if( $k == 0 ) $image_url = $text->prev_sibling()->prev_sibling()->src;
			$content .= $text->outertext;
			$k++;

			if ( $text->next_sibling()->class == 'clearboth top10' ) break;
		endforeach;

		$news_data[] = array(
			'url' => $news[$i]['url'],
 			'date' => $news[$i]['date'],
			'title' => trim($news[$i]['title']),
			'content' => $content,
			'image_url' => $image_url);
	
	}

	return $news_data;
}


/**
 * set featured img for post
 *
 * @return void
 * @author ews
 **/
function ews_set_featured_img($img_url, $post_id)
{
	$image = media_sideload_image($img_url, $post_id, '');
	if( !is_wp_error($image) ){
		$image = preg_replace("/.*(?<=src=[\"'])([^\"']*)(?=[\"']).*/", '$1', $image);	
		$image_id = ews_get_attachment_id_from_src( $image );
		set_post_thumbnail( $post_id, $image_id );
	}
}
/**
 * get attachment id from src
 *
 * @return image attachment ID
 * @author meksof
 **/

function ews_get_attachment_id_from_src($image_src)
{
	global $wpdb;
	$query = "SELECT ID FROM {$wpdb->posts} WHERE guid='$image_src'";
	$id = $wpdb->get_var($query);
	return $id;

}


/**
 * Store news from array into wordpress posts
 *
 * @return Nbre of stored items
 * @author ews
 **/
function store_news($news_data = array() ) 
{
	$stored = $i = 0;
	if( !empty($news_data) ){
		foreach ($news_data as $news_item) {

			// make a wp query by title
			$ID = ews_get_post_by_title( $news_item['title'] );
			if( empty( $ID ) ){
				$post = array(
					'ID'			=> 0,
					'post_title'	=> $news_item['title'],
					'post_status'	=> 'publish',
					'post_content'	=> $news_item['content']
				);

				
				$post_id = wp_insert_post($post); 

				if( ! is_wp_error($post_id) )
					ews_set_featured_img($news_item['image_url'], $post_id );

				// set post appearance options by default to :
				update_post_meta($post_id, 'header_type', 1);
				update_post_meta($post_id, 'sidebar_select', 'secondary-widget-area');
				update_post_meta($post_id, 'sidebar_option', 'none');

				$stored ++;
			}else{
				// the post exist
				// echo " >> The same post exist with id: ". $ID->ID;
				// skip
			}

			// update last synced item
			if( $i == count($news_data) - 1 )
				update_last_synced($news_item['title'], $news_item['date']);

			$i++;
		}
		
	}
	return $stored;
}


/**
 * strip html spaces
 *
 * @return string
 * @author ews
 **/
if( !function_exists('strip_html_spaces'))  :
	function strip_html_spaces($str)
	{
		$string = htmlentities($str, null, 'utf-8');
	    $string = str_replace("&nbsp;", "", $string);
	    return $string;
	}
endif;

/**
 * get any post by post title
 * return NULL if not
 *
 * @return $post or null
 * @author ews
 **/
function ews_get_post_by_title($title  =  '')
{
	global $wpdb;
	if($title == '') return null;
	
	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `wps8_posts` WHERE `post_title` LIKE '%s' AND `post_type` = 'post' AND `post_status` = 'publish'", $title ) );
}

/**
 * check whether it is the latest news item synced
 *
 * @return boolean
 * @author ews
 **/
function is_last_synced($item_title = '')
{
	$data = parse_ini_file(LAST_SYNCED_FILENAME);

	return ($item_title == $data['item_title']);
}

/**
 * update latest news item synced
 *
 * @return void
 * @author ews
 **/
function update_last_synced($item_title = '', $item_date = '')
{
	$current_date = date('Y-m-d H:i:s');
	$data = "; Store last news item here\n[item]\nitem_title = \"$item_title\"\nitem_date = \"$item_date\"\nsync_date = \"$current_date\"";
	file_put_contents(LAST_SYNCED_FILENAME, $data);
}

/**
 * Run news sync and return the number of successfully created posts
 *
 * @return array
 * @author ews
 */
if( !function_exists('ews_run_news_sync') ) :
	function ews_run_news_sync()
	{
		$news = get_news_urls();
		
		$news_data = get_news_data($news);
		
		$synced = store_news($news_data);
		
		if( $synced == 0 ){
			$data = parse_ini_file(LAST_SYNCED_FILENAME);
			$result['type'] = 'no-change';
			$result['last_item_title'] = $data['item_title'];
			$result['last_item_date'] = $data['item_date'];
			$result['sync_date'] = $data['sync_date'];
		}else{
			$result['synced'] = $synced;
			$result['type'] = 'success';
		}

		return $result;
	}
endif;

/**
 * Add cron job action for syncing news
 *
 */
add_action('ews_news_sync', 'ews_run_news_sync');
