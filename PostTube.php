<?php

/**
 * Plugin Name:       PostTube
 * Description:       Convert your post to audio and video.
 * Version:           1.0.0
 * Author:            Senol Sahin
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') or exit;

include plugin_dir_path( __FILE__ ) . 'includes/config.php';
include plugin_dir_path( __FILE__ ) . 'includes/functions.php';
include plugin_dir_path( __FILE__ ) .  'vendor/autoload.php';

use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\SsmlVoiceGender;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use Google\Cloud\TextToSpeech\V1\ListVoicesResponse;

define( 'TUBE_TIME', time() );
define( 'TUBE_ROOT', dirname(__FILE__) );
$upload_dir = wp_upload_dir();

define( 'TUBE_POST_UPLOADS_PATH', $upload_dir['basedir'] );

add_action('admin_menu', 'tubepost_plugin_setup_menu');

function tubepost_plugin_setup_menu()
{
	add_menu_page(
        'PostTube Plugin Page', 
        'PostTube', 
        'manage_options',
		'tube_post',
        'tube_post_page',
        plugins_url( 'includes/assets/img/admin_icon.png',__FILE__ ),
        '6');
}

function tubepost_plugin_activate() { 
	$upload_dir = wp_upload_dir(); 
	$tube_audio_dirname = $upload_dir['basedir'] . '/tube_post/audio';
	$tube_video_dirname = $upload_dir['basedir'] . '/tube_post/video';
	if(!file_exists($tube_audio_dirname)) wp_mkdir_p($tube_audio_dirname);
	if(!file_exists($tube_video_dirname)) wp_mkdir_p($tube_video_dirname);
}
register_activation_hook( __FILE__, 'tubepost_plugin_activate' );


function tube_post_page() {
	include_once(TUBE_ROOT.'/includes/index.php');
}

function tubepost_call_text_to_speech($post_id)
{
	if (tubepost_isEnablePlugin() && !tubepost_isApiKeyExit()) return;
	if(tubepost_isPluginEnable() == 0) return;

	
	$post_title = str_replace(" ", "_", substr(get_the_title($post_id ),0,15));
	define( 'TUBE_POST_TITLE', $post_title);
	define( 'TUBE_POST_ID', $post_id );

    $post = get_post( $post_id ) ;
    if ($post->post_content == null) return;
    $post_content = wp_strip_all_tags($post->post_content);
    $post_content = str_replace("&nbsp;", " ", $post_content);
    $post_content = str_replace(array(
        "\n",
        "\r"
    ) , " ", $post_content);
    $post_content = str_replace("%C2%A0", " ", $post_content);
   

    $post_content = (strlen($post_content) > 5000) ? substr($post_content,0,4999) : $post_content;
	
	tubepost_generate_audio($post_content);
	
    tuebpost_generate_video();

}
add_action('auto-draft_to_publish', 'tubepost_call_text_to_speech');
add_action('publish_future_post', 'tubepost_call_text_to_speech');

function tubepost_generate_audio($post_content) {
	
	$json_path = tubepost_db_select_query("SELECT pitch FROM wp_tube_config WHERE id = 3")[0]['pitch'];
	
	if($json_path == "") return;
	
	putenv('GOOGLE_APPLICATION_CREDENTIALS='.$json_path);
	
	$client = new TextToSpeechClient();
	
	$config = tubepost_db_select_query("SELECT select_language, select_voice, audio_profile, speaking_voice, pitch FROM wp_tube_config");
	
	$synthesisInputText = (new SynthesisInput())
	    ->setText($post_content);

	$voice = (new VoiceSelectionParams())
	->setLanguageCode($config[0]['select_language'])
	->setName( $config[0]['select_voice'] );

	$effectsProfileId = $config[0]['audio_profile'];

	$audioConfig = (new AudioConfig())
	    ->setAudioEncoding(AudioEncoding::MP3)
	    ->setEffectsProfileId(array($effectsProfileId))
	    ->setSpeakingRate($config[0]['speaking_voice'])
        ->setPitch( $config[0]['pitch'] )
        ->setSampleRateHertz( 24000 );
	
	$response = $client->synthesizeSpeech($synthesisInputText, $voice, $audioConfig);
	$audioContent = $response->getAudioContent();
	
	file_put_contents(TUBE_POST_UPLOADS_PATH . '/tube_post/audio/'.TUBE_POST_TITLE.'_'.TUBE_TIME.'.mp3', $audioContent);
	
}

function tuebpost_generate_video()
{
	
    $input_audio = TUBE_POST_UPLOADS_PATH . '/tube_post/audio/'.TUBE_POST_TITLE.'_'.TUBE_TIME.'.mp3';
//     $input_image = TUBE_ROOT . '/includes/assets/img/sample.png';
	
	$image_path = tubepost_db_select_query("SELECT pitch FROM wp_tube_config WHERE id = 2")[0]['pitch'];
	
	if($image_path == "") return;
	
	$input_image = $image_path;
    
	$output = TUBE_POST_UPLOADS_PATH . '/tube_post/video/'.TUBE_POST_TITLE.'_'.TUBE_TIME.'.mp4';

	$command = 'ffmpeg -loop 1 -threads 1 -i ' . $input_image . ' -i ' . $input_audio . ' -c:v libx264 -crf 30 -tune stillimage -c:a aac -b:a 192k -pix_fmt yuv420p -shortest ' . $output;
	
	
    $log = TUBE_ROOT . '/log.txt';
    
	$audio_name = TUBE_POST_TITLE.'_'.TUBE_TIME.'.mp3';
	
	global $wpdb;
	$wpdb->query("INSERT INTO {$wpdb->prefix}tube_log (post_id,post_date,post_title,audio_name,video_name,video_location,post_status,failed_reason) VALUES ('".TUBE_POST_ID."','".date("Y-m-d H:i:s",TUBE_TIME)."', '".TUBE_POST_TITLE."','".$audio_name."', '','".$output."','pendding','') ");

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
    {
        pclose(popen("start /B " . $command . " 1> $log 2>&1", "r")); // windows
    }
    else
    {
		$result = '';
		shell_exec($command . " 1> $log 2>&1 >/dev/null &"); //linux
    }
	
		$video_name = TUBE_POST_TITLE.'_'.TUBE_TIME.'.mp4';
		$result = 'success';
		$reason = '';

		$wpdb->query("UPDATE {$wpdb->prefix}tube_log SET post_status = '".$result."',video_name = '".$video_name."',failed_reason = '".$reason."', video_location = '".$output."' WHERE audio_name = '".$audio_name."'");
	
}


function tubepost_settings_save_action () {
	global $wpdb;
	$json=array();
	
	$lang = sanitize_text_field( $_POST['lang'] );
	$voice = sanitize_text_field( $_POST['voice'] );
	$profile = sanitize_text_field($_POST['profile']);
	$speed = sanitize_text_field($_POST['speed']);
	$pitch = sanitize_text_field($_POST['pitch']);
	
	$query = "UPDATE {$wpdb->prefix}tube_config SET select_language = '".$lang."', select_voice = '".$voice."', audio_profile = '".$profile."', speaking_voice = ".$speed.", pitch = ".$pitch." WHERE id = 1 ";
	
	$wpdb->query($query);
}

add_action('wp_ajax_settings_save_action','tubepost_settings_save_action');
add_action('wp_ajax_nopriv_settings_save_action','tubepost_settings_save_action');


function tubepost_activition_action () {
	global $wpdb;
	
	$val = sanitize_text_field( $_POST['val'] );
	$query = "UPDATE {$wpdb->prefix}tube_config SET pitch = ".$val." WHERE id = 4 ";
	
	$wpdb->query($query);
}

add_action('wp_ajax_activition_action','tubepost_activition_action');
add_action('wp_ajax_nopriv_activition_action','tubepost_activition_action');


function tubepost_mime_types( $mimes ) {
 
// New allowed mime types.
$mimes['json']='application/json';
// Optional. Remove a mime type.
unset( $mimes['exe'] );
 
return $mimes;
}
add_filter( 'upload_mimes', 'tubepost_mime_types' );


?>
