<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arDescription = Array(
		"NAME"=>GetMessage("GD_SONET_USER_ABSENCE_NAME"),
		"DESCRIPTION"=>GetMessage("GD_SONET_USER_ABSENCE_DESC"),
		"ICON"=>"",
		"GROUP"=> Array("ID"=>"sonet"),
		"NOPARAMS"=>"Y",
		"SU_ONLY" => true,
		"ABSENCE_ONLY"=> true,
		"CAN_BE_FIXED"=> true,
	);
?>