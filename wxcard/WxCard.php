<?php
include_once("CommonUtil.php");
/**
 * 微信卡包接口SDK
 * @author danjar@qq.com
 * 1、创建卡券  card_create
 * 2、批量导入门店信息   
 * 3、拉取门店列表
 * 4、获取颜色列表接口
 * 5、生成卡券二维码
 * 6、消耗code
 * 7、code解码接口
 * 8、删除卡券
 * 9、查询code
 * 10、批量查询卡列表
 * 11、查询卡券详情
 * 12、更改code
 * 13、设置卡券失效接口
 * 14、更改卡券信息接口
 * 15、设置测试用户白名单
 * To-do
 * 16、激活/绑定会员卡
 * 17、会员卡交易
 * 18、更新电影票
 * 19、在线选座
 * 20、更新红包金额
 * JSAPI
 * 21、添加到卡包 openCardDetail
 * 22、拉起卡券列表 chooseCard
 * demo
 *      $wxcard = new WxCard("wxxxxxxxxxxxxxxxxx","XXXXXXXXXXXXXXXXXXXXXXXXXXXXX");
		$base_info["logo_url"] =  "http://www.supadmin.cn/uploads/allimg/120216/1_120216214725_1.jpg"; //300*300
		$base_info["brand_name"] = "测试海底捞";//限制12个汉字
		$base_info["code_type"] = 3; //"CODE_TYPE_TEXT":文本   "CODE_TYPE_BARCODE":一维码    "CODE_TYPE_QRCODE":二维码；
		$base_info["title"] =  "132元双人火锅"; //限制9个汉字
		$base_info["color"] =  "Color010"; //范围Color010 到 Color100 
		$base_info["notice"] =  "使用时向服务员"; //限制9个汉字
		$base_info["service_phone"] =  "020-88888888";
		$base_info["description"] =  "不可与其他优惠同享\n"; //上限为 1000 个汉字
		//有效期，type为1时为固定日期区间，单位为秒
		//第一个参数表示固定日期区间， 固定日期区间专用， 表示起用时间 。（单位为秒）
		//第二个参数表示固定日期区间，固定日期区间专用， 表示结束时间。（单位为秒）
		$base_info["date_info"] = array("type"=>1,"arg0"=>strtotime("+2 days"),"arg1"=>strtotime("+10 days"));
		//type为2时为固定时长（自领取后按天算）
		//第一个参数表示固定时长专用， 表示自领取后多少天内有效。（单位为天）
		//第二个参数表示固定时长专用，  表示自领取后多少天开始生效。（单位为天）
		//$base_info["date_info"] = array("type"=>2,"arg0"=>10,"arg1"=>3); 
		$base_info["sku"] = 50000;
		$extra_data["deal_detail"] = "以下锅底2 选1（有菌王锅、麻辣锅、大骨锅、番茄锅、清补凉锅、酸菜鱼锅可选）：";
		//生成券
		//$ret = $wxcard->card_create(2, $base_info,$extra_data);
	    //更新卡券
	    //$ret = $wxcard->card_update("XXXXXXXXXXXXXXXXXXXXX",2,$base_info,array(1,strtotime("-1 days"),strtotime("+5 days")));
	    //设置白名单
		//$wxcard->card_testwhitelist_set_byusername(array("armycome")); 
	   
 */
class WxCard extends CommonUtil{
	// 接口网关地址
	private static $APIHOST = 'https://api.weixin.qq.com/';
	// 卡券类型
	private	$CARD_TYPE = Array("GENERAL_COUPON",
			"GROUPON", "DISCOUNT",
			"GIFT", "CASH", "MEMBER_CARD",
			"SCENIC_TICKET", "MOVIE_TICKET","BOARDING_PASS","LUCKY_MONEY");
	//卡券code展示类型
	private	$CODE_TYPE = Array("CODE_TYPE_TEXT","CODE_TYPE_QRCODE","CODE_TYPE_BARCODE");
	// appid
	private $appid;
	// appsecret
	private $appsecret;
	// 授权token
	private $access_token; 
	function __construct($appid,$appserct) {
	
			$this->appid = $appid;
			$this->appsecret = $appserct;
			$this->access_token = $this->get_access_token();
	     	//echo $this->access_token;
			if(!$this->access_token){
				die("无法获取接口票据，appid或appsercet填写错误！");
			}
	}
    /**
     * POST wreapper for oAuthRequest.
     *
     * @return array
     */
     function postapi($apiname,$data = array()) {
     	  $apiurl =self::$APIHOST.$apiname."?access_token=".$this->access_token;
          $response = $this->oAuthRequest($apiurl,$data,true);
          return json_decode($response, true);
     }
     /**
      * get wreapper for oAuthRequest.
      *
      * @return array
      */
     function getapi($apiname,$data = array()) {
     	$response = $this->oAuthRequest(self::$APIHOST.$apiname,$data,false);
     	return json_decode($response, true);
     }
     /**
      * 获取access_token
      * @return mixed
      */
     private  function get_access_token(){
     	$param = array();
     	$param["grant_type"]="client_credential";
     	$param["appid"]=$this->appid;
     	$param["secret"]=$this->appsecret;
     	$ret = $this->getapi("cgi-bin/token",$param);
     	if(!isset($ret["errcode"]))//AppSecret 错误
     	{
     		//@todo 注意需要缓存票据【memcache、db、file】，建议缓存一小时，每天获取票据有次数限制
     		return $ret["access_token"];
     	}else{
     		return false;
     	}
     }
     /**
      * 根据数据库中存的merchant_type获取对应的$card_type
      */
     private function get_card_type($merchant_type){
     	return $this->CARD_TYPE[$merchant_type-1];
     }
     /**
      * 根据数据库中存的merchant_type获取对应的$card_type
      */
     private function get_code_type($cancel_after_verification){
     	return $this->CODE_TYPE[$cancel_after_verification-1];
     }
     /**
      * 创建卡券
      * @param $merchant_type : 券类型  *[必填]
      * @param $base_info ： 基本的卡券字段数据    *[必填]
      * @param $extra_data :不同类型卡券特有的字段   *[必填]
      * @param $custom_cells:自定义cell
      * $custom_cells = array(
      * "custom_url_name"=>"xxxxx",           	//使用场景 【最多5个汉字】
      * "custom_url"=>"http://www.xxx.com",   	//使用场景链接
      * "custom_url_sub_title"=>"xxxxxx",     	//使用场景提示语 【最多6个汉字】
      * "promotion_url_name"=>"xxxxx",        	//营销场景 【最多5个汉字】
      * "promotion_url"=>"http://www.xxx.com",	//营销场景链接
      * "promotion_url_sub_title"=>"xxxxxx"   	//营销场景提示语 【最多6个汉字】
      * );
      * 其他参数均为基本卡券字段中的选填项 [非必填]
      * @return array
      */
     function card_create($merchant_type,$base_data,$extra_data,$location_id_list="",$sub_title="",$use_limit="",$get_limit="",$url_name_type="",$custom_cells=false,$source="",$can_share=true,$can_give_friend=true,$use_custom_code=false,$bind_openid=false){
        //exit();
        $card_type = $this->get_card_type($merchant_type);
        //有效期
        $date_info =  new DateInfo($base_data["date_info"]["type"], $base_data["date_info"]["arg0"], $base_data["date_info"]["arg1"]);
        //库存
        $sku=  new Sku($base_data["sku"]); 
        //颜色转换
        $colorname = "Color010";
        $colorinfo = $this->card_getcolors();
        foreach ($colorinfo["colors"] as $color){
        	if($color["value"]==$base_data["color"]){
        		$colorname = $color["name"];
        		break;
        	}
        }
        //必填基础字段
        $code_type=$this->get_code_type($base_data["code_type"]);
        $base_info = new BaseInfo($base_data["logo_url"],$base_data["brand_name"],$code_type
        		,$base_data["title"],$colorname, $base_data["notice"], $base_data["service_phone"]
        		,$base_data["description"],$date_info,$sku);
        
        $card = new Card($card_type, $base_info);
        //选填字段
        $base_info->set_location_id_list($location_id_list);
        $base_info->set_sub_title($sub_title);
        $base_info->set_use_limit($use_limit);
        $base_info->set_get_limit($get_limit);
        $base_info->set_use_custom_code( $use_custom_code );
        $base_info->set_bind_openid( $bind_openid );
        $base_info->set_can_share( $can_share );
        $base_info->set_can_give_friend($can_give_friend);
        $base_info->set_url_name_type($url_name_type);
        $base_info->set_source($source);
        //自定义cell
        if(is_array($custom_cells)){
        	$base_info->set_custom_url_name($custom_cells["custom_url_name"]);
        	$base_info->set_custom_url($custom_cells["custom_url"]);
        	$base_info->set_custom_url_sub_title($custom_cells["custom_url_sub_title"]);
        	$base_info->set_promotion_url_name($custom_cells["promotion_url_name"]);
        	$base_info->set_promotion_url($custom_cells["promotion_url"]);
        	$base_info->set_promotion_url_sub_title($custom_cells["promotion_url_sub_title"]);
        }
        //根据卡券类型设置卡券数据
        switch ($card_type)
        {
        	case $this->CARD_TYPE[0]: //通用卡
        		if(!isset($extra_data["default_detail"]))
        		exit("default_detail must be set!");
        		$card->get_card()->set_default_detail($extra_data["default_detail"]); //必填
        		break;
        		
        	case $this->CARD_TYPE[1]: //团购卡
        		if(!isset($extra_data["deal_detail"]))
        			exit("deal_detail must be set!");
        		$card->get_card()->set_deal_detail($extra_data["deal_detail"]); //必填
        		break;
        		
        	case $this->CARD_TYPE[2]: //折扣卡
        		if(!isset($extra_data["discount"]))
        			exit("discount must be set!");
        		$card->get_card()->set_discount($extra_data["discount"]); //必填
        		break;
        		
        	case $this->CARD_TYPE[3]: //礼品卡
        		if(!isset($extra_data["gift"]))
        			exit("gift must be set!");
        		$card->get_card()->set_gift($extra_data["gift"]); //必填
        		break;
        		
        	case $this->CARD_TYPE[4]: //代金券
        		if(isset($extra_data["least_cost"]))
        		$card->get_card()->set_least_cost($extra_data["least_cost"]);
        		
        		if(!isset($extra_data["reduce_cost"]))
        			exit("reduce_cost must be set!");
        		$card->get_card()->set_reduce_cost($extra_data["reduce_cost"]); //必填
        		break;
        		
        	case $this->CARD_TYPE[5]: //会员卡
        		if(!isset($extra_data["supply_bonus"])||!isset($extra_data["supply_balance"])||!isset($extra_data["prerogative"]))
        			exit("supply_bonus and supply_balance and prerogative must be set!");
        		$card->get_card()->set_supply_bonus($extra_data["supply_bonus"]); //必填
        		$card->get_card()->set_supply_balance($extra_data["supply_balance"]);//必填
        		$card->get_card()->set_prerogative($extra_data["prerogative"]);//必填
        		//bind_old_card_url or activate_url must be set one!
        		if(!isset($extra_data["bonus_rules"])&&!isset($extra_data["bonus_rules"]))
        		{
        			exit("bind_old_card_url or activate_url must be set one!");
        			
        		}else{
        			if(isset($extra_data["bind_old_card_url"])){
        				$card->get_card()->set_bind_old_card_url($extra_data["bind_old_card_url"]);
        			}
        			if(isset($extra_data["activate_url"])){
        				$card->get_card()->set_activate_url($extra_data["activate_url"]);
        			}
        		}
        		if(isset($extra_data["bonus_cleared"]))
        		$card->get_card()->set_bonus_cleared($extra_data["bonus_cleared"]);
        		
        		if(isset($extra_data["bonus_rules"]))
        		$card->get_card()->set_bonus_rules($extra_data["bonus_rules"]);
        		
        		if(isset($extra_data["balance_rules"]))
        		$card->get_card()->set_balance_rules($extra_data["balance_rules"]);
        		
        		break;
        		
        	case $this->CARD_TYPE[6]: //门票
        		if(isset($extra_data["ticket_class"]))
        		$card->get_card()->set_ticket_class($extra_data["ticket_class"]);
        		
        		if(isset($extra_data["guide_url"]))
        		$card->get_card()->set_guide_url($extra_data["guide_url"]);
        		break;
        		
        	case $this->CARD_TYPE[7]: //电影票
        		if(isset($extra_data["detail"]))
        		$card->get_card()->set_detail($extra_data["detail"]);
        		break;
        		break;
        		
        	case $this->CARD_TYPE[8]: //飞机票
        		if(!isset($extra_data["from"])||!isset($extra_data["to"])||!isset($extra_data["flight"])||!isset($extra_data["departure_time"])||!isset($extra_data["landing_time"]))
        			exit("from and to and flight and departure_time and landing_time must be set!");
        		$card->get_card()->set_from($extra_data["from"]); //必填
        		$card->get_card()->set_to($extra_data["to"]); //必填
        		$card->get_card()->set_flight($extra_data["flight"]); //必填
        		$card->get_card()->set_departure_time($extra_data["departure_time"]); //必填 
        		$card->get_card()->set_landing_time($extra_data["landing_time"]); //必填
        		break;
        		
        	case $this->CARD_TYPE[9]: //红包，无额外数据
        		break;
        		
        	default:
        		exit("CardType Error");
        }
        //cardjson
        $jsondata = json_decode($card->toJson(),true);
        return  $this->postapi("card/create",$jsondata);
     }
     /**
      * 查询卡券详情
      * card/get
      */
     function card_get($card_id){
     	if(empty($card_id)){
     		$retdata["errcode"] = 10001;
     		$retdata["errmsg"] = "card_id must be set!";
     		return $retdata;
     	}else{
     		$param = array();
     		$param["card_id"] = $card_id;
     		return $this->postapi("card/get",$param);
     	}
     }
     /**
      * 更改卡券信息接口
      * card/update
      * @param $card_id [*必填] 
      * @param $merchant_type [*必填] 
      * 支持修改的字段：
      * @param [*必填] $modify_base_info = array(
	  * 								"logo_url"=>"www.baidu.com",
	  * 								"color"=>""Color010",
	  * 								"notice"=>"提醒",
	  * 								...
      * 								);
      * @param [*必填] $modify_date_info = array(1,"1455124521","1555124521");   
      * 修改卡券有效期时间：
      * 新的时间类型 = 老的时间类型  值为1或者2
		新的开始时间需要<=老的开始时间
		新的结束时间需要>=老的结束时间
      * 返回数据：
      * {
		"errcode":0,
		"errmsg":"ok"
		}
      */
     function card_update($card_id,$merchant_type,$modify_base_info,$modify_date_info){
     	if(empty($card_id)||empty($modify_base_info)||empty($merchant_type)){
     		$retdata["errcode"] = 10001;
     		$retdata["errmsg"] = "card_id and merchant_type and modify_base_info and modify_date_info must be set!";
     		return $retdata;
     	}else{
     		//字段白名单
     		$allowmodify = array("logo_url","color","notice","service_phone","description","location_id_list","url_name_type","custom_url","code_type","use_limit","get_limit","can_give_friend","can_share");
     		$flag = true;
     		$notallow = "";
     		foreach($modify_base_info as $k=>$v){
     			if(!in_array($k,$allowmodify)){
     				$notallow = $k;
     				$flag = false;
     				break;
     			}
     		}
     		//修改的字段在白名单内
     		if($flag){
	     		$card_type = $this->get_card_type($merchant_type);
	     		//检查卡券类型是否合法
	     		if(in_array($card_type,$this->CARD_TYPE))
	     		{
	     			$param["card_id"] = $card_id;
	     			if($modify_date_info[0]==1){ //固定日期区间
	     				$modify_base_info["date_info"]["type"] = 1 ;
	     				$modify_base_info["date_info"]["begin_timestamp"] = $modify_date_info[1];
	     				$modify_base_info["date_info"]["end_timestamp"] = $modify_date_info[2];
	     			}
					else{ //固定时长（自领取后按天算）
	     				$modify_base_info["date_info"]["type"] = 2 ;
	     				$modify_base_info["date_info"]["fixed_term"] = $modify_date_info[1];
	     				$modify_base_info["date_info"]["fixed_begin_term"] = $modify_date_info[2];
	     			}
	     		    $param[strtolower($card_type)]["base_info"] = $modify_base_info;
	     		    return $this->postapi("card/update",$param);
	     		}else{
	     			$retdata["errcode"] = 10001;
	     			$retdata["errmsg"] = "没有该类型的卡券！";
	     			return $retdata;
	     		}
     		}else{
     			$retdata["errcode"] = 10001;
     			$retdata["errmsg"] = $notallow."不允许修改!";
     			return $retdata;
     		}
     	}
     }
     /**
      * 删除卡券接口允许商户删除任意一类卡券。删除卡券后，该卡券对应已生成的领取用二维码、添加
      * 到卡包 JS API 均会失效。
      * 注意： 如用户在商家删除卡券前已领取一张或多张该卡券依旧有效。 即删除卡券不能删除已被用户
      * 领取，保存在微信客户端中的卡券。
      * card/delete
      * @param
      */
     function card_delete($card_id){
     	if(empty($card_id)){
     		$retdata["errcode"] = 10001;
     		$retdata["errmsg"] = "card_id must be set!";
     		return $retdata;
     	}else{
     		$param = array();
     		$param["card_id"] = $card_id;
     		return $this->postapi("card/delete",$param);
     	}
     }
     
     /**
      * 批量导入门店信息
      * @param $location_list 数组单元json解析示例如下，各项均必填：
      * "business_name":"TIT 创意园 1 号店",
		"province":"广东省",
		"city":"广州市",
		"district":"海珠区",
		"address":"中国广东省广州市海珠区艺苑路 11 号",
		"telephone":"020-89772059",
		"category":"房产小区",
		"longitude":"115.32375",
		"latitude":"25.097486"
      */
     function card_location_batchadd($location_list){
     	if(!is_array($location_list)){
     		$retdata["errcode"] = 10001;
     		$retdata["errmsg"] = "location_list should be array!";
     		return $retdata;
     	}else{
     		$param = array();
	     	$param["location_list"] =array();
	     	$param["location_list"] = $location_list;
	     	return $this->postapi("card/location/batchadd",$param);
	     }
     }  
      
     /**
      * 拉取门店列表
      * card/location/batchget
      * @param $offset 偏移量，0 开始 【*必填】
      * @param $count 拉取数量  【*必填】
      * @return array
      * 返回json示例
      * {   "errcode": 0,
			"errmsg": "ok",
			"location_list": [
			{
				"location_id": “493”,
				"name": "steventao home",
				"phone": "020-12345678",
				"address": "广东省广州市番禺区广东省广州市番禺区南浦大道 ",
				"longitude": 113.280212402,
				"latitude": 23.0350666046
			},
			{
				"location_id": “468”,
				"name": "TIT 创意园 B4",
				"phone": "020-12345678",
				"address": "广东省广州市海珠区 ",
				"longitude": 113.325248718,
				"latitude": 23.1008300781
			}],
			"count": 2
		}
      * 
      */ 
     function card_location_batchget($offset,$count){
     	$param = array();
     	$param["offset"] = $offset;
     	$param["count"] = $count;
     	return $this->postapi("card/location/batchget",$param);
     }
     
     /**
      * 获取颜色列表接口
      * card_getcolors
      * @return array
      * {
		"errcode":0,
		"errmsg":"ok",
		"colors":[
					{"name":"Color010","value":"#61ad40"},
					{"name":"Color020","value":"#169d5c"},
					{"name":"Color030","value":"#239cda"}
				 ]
		}
      */
     function card_getcolors(){
     	$param = array();
     	return $this->postapi("card/getcolors",$param);
     }
     /**
      * 生成卡券二维码
      * card/qrcode/create
      * {
		"errcode":0,
		"errmsg":"ok",
		"ticket":"gQG28DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL0FuWC1DNmZuVEhvMVp4NDNMRnNRAAIEesLvUQMECAcAAA==",
		"url":"http://weixin.qq.com/q/rnUKFP-k5VacoJpx3V4R"
		}
		获取二维码 ticket后，开发者可用 ticket换取二维码图片。换取指引参考：http://mp.weixin.qq.com/wiki/index.php?title=生成带参数的二维码
      */
     function card_qrcode_create($card_id,$code=false,$openid=false,$expire_seconds=false,$is_unique_code=false,$balance=false){
     	if(empty($card_id)){
     		$retdata["errcode"] = 10001;
     		$retdata["errmsg"] = "card_id must be set!";
     		return $retdata;
     	}else{
     		$param = array();
     		$param["action_name"] ="QR_CARD";
     		$card = array();
     		$card["card_id"] = $card_id;
     		if($code)$card["code"] = $code;
     		if($openid)$card["openid"] = $openid;
     		if($expire_seconds)$card["expire_seconds"] = $expire_seconds;
     		if($is_unique_code)$card["is_unique_code"] = $is_unique_code;
     		if($balance)$card["balance"] = $balance;
     		$param["action_info"]["card"] = $card;
     		return $this->postapi("card/qrcode/create",$param);
     	}
     }
     /**
      * 消耗 code 接口是核销卡券的唯一接口。
	  *	自定义 code（use_custom_code 为 true）的优惠券，在 code 被核销时，必须调用此接口。用于将用户客户端的 code 状态变更。
	  *	自定义 code 的卡券调用接口时， post 数据中需包含 card_id，非自定义 code 不需上报。
	  * card/code/consume
	  * @param $code [*必填]	
	  * @param $card_id 注意：自定义 code 的卡券调用接口时， post数据中需包含 card_id
	  * @return array
	  * 返回数据示例：
	  * {
			"errcode":0,
			"errmsg":"ok",
			"card":{"card_id":"pFS7Fjg8kV1IdDz01r4SQwMkuCKc"},
			"openid":"oFS7Fjl0WsZ9AMZqrI80nbIq8xrA"
		}
      */
     public function card_code_consume($code,$card_id=false){
     	if(empty($code)){
     		$retdata["errcode"] = 10001;
     		$retdata["errmsg"] = "code must be set!";
     		return $retdata;
     	}else{
     		$param = array();
     		$param["code"] = $code;
     		if($card_id)$param["card_id"] = $card_id;
     		return $this->postapi("card/code/consume",$param);
     	}
     }
     /**
      * code解码接口
      * card/code/decrypt
      * @param $encrypt_code 通过 choose_card_info 获取的加密字符串    【*必填】
      * @return array
      * {
		"errcode":0,
		"errmsg":"ok",
		"code":"751234212312"
		}
      * 
      */
     public function card_code_decrypt($encrypt_code){
     	if(empty($encrypt_code)){
     		$retdata["errcode"] = 10001;
     		$retdata["errmsg"] = "encrypt_code must be set!";
     		return $retdata;
     	}else{
     		$param = array();
     		$param["encrypt_code"] = $encrypt_code;
     		return $this->postapi("card/code/decrypt",$param);
     	}
     }
    
     /**
      * 查询code
      * card/code/get
      * {
			"errcode":0,
			"errmsg":"ok",
			"openid":"oFS7Fjl0WsZ9AMZqrI80nbIq8xrA",
			"card":{
					"card_id":"pFS7Fjg8kV1IdDz01r4SQwMkuCKc",
					"begin_time": 1404205036,
					"end_time": 1404205036,
				   }
		}
      */
     function card_code_get($code){
     	if(empty($code)){
     		$retdata["errcode"] = 10001;
     		$retdata["errmsg"] = "code must be set!";
     		return $retdata;
     	}else{
     		$param = array();
     		$param["code"] = $code;
     		return $this->postapi("card/code/get",$param);
     	}
     }
     /**
      * 批量查询卡列表
      * card/batchget
      * @param $offset  查询卡列表的起始偏移量，从 0 开始，即 offset: 5 是指从从列表里的第六个开始读取。
      * @param $count  需要查询的卡片的数量（数量最大 50）
      * @return array
      * Array
		(
		    [errcode] => 0
		    [errmsg] => ok
		    [card_id_list] => Array
		        (
		            [0] => p1fzRjvhNJFAJzycpSvNvveufGtA
		            [1] => p1fzRjtE7ILVHX1aKNhFE_kta5kI
		            [2] => p1fzRjgPSPeqChJjW2JAQuZUo99s
		            [3] => p1fzRjvO-Dj7u127-1xjmRXd9RCw
		            [4] => p1fzRjl1F0U-85UVg-BstT3TDkzI
		            [5] => p1fzRjiUIs0P3GtHTsrjDBcexDRc
		            [6] => p1fzRjuyhRfRkKrFyeOcowQlOdmM
		            [7] => p1fzRjnIIjfphrcghaVkO5LO9RDQ
		            [8] => p1fzRjnfAPfTl2pqF7t5PzyI-RsE
		            [9] => p1fzRjjfz8Z7-8yuCQ5JlvjaMXdk
		        )
		    [total_num] => 11
		)
      */
     function card_batchget($offset,$count){
     	if($count>50){
     		$retdata["errcode"] = 10001;
     		$retdata["errmsg"] = "count must lt 50!";
     		return $retdata;
     	}else{
     		$param = array();
     		$param["offset"] = $offset;
     		$param["count"] = $count;
     		return $this->postapi("card/batchget",$param);
     	}
     }
     /**
      * 更改code
      * card/code/update
      * {
		"errcode":0,
		"errmsg":"ok"
		}
      */
     function card_code_update($card_id,$old_code,$new_code){
     	if(empty($$old_code)||empty($card_id)||empty($new_code)){
     		$retdata["errcode"] = 10001;
     		$retdata["errmsg"] = "card_id and old_code and new_code must be set!";
     		return $retdata;
     	}else{
     		$param = array();
     		$param["code"] = $old_code;
     		$param["card_id"] = $card_id;
     		$param["new_code"] = $new_code;
     		return $this->postapi("card/get",$param);
     	}
     }
     /**
      * 设置卡券失效接口
      * 为满足改票、退款等异常情况，可调用卡券失效接口将用户的卡券设置为失效状态。
      * 注：设置卡券失效的操作不可逆，即无法将设置为失效的卡券调回有效状态，商家须慎重调用该接口。
      * card/code/unavailable
      * @param $code 需要设置为失效的 code
      * @param $card_id 自定义 code 的卡券必填。 非自定义 code 的卡券不填。
      * @return array
      * {
		"errcode":0,
		"errmsg":"ok"
		}
      */
     function card_code_unavailable($code,$card_id=false){
     	if(empty($code)){
     		$retdata["errcode"] = 10001;
     		$retdata["errmsg"] = "code must be set!";
     		return $retdata;
     	}else{
     		$param = array();
     		$param["code"] = $code;
     		if($card_id)$param["card_id"] = $card_id;
     		return $this->postapi("card/code/unavailable",$param);
     	}
     }
     /**
      * 根据openid设置测试白名单
      * @param array $openid 注意这是一个数组！
      * 注：同时支持“openid”、“username”两种字段设置白名单，总数上限为 10 个
      */
     function card_testwhitelist_set_byopenid($openid){
     	$param = array();
     	$param["openid"] = $openid;
     	return $this->postapi("card/testwhitelist/set",$param);
     }
     /**
      * 根据username设置测试白名单
      * @param array $username 注意这是一个数组！
      */
     function card_testwhitelist_set_byusername($username){
     	$param = array();
     	$param["username"] = $username;
     	return $this->postapi("card/testwhitelist/set",$param);
     }
     
     /**************************JSAPI***********************************/
     
     /**
      * 添加到卡包（openCardDetail） 构造WeixinJSBridge.invoke('batchAddCard') 接口的传入参数,该JS接口会返回领取后的状态，以便做数据库处理
      * 可一张或多张
      * @param $card_id 数组
      */
     function batch_add_card($card_array,$code="",$openid=""){
     	$data["card_list"] = array();
     	$timestamp = time();
     	if(is_array($card_array)){
     		foreach ($card_array as $card_id){
     			$temp = array();
     			$array = array();
     			$temp["appsecret"] = $this->appsecret;
     			$temp["card_id"] = $card_id;
     			$temp["code"] = $code;
     			$temp["openid"] = $openid;
     			$temp["timestamp"] = $timestamp."";
     			$temp["signature"] = $this->createSignature($temp);
     			unset($temp["appsecret"]);
     			unset($temp["card_id"]);
     			$array["card_id"] = $card_id;
     			$array["card_ext"] = json_encode($temp);
     			array_push($data["card_list"], $array);
     			unset($temp);
     			unset($array);
     		}
     		return $data;
     	}else{
     		$retdata["errcode"] = 10001;
     		$retdata["errmsg"] = "card_array must be an array!";
     		return $retdata;
     	}
     }
     /**
      * 拉起卡券列表 chooseCard 微信提供chooseCard接口供商户前端网页调用,用于拉起用户名下该商家的所有卡券内容。
      * 注意：$card_id $card_type 这两个参数不可以同时填写
      * WeixinJSBridge.invoke('chooseCard', {
		"app_id": "wx0f283743a74d29e4",
		"location_id ": 11111,
		"sign_type": "sha1",
		"card_sign": "33333",
		"card_id": "pFS7Fjg8kV1IdDz01r4SQwMkuCKc",
		"card_type": "GROUPON",
		"time_stamp": 44444,
		"nonce_str": "55555"
		});
		该JS接口返回值示例：
		{
		"err_msg":" choose_card:ok ",
		"choose_card_info":[
							{
							26
							"card_id": "p3G6It08WKRgR0hyV3hHVb6pxrPQ",
							"encrypt_code":
							"XXIzTtMqCxwOaawoE91+VJdsFmv7b8g0VZIZkqf4GWA60Fzpc8ksZ/5ZZ0DVkXdE"
							]
		}
		}
      */
     function choose_card($card_id="",$merchant_type="",$location_id=""){
     	$timestamp = time();
     	$param["app_id"] = $this->appid;
     	$param["location_id"] = $location_id;
     	$param["sign_type"] = "SHA1";
     	$param["time_stamp"] = $timestamp."";
     	$param["nonce_str"] = $this->create_noncestr(16);
     	$param["card_id"] = $card_id;
     	if(!empty($merchant_type)){
     		$card_type = $this->get_card_type($merchant_type);
     		$param["card_type"] = $card_type;
     	}
     	$param["appsecret"] = $this->appsecret;
     	$param["card_sign"] = $this->createSignature($param);
     	unset($param["appsecret"]);
     	return $param;
     }
}





/**
 * 构造卡包基础bean
 *
 */
class Sku{
	function __construct($quantity){
		if(empty($quantity)||!is_int($quantity))
			exit("quantity must be integer and gt 0");
		$this->quantity = $quantity;
	}
};
class DateInfo{
	function __construct($type, $arg0, $arg1 = null)
	{

		if (!is_int($type) )
			exit("DateInfo.type must be integer");
		$this->type = $type;
		if ( $type == 1 )  //固定日期区间
		{
			if (!is_int($arg0) || !is_int($arg1))
				exit("begin_timestamp and  end_timestamp must be integer");
			$this->begin_timestamp = $arg0."";
			$this->end_timestamp = $arg1."";
		}
		else if ( $type == 2 )  //固定时长（自领取后多少天内有效）
		{
			if (!is_int($arg0)||!is_int($arg1))
				exit("fixed_term and fixed_begin_term must be integer");
			$this->fixed_term = $arg0;
			$this->fixed_begin_term = $arg1;
		}else
			exit("DateInfo.tpye Error");
	}
};
class BaseInfo{
	function __construct($logo_url, $brand_name, $code_type, $title, $color, $notice, $service_phone,
			$description, $date_info, $sku)
	{
		if (! $date_info instanceof DateInfo )
			exit("date_info Error");
		if (! $sku instanceof Sku )
			exit("sku Error");
		$this->logo_url = $logo_url;
		$this->brand_name = $brand_name;
		$this->code_type = $code_type;
		$this->title = $title;
		$this->color = $color;
		$this->notice = $notice;
		$this->service_phone = $service_phone;
		$this->description = $description;
		$this->date_info = $date_info;
		$this->sku = $sku;
	}
	function set_sub_title($sub_title){
		if(!empty($sub_title))
			$this->sub_title = $sub_title;
	}
	function set_use_limit($use_limit){
		if(!empty($use_limit)){
			if($use_limit<1){
				exit("use_limit必须大于0");
			}
			if (!is_int($use_limit)){
				exit("use_limit must be integer");
			}
			$this->use_limit = $use_limit;
		}
	}
	function set_get_limit($get_limit){
		if(!empty($get_limit)){
			if (! is_int($get_limit))
				exit("get_limit must be integer");
			$this->get_limit = $get_limit;
		}
	}
	function set_use_custom_code($use_custom_code){
		$this->use_custom_code = $use_custom_code;
	}
	function set_bind_openid($bind_openid){
		$this->bind_openid = $bind_openid;
	}
	function set_can_share($can_share){
		$this->can_share = $can_share;
	}
	function set_can_give_friend($can_give_friend){
		$this->can_give_friend = $can_give_friend;
	}
	function set_location_id_list($location_id_list){
		$this->location_id_list = $location_id_list;
	}
	function set_url_name_type($url_name_type){
		if(!empty($url_name_type)){
			$this->url_name_type = $url_name_type;
		}
	}
	function set_source($source){
		if(!empty($source))
			$this->source = $source;
	}
	//商户自定义入口名称，与custom_url 字段共同使用，长度限制在 5 个汉字内。
	function set_custom_url_name($custom_url_name){
		if(!empty($custom_url_name))
			$this->custom_url_name = $custom_url_name;
	}
	//商户自定义入口跳转外链的地址链接,跳转页面内容需与自定义cell 名称保持匹配。
	function set_custom_url($custom_url){
		if(!empty($custom_url))
			$this->custom_url = $custom_url;
	}
	//显示在入口右侧的 tips，长度限制在 6 个汉字内。
	function set_custom_url_sub_title($custom_url_sub_title){
		if(!empty($custom_url_sub_title))
			$this->custom_url_sub_title = $custom_url_sub_title;
	}
	//营销场景的自定义入口。
	function set_promotion_url_name($promotion_url_name){
		if(!empty($promotion_url_name))
			$this->promotion_url_name = $promotion_url_name;
	}
	//入口跳转外链的地址链接。
	function set_promotion_url($promotion_url){
		if(!empty($promotion_url))
			$this->promotion_url = $promotion_url;
	}
	//显示在入口右侧的 tips，长度限制在 6 个汉字内。
	function set_promotion_url_sub_title($promotion_url_sub_title){
		if(!empty($$promotion_url_sub_title))
			$this->promotion_url_sub_title = $promotion_url_sub_title;
	}
};
class CardBase{
	public function __construct($base_info){
		$this->base_info = $base_info;
	}
};
//通用券
class GeneralCoupon extends CardBase{
	function set_default_detail($default_detail){
		$this->default_detail = $default_detail;
	}
};
//团购券
class Groupon extends CardBase{
	function set_deal_detail($deal_detail){
		$this->deal_detail = $deal_detail;
	}
};
//折扣券
class Discount extends CardBase{
	function set_discount($discount){
		$this->discount = $discount;
	}
};
//礼品券
class Gift extends CardBase{
	function set_gift($gift){
		$this->gift = $gift;
	}
};
//代金券
class Cash extends CardBase{
	function set_least_cost($least_cost){
		$this->least_cost = $least_cost*100;
	}
	function set_reduce_cost($reduce_cost){
		$this->reduce_cost = $reduce_cost*100;
	}
};
//会员卡
class MemberCard extends CardBase{
	function set_supply_bonus($supply_bonus){
		$this->supply_bonus = $supply_bonus;
	}
	function set_supply_balance($supply_balance){
		$this->supply_balance = $supply_balance;
	}
	function set_bonus_cleared($bonus_cleared){
		$this->bonus_cleared = $bonus_cleared;
	}
	function set_bonus_rules($bonus_rules){
		$this->bonus_rules = $bonus_rules;
	}
	function set_balance_rules($balance_rules){
		$this->balance_rules = $balance_rules;
	}
	function set_prerogative($prerogative){
		$this->prerogative = $prerogative;
	}
	function set_bind_old_card_url($bind_old_card_url){
		$this->bind_old_card_url = $bind_old_card_url;
	}
	function set_activate_url($activate_url){
		$this->activate_url = $activate_url;
	}
};
//门票
class ScenicTicket extends CardBase{
	function set_ticket_class($ticket_class){
		$this->ticket_class = $ticket_class;
	}
	function set_guide_url($guide_url){
		$this->guide_url = $guide_url;
	}
};
//电影票
class MovieTicket extends CardBase{
	function set_detail($detail){
		$this->detail = $detail;
	}
};
//飞机票
class BOARDINGPASS extends CardBase{
	//起点，上限为 18 个汉字。
	function set_from($from){
		$this->from = $from;
	}
	//终点，上限为 18 个汉字。
	function set_to($to){
		$this->to = $to;
	}
	//航班
	function set_flight($flight){
		$this->flight = $flight;
	}
	//起飞时间，上限为 17 个汉字。
	function set_departure_time($departure_time){
		$this->departure_time = $departure_time;
	}
	//降落时间，上限为 17 个汉字。
	function set_landing_time($landing_time){
		$this->landing_time = $landing_time;
	}
	//在线值机的链接,选填
	function set_check_in_url($check_in_url){
		$this->check_in_url = $check_in_url;
	}
};
//红包，无额外数据
class LUCKYMONEY extends CardBase{

};
class Card{  //工厂
	private	$CARD_TYPE = Array("GENERAL_COUPON",
			"GROUPON", "DISCOUNT",
			"GIFT", "CASH", "MEMBER_CARD",
			"SCENIC_TICKET", "MOVIE_TICKET","BOARDING_PASS","LUCKY_MONEY");

	function __construct($card_type, $base_info)
	{
		if (!in_array($card_type, $this->CARD_TYPE))
			exit("CardType Error");
		if (! $base_info instanceof BaseInfo )
			exit("base_info Error");
		$this->card_type = $card_type;
		switch ($card_type)
		{
			case $this->CARD_TYPE[0]:
				$this->general_coupon = new GeneralCoupon($base_info);
				break;
			case $this->CARD_TYPE[1]:
				$this->groupon = new Groupon($base_info);
				break;
			case $this->CARD_TYPE[2]:
				$this->discount = new Discount($base_info);
				break;
			case $this->CARD_TYPE[3]:
				$this->gift = new Gift($base_info);
				break;
			case $this->CARD_TYPE[4]:
				$this->cash = new Cash($base_info);
				break;
			case $this->CARD_TYPE[5]:
				$this->member_card = new MemberCard($base_info);
				break;
			case $this->CARD_TYPE[6]:
				$this->scenic_ticket = new ScenicTicket($base_info);
				break;
			case $this->CARD_TYPE[7]:
				$this->movie_ticket = new MovieTicket($base_info);
				break;
			case $this->CARD_TYPE[8]:
				$this->boarding_pass = new BOARDINGPASS($base_info);
				break;
			case $this->CARD_TYPE[9]:
				$this->lucky_money = new LUCKYMONEY($base_info);
				break;
			default:
				exit("CardType Error");
		}
		return true;
	}
	function get_card()
	{
		switch ($this->card_type)
		{
			case $this->CARD_TYPE[0]:
				return $this->general_coupon;
			case $this->CARD_TYPE[1]:
				return $this->groupon;
			case $this->CARD_TYPE[2]:
				return $this->discount;
			case $this->CARD_TYPE[3]:
				return $this->gift;
			case $this->CARD_TYPE[4]:
				return $this->cash;
			case $this->CARD_TYPE[5]:
				return $this->member_card;
			case $this->CARD_TYPE[6]:
				return $this->scenic_ticket;
			case $this->CARD_TYPE[7]:
				return $this->movie_ticket;
			case $this->CARD_TYPE[8]:
				return $this->boarding_pass;
			case $this->CARD_TYPE[9]:
				return $this->lucky_money;
			default:
				exit("GetCard Error");
		}
	}
	function toJson()
	{
		return "{ \"card\":" . json_encode($this) . "}";
	}
};