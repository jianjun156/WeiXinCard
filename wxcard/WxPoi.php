<?php
include_once("CommonUtil.php");
/**
 * 微信门店接口
 * @author danjar@qq.com
 * 1、新增门店接口
 * 2、拉取门店列表
 * @todo
 **/
class WxPoi extends CommonUtil{ 
	// 接口网关地址
	private static $APIHOST = 'http://api.weixin.qq.com/cgi-bin/';
	// appid
	private $appid;
	// appsecret
	private $appsecret;
	// 授权token
	private $access_token;
	// 当前登陆用户uid
	private $uid;
	function __construct($appid,$appserct) {
	
			$this->appid = $appid;
			$this->appsecret = $appserct;
			$this->access_token = $this->get_access_token();
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
		//s($data);
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
	 * 新增单个门店poi
	 * 示例数据
		   {
				"sid":"33788392", //用门店主键自增id 【必给】
				"business_name":"麦当劳", //门店名称 【必给】
				"branch_name":"艺苑路店", //分店名称
				"province":"广东省", //门店所在的省份 【必给】
				"city":"广州市", //门店所在的城市  【必给】
				"district":"海珠区", //门店所在地区 
				"address":"艺苑路11 号", //门店所在的详细街道地址 【必给】
				"telephone":"020-12345678", //门店的电话 【必给】
				"categories":["美食,快餐小吃"], //门店的类型（详细分类参见分类附表，不同级分类用“,”隔开，如：美食，川菜，火锅） 【必给】
				"longitude":115.32375, //门店所在地理位置的经度 【必给】
				"latitude":25.097486, //门店所在地理位置的纬度 【必给】
				"photo_list":[{"photo_url":"https:// XXX.com"}，{"photo_url":"https://XXX.com"}], //图片列表，url形式，可以有多张图片，尺寸为640*340px。必须为上一接口生成的 url【必给】
				"recommend":"麦辣鸡腿堡套餐，麦乐鸡，全家桶", //推荐品，餐厅可为推荐菜；酒店为推荐套房；景点为荐游玩景点等，针对自己行业的推荐内容
				"special":"免费 wifi，外卖服务", //特色服务，如免费 wifi，免费停车，送货上门等商户能提供的特色功能或服务 【必给】
				"introduction":"麦当劳是全球大型跨国连锁餐厅", //商户简介，主要介绍商户信息等
				"open_time":"8:00-20:00", //营业时间，24小时制表示，用“-”连接，如8:00-20:00 【必给】
				"avg_price":35 //人均价格，大于 0 的整数
			}
	 */
	public function poi_addpoi($poibase_info){
		if(!is_array($poibase_info)){
			$retdata["errcode"] = 10001;
			$retdata["errmsg"] = "poibase_info should be array!";
			return $retdata;
		}else{
			$param = array();
			$param["business"] =array();
			$poibase_info["offset_type"] = 1; //坐标类型，1为火星坐标（目前只能选 1）
			$param["business"]["base_info"] = $poibase_info;
			return $this->postapi("poi/addpoi",$param);
		}
	}
	/**
	 * 拉取门店列表
	 * @param $offset 偏移量，0 开始 【*必填】
     * @param $count 拉取数量  【*必填】
     * {
			"errcode":0,
			"errmsg":"ok"
			"business_list":[
				{"base_info":{
					"sid":"100",
					"poi_id":"271864249",
					"business_name":"麦当劳",
					"branch_name":"艺苑路店",
					"address":"艺苑路 11 号",
					"available_state":3 //门店是否可用状态。1 表示系统错误、2表示审核中、3 审核通过、4 审核驳回。当该字段为 1、2、4 状态时，poi_id 为空
				}},
				{"base_info":{
					"sid":"101",
					"business_name":"麦当劳",
					"branch_name":"赤岗路店",
					"address":"赤岗路 102 号",
					"available_state":4
			    }}
			 ],
			"total_count":"2",
		}
	 */
	public function poi_getpoilist($begin,$limit){
		$param = array();
     	$param["begin"] = $begin;
     	$param["limit"] = $limit;
     	return $this->postapi("poi/getpoilist",$param);
	}
}