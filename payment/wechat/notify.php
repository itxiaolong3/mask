<?php
define('IN_MOBILE', true);
require '../../../../framework/bootstrap.inc.php';
global $_W, $_GPC;
$input = file_get_contents('php://input');
$isxml = true;
if (!empty($input) && empty($_GET['out_trade_no'])) {
    $obj = isimplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);
    $res = $data = json_decode(json_encode($obj), true);
    $filename=$_W['attachurl'].'notifyinfo.txt';
    file_put_contents($filename,$data['result_code'].'==result_code'.$data['return_code'].'===return_code');
    if (empty($data)) {
        $result = array(
            'return_code' => 'FAIL',
            'return_msg' => ''
        );
        echo array2xml($result);
        exit;
    }
    if ($data['result_code'] != 'SUCCESS' || $data['return_code'] != 'SUCCESS') {
        $result = array(
            'return_code' => 'FAIL',
            'return_msg' => empty($data['return_msg']) ? $data['err_code_des'] : $data['return_msg']
        );
        echo array2xml($result);
        exit;
    }
    $get = $data;
} else {
    $isxml = false;
    $get = $_GET;
}
load()->web('common');
load()->model('mc');
load()->func('communication');
$_W['uniacid'] = $_W['weid'] = intval($get['attach']);
$_W['uniaccount'] = $_W['account'] = uni_fetch($_W['uniacid']);
$_W['acid'] = $_W['uniaccount']['acid'];
$paySetting = uni_setting($_W['uniacid'], array('payment'));
if($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS' ){
    $logno = trim($res['out_trade_no']);

    if (empty($logno)) {
        exit;
    }
    $str=$_W['siteroot'];
    $n = 0;
    for($i = 1;$i <= 3;$i++) {
        $n = strpos($str, '/', $n);
        $i != 3 && $n++;
    }
    $url=substr($str,0,$n);

    $order=pdo_get('mask_order',array('code'=>$logno));
    //更新订单状态和子订单状态
    pdo_update('mask_order_goods',array('state'=>2),array('order_id'=>$order['id']));
    pdo_update('mask_order',array('state'=>2),array('code'=>$logno));

    //自己的信息
    $nickname=pdo_getcolumn('mask_user', array('id' => $order['user_id']), 'nickname',1);

    //////////////////限制字符串长度/////////////////////////////
    $str=$nickname;
    //处理长度  不管中英文，都代表1个长度
    preg_match_all("/./us", $str, $match);
    $str_arr=$match[0];
    $length_val=count($str_arr);//字符串长度
    $show_str=implode('',$str_arr);//最后要显示的字符串
    //控制的显示长度
    $length_limit=5;
    //字符串超出控制长度，显示处理
    if($length_val>$length_limit){
        $show_str="";
        for ($i=0;$i<$length_limit;$i++){
            $show_str.=$str_arr[$i];
        }
        $show_str.="...";//最后以...代表后面字符省略
    }
    $nickname=$show_str;
    //////////////////////////////
    /// 随机生成数
    function randFloat($min, $max){
        return $min + mt_rand()/mt_getrandmax() * ($max-$min);
    }
    //新增交易记录，佣金分配
    //订单类型
    //$type=$order['type'];
    //1,判断推荐人身份（直推），间推
    $pid=pdo_getcolumn('mask_relation', array('uid' => $order['user_id']), 'pid',1);
    //通过商品id来判断分销类型
    $feelgood=pdo_get('mask_order_goods', array('order_id'=>$order['id'],'dishes_id'=>26));
    if ($feelgood){
        //领取免费面膜需要发给红包
        if ($pid){
            $hbdata['rtype']=8;
            $hbdata['rstate']=0;
            $hbdata['ruid']=$pid;
            $hbdata['rbuyername']=$nickname;
            $hbdata['rordernumber']=$order['order_num'];
            $card=pdo_get('mask_bankcard', array('uid'=>$pid));
            $hbdata['rcardid']=$card['id'];
            //随机红包
            $hbmoney=number_format(randFloat(0.1,0.5),2);
            $hbdata['rmoney']=$hbmoney;//红包奖励
            $hbdata['rcomment']=$nickname."推广红包区域奖励(".$hbmoney.")元";
            $hbdata['raddtime']=date('Y-m-d H:i:s',time());
            pdo_insert('mask_record',$hbdata);
        }
    }
    $testsql=" select * from ".tablename('mask_order_goods')." where dishes_id=24  and order_id='{$order['id']}'";
    $ress=pdo_fetch($testsql);
    $filenotify1=$_W['attachurl'].'wxloginfo1.txt';
    $getoginfo=json_encode($res);
    //file_put_contents($filenotify1,"sql=".$testsql."----结果=".$getoginfo);
    if ($ress){
        //直接升级会员身份level
        $uplevel=pdo_update('mask_user', array('level' => 1), array('id' => $order['user_id']));
        $filenotify=$_W['attachurl'].'wxloginfo.txt';
        //file_put_contents($filenotify,"用户id=".$order['user_id']."----结果=".$uplevel);
        if ($pid){
            //直推等级
            $puserinfo=pdo_get('mask_user', array('id'=>$pid));
            $onelevel=$puserinfo['level'];
            //代理记录
            $dldata['rtype']=1;
            $dldata['rstate']=0;
            $dldata['rmoney']=150;//直推奖励
            $dldata['ruid']=$pid;
            $dldata['rbuyername']=$nickname;
            $dldata['rordernumber']=$order['order_num']; //银卡
            $card=pdo_get('mask_bankcard', array('uid'=>$pid));
            $dldata['rcardid']=$card['id'];
            $dldata['rcomment']="合伙人奖励(150元)";
            $dldata['raddtime']=date('Y-m-d H:i:s',time());
            //银卡记录
            $ykdata['rtype']=1;
            $ykdata['rstate']=0;
            $ykdata['rmoney']=30;//直推奖励
            $ykdata['ruid']=$pid;
            $ykdata['rbuyername']=$nickname;
            $ykdata['rordernumber']=$order['order_num'];
            $card=pdo_get('mask_bankcard', array('uid'=>$pid));//银卡
            $ykdata['rcardid']=$card['id'];
            $ykdata['rcomment']="银卡销售额奖励(30元)";
            $ykdata['raddtime']=date('Y-m-d H:i:s',time());
            //金卡记录
            $jkdata['rtype']=1;
            $jkdata['rstate']=0;
            $jkdata['rmoney']=40;//直推奖励
            $jkdata['ruid']=$pid;
            $jkdata['rbuyername']=$nickname;
            $jkdata['rordernumber']=$order['order_num'];
            $card=pdo_get('mask_bankcard', array('uid'=>$pid)); //银卡
            $jkdata['rcardid']=$card['id'];
            $jkdata['rcomment']="金卡招商奖励(40元)";
            $jkdata['raddtime']=date('Y-m-d H:i:s',time());

            switch ($onelevel){
                case 1:
                    pdo_insert('mask_record',$dldata);
                    //上级只是代理还需要继续找银卡金卡
                    $ppid=findpid($pid);
                    if ($ppid){
                        findlevel($ppid,$order['order_num'],$nickname);
                    }
                    //更新余额
                    //pdo_update('mask_user', array('wallet +=' => 150), array('id' => $pid));
                    break;
                case 2:
                    pdo_insert('mask_record',$dldata);
                    pdo_insert('mask_record',$ykdata);
                    //更新余额
                    //pdo_update('mask_user', array('wallet +=' => 180), array('id' => $pid));
                    //银卡
                    //去上级找金卡
                    $fpid=findpid($pid);
                    if ($fpid){
                        findkinklevel($fpid,$ordernum,$nickname);
                    }
                    break;
                case 3:
                    //金卡
                    pdo_insert('mask_record',$dldata);
                    pdo_insert('mask_record',$ykdata);
                    pdo_insert('mask_record',$jkdata);
                    //更新余额
                    //pdo_update('mask_user', array('wallet +=' => 220), array('id' => $pid));
                    break;
                default:
                    //寻找推荐人上级是否是金银卡
                    $ppid=findpid($pid);
                    if ($ppid){
                        findlevel($ppid,$order['order_num'],$nickname);
                    }
            }
            //市代记录
            $sddata['rtype']=1;
            $sddata['rstate']=0;
            $sddata['rmoney']=8;//直推奖励
            $sddata['rbuyername']=$nickname;
            $sddata['rordernumber']=$order['order_num'];
            $sddata['rcomment']="市代区域奖励(8元)";
            $sddata['raddtime']=date('Y-m-d H:i:s',time());
            //省代记录
            $shendaidata['rtype']=1;
            $shendaidata['rstate']=0;
            $shendaidata['rmoney']=4;//直推奖励
            $shendaidata['rbuyername']=$nickname;
            $shendaidata['rordernumber']=$order['order_num'];
            $shendaidata['rcomment']="省代区域奖励(4元)";
            $shendaidata['raddtime']=date('Y-m-d H:i:s',time());
            //获取上级
            function findpid($uid){
                $ppid=pdo_getcolumn('mask_relation', array('uid' => $uid), 'pid',1);
                if ($ppid){
                    return $ppid;
                }else{
                    return '';
                }
            }
            //金银卡都找
            function findlevel($uid,$ordernum,$nickname){
                //间推中的银卡记录
                $ykdata['rtype']=1;
                $ykdata['rstate']=0;
                $ykdata['rmoney']=30;//直推奖励
                $ykdata['ruid']=$uid;
                $ykdata['rbuyername']=$nickname;
                $ykdata['rordernumber']=$ordernum;
                $card=pdo_get('mask_bankcard', array('uid'=>$uid));
                $ykdata['rcardid']=$card['id'];
                $ykdata['rcomment']="银卡销售额奖励(30元)";
                $ykdata['raddtime']=date('Y-m-d H:i:s',time());
                //间推中的金卡记录
                $jkdata['rtype']=1;
                $jkdata['rstate']=0;
                $jkdata['rmoney']=40;//直推奖励
                $jkdata['ruid']=$uid;
                $jkdata['rbuyername']=$nickname;
                $jkdata['rordernumber']=$ordernum;
                $card=pdo_get('mask_bankcard', array('uid'=>$uid));
                $jkdata['rcardid']=$card['id'];
                $jkdata['rcomment']="金卡招商奖励(40元)";
                $jkdata['raddtime']=date('Y-m-d H:i:s',time());

                $ppinfo=pdo_get('mask_user', array('id'=>$uid));
                $ponelevel=$ppinfo['level'];
                if ($ponelevel==2){
                    //银卡
                    pdo_insert('mask_record',$ykdata);
                    $fpid=findpid($uid);
                    if ($fpid){
                        //银卡找到，就往上只找金卡了
                        findkinklevel($fpid,$ordernum,$nickname);
                    }
                }else if($ponelevel==3){
                    //金卡
                    pdo_insert('mask_record',$ykdata);
                    pdo_insert('mask_record',$jkdata);
                }else{
                    $fpid=findpid($uid);
                    if ($fpid){
                        findlevel($fpid,$ordernum,$nickname);
                    }
                }
            }
            //只找金卡
            function findkinklevel($uid,$ordernum,$nickname){
                //间推中的金卡记录
                $jkdata['rtype']=1;
                $jkdata['rstate']=0;
                $jkdata['rmoney']=40;//直推奖励
                $jkdata['ruid']=$uid;
                $jkdata['rbuyername']=$nickname;
                $jkdata['rordernumber']=$ordernum;
                $card=pdo_get('mask_bankcard', array('uid'=>$uid));
                $jkdata['rcardid']=$card['id'];
                $jkdata['rcomment']="金卡招商奖励(40元)";
                $jkdata['raddtime']=date('Y-m-d H:i:s',time());

                $ppinfo=pdo_get('mask_user', array('id'=>$uid));
                $ponelevel=$ppinfo['level'];
                if($ponelevel==3){
                    //金卡
                    pdo_insert('mask_record',$jkdata);
                }else{
                    $fpid=findpid($uid);
                    if ($fpid){
                        findkinklevel($fpid,$ordernum,$nickname);
                    }
                }
            }
            //市代
            $cityaddress=pdo_getall('mask_areaagent',array('state'=>1));
            $orderaddressarr=explode('-',$order['address']);
            foreach ($cityaddress as $k=>$v){
                $addressarr=explode('-',$v['address']);
                if ($addressarr[1]==$orderaddressarr[1]){
                    $sddata['ruid']=$v['uid'];
                    $card=pdo_get('mask_bankcard', array('uid'=>$v['uid'])); //银卡
                    $sddata['rcardid']=$card['id'];
                    pdo_insert('mask_record',$sddata);
                    //更新余额
                    //pdo_update('mask_user', array('wallet +=' => 228), array('id' => $pid));
                }
            }
            //省代
            foreach ($cityaddress as $k=>$v){
                $addressarr=explode('-',$v['address']);
                if ($addressarr[0]==$orderaddressarr[0]){
                    $shendaidata['ruid']=$v['uid'];
                    $card=pdo_get('mask_bankcard', array('uid'=>$v['uid'])); //银卡
                    $shendaidata['rcardid']=$card['id'];
                    pdo_insert('mask_record',$shendaidata);
                    //更新余额
                    //pdo_update('mask_user', array('wallet +=' => 228), array('id' => $pid));
                }
            }
            //间推等级
            $twopid=pdo_getcolumn('mask_relation', array('uid' => $pid), 'pid',1);
            if ($twopid){
                $jtdata['rtype']=2;
                $jtdata['rstate']=0;
                $jtdata['rmoney']=48;//间推奖励
                $jtdata['ruid']=$twopid;
                $jtdata['rbuyername']=$nickname;
                $jtdata['rordernumber']=$order['order_num'];
                $card=pdo_get('mask_bankcard', array('uid'=>$twopid));
                $jtdata['rcardid']=$card['id'];
                $jtdata['rcomment']="二级合伙人奖励(48元)";
                $jtdata['raddtime']=date('Y-m-d H:i:s',time());
                $jup=pdo_insert('mask_record',$jtdata);
                if ($jup){
                    //更新余额
                    //pdo_update('mask_user', array('wallet +=' => 48), array('id' => $pid));
                }
            }

        }
    }

    //修改库存量

//	$czorder=pdo_get('mask_czorder',array('code'=>$logno));
//	$hyorder=pdo_get('mask_hyorder',array('code'=>$logno));
//	$qgorder=pdo_get('mask_qgorder',array('code'=>$logno));
//	$grouporder=pdo_get('mask_grouporder',array('code'=>$logno));
//	if($grouporder['state']==1){
//	pdo_update('mask_grouporder',array('state'=>2,'pay_time'=>time()),array('id'=>$grouporder['id']));
//	//改变商品
//	pdo_update('mask_groupgoods',array('ysc_num +='=>$grouporder['goods_num'],'inventory -='=>$grouporder['goods_num']),array('id'=>$grouporder['goods_id']));
//	if($grouporder['group_id']>0){
//	$count=pdo_get('mask_grouporder', array('group_id'=>$grouporder['group_id'],'state '=>2), array('count(user_id) as count'));
//	$group=pdo_get('mask_group',array('id'=>$grouporder['group_id']));
//	if($group['kt_num']==$count['count']){
//		$state=2;
//	}else{
//		$state=1;
//	}
//	//改变团状态
//	pdo_update('mask_group',array('state'=>$state,'yg_num +='=>1),array('id'=>$grouporder['group_id']));
//	}
//	}
//	if($qgorder['state']==1){
//		$time=time();
//		$good=pdo_get('mask_qggoods',array('id'=>$qgorder['good_id']));
//		$dq_time=$time+$good['consumption_time']*60*60*24;
//		pdo_update('mask_qgorder',array('state'=>2,'dq_time'=>$dq_time,'pay_time'=>date('Y-m-d H:i:s',$time)),array('id'=>$qgorder['id']));
//	}
//	if($hyorder and $hyorder['state']==1){
//		pdo_update('mask_hyorder',array('state'=>2,'day'=>date('d'),'time'=>date('Y-m-d H:i:s')),array('id'=>$hyorder['id']));
//		pdo_update('mask_user',array('dq_time'=>date('Y-m-d',strtotime("+".$hyorder['month']." month")),'hy_day'=>date('d'),'user_name'=>$hyorder['user_name'],'user_tel'=>$hyorder['user_tel']),array('id'=>$hyorder['user_id']));
//	}
//	if($czorder and $czorder['state']==1){
//		pdo_update('mask_czorder',array('state'=>2),array('id'=>$czorder['id']));
//		file_get_contents("".$url."/app/index.php?i=".$czorder['uniacid']."&c=entry&a=wxapp&do=Recharge&m=mask&user_id=".$czorder['user_id']."&money=".$czorder['money']."&money2=".$czorder['money2']);//改变订单状态
//		file_get_contents("".$url."/app/index.php?i=".$czorder['uniacid']."&c=entry&a=wxapp&do=CzMessage&m=mask&order_id=".$czorder['id']);//改变订单状态
//	}
    if($order['state']==1){
        //下单成功模板消息
        //file_get_contents("".$url."/app/index.php?i=".$order['uniacid']."&c=entry&a=wxapp&do=NewOrderMessage&m=mask&order_id=".$order['id']);//模板消息
        //file_get_contents("".$url."/app/index.php?i=".$order['uniacid']."&c=entry&a=wxapp&do=payorder&m=mask&order_id=".$order['id']);
        //file_get_contents("".$url."/app/index.php?i=".$order['uniacid']."&c=entry&a=wxapp&do=Message&m=mask&order_id=".$order['id']);//模板消息
        //file_get_contents("".$url."/app/index.php?i=".$order['uniacid']."&c=entry&a=wxapp&do=QtPrint&m=mask&order_id=".$order['id']);//打印机
        //file_get_contents("".$url."/app/index.php?i=".$order['uniacid']."&c=entry&a=wxapp&do=HcPrint&m=mask&order_id=".$order['id']);//打印机
        //file_get_contents("".$url."/app/index.php?i=".$order['uniacid']."&c=entry&a=wxapp&do=sms&m=mask&type=1&store_id=".$order['store_id']);//短信
        //分销佣金
        //file_get_contents("".$url."/app/index.php?i=".$order['uniacid']."&c=entry&a=wxapp&do=JsCommission&m=mask&order_id=".$order['id']);
    }
//	if($order['type']==2 and $order['dn_state']==1){//店内
//
//		file_get_contents("".$url."/app/index.php?i=".$order['uniacid']."&c=entry&a=wxapp&do=payorder&m=mask&order_id=".$order['id']);
//		file_get_contents("".$url."/app/index.php?i=".$order['uniacid']."&c=entry&a=wxapp&do=sms&m=mask&type=2&store_id=".$order['store_id']);//短信
//		file_get_contents("".$url."/app/index.php?i=".$order['uniacid']."&c=entry&a=wxapp&do=QtPrint&m=mask&order_id=".$order['id']);//打印机
//		file_get_contents("".$url."/app/index.php?i=".$order['uniacid']."&c=entry&a=wxapp&do=HcPrint&m=mask&order_id=".$order['id']);//打印机
//		file_get_contents("".$url."/app/index.php?i=".$order['uniacid']."&c=entry&a=wxapp&do=addintegral&m=mask&type=2&order_id=".$order['id']);//短信
//			//分销佣金
//	file_get_contents("".$url."/app/index.php?i=".$order['uniacid']."&c=entry&a=wxapp&do=JsCommission&m=mask&order_id=".$order['id']);
//	}
//	if($order['type']==3 and $order['yy_state']==1){//预约
//		pdo_update('mask_order',array('yy_state'=>2),array('code'=>$logno));
//		file_get_contents("".$url."/app/index.php?i=".$order['uniacid']."&c=entry&a=wxapp&do=sms&m=mask&type=3&store_id=".$order['store_id']);//短信
//			//分销佣金
//	file_get_contents("".$url."/app/index.php?i=".$order['uniacid']."&c=entry&a=wxapp&do=JsCommission&m=mask&order_id=".$order['id']);
//	}
//	if($order['type']==4 and $order['dm_state']==1){//当面付
//		file_get_contents("".$url."/app/index.php?i=".$order['uniacid']."&c=entry&a=wxapp&do=NewDmOrderMessage&m=mask&order_id=".$order['order_id']);//短信
//		$res=pdo_update('mask_order',array('dm_state'=>2),array('id'=>$order['id']));
//		file_get_contents("".$url."/app/index.php?i=".$order['uniacid']."&c=entry&a=wxapp&do=sms&m=mask&type=2&store_id=".$order['store_id']);//短信
//		file_get_contents("".$url."/app/index.php?i=".$order['uniacid']."&c=entry&a=wxapp&do=QtPrint&m=mask&order_id=".$order['id']);//打印机
//		file_get_contents("".$url."/app/index.php?i=".$order['uniacid']."&c=entry&a=wxapp&do=addintegral&m=mask&type=5&order_id=".$order['id']);//短信
//			//分销佣金
//	file_get_contents("".$url."/app/index.php?i=".$order['uniacid']."&c=entry&a=wxapp&do=JsCommission&m=mask&order_id=".$order['id']);
//
//	}

//	$store=pdo_get('mask_store',array('code'=>$logno));
//		if($store['zf_state']==1){
//			  $res=pdo_update('mask_store',array('zf_state'=>2),array('id'=>$store['id']));
//			   file_get_contents("".$url."/app/index.php?i=".$store['uniacid']."&c=entry&a=wxapp&do=SaveRzLog&m=mask&store_id=".$store['id']."&money=".$store['money']);
//
//		}
    $result = array(
        'return_code' => 'SUCCESS',
        'return_msg' => 'OK'
    );
    echo array2xml($result);
    exit;
}else{
    //订单已经处理过了
    $result = array(
        'return_code' => 'SUCCESS',
        'return_msg' => 'OK'
    );
    echo array2xml($result);
    exit;
}