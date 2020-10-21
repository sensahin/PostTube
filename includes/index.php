<?php

namespace tubepost\includes;

include_once('config.php');
include_once('functions.php');
global $wpdb;
// $wpdb->query("DROP TABLE wp_tube_config");
$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}tube_log`  (
	  `id` int NOT NULL AUTO_INCREMENT,
	  `post_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	  `post_title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	  `post_date` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `audio_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	  `video_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	  `video_location` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `post_status` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  `failed_reason` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci,
	  PRIMARY KEY (`id`) USING BTREE)");
$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}tube_config` (
		`id` int NOT NULL AUTO_INCREMENT,
		`select_language` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
		`select_voice` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
		`audio_profile` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
		`speaking_voice` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
		`pitch` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
	  PRIMARY KEY (`id`) USING BTREE)");

global $wpdb;

$data = tubepost_db_select_query("SELECT id,post_date,post_title,post_status,audio_name, video_name, failed_reason FROM {$wpdb->prefix}tube_log ORDER BY id DESC LIMIT 50");

$json =json_encode($data);
$tube_config = tubepost_db_select_query("SELECT id, select_language, select_voice, audio_profile, speaking_voice, pitch FROM {$wpdb->prefix}tube_config WHERE id = 1");

$output = wp_upload_dir();
$output_path = $output['baseurl'];
if(count($tube_config) == 0)
{
	
	$wpdb->query("INSERT INTO {$wpdb->prefix}tube_config (select_language, select_voice, audio_profile, speaking_voice, pitch) VALUES ('en-US', 'en-US-Standard-B', 'telephony-class-application', '1', '0')");
	
	$wpdb->query("INSERT INTO {$wpdb->prefix}tube_config (pitch) VALUES ('')");
	$wpdb->query("INSERT INTO {$wpdb->prefix}tube_config (pitch) VALUES ('')");
	$wpdb->query("INSERT INTO {$wpdb->prefix}tube_config (pitch) VALUES ('')");
	$tube_config = tubepost_db_select_query("SELECT id, select_language, select_voice, audio_profile, speaking_voice, pitch FROM {$wpdb->prefix}tube_config");
}
$image_path = tubepost_db_select_query("SELECT pitch FROM {$wpdb->prefix}tube_config WHERE id = 2")[0]['pitch'];
$json_path=tubepost_db_select_query("SELECT pitch FROM {$wpdb->prefix}tube_config WHERE id = 3")[0]['pitch'];
$activate_status = tubepost_db_select_query("SELECT pitch FROM {$wpdb->prefix}tube_config WHERE id = 4")[0]['pitch'];

 function get_tube_voice_type( $lang_name ) {

	$pos = strpos( $lang_name, 'Wavenet' );

	if ( false === $pos ) {
		return esc_html( 'Standard' );
	}

	return esc_html( 'WaveNet' );

}

function get_tube_lang_by_code( $lang_code ) {

	if ( is_object( $lang_code ) ) {
		$lang_code = $lang_code[0];
	}

	$languages = [
		'da-DK'  => 'Danish (Dansk)',
		'nl-NL'  => 'Dutch (Nederlands)',
		'en-AU'  => 'English (Australian)',
		'en-GB'  => 'English (UK)',
		'en-US'  => 'English (US)',
		'fr-CA'  => 'French Canada (Français)',
		'fr-FR'  => 'French France (Français)',
		'de-DE'  => 'German (Deutsch)',
		'it-IT'  => 'Italian (Italiano)',
		'ja-JP'  => 'Japanese (日本語)',
		'ko-KR'  => 'Korean (한국어)',
		'nb-NO'  => 'Norwegian (Norsk)',
		'pl-PL'  => 'Polish (Polski)',
		'pt-BR'  => 'Portuguese Brazil (Português)',
		'pt-PT'  => 'Portuguese Portugal (Portugal)',
		'ru-RU'  => 'Russian (Русский)',
		'sk-SK'  => 'Slovak (Slovenčina)',
		'es-ES'  => 'Spanish (Español)',
		'sv-SE'  => 'Swedish (Svenska)',
		'tr-TR'  => 'Turkish (Türkçe)',
		'uk-UA'  => 'Ukrainian (Українська)',
		'ar-XA'  => 'Arabic (العربية)',
		'cs-CZ'  => 'Czech (Čeština)',
		'el-GR'  => 'Greek (Ελληνικά)',
		'en-IN'  => 'Indian English',
		'fi-FI'  => 'Finnish (Suomi)',
		'vi-VN'  => 'Vietnamese (Tiếng Việt)',
		'id-ID'  => 'Indonesian (Bahasa Indonesia)',
		'fil-PH' => 'Philippines (Filipino)',
		'hi-IN'  => 'Hindi (हिन्दी)',
		'hu-HU'  => 'Hungarian (Magyar)',
		'cmn-CN' => 'Chinese (官话)',
        'cmn-TW' => 'Taiwanese Mandarin (中文(台灣))',
        'bn-IN'  => 'Bengali (বাংলা)',
        'gu-IN'  => 'Gujarati (ગુજરાતી)',
        'kn-IN'  => 'Kannada (ಕನ್ನಡ)',
        'ml-IN'  => 'Malayalam (മലയാളം)',
        'ta-IN'  => 'Tamil (தமிழ்)',
        'te-IN'  => 'Telugu (తెలుగు)',
		'th-TH'  => 'Thai (ภาษาไทย)',
		'yue-HK' => 'Yue Chinese',
	];

	return $languages[ $lang_code ];
}

// function tube_post_home_page(){

	$select_language = $tube_config[0]['select_language'];
	$select_voice = $tube_config[0]['select_voice'];
	$select_profile  = $tube_config[0]['audio_profile'];
	$speaking_speed = $tube_config[0]['speaking_voice'];
	$pitch 			= $tube_config[0]['pitch'];

	use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;

	
	$isRequest = tubepost_isEnablePlugin() ;

	if($isRequest == '')
	{
		$isRequest = 0;
	}


	wp_enqueue_script('jquery');
 
	wp_register_style('style1-css', plugins_url('css/plugins.bundle.css', __FILE__));
	wp_enqueue_style('style1-css');
	wp_register_style('style2-css', plugins_url('css/style.bundle.css', __FILE__));
	wp_enqueue_style('style2-css');
	wp_register_style('style3-css', plugins_url('css/style.css', __FILE__));
	wp_enqueue_style('style3-css');


?>


	<div class="container" hidden>
		<div class="kt_header" style="background:white; margin-top:15px;">
			<div class="example-preview">
				<ul class="nav nav-tabs" id="myTab" role="tablist">
					<li class="nav-item">
						<button onclick="pageChange(0)" class="nav-link active" id="link_0">
							<span class="nav-icon">
								<i class="flaticon2-chat-1"></i>
							</span>
							<span class="nav-text">Home</span></button>
					</li>

					<li class="nav-item">
						<button class="nav-link " id="link_1" onclick="pageChange(1)">
							<span class="nav-icon">
								<i class="fas fa-key"></i>
							</span>
							<span class="nav-text">API Settings</span>
						</button>
					</li>

					<li class="nav-item">
						<button class="nav-link" id="link_2" onclick="pageChange(2)">
							<span class="nav-icon">
								<i class="flaticon-letter-g"></i>
							</span>
							<span class="nav-text">Voice Settings</span>
						</button>
					</li>

					<li class="nav-item">
						<button class="nav-link" id="link_3" onclick="pageChange(3)">
							<span class="nav-icon">
								<i class="flaticon2-image-file"></i>
							</span>
							<span class="nav-text">Video Settings</span>
						</button>
					</li>

					<li class="nav-item">
						<button class="nav-link" id="link_4" onclick="pageChange(4)">
							<span class="nav-icon">
								<i class="flaticon-pie-chart-1"></i>
							</span>
							<span class="nav-text">Requirements</span>
						</button>
					</li>

					<li class="nav-item">
						<button class="nav-link btn" id="link_5" onclick="pageChange(5)">
							<span class="nav-icon">
								<i class="flaticon2-layers-1"></i>
							</span>
							<span class="nav-text">Library</span>
						</button>
					</li>
				</ul>
			</div>
		</div>
		<!--begin Homepage -->
		
		<!--end Homepage -->

		<!-- Bigin SpeechService page -->
		<section class="page-1 card card-custom" hidden>

			<div>
					<h1>
						Google Cloud Text-to-Speech API Settings
				</h1>
				<p>
					
				</p>
					<p>
						Same like all other APIs, Google Cloud Text-to-Speech API also have some limitations and restrictions. You can read more on <a href="https://cloud.google.com/text-to-speech/quotas">here</a>.
				</p>
				<h2>
					Content limits
				</h2>
				<p>
					Content to Text-to-Speech is provided as text data, either as raw strings or SSML-formatted data. The API contains the following limits on the size of this content (and are subject to change by Google):
				</p>
				<p>
					<b>Total characters per request: 5,000</b>
				</p>
					<h2>
					Requests limits
				</h2>
				<p>
					The current API usage limits for Text-to-Speech are as follows (and are subject to change by Google):
				</p>
				<p>
					<b>Requests per minute: 300</b>
				</p>
				<p>
					<b>Characters per minute: 150,000</b>
				</p>
			</div>

			<div>
				<div>
					<div>
							<?php

						if ( ! function_exists( 'wp_handle_upload' ) ) 
							require_once( ABSPATH . 'wp-admin/includes/file.php' );
						if(!empty($_FILES) && !empty($_FILES['key-file']))
						{
							$uploadedfile = $_FILES['key-file'];
							$upload_overrides = array( 'test_form' => false );
						
							$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
							
							if ( $movefile ) {
								$wpdb->query("UPDATE {$wpdb->prefix}tube_config SET pitch = '".$movefile['file']."' WHERE id = 3");
								$json_path = tubepost_db_select_query("SELECT pitch FROM {$wpdb->prefix}tube_config WHERE id = 3")[0]['pitch'];
							}
						}
						if(!tubepost_isApiKeyExit())
						{
							$wpdb->query("UPDATE {$wpdb->prefix}tube_config SET pitch = '' WHERE id = 3");
							$json_path = tubepost_db_select_query("SELECT pitch FROM {$wpdb->prefix}tube_config WHERE id = 3")[0]['pitch'];
						}
						else if(tubepost_isApiKeyExit()) {
								putenv('GOOGLE_APPLICATION_CREDENTIALS='.$json_path);

								$client = new TextToSpeechClient();

								$response = $client->listVoices();
								$voices   = $response->getVoices();
							}
						?>
						<div>
							<p><b>Current Key File:</b><?php echo $json_path;?></p>
							<form action="" method="post" enctype="multipart/form-data">
								<label  for="file">Filename:</label>
								<input type="file" name="key-file" accept=".json" id="key-file"><br>
								<input class='btn btn-primary' type="submit" name="save" value="save">
							</form>
						</div>
					</div>
				</div>
				
				</div>


				</section>
			<!-- End SpeechService page -->
			<!-- Bigin Google API Setting page -->
			<section class="page-2 card card-custom" hidden>
					<div>
						<h1>
							Please configure voice settings below.
						</h1>
					</div>

				<div class="row">
					<div class="col-6">
						<form class="form" id="configuration" onsubmit="return false;">
							<div class="card-body">

								<div class="form-group row" style="padding-top:20px;" >
									<label for="exampleSelectl" class='col-4'>Select lanugage</label>
									<select class="form-control form-control-solid col-8 form-control-sm" name='select_language' id="select_language" onchange="onChangeLanguage()">

									</select>
								</div>

								<div class="form-group row" style="padding-top:20px;" >
									<label for="exampleSelectd" class="col-4">Select voice</label>
									<select class="form-control form-control-solid col-8 form-control-sm" name='select_voice' id="select_voice">
									</select>
								</div>
								<div class="form-group row" style="padding-top:20px;">
									<label class='col-4' for="exampleSelects">Profile:</label>
									<select class="form-control form-control-solid col-8 form-control-sm" name='select_profile' id="select_profile">
									</select>
								</div>


								<div class="form-group row" style="padding-top:20px;">
									<label class='col-4' for="exampleSelects">Speaking speed:</label>
									<div class="col-8">
										<input type="range" onInput="onChangeSpeed(this.value)" value="<?php echo $speaking_speed;?>" class="custom-range" min="0.25" step="0.25" max="4" name="speakSpeed" id="speakSpeed">
										<div style="font-size:1rem;">Speed rating: <strong id ='speed_value'></strong></div>
									</div>
								</div>

								<div class="form-group row" style="padding-top:20px;">
									<label class='col-4' for="exampleSelects">Pitch:</label>
									<div class="col-8">
										<input type="range" class="custom-range" onInput="onChangePitch(this.value)" min="-20" max="20" value="<?php echo $pitch;?>" name='pitch' id="pitch" step="0.1">
										<div style="font-size:1rem;">Current Pitch: <strong id ='pitch_value'></strong></div>
									</div>
								</div>

								<button type="submit" class="btn btn-primary btn-save " onclick="saveConfig()" style=" width:100%">Save</button>
							</div>
						</form>
					</div>

				</div>
			</section>
			<!-- End Google API Setting page -->
			<!-- Begin Video Setting page -->
			<section class="page-3 card card-custom" hidden>
					<div>
						<h1>
							Please configure video settings below.
						</h1>
						<p>
							
						</p>
						<h3>
							Important: Make sure your image format is PNG and less than 400KB in size. This is very important. Do not use high quality image if your server spec is not good enough.
						</h3>
					</div>

				<div>

					<div>

						
						<?php
						
							if ( ! function_exists( 'wp_handle_upload' ) ) 
								require_once( ABSPATH . 'wp-admin/includes/file.php' );
							if(!empty($_FILES) && !empty($_FILES['file']))
							{
								$uploadedfile = $_FILES['file'];
								$upload_overrides = array( 'test_form' => false );
								$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
								if ( $movefile ) {
									$wpdb->query("UPDATE {$wpdb->prefix}tube_config SET pitch = '".$movefile['file']."' WHERE id = 2");
									$image_path = tubepost_db_select_query("SELECT pitch FROM {$wpdb->prefix}tube_config WHERE id = 2")[0]['pitch'];
									}
							}
							if(!tubepost_isImageExit())
							{
								$wpdb->query("UPDATE {$wpdb->prefix}tube_config SET pitch = '' WHERE id = 2");
									$image_path = tubepost_db_select_query("SELECT pitch FROM {$wpdb->prefix}tube_config WHERE id = 2")[0]['pitch'];
							}
							 ?>
							<div>
								<p><b>Current Image File:</b><?php echo $image_path;?></p>
								<form action="" method="post" enctype="multipart/form-data">
								<?php wp_nonce_field('json-import'); ?>
								
								<label for="file">Filename:</label>
								<input type="file"  name="file" id="file"><br>
								<input class='btn btn-primary' type="submit" name="save" value="save">
								</form>
							</div>
					
						
					</div>
				</div>

			</section>
			<!--End Video Setting page -->
			<!--Begin Status page -->
			<section class="page-4 card card-custom" hidden>
					<div>
						<h1>
							System Requirements
						</h1>
						<p>
							
						</p>
						<h3>
							Important
						</h3>
						<p>
							You need a high spec server if you are planning to use high quality image for your videos.
						</p>
						<p>
							<b>Recommended: Min 2GB Memory, Min 2CPUs</b>
						</p>
					</div>
				<div>

					<div class=" form-group row">
						<div class="col-4 tagName">mbstring installed</div>
						<?php if (tubepost_get_mbstring_installed() == 'YES') {?>
						<span class="label label-lg label-success label-inline mr-2 tagValue">YES</span>
						<?php } 
						if (tubepost_get_mbstring_installed() == 'NO') { ?>
						<span class="label label-lg label-danger label-inline mr-2 tagValue">NO</span>
						<?php }?>
					</div>
					<div class=" form-group row">
						<?php if (tubepost_get_curl_installed() == 'YES') {?>
						<div class="col-4 tagName">cURL installed</div>

						<span class="label label-lg label-success label-inline mr-2 tagValue">YES</span>
						<?php } 
						if (tubepost_get_curl_installed() == 'NO') { ?>
						<span class="label label-lg label-danger label-inline mr-2 tagValue">NO</span>
						<?php }?>
					</div>
					<div class=" form-group row">
						<div class="col-4 tagName">ZIP installed</div>
						<?php if(tubepost_get_zip_installed() == 'YES') {?>
						<span class="label label-lg label-success label-inline mr-2 tagValue">YES</span>
						<?php }
						if(tubepost_get_zip_installed() == 'NO') {?>
						<span class="label label-lg label-danger label-inline mr-2 tagValue">NO</span>
						<?php }?>
					</div>
					<div class=" form-group row">
						<div class="col-4 tagName">BCMath installed:</div>
						<?php if (tubepost_get_bcmath_installed() == 'YES') {?>
						<span class="label label-lg label-success label-inline mr-2 tagValue">YES</span>
						<?php }
						if (tubepost_get_bcmath_installed() == 'NO') {?>
						<span class="label label-lg label-danger label-inline mr-2 tagValue">NO</span>
						<?php }?>
					</div>
					<div class=" form-group row">
						<div class="col-4 tagName">XML installed</div>
						<?php if (tubepost_get_xml_installed() == 'YES') {?>
						<span class="label label-lg label-success label-inline mr-2 tagValue">YES</span>
						<?php }
						if (tubepost_get_xml_installed() == 'NO') {?>
						<span class="label label-lg label-danger label-inline mr-2 tagValue">NO</span>
						<?php }?>
					</div>
					<div class=" form-group row">
						<div class="col-4 tagName">DOM installed</div>
						<?php if (tubepost_get_dom_installed() == 'YES') {?>
						<span class="label label-lg label-success label-inline mr-2 tagValue">YES</span>
						<?php }
						if (tubepost_get_dom_installed() == 'NO') {?>
						<span class="label label-lg label-danger label-inline mr-2 tagValue">NO</span>
						<?php }?>
					</div>
					<div class=" form-group row">
						<div class="col-4 tagName">shell_exec() enabled:</div>
						<?php if (tubepost_get_shell_enabled() == 'YES') {?>
						<span class="label label-lg label-success label-inline mr-2 tagValue">YES</span>
						<?php }
						if (tubepost_get_shell_enabled() == 'NO') {?>
						<span class="label label-lg label-danger label-inline mr-2 tagValue">NO</span>
						<?php }?>
					</div>
					<div class=" form-group row">
						<div class="col-4 tagName">ffmpeg 4.X.X installed</div>
						<?php if (tubepost_is_ffmpeg_version4xx() == 'YES') {?>
						<span class="label label-lg label-success label-inline mr-2 tagValue">YES</span>
						<?php }
						if (tubepost_is_ffmpeg_version4xx() == 'NO') {?>
						<span class="label label-lg label-danger label-inline mr-2 tagValue">NO</span>
						<?php }?>
					</div>
					<div class=" form-group row">
						<div class="col-4 tagName">ffmpeg exists in /usr/bin/ffmpeg ?:</div>
						<?php if (tubepost_get_ffmpeg_installed() == 'YES') {?>
						<span class="label label-lg label-success label-inline mr-2 tagValue">YES</span>
						<?php }
						if (tubepost_get_ffmpeg_installed() == 'NO') {?>
						<span class="label label-lg label-danger label-inline mr-2 tagValue">NO</span>
						<?php }?>
					</div>
				</div>
			</section>
			<!--End Status page -->
			<!--Begin Log page -->
			<section id="log_table" class="page-5 card card-custom" hidden style="max-width: 1500px;">
					<div>
						<h1>
							Library
						</h1>
						<p>
							All audios are located here: <b>/output/audio/</b>
						</p>
						<p>
							All videos are located here: <b>/output/video/</b>
						</p>
						<p>
							Only last 50 content displayed on this page. Please do not forget to clean this folder time to time.
						</p>
						<p>
							<b>Note:</b> Video generation takes some time depending on your server spec. Please wait couple of minutes before downloading the video.
						</p>
					</div>
				<div>
					<!--begin::Search Form-->
					<div class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded" id="kt_datatable" style="">

						<table class="datatable-table" style="width:100%;">
							<thead class="datatable-head">
								
							
							<tr class="datatable-row">
								<th class="datatable-cell " >No</th>
								<th class="datatable-cell " >VideoName</th>
								<th class="datatable-cell " >AudioName</th>
								<th class="datatable-cell " >PostDate</th>
								<th class="datatable-cell " >Status</th>
								<th class="datatable-cell " >Download</th>
							</tr>
							</thead>
							<tbody class="datatable-body ">
								<tr></tr>
							</tbody>
						</table>
					</div>
				</div>
			</section>
			<!--End Log page -->
		<section class="page-0" hidden>
			<div class="card card-custom">
					<div>
						<h1>
							Welcome to PostTube.
						</h1>
						<h4>
							Please make sure to complete below steps before start.
						</h4>
						<p>
						</p>
							<ol>
  <li>Check requirements and make sure you have everything installed.</li>
  <li>Go to API Settings page and import your Google Text-to-Speech keyfile. You can follow the tutorial <a href="https://posttube.co/creating-and-importing-google-text-to-speech-key-file/">here</a>.</li>
  <li>Go to Voice Settings page and select the language and voice that you would like to use.</li>
  <li>Go to Video Settings page and select an image for your videos. <b>Important:</b> Make sure your image format is PNG and less than 400KB in size. This is very important. Do not use high quality image if your server spec is not good enough.</li>
<li>Done! Now whenever you publish a post, a video and audio will be generated automatically. You can download them in Library page.</li>
</ol> 

<div>
							<?php
									if (!empty($json_path))
						{
							echo '<span style="color:green;text-align:center;">Key file exists!</span>';
						}
						else {
							echo '<span style="color:red;text-align:center;">Key file missing! You can not use plugin without key file.</span>';
						}
						?>
						</div>
						<div>
							<?php
							if (!empty($image_path))
						{
							echo '<span style="color:green;text-align:center;">Image exists!</span>';
						}
						else {
							echo '<span style="color:red;text-align:center;">Image missing! You can not generate video without an image.</span>';
						}
						
						?>
							<form onsubmit="return false;">
							<?php 
								if(tubepost_isApiKeyExit() && tubepost_isImageExit())
								{
							?>
							<input type='checkbox' id="toggle_activition" <?php if($activate_status) echo "checked";?> >
							<label  for="checkbox">Activate plugin?</label>
							<br>
							<button type='submit' onclick="toggleActivite()" class='btn btn-primary'>Save</button>
							<?php }
							else {
							?>
							<input type='checkbox' disabled>
							<label  for="checkbox">Activate plugin?</label>
							<?php }?>
							
							</form>
						</div>
					</div>
			</div>
		</section>
			</div>
		<div id='slideshow' class="swal2-container swal2-center swal2-backdrop-show" style="display:none;">
			<div aria-labelledby="swal2-title" aria-describedby="swal2-content" class="swal2-popup swal2-modal swal2-icon-success swal2-show" tabindex="-1" role="dialog" aria-live="assertive" aria-modal="true" style="display: flex;">
				<div class="swal2-header">
					<div class="swal2-icon swal2-success swal2-icon-show" hidden style="display: flex;">
						<div class="swal2-success-circular-line-left" style="background-color: rgb(255, 255, 255);"></div>
						<span class="swal2-success-line-tip"></span> <span class="swal2-success-line-long"></span>
						<div class="swal2-success-ring"></div>
						<div class="swal2-success-fix" style="background-color: rgb(255, 255, 255);"></div>
						<div class="swal2-success-circular-line-right" style="background-color: rgb(255, 255, 255);"></div>
					</div>
					<div class="swal2-icon swal2-error swal2-icon-show" style='display:flex;' hidden><span class="swal2-x-mark">
						<span class="swal2-x-mark-line-left"></span>
						<span class="swal2-x-mark-line-right"></span>
						</span>
					</div>
				</div>
				<div class="swal2-content">
					<div id="swal2-content" class="swal2-html-container" style="display: block;">Settings saved! </div>
				</div>
				<div class="swal2-actions"><button type="button" onclick="hide_modal()" id='dialog_ok_button' class="swal2-confirm btn font-weight-bold btn-light-primary" aria-label="" style="display: inline-block;">Ok, got it!</button></div>
			</div>
		</div>

	
<script type="text/javascript">
	
	var data = '<?php echo $json ?>'; 
	var root_url = '<?php echo  plugin_dir_url(__FILE__) ?>';
	var dataJSONArray = JSON.parse(data);
	var plugin_url = '<?php echo  plugins_url() ?>';
</script>
<script>
	var isRequest = <?php echo $isRequest?>;
	jQuery(document).ready(function(){
		
		for(var i = 1; i<6; i++)
			{
				jQuery('.nav-tabs .nav-item #link_'+i).css('border-bottom','1px');
			}
			
			refresh_nav();
			init_selects();
		
		document.getElementById('speed_value').innerHTML = <?php echo $speaking_speed?>;
		document.getElementById('pitch_value').innerHTML = <?php echo $pitch?>;	
		
		if(isRequest == true)
		{
			pageChange(4);
		}
		else 
		{
			pageChange(0);
		}
		
		dataEntry();
		});
	function refresh_nav() {
		for(var i = 0; i < 6; i++)
		{
			jQuery('.nav-tabs .nav-item #link_'+i).attr('hidden',true);
			if(isRequest == false)
			{
				if(i == 2)
				{
					<?php
						$isExistKey = tubepost_isApiKeyExit();
						if(!$isExistKey)
						{?>
						continue;
						<?php } else {?>
							jQuery('#google_link').removeAttr('hidden');
						<?php
						}
						?>
				}
				jQuery('.nav-tabs .nav-item #link_'+i).removeAttr('hidden');
			}
			else 
			{

				jQuery('.nav-tabs .nav-item #link_4').removeAttr('hidden');

			}
			
			
		}
	}

	function show_google() {
		jQuery('.nav-tabs .nav-item #link_2').removeAttr('hidden');
		jQuery('#google_link').removeAttr('hidden');
		
// 		setTimeout(reload, 1000);
	}
	
	function reload() {
		location.reload();
	}

	jQuery(document).on('change', "#file-key", function() {
		if (jQuery(this).val()) {
			var filename = jQuery(this).val().split("\\");
			filename = filename[filename.length - 1];
			jQuery('.fileName-key').text(filename);
		}
	});
	jQuery(document).on('change', "#file-image", function() {
		if (jQuery(this).val()) {
			var filename = jQuery(this).val().split("\\");
			filename = filename[filename.length - 1];
			jQuery('.fileName-image').text(filename);
		}
	});
	
	function pageChange(id) {
		jQuery('section').attr('hidden', true);
		jQuery('.page-' + id).removeAttr('hidden');
		jQuery('button').removeClass('active');
		jQuery('#link_' + id).addClass('active');
		for(var i = 0; i < 6; i++)
		{
			jQuery('.nav-tabs .nav-item #link_'+i).removeAttr('style');
			if(i == id) {continue;}
			else 
			{
				jQuery('.nav-tabs .nav-item #link_'+i).css('border-bottom','1px');
			}

		}
	}

	function init_selects()
	{
		var selector_lang = document.getElementById('select_language');
		document.getElementById('select_language').innerHTML = "";
		var language_names = new Array();
		var language_codes = new Array();
		<?php 
		$temp = '';
		if (is_array($voices) || is_object($voices))
		{
		foreach ( $voices as $voice ) { 

		if($temp == esc_html( get_tube_lang_by_code( $voice->getLanguageCodes() ) ) ) {continue;} ?>
		language_codes.push('<?php echo esc_html($voice->getLanguageCodes()[0]); ?>');
		language_names.push('<?php echo esc_html( get_tube_lang_by_code( $voice->getLanguageCodes() ) ); ?>');			
		<?php $temp = esc_html( get_tube_lang_by_code( $voice->getLanguageCodes() ) ); }}?>
			for(var i = 0; i < language_names.length; i++)
			{
			  let newOption = new Option(language_names[i], language_codes[i]);
			  selector_lang.add(newOption,i);  
			}
		var selectedLanguage = '<?php echo $select_language;?>' ;
		var selectedIndex = language_codes.indexOf(selectedLanguage);
		document.getElementById("select_language").selectedIndex = selectedIndex;
		init_audio_profile_select();
		onChangeLanguage();
	}
	
	function init_audio_profile_select()
	{
		var select_profile = document.getElementById('select_profile');
		document.getElementById('select_profile').innerHTML = "";
		var audio_profile_names = new Array('wearable-class-device','handset-class-device','headphone-class-device','small-bluetooth-speaker-class-device','medium-bluetooth-speaker-class-device','large-home-entertainment-class-device','large-automotive-class-device','telephony-class-application');
		
		var audio_profile_values = new Array('Smart watches and other wearables','Smartphones','Earbuds or headphones','Small home speakers','Smarthome speakers','Home entertainment systems','Car speakers','Interactive voice response');
		
		for(var i = 0; i < audio_profile_names.length; i++)
			{
			  let newOption = new Option(audio_profile_values[i], audio_profile_names[i]);
			  select_profile.add(newOption,i);  
			}
		var selectedprofile = '<?php echo $select_profile;?>' ;
		var selectedIndex = audio_profile_names.indexOf(selectedprofile);
		document.getElementById("select_profile").selectedIndex = selectedIndex;
	}
	function onChangeLanguage()
	{
		var lang = document.getElementById('select_language').value;
		 var categories_array = new Array();
		 var indexed_voice_array = new Array();
		<?php 
		if (is_array($voices) || is_object($voices))
		{
		foreach ( $voices as $voice ) { ?>
		categories_array.push('<?php echo esc_html( $voice->getLanguageCodes()[0] );?>-<?php echo get_tube_voice_type( $voice->getName() );?>-<?php echo esc_html( substr( $voice->getName(), -1 ) );?>');			<?php }}?>	
		var select_voice = document.getElementById('select_voice');
		document.getElementById('select_voice').innerHTML = "";
		if(document.getElementById("select_voice").length == 0)
		{
        for(var i = 0; i < categories_array.length; i++)
        {
		  if(!categories_array[i].includes(lang)){continue}
		  	indexed_voice_array.push(categories_array[i]);
          let newOption = new Option(categories_array[i], categories_array[i]);
          select_voice.add(newOption,i);  
        }
       }
       	var selectedprofile = '<?php echo $select_voice;?>' ;
		var selectedIndex = indexed_voice_array.indexOf(selectedprofile);
		if(selectedIndex == -1) {selectedIndex = 0;}
		document.getElementById("select_voice").selectedIndex = selectedIndex;
	}
	
	function saveConfig() {
        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php')?>',
            type: 'POST',
            data: {
            	action:'settings_save_action',
            	lang : jQuery('#select_language').val(),
            	voice : jQuery('#select_voice').val(),
            	profile : jQuery('#select_profile').val(),
            	speed : jQuery('#speakSpeed').val(),
            	pitch : jQuery('#pitch').val(),
        	},
            success: function(response) {
                show_modal('Settings saved!');
            },
			
            error: function(response) {
                alert('faild');
            }
        })
    }
	
	function toggleActivite() {
		var val = document.getElementById("toggle_activition").checked;
		jQuery.ajax({
			url: '<?php echo admin_url('admin-ajax.php')?>',
			type: 'POST',
			data: {
				action:'activition_action',
				val : val,
			},
			success: function(response) {
				show_modal('Settings saved!');
			},

			error: function(response) {
				alert('faild');
			}
		})
	}

	function onChangeSpeed(val)
    {
      document.getElementById('speed_value').innerHTML = val;
    }

    function onChangePitch(val)
    {
    	document.getElementById('pitch_value').innerHTML = val;	
    }
	
	function hide_modal()
	{
		jQuery('#slideshow').css('display','none');
		jQuery('.swal2-success').attr('hidden',true);
		jQuery('.swal2-error').attr('hidden',true);
	}

	function show_modal(id)
	{
		jQuery('#slideshow').removeAttr('style');
		jQuery('.swal2-success').removeAttr('hidden');
		document.getElementById("swal2-content").innerHTML = id;
	}
	function show_Error_modal(message)
	{
		jQuery('#slideshow').removeAttr('style');
		jQuery('.swal2-error').removeAttr('hidden');
		document.getElementById("swal2-content").innerHTML = message;	
	}

	function download_file(url,filename) {

		var element = document.createElement('a');
		console.log("url = " + url);
		console.log("filename = " + filename);
		
		element.setAttribute('href', url);
		element.setAttribute('download', filename);

		element.style.display = 'none';
		document.body.appendChild(element);

		element.click();

		document.body.removeChild(element);
	}
	function dataEntry()
	{
		
		
		  // var table = document.getElementById("log_table");
		  table = jQuery('#log_table');
	
		  for(let i = 0; i < dataJSONArray.length; i++)
		  {
			  var videoname = dataJSONArray[i]['video_name'];
			  var audioname = dataJSONArray[i]['audio_name'];
		  	if(videoname.length > 22)
			{
				videoname = videoname.substring(0,19)+"..."+"mp4";
			}
			  if(audioname.length > 22)
			{
				audioname = audioname.substring(0,19)+"..."+"mp3";
			}
			 
			  dataJSONArray[i]['id'] = dataJSONArray.length - dataJSONArray[i]['id'] +1;
		  	jQuery('#log_table tr:last').after(
		  	
		  	'<tr class="datatable-row"><td class="datatable-cell">'+dataJSONArray[i]['id']+'</td>'+'<td class="datatable-cell">'+videoname+'</td>'+'<td class="datatable-cell">'+audioname+'</td>'+'<td class="datatable-cell">'+dataJSONArray[i]['post_date']+'</td><td class="datatable-cell">'+dataJSONArray[i]['post_status']+'</td><td id="action_'+dataJSONArray[i]['id']+'" class="datatable-cell">\<a class="download_audio" onclick="download_file(\'<?php echo $output_path;?>/tube_post/audio/'+dataJSONArray[i]['audio_name']+'\',\''+dataJSONArray[i]['audio_name']+'\')" style="box-shadow: 0 0 black;"> \
                                <span class="svg-icon svg-icon-md">\
                                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">\
                                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">\
                                            <polygon points="0 0 24 0 24 24 0 24"/>\
                                            <path d="M5.85714286,2 L13.7364114,2 C14.0910962,2 14.4343066,2.12568431 14.7051108,2.35473959 L19.4686994,6.3839416 C19.8056532,6.66894833 20,7.08787823 20,7.52920201 L20,20.0833333 C20,21.8738751 19.9795521,22 18.1428571,22 L5.85714286,22 C4.02044787,22 4,21.8738751 4,20.0833333 L4,3.91666667 C4,2.12612489 4.02044787,2 5.85714286,2 Z" fill="#000000" fill-rule="nonzero" opacity="0.3"/>\
                                            <path d="M9.83333333,17 C8.82081129,17 8,16.3159906 8,15.4722222 C8,14.6284539 8.82081129,13.9444444 9.83333333,13.9444444 C10.0476105,13.9444444 10.2533018,13.9750785 10.4444444,14.0313779 L10.4444444,9.79160113 C10.4444444,9.47824076 10.6398662,9.20124044 10.9268804,9.10777287 L14.4407693,8.0331119 C14.8834716,7.88894376 15.3333333,8.23360047 15.3333333,8.71694016 L15.3333333,9.79160113 C15.3333333,10.1498215 14.9979332,10.3786009 14.7222222,10.4444444 C14.3255297,10.53918 13.3070112,10.7428837 11.6666667,11.0555556 L11.6666667,15.5035214 C11.6666667,15.5583862 11.6622174,15.6091837 11.6535404,15.6559869 C11.5446237,16.4131089 10.771224,17 9.83333333,17 Z" fill="#000000"/>\
                                        </g>\
                                    </svg>\
                                </span>\
                                </a>\
                                <a class = "download_video" onclick="download_file(\'<?php echo $output_path;?>/tube_post/video/'+dataJSONArray[i]['video_name']+'\',\''+dataJSONArray[i]['video_name']+'\')" style="box-shadow: 0 0 black;">\
                                <span class="svg-icon svg-icon-md">\
                                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">\
                                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">\
                                            <rect x="0" y="0" width="24" height="24"/>\
                                            <rect fill="#000000" x="2" y="6" width="13" height="12" rx="2"/>\
                                            <path d="M22,8.4142119 L22,15.5857848 C22,16.1380695 21.5522847,16.5857848 21,16.5857848 C20.7347833,16.5857848 20.4804293,16.4804278 20.2928929,16.2928912 L16.7071064,12.7071013 C16.3165823,12.3165768 16.3165826,11.6834118 16.7071071,11.2928877 L20.2928936,7.70710477 C20.683418,7.31658067 21.316583,7.31658098 21.7071071,7.70710546 C21.8946433,7.89464181 22,8.14899558 22,8.4142119 Z" fill="#000000" opacity="0.3"/>\
                                        </g>\
                                    </svg>\
                                </span>\
                                </a>\
                                <a class = "failed_info" onclick="show_Error_modal(\''+dataJSONArray[i]['failed_reason']+'\')" style="box-shadow: 0 0 black;" hidden>\
                                    <span class="svg-icon svg-icon-danger">\
                                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">\
                                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">\
                                            <rect x="0" y="0" width="24" height="24"/>\
                                            <circle fill="#000000" opacity="0.3" cx="12" cy="12" r="10"/>\
                                            <rect fill="#000000" x="11" y="7" width="2" height="8" rx="1"/>\
                                            <rect fill="#000000" x="11" y="16" width="2" height="2" rx="1"/>\
                                        </g>\
                                        </svg>\
                                    </span>\
                                </a>\
                        </td></tr>'
		  	);	
		if(dataJSONArray[i]['post_status'] != 'success')
		  	{
		  		id = i+1;
		  		jQuery('#action_'+id+' .failed_info').removeAttr('hidden');

		  	}
		  }
		  
		 
	}
	
	var uploadField = document.getElementById("file");

	uploadField.onchange = function() {
		if(this.files[0].size > 409600){
		   alert("File is too big!");
		   this.value = "";
		};
	};
	
	jQuery('.container').removeAttr('hidden');
</script>
