<?php

    /**
     *  活动列表
     */
    define( 'IN_MOBILE' , true );

    require( dirname( __FILE__ ) . '/include/init.php' );
    require_once( ROOT_PATH . 'include/lib_order.php' );
    include_once( ROOT_PATH . 'include/lib_transaction.php' );

    /* 载入语言文件 */
    require_once( ROOT_PATH . 'lang/' . $_CFG['lang'] . '/shopping_flow.php' );
    require_once( ROOT_PATH . 'lang/' . $_CFG['lang'] . '/user.php' );

    /* ------------------------------------------------------ */
//-- PROCESSOR
    /* ------------------------------------------------------ */

    assign_template();
    assign_dynamic( 'activity' );
    $position = assign_ur_here( 0 , $_LANG['shopping_activity'] );
    $smarty->assign( 'page_title' , $position['title'] );    // 页面标题
    $smarty->assign( 'ur_here' , $position['ur_here'] );  // 当前位置
// 数据准备

    /* 取得用户等级 */
    $user_rank_list = array();
    $user_rank_list[0] = $_LANG['not_user'];
    $sql = "SELECT rank_id, rank_name FROM " . $ecs->table( 'user_rank' );
    $res = $db->query( $sql );
    while ( $row = $db->fetchRow( $res ) ) {
        $user_rank_list[$row['rank_id']] = $row['rank_name'];
    }


// 开始工作
    $sql = "SELECT * FROM " . $ecs->table( 'favourable_activity' ) . " ORDER BY `sort_order` ASC,`end_time` DESC";
    $res = $db->query( $sql );

    $list = array();
    while ( $row = $db->fetchRow( $res ) ) {
        $row['start_time'] = local_date( 'Y-m-d H:i' , $row['start_time'] );
        $row['end_time'] = local_date( 'Y-m-d H:i' , $row['end_time'] );

        //享受优惠会员等级
        $user_rank = explode( ',' , $row['user_rank'] );
        $row['user_rank'] = array();
        foreach ( $user_rank as $val ) {
            if ( isset( $user_rank_list[$val] ) ) {
                $row['user_rank'][] = $user_rank_list[$val];
            }
        }

        //优惠范围类型、内容
        if ( $row['act_range'] != FAR_ALL && !empty( $row['act_range_ext'] ) ) {
            if ( $row['act_range'] == FAR_CATEGORY ) {
                $row['act_range'] = $_LANG['far_category'];
                $row['program'] = 'category.php?id=';
                $sql = "SELECT cat_id AS id, cat_name AS name FROM " . $ecs->table( 'category' ) .
                    " WHERE cat_id " . db_create_in( $row['act_range_ext'] );
            } elseif ( $row['act_range'] == FAR_BRAND ) {
                $row['act_range'] = $_LANG['far_brand'];
                $row['program'] = 'brand.php?id=';
                $sql = "SELECT brand_id AS id, brand_name AS name FROM " . $ecs->table( 'brand' ) .
                    " WHERE brand_id " . db_create_in( $row['act_range_ext'] );
            } else {
                $row['act_range'] = $_LANG['far_goods'];
                $row['program'] = 'goods.php?id=';
                $sql = "SELECT goods_id AS id, goods_name AS name FROM " . $ecs->table( 'goods' ) .
                    " WHERE goods_id " . db_create_in( $row['act_range_ext'] );
            }
            $act_range_ext = $db->getAll( $sql );
            $row['act_range_ext'] = $act_range_ext;
        } else {
            $row['act_range'] = $_LANG['far_all'];
        }

        //优惠方式

        switch ( $row['act_type'] ) {
            case 0:
                $row['act_type'] = $_LANG['fat_goods'];
                $row['gift'] = unserialize( $row['gift'] );
                if ( is_array( $row['gift'] ) ) {
                    foreach ( $row['gift'] as $k => $v ) {
                        $row['gift'][$k]['thumb'] = get_image_path( $v['id'] , $db->getOne( "SELECT goods_thumb FROM " . $ecs->table( 'goods' ) . " WHERE goods_id = '" . $v['id'] . "'" ) , true );
                    }
                }
                break;
            case 1:
                $row['act_type'] = $_LANG['fat_price'];
                $row['act_type_ext'] .= $_LANG['unit_yuan'];
                $row['gift'] = array();
                break;
            case 2:
                $row['act_type'] = $_LANG['fat_discount'];
                $row['act_type_ext'] .= "%";
                $row['gift'] = array();
                break;
        }

        $list[] = $row;
    }
    /*
     * 异步显示商品列表 by wang
     */
    if ( $_GET['act'] == 'asynclist' ) {
        $sayList = array();
        if ( is_array( $list ) ) {
            foreach ( $list as $val ) {


                $max_amount = $val['max_amount'] ? $val['max_amount'] : $_LANG['nolimit'];

                if ( $val['act_range'] != $_LANG['far_all'] ) {
                    $extends = ':<br />';
                    foreach ( $val['act_range_ext'] as $key => $value ) {
                        @$extends .= "<a href=\"" . $val['program'] . $value['id'] . "\" taget='_blank' class='f6'><span class='f_user_info'><u>" . $value['name'] . "</u></span></a>";
                    }
                }
                $user_rank = "";
                foreach ( $val['user_rank'] as $rank ) {
                    @$user_rank .= $rank . "&nbsp;";
                }


                $act_type_ext = ( $val['act_type'] != $_LANG['fat_goods'] ) ? $val['act_type_ext'] : '';
                $gift = '';
                foreach ( $val['gift'] as $key => $value ) {
                    $price = $value['price'] > 0 ? $value['price'] . $_LANG['unit_yuan'] : $_LANG['for_free'];
                    @$gift .= "<dl class='gift'><dt><a href='goods.php?id=" . $value['id'] . "'><img src='" . $config['site_url'] . $value['thumb'] . "' /></a></dt><dd><a href='goods.php?id=" . $value['id'] . "'>" . $value['name'] . "</a></dd><dd>" . $price . "</dd></dl>";
                }

                $sayList[] = array(
                    'pro-inner' => '<section class="order_box padd1 radius10"><table class="ectouch_table" width="100%" border="0" cellspacing="0" cellpadding="5">
        <tr>
          <td width="25%" bgcolor="#ffffff" align="right">' . $_LANG['label_act_name'] . '</td>
          <td width="75%" colspan="3" bgcolor="#ffffff" align="left">' . $val['act_name'] . '</td>
        </tr>
        <tr>
          <td width="15%"  bgcolor="#ffffff" align="right">' . $_LANG['label_start_time'] . '</td>
          <td width="35%" bgcolor="#ffffff" align="left">' . $val['start_time'] . '</td>
          <td width="15%" bgcolor="#ffffff" align="right">' . $_LANG['label_max_amount'] . '
            </td>
          <td width="35%" bgcolor="#ffffff" align="left">
          ' . $max_amount . '
          </td>
        </tr>
        <tr>
          <td bgcolor="#ffffff" align="right">' . $_LANG['label_end_time'] . '</td>
          <td bgcolor="#ffffff" align="left">' . $val['end_time'] . '</td>
          <td bgcolor="#ffffff" align="right">' . $_LANG['label_min_amount'] . '</td>
          <td width="200" bgcolor="#ffffff" align="left">' . $val['min_amount'] . '</td>
        </tr>
        <tr>
          <td bgcolor="#ffffff" align="right">' . $_LANG['label_act_range'] . '</td>
          <td bgcolor="#ffffff" align="left"> ' . $val['act_range'] . $extends . '
          </td>
          <td bgcolor="#ffffff" align="right">' . $_LANG['label_user_rank'] . '</td>
          <td bgcolor="#ffffff" align="left">' . $user_rank . '</td>
        </tr>
        <tr>
          <td bgcolor="#ffffff" align="right">' . $_LANG['label_act_type'] . '</td>
          <td colspan="3" bgcolor="#ffffff" align="left"> 
          ' . $val['act_type'] . '
          
        </td>
        </tr>
        <tr>
        <td colspan="4" bgcolor="#ffffff" align="right">
        ' . $gift . '
         </td>
        </tr>
      </table></section>',
                );
            }
        }
        echo json_encode( $sayList );
        exit;
    }

    /*
     * 异步显示商品列表 by wang end
     */

    $smarty->assign( 'list' , $list );

    $smarty->assign( 'helps' , get_shop_help() );       // 网店帮助
    $smarty->assign( 'lang' , $_LANG );

    $smarty->assign( 'feed_url' , ( $_CFG['rewrite'] == 1 ) ? "feed-typeactivity.xml" : 'feed.php?type=activity' ); // RSS URL
    $smarty->display( 'activity.dwt' );

