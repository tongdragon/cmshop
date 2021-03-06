<?php
/* *
 * MD5
 * 详细：MD5加密
 * 版本：3.3
 * 日期：2012-07-19
 * md5Sign签名及拿到签名结果：http://doc.open.alipay.com/doc2/detail?treeId=58&articleId=103591&docType=1
 * md5Verify签名验证：http://doc.open.alipay.com/doc2/detail.htm?spm=0.0.0.0.uoEwNI&treeId=58&articleId=103596&docType=1
 */

/**
 * 签名字符串
 * @param $prestr 需要签名的字符串
 * @param $key 私钥
 * return 签名结果
 */
function md5Sign($prestr, $key) {
	$prestr = $prestr . $key;
	return md5($prestr);
}

/**
 * 验证签名
 * @param $prestr 需要签名的字符串
 * @param $sign 签名结果
 * @param $key 私钥
 * return 签名结果
 */
function md5Verify($prestr, $sign, $key) {
	$prestr = $prestr . $key;
	$mysgin = md5($prestr);
	if($mysgin == $sign) {
		return true;
	}
	return false;
}
?>