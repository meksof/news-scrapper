<?php
/*
Plugin Name: scraping news
Plugin URI: http://monprojet.tn
Description: News scraping. Capable of syncing from commercial guru news website 
Version: 1.1
Author: ews
Author URI: http://monprojet.tn
License: GPL2
*/
	

require_once('sync-news.php');


if( !function_exists('scraping_news_menu') ) :
	function scraping_news_menu(){

		add_menu_page('scraping news', 'Scraping News', 'manage_options', 'scraping-news', 'scraping_news_form');

	}
endif;
add_action('admin_menu', 'scraping_news_menu');

function scraping_news_form(){
	require_once('form.php');
}