<?php
/*
Plugin Name: TweetUpdater
Description: WordPress plugin to update Twitter status when you create or publish a post.
Version: 3.x.alpha1
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
			// Fix for scheduled posts (from uniqueculture)
			if (strlen(trim($title)) == 0) { $this_post = get_post($post_ID); if ($this_post) { $title = $this_post->post_title; } }
			//I've not figured out what this fixes yet, maybe sometimes post_title is not set when get_post() is first called? - [DefProc]

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



/* Plugin action on future_to_publish (when a scheduled post gets published) */

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

function tweet_updater_format_tweet( $tweet_format, $title, $link, $post_ID, $use_curl, $url_method )
{
	//initialise tweet
	$tweet = $tweet_format;
	
	//retieve the short url
	$short_url = get_tinyurl($use_curl,$url_method,$link,$post_ID);

	// Error handling: If plugin is deacitvated, repeat to use default link supplier
	if( $short_url['error'] == 'repeat1' ) 
	{ 
		$short_url = get_tinyurl($use_curl,$short_url['url_method'],$link,$post_ID); 
	}

	// Additional error handing is possible: returning $tweet = ''; will cause sending to be aborted.

	//do the placeholder string replace
	$tweet = str_replace ( '#title#', $title, $tweet);
	$tweet = str_replace ( '#url#', $short_url, $tweet);
	
	return $tweet;
}

/* Get the selected short url */

function get_tinyurl( $use_curl, $url_method, $link, $post_ID ) 
{
	if ( $url_method == 'zzgd' ) 
	{ 
		$target_url = "http://zz.gd/api-create.php?url=" . $link;
		if ( $use_curl == '1' ) 
		{ 
			$short_url = file_get_contents_curl($target); 
		} 
		else 
		{ 
			$short_url = file_get_contents($target); 
		}
	}
	else if ( $url_method == 'tinyurl' ) 
	{
		$target = "http://tinyurl.com/api-create.php?url=" . $link;
		if ( $use_curl == '1' ) 
		{
			$short_url = file_get_contents_curl($target);
		} 
		else 
		{      
			$short_url = file_get_contents($target);
		}
	}
	else if ( $url_method == 'bitly' ) 
	{
		$options = get_option('tweet_updater_options');
		$short_url = tu_make_bitly_url($link,$options['bitly_username'],$options['bitly_appkey'],$use_curl);
	}
	else if ( $url_method == 'petite' ) 
	{
		if(function_exists('get_la_petite_url_permalink')) 
		{
			// return short link from la_petite_url plugin function
			$short_url = get_la_petite_url_permalink($post_ID); 
		} 
		else 
		{
			// Don't want things to fail completely if la_petite_url gets deactivated, so:
			
			// reset to default	
	//		$options = get_option('tweet_updater_options'); 
	//		$option['url_method'] = 'tinyurl';
	//		update_option( 'tweet_updater_options', $options ); 
			
		/* Should we reset to default? It could be a temporary problem, and the error handling loop would deal with it */
			
			// send error message
			$short_url = array( 
				'error_message' => 'repeat1', 
				'url_method' => 'tinyurl',
						);
		}
	}
	else if ( $url_method == 'permalink' ) 
	{
		$short_url = $link; 
	}

	return $short_url;
}


/* get a bit.ly url */

function tu_make_bitly_url($link,$login,$appkey,$use_curl) 
{
	$bitly = 'http://api.bit.ly/v3/shorten?login='.$login.'&apiKey='.$appkey.'&format=json&history=1&longUrl='.urlencode($link);

	//get the url
	if ($use_curl == '1') 
	{ 
		$response = file_get_contents_curl($bitly); 
	}
	else 
	{ 
		$response = file_get_contents($bitly); 
	}

	$json = @json_decode($response,true);
	$short_url = $json['data']['url'];
	
	return $short_url;
}



/* alternative funtion to file_get_contents(), using curl */

function file_get_contents_curl($target_url) 
{
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_URL, $target_url);
	
	$data = curl_exec($ch);

	curl_close($ch);

	return $data;
}







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
	add_action( 'future_to_publish', 'vc_tweet_future', 1, 1 );

/* Add the admin options page */
	add_action( 'admin_menu', 'tweet_updater_admin_add_page' );

/* Intialise on first activation */
	register_activation_hook( __FILE__, 'tweet_updater_activate' );


?>
