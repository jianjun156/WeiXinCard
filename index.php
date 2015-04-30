<?php
include "wxcard/Wxcard.php";
include "wxcard/WxPoi.php";
$appid = "XXXXXXXXXXXXXXXXXX";
$appserct = "XXXXXXXXXXXXXXXXXXXXXXXXXXXX";
$wxcard = new WxCard($appid, $appserct);
$wxpoi = new WxPoi($appid, $appserct);
$cardlist = $wxcard->card_batchget(0, 10); //拉取卡券列表
s($cardlist);
$poilist = $wxpoi->poi_getpoilist(0, 10); //拉取门店列表
s($poilist);
//调试打印函数
function s($val='',$exit=0){

	$val=$val===''?time():$val;
	echo "<pre>";
	print_r($val);
	echo "</pre>";
	echo("<hr>");
	if($exit)exit;
	return;
	echo("<pre style='background:#ffffff'>");
	if(is_bool($val)){
		var_dump($val);
	}else{
		print_r($val);
	}
	echo("</pre>");
	echo("<hr>");
}