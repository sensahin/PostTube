<?php

try{
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET,DB_USER,DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}catch(PDOException $e){
    error("DB_ERROR","Database Connection failed->" . $e->getMessage());
    die();
}


function tubepost_db_select_query($query='',$params=array()){
	global $pdo;
	try{
		$stmt = $pdo->prepare($query);
    	$stmt-> execute(array_values((array)$params));
    	return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}catch(PDOException $e){
    	$error="</br>error:".$e->getMessage();
    	$error.="</br>query:".$query;
    	error("DB_ERROR",$error);
    	return array();
    }
}

?>
