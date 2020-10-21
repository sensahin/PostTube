<?php

include_once('config.php');

function tubepost_isEnablePlugin() {
	if(tubepost_get_mbstring_installed() == 'NO' || tubepost_get_curl_installed() == 'NO' || tubepost_get_zip_installed() == 'NO' || tubepost_get_bcmath_installed() == 'NO' || tubepost_get_xml_installed() == 'NO' || tubepost_get_dom_installed() == 'NO' || tubepost_get_shell_enabled() == 'NO' || tubepost_get_ffmpeg_installed() == 'NO') 
		return true;
	else return false;
}

function tubepost_isApiKeyExit() {
	try {
		$json_path=tubepost_db_select_query("SELECT pitch FROM wp_tube_config WHERE id = 3")[0]['pitch'];
		
		$file_exists = file_exists ( $json_path );

		return $file_exists ? true : false;
	} catch (Exception $e) {
	    	return false;
	}
}

function tubepost_isPluginEnable() {
	try {
		$val = tubepost_db_select_query("SELECT pitch FROM wp_tube_config WHERE id = 4")[0]['pitch'];

		return $val;
	} catch (Exception $e) {
	    	return false;
	}
}

function tubepost_isImageExit() {
	try {
		$image_path=tubepost_db_select_query("SELECT pitch FROM wp_tube_config WHERE id = 2")[0]['pitch'];
		
		$file_exists = file_exists ( $image_path );

		return $file_exists ? true : false;
	} catch (Exception $e) {
	    	return false;
	}
}

function tubepost_get_shell_enabled () {
	try {
		$shell_enable = is_callable('shell_exec') && false === stripos(ini_get('disable_functions'), 'shell_exec');

		return $shell_enable ? 'YES' : 'NO';
	} catch (Exception $e) {
	    return 'NO';
	}
}

function tubepost_get_zip_installed() {
	try {
		$zip_installed = extension_loaded( 'zip' );

		return $zip_installed ? 'YES' : 'NO';
	} catch (Exception $e) {
	    return 'NO';
	}
}

function tubepost_get_dom_installed() {
	try {
		$dom_installed = extension_loaded( 'dom' );

		return $dom_installed ? 'YES' : 'NO';
	} catch (Exception $e) {
	    return 'NO';
	}
}

function tubepost_get_xml_installed() {
	try {
		$xml_installed = extension_loaded( 'xml' );

		return $xml_installed ? 'YES' : 'NO';
	} catch (Exception $e) {
	    return 'NO';
	}
}

function tubepost_get_curl_installed() {
	try {
		$curl_installed = extension_loaded( 'curl' );

		return $curl_installed ? 'YES' : 'NO';
	} catch (Exception $e) {
	    return 'NO';
	}
}


function tubepost_get_bcmath_installed () {
	try {
		$bcmath_installed = extension_loaded( 'bcmath' );

		return $bcmath_installed ? 'YES' : 'NO';
	} catch (Exception $e) {
	    return 'NO';
	}
}

function tubepost_get_mbstring_installed() {
	try {
		$mbstring_installed = extension_loaded( 'mbstring' );

		return $mbstring_installed ? 'YES' : 'NO';
	} catch (Exception $e) {
	    return 'NO';
	}
}

function tubepost_get_ffmpeg_installed () {
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
    {
        $ffmpeg = pclose(popen("ffmpeg -version", "r")); // windows
    }
    else {
		$ffmpeg = trim(shell_exec('ffmpeg -version'));
    }
	if (empty($ffmpeg))
	{
	    return 'NO';
	}
	else
		return 'YES';
}

function tubepost_is_ffmpeg_version4xx () {
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
    {
        $ffmpeg = pclose(popen("ffmpeg -version", "r")); // windows
    }
    else {
		$ffmpeg = trim(shell_exec('ffmpeg -version'));
    }
	if (strtoupper(substr($ffmpeg, 15, 1)) == "4")
	{
	    return 'YES';
	}
	else
		return 'NO';
}

?>