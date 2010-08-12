<?php
/*
Plugin Name: TweetUpdater
Description: WordPress plugin to update Twitter status when you create or publish a post.
Version: 3.x
Author: Patrick Fenner, Jordan Trask "@comm"
Author URI: http://def-proc.co.uk/Projects/TweetUpdater
Based on TwitterUpdater, version 1.0 by Victoria Chan: http://blog.victoriac.net/?p=87

*/

require_once('twitteroauth.php');
require_once('tweetupdater_manager.php');



	/*** TweetUpdater Actions ***/


/* Plugin action on publish_post (submitting a post with publish/update) */

function vc_tweet_publish($post_ID)  
{
	//load plugin preferences
	$options = get_option('tweet_updater_options'); 

	$this_post = get_post($post_ID);
	$title = $this_post->post_title; 
	$link = get_permalink($post_ID);
	$tweet = '';
	
	if( wp_is_post_revision($post_ID) ) { /* do nothing */ } 
	elseif ( wp_is_post_autosave($post_ID) ) { /* do nothing */ }
	else 
	{
		// if it's a newly published
		if($_POST['original_post_status'] == 'draft' && $_POST['publish'] == 'Publish' && $options['newpost_update'] == '1')
		{
			// Format the message
			$tweet = tweet_updater_format_tweet( $options['newpost_format'], $title, $link, $post_ID, $options['use_curl'], $options['url_method'] );
		} 
		// or if it's a published and updated
		else if ($_POST['original_post_status'] == 'publish' && $options['edited_update'] == '1') 
		{  
			// Fix for scheduled posts (thanks uniqueculture)
			if (strlen(trim($title)) == 0) { $this_post = get_post($post_ID); if ($this_post) { $title = $this_post->post_title; } }

			// Format the message
			$tweet = tweet_updater_format_tweet( $options['edited_format'], $title, $link, $post_ID, $options['use_curl'], $options['url_method'] );
		}     
		if($tweet != '')
		{
			// Send the message
	    		$result = tweet_updater_update_status($tweet);
		}
	}

   return $post_ID;
}



/* Plugin action on future_to_publish (when publishing time arrives for a scheduled post) */

function vc_tweet_future($post_ID)  
{
	//load plugin preferences
	$options = get_option('tweet_updater_options'); 

	$this_post = get_post($post_ID);
	$title = $this_post->post_title; 
	$link = get_permalink($post_ID);
	$tweet = '';

	if( $options['newpost_update'] == '1' )
	{
		// Format the message
		$tweet = tweet_updater_format_tweet( $options['newpost_format'], $title, $link, $post_ID, $options['use_curl'], $options['url_method'] );
	} 
    		if($tweet != '')
	{
		// Send the message
	   		$result = tweet_updater_update_status($tweet);
	}

	return $post_ID;
}


	/*** Additional Functions ***/


/* Single function to output a formatted tweet */

function tweet_updater_format_tweet( $format, $title, $link, $post_ID, $use_curl, $url_method )
{
	//initialise tweet
	$tweet = $format;
	
	//retieve the short url
	$tinyurl = get_tinyurl($use_curl,$url_method,$link,$post_ID);

	//do the placeholder string replace
	$tweet = str_replace ( '#title#', $title, $tweet);
	$tweet = str_replace ( '#url#', $tinyurl, $tweet);
	
	return $tweet;
}

//get_tinyurl()
/*
function get_tinyurl($shortmethod,$urlmethod,$link,$post_ID) {
      update_option('tu_last',$link);
      if ($urlmethod == '1') { 
              $url = "http://zz.gd/api-create.php?url=".$link;
              if ($shortmethod == '1') {
                      $turl = file_get_contents_curl($url);
              } else {
                      $turl = file_get_contents($url);            
              }
      }
      else if ($urlmethod == '2') {
              $url = "http://tinyurl.com/api-create.php?url=".$link;
              if ($shortmethod == '1') {
                      $turl = file_get_contents_curl($url);
              } else {      
                      $turl = file_get_contents($url);
              }
      }
      else if ($urlmethod == '3') {
              $url = $link;
              if ($shortmethod == '1') {
                      $turl = make_bitly_url($url,get_option('tu_bitly_username'),get_option('tu_bitly_appkey'),'1');
              } else {
                      $turl = make_bitly_url($url,get_option('tu_bitly_username'),get_option('tu_bitly_appkey'),'0');
              }
      }
      else if ($urlmethod == '4') { // Added support for la_petite_url shortener plugin
              if(function_exists('get_la_petite_url_permalink')) {
                      $turl = get_la_petite_url_permalink($post_ID); // return short link from la_petite_url
              } else {
                      // Don't want things to fail completely if la_petite_url gets deactivated, so reset to default and continue
                      update_option('url-method', '2');
                      $url = "http://tinyurl.com/api-create.php?url=".$link;
                      if ($shortmethod == '1') {
                             $turl = file_get_contents_curl($url);
                      } else {      
                             $turl = file_get_contents($url);
                      }
              }
      }
      else if ($urlmethod == '5') { // Added selection of full permalink
              $turl = get_permalink($post_ID); 
      }
      return($turl);
}
*/
//make_bitly_url()
/*
function make_bitly_url($url,$login,$appkey,$curl) {
      $bitly = 'http://api.bit.ly/v3/shorten?login='.$login.'&apiKey='.$appkey.'&format=json&history=1&longUrl='.urlencode($url);
      //get the url
      if ($curl == '1') { $response = file_get_contents_curl($bitly); }
      else { $response = file_get_contents($bitly); }
	
      $json = @json_decode($response,true);
      $shorturl = $json['data']['url'];
      return $shorturl;
}
*/
//file_get_contents_curl()
/*
function file_get_contents_curl($url) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_URL, $url);
      $data = curl_exec($ch);
      curl_close($ch);
      return $data;
}
*/
//


	/*** Twitter OAuth Functions ***/



/* Get Request Tokens */

function tweet_updater_register($tokens)
{
	// Build TwitterOAuth object with client credentials. 
	$connection = new TwitterOAuth($tokens['consumer_key'], $tokens['consumer_secret']);

	// Get the request tokens
	$request = $connection->getRequestToken();
	
	// Retrive tokens from request and store in array
	$tokens['request_key'] = $request["oauth_token"];
	$tokens['request_secret'] = $request["oauth_token_secret"];
	
	// Generate a request link and output it
	$tokens['request_link'] = $connection->getAuthorizeURL($request);
	
	return $tokens;
}

/* Get Access Tokens */

function tweet_updater_authorise($tokens)
{	
	// Initiate a new TwitterOAuth object. Provide the request token and request token secret
	$connection = new TwitterOAuth($tokens['consumer_key'], $tokens['consumer_secret'], $tokens['request_key'], $tokens['request_secret']);
	
	// Get the access tokens
	$request = $connection->getAccessToken();
	
	/*
	 ------------------------------------------------------------------------------------------
	 *** A failed request (e.g. not authorised by user in twitter) is not handled at all  	***
	 *** and outputs an error array from getAccessToken() 					***
	 ------------------------------------------------------------------------------------------
	 */
	 
	// Retrieve access token from request:
	$tokens['access_key'] = $request['oauth_token'];
	$tokens['access_secret'] = $request['oauth_token_secret'];

	return $tokens;
}

/* Validate Access */

function tweet_updater_verify($tokens)
{
	// Initiate TwitterOAuth using access tokens
	$connection = new TwitterOAuth($tokens['consumer_key'], $tokens['consumer_secret'], $tokens['access_key'], $tokens['access_secret']);
	
	$result = $connection->get('account/verify_credentials');
	
	$verify = array(
		'exit_code' => '',
		'user_name' => '',
			);
	
	if ($result->id) 
	{ 
		$verify['exit_code'] = "1"; 
		$verify['user_name'] = $result->screen_name;
	}
	else 		 
	{ 
		$verify['exit_code'] = "3"; 
	}

	return $verify;
}

/* Send a Tweet */

function tweet_updater_update_status($tweet)
{
	
	$tokens = get_option('tweet_updater_auth');
	
	if( $tokens['auth3_flag'] == '1' )
	{
		// Initiate a TwitterOAuth with access tokens
		$connection = new TwitterOAuth($tokens['consumer_key'], $tokens['consumer_secret'], $tokens['access_key'], $tokens['access_secret']);
		
		// Post an update to Twitter via your application:
		$result = $connection->post('statuses/update', array('status' => $tweet));

	}
	else 
	{
		$result = array( 
			'plugin_error' => 'auth', 
			'error_description' => 'TweetUpdater is not linked to a twitter account'
				);
	}

	return $result;
}



	/*** WordPress Hooks ***/


/* Action for when a post is published/edited (but not just saved) */
	add_action( 'publish_post', 'vc_tweet_publish', 1, 1 );

/* Action for when a future post is published */
//	add_action( 'future_to_publish', 'vc_twit_future', 1, 1 );

/* Add the admin options page */
	add_action( 'admin_menu', 'tweet_updater_admin_add_page' );

/* Intialise on first activation */
	register_activation_hook( __FILE__, 'tweet_updater_activate' );

?>
