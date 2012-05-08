<?php
define('SOUNDCLOUD_PLUGIN_VERSION', '0.1');

#@TODO: Add MVC implementation
#@TODO: Check out array-to-XML parsers
#@TODO: Check OAIPMH harverster plugin for code that loads status to db , see indexcontroller.php #jobdispatcher to get onto other thread 
#@TODO: Look at paths.php for better way to get file path
#@TODO: make jQuery in config_form.php work 
#@TODO: bind jQuery to "Add Item" and "Save Changes" buttons to confirm upload 
	
/** Plugin hooks */
add_plugin_hook('install', 'soundcloud_install');
add_plugin_hook('uninstall', 'soundcloud_uninstall');
add_plugin_hook('config_form', 'soundcloud_config_form');
add_plugin_hook('config', 'soundcloud_config');
add_plugin_hook('admin_append_to_items_form_files', 'soundcloud_admin_append_to_items_form_files');
add_plugin_hook('after_save_item', 'soundcloud_after_save_item');
add_plugin_hook('admin_append_to_items_show_secondary', 'soundcloud_admin_append_to_items_show_secondary');

// Hook Functions

/**
 * Displays SoundCloud links in admin/show section 
 * @return void
 **/    
function soundcloud_admin_append_to_items_show_secondary() {         
	echo '<div class="info-panel">';
    echo '<h2>SoundCloud</h2>';
	echo listSoundCloudLinks();
	echo '</div>';
 
}

/**
 * Gives user the option to post to SoundCloud 
 * @return void
 **/    
function soundcloud_admin_append_to_items_form_files() {
	?>

	<span><b>Upload to SoundCloud</b></span>
	<input type="hidden" name="PostToSoundCloudBool" value="0">
	<input type="checkbox" name="PostToSoundCloudBool" value="1" <?php if(get_option('post_to_soundcloud_default_bool') == '1') {echo 'checked';} ?>>
	<div><em>Note that if this box is checked, saving the item may take a while.</em></div>
	<!--TODO: Must files be uniquely named? If, so warn here -->
	</br>
	<span><b>Make Public on SoundCloud</b></span>
	<input type="hidden" name="SoundCloudPublicBool" value="0">
	<input type="checkbox" name="SoundCloudPublicBool" value="1" <?php if(get_option('soundcloud_public_default_bool') == '1') {echo 'checked';} ?>/>
	<div><em>If you index your item, it will appear on the results of search engines such as Google's.</em></div>
	</br>
	</br>

	<?php
}

/**
 * Sets configuartion options to default 
 * @return void
 **/    
function soundcloud_install()
{

	set_option('post_to_soundcloud_default_bool', '1');
	set_option('soundcloud_public_default_bool', '1');

}

/**
 * Displays configuration form 
 * @return void
 **/    
function soundcloud_config_form()
{
	include 'config_form.php';
}

/**
 * Configures based on inputs in config_form.php 
 * @return void
 **/    
function soundcloud_config()
{

	set_option('post_to_soundcloud_default_bool', $_POST['postToSoundCloudDefaultBool']);
	set_option('soundcloud_public_default_bool', $_POST['soundCloudPublicDefaultBool']);
	set_option('access_token', $_POST['accessToken']);
	set_option('client_id', $_POST['clientIdTwo']);
	set_option('client_secret', $_POST['clientSecretTwo']);
		
}

/**
 * Deletes persistent variables 
 * @return void
 **/    
function soundcloud_uninstall()
{
	
	delete_option('post_to_soundcloud_default_bool');
	delete_option('soundcloud_public_default_bool');
	delete_option('access_token');
	delete_option('client_id');
	delete_option('client_secret');
	delete_option('redirect_uri');
	
}

/**
 * Post Files and metadata of an Omeka Item to SoundCloud 
 * @return void
 **/    
function soundcloud_after_save_item($item)
{
	
	//if thrye, runs single-thread for HTTP responces and throws uncaught exception so echo and print_r statements are seen 
	$DEBUG = TRUE;

	if($_POST["PostToSoundCloudBool"] == '1') {

		/**
		 * @param $first true if this is the first PUT to the bucket, false otherwise 
		 * @param $fileToBePut the Omeka file to by uploaded to the Internet Archive 
		 * @return A cURL object with parameters set to upload an Omeka File
		 */		 
		function getCurlObject(File $fileToBePut)
		{
			
			$cURL = curl_init();
			
			curl_setopt($cURL, CURLOPT_URL, 'https://api.soundcloud.com/tracks.json');
			curl_setopt($cURL, CURLOPT_HEADER, 1);
		    curl_setopt($cURL, CURLOPT_CONNECTTIMEOUT, 30);
		    curl_setopt($cURL, CURLOPT_LOW_SPEED_LIMIT, 1);
		    curl_setopt($cURL, CURLOPT_LOW_SPEED_TIME, 180);
		    curl_setopt($cURL, CURLOPT_NOSIGNAL, 1);
			curl_setopt($cURL, CURLOPT_POST, 1);
			curl_setopt($cURL, CURLOPT_POSTFIELDS, array('oauth_token'=>get_option('access_token'),'track[asset_data]'=>'@'.FILES_DIR.'/'.item_file('archive filename'),'track[title]'=>preg_replace('/(\s+)/','_',item_file('original filename')),'track[sharing]'=>(($_POST["SoundCloudPublicBool"] == '1') ? 'public' : 'private')));
			curl_setopt($cURL, CURLOPT_RETURNTRANSFER, TRUE);

			print_r(array('oauth_token'=>get_option('access_token'),'track[asset_data]'=>'@'.FILES_DIR.'/'.item_file('archive filename'),'track[title]'=>item_file('original filename'),'track[sharing]'=>(($_POST["SoundCloudPublicBool"] == '1') ? 'public' : 'private')));
			curl_exec($cURL);
			print_r(curl_getinfo($cURL));
			
			//uncomment for debugging
			// file_download_uri($whatever);
			
			return $cURL;
		}
		
		/**
		 * Adds handle for to cURL multi object 
		 * @param $curlMultiHandle pointer to multi cURL multi handle that will be added to
		 * @param $cURL single cURL handle to add 
		 * @return $curl the object for curl_multi_remove_handle
		 **/    		
		function addHandle(&$curlMultiHandle,$cURL)
		{
			curl_multi_add_handle($curlMultiHandle,$cURL);
			return $cURL;
		}
				
		/**
		 * Executes the cURL multi handle until there are no outstanding jobs 
		 * @return void
		 **/    		
		function execMultiHandle(&$curlMultiHandle)
		{
			$flag=null;
			do {
			//fetch pages in parallel
			curl_multi_exec($curlMultiHandle,$flag);
			} while ($flag > 0);			
		}
		
		//from Soundcloud.php
		$mimeTypes = array('video/mp4','video/mp4','audio/x-aiff','audio/flac','audio/mpeg','audio/ogg','audio/x-wav');

		//set item
		set_current_item($item);
		
		while(loop_files_for_item())
		{
			echo item_file('MIME Type');
			if (in_array(item_file('MIME Type'), $mimeTypes))
			{
				echo 'in if for '.item_file('original filename');
				curl_exec(getCurlObject(get_current_file()));			
			}
		}

		//throws uncaught error for debugging
		//file_download_uri($whatever);
		
	}

}