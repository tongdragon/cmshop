<?php

    /* *
     * 配置文件
     * 版本：3.3
     * 日期：2012-07-19
     * 提示：如何获取安全校验码和合作身份者id
     * 1.用您的签约支付宝账号登录支付宝网站(www.alipay.com)
     * 2.点击“商家服务”(https://b.alipay.com/order/myorder.htm)
     * 3.点击“查询合作者身份(pid)”、“查询安全校验码(key)”

     * 安全校验码查看时，输入支付密码后，页面呈灰色的现象，怎么办？
     * 解决方法：
     * 1、检查浏览器配置，不让浏览器做弹框屏蔽设置
     * 2、更换浏览器或电脑，重新登录查询。
     */


    /**
     * 支付宝纯担保交易接口接口
     */
    function fetchConfig()
    {
        $payment = getPayment( 'alipay' );
        $setting = unserialize_config( $payment['pay_config'] );

        //合作身份者id，以2088开头的16位纯数字
        $alipay_config['partner'] = trim( $setting['alipay_partner'] );

        //收款支付宝账号，一般情况下收款账号就是签约账号
        $alipay_config['seller_id'] = trim( $setting['alipay_partner'] );

        $alipay_config['seller_email'] = trim( $setting['alipay_account'] );
        //商户的私钥（后缀是.pem）文件相对路径
        $alipay_config['private_key_path'] = getcwd() . '\includes\modules\Payment\alipay\key\rsa_private_key.pem';
        //支付宝公钥（后缀是.pen）文件相对路径
        $alipay_config['ali_public_key_path'] = getcwd() . '\includes\modules\Payment\alipay\key\rsa_public_key.pem';
        // 安全校验码
        $alipay_config['alipay_key'] = trim( $setting['alipay_key'] );
        //签名方式
        $alipay_config['sign_type'] = strtoupper( 'MD5' );

        //字符编码格式 目前支持 gbk 或 utf-8
        $alipay_config['input_charset'] = strtolower( 'utf-8' );

        //ca证书路径地址，用于curl中ssl校验
        //请保证cacert.pem文件在当前文件夹目录中
        $alipay_config['cacert'] = getcwd() . '\includes\modules\payment\certs\cacert.pem';

        //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        $alipay_config['transport'] = 'https';

        return $alipay_config;
    }

