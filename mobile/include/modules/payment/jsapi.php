<?php
    /**
     * 云动商圈微信新版JSAPI支付插件
     */
    if ( !defined( 'IN_MOBILE' ) ) {
        die( 'Hacking attempt' );
    }
    $payment_lang = ROOT_PATH . 'lang/' . $GLOBALS['_CFG']['lang'] . '/payment/jsapi.php';
    if ( file_exists( $payment_lang ) ) {
        global $_LANG;

        include_once( $payment_lang );
    }

    /* 模块的基本信息 */
    if ( isset( $set_modules ) && $set_modules == true ) {
        $i = isset( $modules ) ? count( $modules ) : 0;

        /* 代码 */
        $modules[$i]['code'] = basename( __FILE__ , '.php' );

        /* 描述对应的语言项 */
        $modules[$i]['desc'] = 'jsapi_desc';

        /* 是否支持货到付款 */
        $modules[$i]['is_cod'] = '0';

        /* 是否支持在线支付 */
        $modules[$i]['is_online'] = '1';

        /* 作者 */
        $modules[$i]['author'] = '云动商圈';

        /* 网址 */
        $modules[$i]['website'] = 'http://www.mbizzone.com';

        /* 版本号 */
        $modules[$i]['version'] = '1.0.0';

        /* 配置信息 */
        $modules[$i]['config'] = array(
            array( 'name' => 'appid' , 'type' => 'text' , 'value' => '' ) ,
            array( 'name' => 'mchid' , 'type' => 'text' , 'value' => '' ) ,
            array( 'name' => 'key' , 'type' => 'text' , 'value' => '' ) ,
            array( 'name' => 'appsecret' , 'type' => 'text' , 'value' => '' ) ,
            array( 'name' => 'logs' , 'type' => 'text' , 'value' => '0' ) ,
        );

        return;
    }

    class jsapi
    {
        function __construct()
        {
            $payment = get_payment( 'jsapi' );

            if ( !defined( 'WXAPPID' ) ) {
                define( "WXAPPID" , $payment['appid'] );
                define( "WXMCHID" , $payment['mchid'] );
                define( "WXKEY" , $payment['key'] );
                define( "WXAPPSECRET" , $payment['appsecret'] );
                define( "WXCURL_TIMEOUT" , 30 );
                $notify_url = return_url( basename( __FILE__ , '.php' ) );
                define( 'WXNOTIFY_URL' , $notify_url );
                define( 'WXSSLCERT_PATH' , dirname( __FILE__ ) . '/WxPayPubHelper/cacert/apiclient_cert.pem' );
                define( 'WXSSLKEY_PATH' , dirname( __FILE__ ) . '/WxPayPubHelper/cacert/apiclient_key.pem' );
            }
            require_once( dirname( __FILE__ ) . "/WxPayPubHelper/WxPayPubHelper.php" );

        }


        function get_code( $order , $payment )
        {

            $jsApi = new JsApi_pub();


            if ( !isset( $_GET['code'] ) ) {
                $redirect = urlencode( $GLOBALS['ecs']->url() . 'flow.php?step=ok&order_id=' . $order['order_sn'] );
                $url = $jsApi->createOauthUrlForCode( $redirect );
                Header( "Location: $url" );
            } else {
                $code = $_GET['code'];
                $jsApi->setCode( $code );
                $openid = $jsApi->getOpenId();
            }
            $html = '';

            if ( $openid ) {
                $unifiedOrder = new UnifiedOrder_pub();

                $unifiedOrder->setParameter( "openid" , $openid );//商品描述
                $unifiedOrder->setParameter( "body" , $order['order_sn'] );//商品描述
                $out_trade_no = $order['order_sn'];
                $unifiedOrder->setParameter( "out_trade_no" , "$out_trade_no" );//商户订单号
                $unifiedOrder->setParameter( "attach" , strval( $order['log_id'] ) );//商户支付日志
                $unifiedOrder->setParameter( "total_fee" , strval( intval( $order['order_amount'] * 100 ) ) );//总金额


                //异步通知地址
                $unifiedOrder->setParameter( "notify_url" , WXNOTIFY_URL );//通知地址
                $unifiedOrder->setParameter( "trade_type" , "JSAPI" );//交易类型


                $prepay_id = $unifiedOrder->getPrepayId();

                $jsApi->setPrepayId( $prepay_id );

                $jsApiParameters = $jsApi->getParameters();

//			$ss=$jsApi->mygetSign();
//			 $root_url = str_replace('mobile/', '', $GLOBALS['ecs']->url());
                //var_dump($root_url);
                //var_dump(ROOT_PATH);
                //var_dump($jsApiParameters);
                //var_dump($ss);

                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                $allow_use_wxPay = true;

                if ( strpos( $user_agent , 'MicroMessenger' ) === false ) {
                    $allow_use_wxPay = false;
                } else {
                    preg_match( '/.*?(MicroMessenger\/([0-9.]+))\s*/' , $user_agent , $matches );
                    if ( $matches[2] < 5.0 ) {
                        $allow_use_wxPay = false;
                    }
                }
                $html .= '<script language="javascript">';
                if ( $allow_use_wxPay ) {
                    $html .= "function jsApiCall(){";
                    $html .= "WeixinJSBridge.invoke(";
                    $html .= "'getBrandWCPayRequest',";
                    $html .= $jsApiParameters . ",";
                    $html .= "function(res){";
                    $html .= "if(res.err_msg == 'get_brand_wcpay_request:ok'){window.location.href='" . $GLOBALS['ecs']->url() . "respond.php'}";
                    //$html .= "WeixinJSBridge.log(res.err_msg);";
                    $html .= "}";
                    $html .= ");";
                    $html .= "}";
                    $html .= "function callpay(){";
                    $html .= 'if (typeof WeixinJSBridge == "undefined"){';
                    $html .= "if( document.addEventListener ){";
                    $html .= "document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);";
                    $html .= "}else if (document.attachEvent){";
                    $html .= "document.attachEvent('WeixinJSBridgeReady', jsApiCall); ";
                    $html .= "document.attachEvent('onWeixinJSBridgeReady', jsApiCall);";
                    $html .= "}";
                    $html .= "}else{";
                    $html .= "jsApiCall();";
                    $html .= "}}";
                } else {
                    $html .= 'function callpay(){';
                    $html .= 'alert("您的微信不支持支付功能,请更新您的微信版本")';
                    $html .= "}";
                }
                $html .= '</script>';
                $html .= '<button  class="c-btn4"  type="button" onclick="callpay()">微信支付!!!</button>';

                return $html;

            } else {
                $html .= '<script language="javascript">';
                $html .= 'function callpay(){';
                $html .= 'alert("请在微信中使用微信支付")';
                $html .= "}";
                $html .= '</script>';
                $html .= '<button type="button" onclick="callpay()" class="pay_bottom">微信支付</button>';

                return $html;
            }
        }

        function respond()
        {
            $xml = isset( $GLOBALS['HTTP_RAW_POST_DATA'] ) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents( "php://input" );


            $re = isset( $GLOBALS['HTTP_RAW_POST_DATA'] );
            var_dump($re);
            $xml = '<xml><return_code><![CDATA[SUCCESS]]></return_code>
<return_msg><![CDATA[OK]]></return_msg>
<appid><![CDATA[wx2dbfb36690f99a25]]></appid>
<mch_id><![CDATA[1448728902]]></mch_id>
<nonce_str><![CDATA[aPl1epzUvmKlpZIx]]></nonce_str>
<sign><![CDATA[78593D3C3E021D38DDFB65658D52A010]]></sign>
<result_code><![CDATA[SUCCESS]]></result_code>
<prepay_id><![CDATA[wx20171120152706dd296b9bfb0681605674]]></prepay_id>
<trade_type><![CDATA[JSAPI]]></trade_type>
</xml>';

            $notify = new Notify_pub();
            $notify->saveData( $xml );
            if ( $notify->checkSign() ) {

                $data = $notify->xmlToArray( $xml );

                if ( $data ) {
                    if ( $data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS' ) {
                        $total_fee = $data["total_fee"];


                        $log_id = $data["attach"];

                        var_dump($log_id);

                        if ( !empty( $log_id ) ) {

                            $sql = 'SELECT order_amount FROM ' . $GLOBALS['ecs']->table( 'pay_log' ) . " WHERE log_id = '$log_id'";
                            $amount = $GLOBALS['db']->getOne( $sql );
                            if ( intval( $amount * 100 ) == $total_fee ) {
                                order_paid( $log_id , 2 );

                                return true;
                            }
                        }
                    }
                }


            }

            return false;
        }
    }
