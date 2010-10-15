<?php
/*
Plugin Name: TweetUpdater
Description: Update your Twitter status when you publish or update a post. Based on TwitterUpdater v1.0 by <a href="http://blog.victoriac.net/ja/geek/twitter-updater">Victoria Chan</a>
Version: 3.0.1
Author: Patrick Fenner (Def-Proc.co.uk)
Author URI: http://www.deferredprocrastination.co.uk/
Plugin URI: http://www.deferredprocrastination.co.uk/projects/tweetupdater


Help & Support: http://github.com/DefProc/TweetUpdater/issues


THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS 
IN THE SOFTWARE.
 
*/

require_once('twitteroauth.php');
require_once('tweetupdater_manager.php');




	/*** TweetUpdater Actions ***/


/* Plugin action when status changes to publish */

function tweet_updater_published($post) //$post_ID)  
{
	//load plugin preferences
	$options = get_option('tweet_updater_options'); 
	
	if ( $options['newpost_update'] == "1" && tweet_updater_is_tweetable($post, $options) )
	{
		$post_ID = $post->ID;
		$title = $post->post_title; 
		$link = get_permalink($post_ID);
		$tweet = '';
		
		// Format the message
		$tweet = tweet_updater_format_tweet( $options['newpost_format'], $title, $link, $post_ID, $options['use_curl'], $options['url_method'] );
	
			if($tweet != '')
			{
				// Send the message
		    		$result = tweet_updater_update_status($tweet);
			}
		
	} 

	return $post;
}


/* Plugin action when published is (re)published (i.e. updated) */

function tweet_updater_edited($post) //$post_ID)  
{
	//load plugin preferences
	$options = get_option('tweet_updater_options'); 
	
	if ( $options['edited_update'] == "1" && tweet_updater_is_tweetable($post, $options) )
	{
		$post_ID = $post->ID;
		$title = $post->post_title; 
		$link = get_permalink($post_ID);
		$tweet = '';

		// Format the message
		$tweet = tweet_updater_format_tweet( $options['edited_format'], $title, $link, $post_ID, $options['use_curl'], $options['url_method'] );

			if($tweet != '')
			{
				// Send the message
		    		$result = tweet_updater_update_status($tweet);
			}
		
	} 
	
	return $post;
}





	/*** Additional Functions ***/


// checks if the post has either a given custom field key/value
// or is part of a selected category
function tweet_updater_is_tweetable($post, $options) 
{
	if( $options['limit_activate'] == '1' ) 
	{
		// limiter is activated, check if the post is part of 
		// a category which is tweetable
		if( is_array($options['limit_to_category']) && sizeof($options['limit_to_category']) > 0 ) 
		{
			$post_categories = wp_get_post_categories($post->ID);
			
			//echo "Post Categories = <br /><pre>" . print_r($post_categories, true) . "</pre><br />";
			
			if( is_array($post_categories) && sizeof($post_categories) > 0 ) 
			{
				foreach( $options['limit_to_category'] as $key => $value )
				{
					if( $value > 0 && in_array( $value, $post_categories ) ) 
					{
						//echo "in cat: TRUE";
						return true;
					} 
					//else 
					//{
					//	echo "\$value ($value) is not in \$post_categories array, or \$value ($value) <= 0.<br />"; 
					//}
				}
			} //else { echo "\$post_categories is not array, or has no size."; }
		} //else { echo "limit_to_category is not array, or has no size."; } 
		
		// Ok, no category found so continue with checking for the custom fields 
		if( !empty( $options['limit_to_custom_field_key'] ) ) 
		{
			// If the custom_field_val is empty, just check the key for a match
			if( empty( $options['limit_to_custom_field_val'] ) || $options['limit_to_custom_field_val'] == '*' )
			{				
				$custom_field_val = get_post_meta( $post->ID, $options['limit_to_custom_field_key'], true );
				
				if( !empty($custom_field_val) ) 
				{
					//echo "key matches: true";
					return true;
				} 
			}
			// If there's a custom_field_val, check both match
			else if( !empty( $options['limit_to_custom_field_val'] ) )
			{
				$custom_field_val = get_post_meta( $post->ID, $options['limit_to_custom_field_key'], true );
				
				if( !empty($custom_field_val) && $custom_field_val == $options['limit_to_custom_field_val'] ) 
				{
					//echo "key and value match: true";
					return true;
				} 
			}
		}

		// in all other cases return false
		//echo "no matches: false";
		return false;

	} 
	else 
	{
		// limit is not active so everything is tweetable
		//echo "limiter inactive: false";
		return true;
	} 
}


/* Single function to output a formatted tweet */

function tweet_updater_format_tweet( $tweet_format, $title, $link, $post_ID, $use_curl, $url_method )
{
	//initialise tweet
	$tweet = $tweet_format;
	
	//retieve the short url
	$short_url = tu_get_shorturl($use_curl,$url_method,$link,$post_ID);

	// Error handling: If plugin is deacitvated, repeat to use default link supplier
	if( $short_url['error_code'] == '1' ) 
	{ 
		$short_url = tu_get_shorturl($use_curl,$short_url['url_method'],$link,$post_ID); 
	}

	// Additional error handing is possible: returning $tweet = ''; will cause sending to be aborted.

	//do the placeholder string replace
	$tweet = str_replace ( '#title#', $title, $tweet);
	$tweet = str_replace ( '#url#', $short_url, $tweet);
	
	return $tweet;
}

/* Get the selected short url */

function tu_get_shorturl( $use_curl, $url_method, $link, $post_ID ) 
{
	//Internal URL providers:
	if ( $url_method == 'petite' ) 
	{
		if(function_exists('get_la_petite_url_permalink')) 
		{
			// return short link from la_petite_url plugin function
			$short_url = get_la_petite_url_permalink($post_ID); 
		} 
		else 
		{
			// send error message
			$short_url = array( 
				'error_code' => '1',
				'error_message' => 'Function deactivated. Repeat with another method.', 
				'url_method' => 'tinyurl',
						);
		}
	}
	else if ( $url_method == 'permalink' ) 
	{
		$short_url = $link; 
	}

	//External URL shorteners:
	else if ( $url_method == 'bitly' ) 
	{
		$options = get_option('tweet_updater_options');
		$short_url = tu_make_bitly_url($link,$options['bitly_username'],$options['bitly_appkey'],$use_curl);
	}
	else if ( $url_method == 'tinyurl' ) 
	{
		$target_url = "http://tinyurl.com/api-create.php?url=" . $link;
		if ( $use_curl == '1' ) 
		{
			$short_url = file_get_contents_curl($target_url);
		} 
		else 
		{      
			$short_url = file_get_contents($target_url);
		}
	}
	if ( $url_method == 'stwnsh' ) 
	{ 
		$target_url = "http://stwnsh.com/api.php?format=simple&action=shorturl&url=" . $link;
		if ( $use_curl == '1' ) 
		{ 
			$short_url = file_get_contents_curl($target_url); 
		} 
		else 
		{ 
			$short_url = file_get_contents($target_url); 
		}
	}
	else if ( $url_method == 'zzgd' ) 
	{ 
		$target_url = "http://zz.gd/api-create.php?url=" . $link;
		if ( $use_curl == '1' ) 
		{ 
			$short_url = file_get_contents_curl($target_url); 
		} 
		else 
		{ 
			$short_url = file_get_contents($target_url); 
		}
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
	
	//error handling hook
	$status = array( 'status_code' => $json['status_test'], 'status_txt' => $json['status_txt'] );
	
	return $short_url;
}



/* alternative funtion to file_get_contents(), using cURL */

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


/* Action for when a post is published */
	add_action( 'draft_to_publish', 'tweet_updater_published', 1, 1 );
	add_action( 'new_to_publish', 'tweet_updater_published', 1, 1 );
	add_action( 'pending_to_publish', 'tweet_updater_published', 1, 1 );
	add_action( 'future_to_publish', 'tweet_updater_published', 1, 1 );

/* Action when post is updated */
	add_action( 'publish_to_publish', 'tweet_updater_edited', 1, 1 );

/* Add the admin options page */
	add_action( 'admin_menu', 'tweet_updater_admin_add_page' );

/* Intialise on first activation */
	register_activation_hook( __FILE__, 'tweet_updater_activate' );

	//add hook to include settings link on plugins page
	$plugin = plugin_basename(__FILE__); 
	add_filter("plugin_action_links_$plugin", 'tweet_updater_add_settings_link' );

?>
