<?php
    /**
     * 快钱联合注册接口
     */

    define( 'IN_MOBILE' , true );

    require( dirname( __FILE__ ) . '/includes/init.php' );
    $backUrl = $ecs->url() . ADMIN_PATH . '/receive.php';
    header( "location:http://cloud.ecshop.com/payment_apply.php?mod=kuaiqian&par=$backUrl" );
    exit;
?>
