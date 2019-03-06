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
    $filename1=$_W['attachurl'].'newpaynotify.txt';
    file_put_contents($filename,$data['result_code'].'==result_code'.$data['return_code'].'===return_code');
    file_put_contents($filename1,json_encode($res));
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
    $getoid=trim($res['attach']);
    //pdo_update('mask_order',array('state'=>2),array('code'=>$logno));
    pdo_update('mask_order',array('state'=>2),array('id'=>$getoid));
    //$order=pdo_get('mask_order',array('code'=>$logno));
    $order=pdo_get('mask_order',array('id'=>$getoid));
    //更新订单状态和子订单状态
    pdo_update('mask_order_goods',array('state'=>2),array('order_id'=>$order['id']));


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
            $isava=pdo_get('mask_record', array('ruid'=>$pid,'rordernumber'=>$order['order_num']));
            if (empty($isava)){
                pdo_insert('mask_record',$hbdata);
            }
        }
    }
    $testsql=" select * from ".tablename('mask_order_goods')." where dishes_id=24  and order_id='{$order['id']}'";
    $ress=pdo_fetch($testsql);
    $filenotify1=$_W['attachurl'].'xiaolonglaji.txt';
    if ($ress){
        //直接升级会员身份level
        $uplevel=pdo_update('mask_user', array('level' => 1), array('id' => $order['user_id']));
        $filenotify=$_W['attachurl'].'wxloginfo.txt';
        //file_put_contents($filenotify,"用户id=".$order['user_id']."----结果=".$uplevel);
        if ($pid){
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
                $isava=pdo_get('mask_record', array('ruid'=>$twopid,'rordernumber'=>$order['order_num'],'rmoney'=>48));
                if (empty($isava)){
                    pdo_insert('mask_record',$jtdata);
                }

            }
            //市代记录
            $sddata['rtype']=5;
            $sddata['rstate']=0;
            $sddata['rmoney']=8;//直推奖励
            $sddata['rbuyername']=$nickname;
            $sddata['rordernumber']=$order['order_num'];
            $sddata['rcomment']="市代区域奖励(8元)";
            $sddata['raddtime']=date('Y-m-d H:i:s',time());
            //省代记录
            $shendaidata['rtype']=6;
            $shendaidata['rstate']=0;
            $shendaidata['rmoney']=4;//直推奖励
            $shendaidata['rbuyername']=$nickname;
            $shendaidata['rordernumber']=$order['order_num'];
            $shendaidata['rcomment']="省代区域奖励(4元)";
            $shendaidata['raddtime']=date('Y-m-d H:i:s',time());
            //市代
            $cityaddress=pdo_getall('mask_areaagent',array('state'=>1));
            $orderaddressarr=explode('-',$order['address']);
            foreach ($cityaddress as $k=>$v){
                $addressarr=explode('-',$v['address']);
                //先查询该区域商是省代还是市代
                //省代
                if ($v['leveltype']==2){
                    if ($addressarr[0]==$orderaddressarr[0]){
                        $shendaidata['ruid']=$v['uid'];
                        $card=pdo_get('mask_bankcard', array('uid'=>$v['uid'])); //银卡
                        $shendaidata['rcardid']=$card['id'];
                        $isava=pdo_get('mask_record', array('ruid'=>$v['uid'],'rordernumber'=>$order['order_num'],'rmoney'=>4));
                        if (empty($isava)){
                            pdo_insert('mask_record',$shendaidata);
                        }
                        //更新余额
                        //pdo_update('mask_user', array('wallet +=' => 228), array('id' => $pid));
                    }
                }else if($v['leveltype']==1){
                    //市代
                    if ($addressarr[1]==$orderaddressarr[1]){
                        $sddata['ruid']=$v['uid'];
                        $card=pdo_get('mask_bankcard', array('uid'=>$v['uid'])); //银卡
                        $sddata['rcardid']=$card['id'];
                        $isava=pdo_get('mask_record', array('ruid'=>$v['uid'],'rordernumber'=>$order['order_num'],'rmoney'=>8));
                        if (empty($isava)){
                            pdo_insert('mask_record',$sddata);
                        }
                        //更新余额
                        //pdo_update('mask_user', array('wallet +=' => 228), array('id' => $pid));
                    }
                }
            }

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
            $ykdata['rtype']=3;
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
            $jkdata['rtype']=4;
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
                    $onelevelfilename=$_W['attachurl'].'onelevelfilename.txt';
                    $onelevelfilename1=$_W['attachurl'].'onmore.txt';
                    $isava=pdo_get('mask_record', array('ruid'=>$pid,'rordernumber'=>$order['order_num'],'rmoney'=>150));
                    if (empty($isava)){
                        pdo_insert('mask_record',$dldata);
                    }
                    //上级只是代理还需要继续找银卡金卡
                    $ppid=pdo_getcolumn('mask_relation', array('uid' => $pid), 'pid',1);
                    if ($ppid){
                        file_put_contents($onelevelfilename,"直推人的上级id=".$ppid,FILE_APPEND);
                        findlevel($ppid,$order['order_num'],$nickname);
                    }
                    //更新余额
                    //pdo_update('mask_user', array('wallet +=' => 150), array('id' => $pid));
                    break;
                case 2:
                    $isava=pdo_get('mask_record', array('ruid'=>$pid,'rordernumber'=>$order['order_num'],'rmoney'=>150));
                    if (empty($isava)){
                        pdo_insert('mask_record',$dldata);
                    }
                    $isava2=pdo_get('mask_record', array('ruid'=>$pid,'rordernumber'=>$order['order_num'],'rmoney'=>30));
                    if (empty($isava2)){
                        pdo_insert('mask_record',$ykdata);
                    }
                    //银卡
                    //去上级找金卡
                    $fpid=pdo_getcolumn('mask_relation', array('uid' => $pid), 'pid',1);
                    if ($fpid){
                        findkinklevel($fpid,$order['order_num'],$nickname);
                        // echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
                    }else{
                        // echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
                        // die();
                    }
                    break;
                case 3:
                    //金卡
                    $isava=pdo_get('mask_record', array('ruid'=>$pid,'rordernumber'=>$order['order_num'],'rmoney'=>150));
                    if (empty($isava)){
                        pdo_insert('mask_record',$dldata);
                    }
                    $isava2=pdo_get('mask_record', array('ruid'=>$pid,'rordernumber'=>$order['order_num'],'rmoney'=>30));
                    if (empty($isava2)){
                        pdo_insert('mask_record',$ykdata);
                    }
                    $isava3=pdo_get('mask_record', array('ruid'=>$pid,'rordernumber'=>$order['order_num'],'rmoney'=>40));
                    if (empty($isava3)){
                        pdo_insert('mask_record',$jkdata);
                    }
                    break;
                default:
                    //寻找推荐人上级是否是金银卡
                    $ppid=pdo_getcolumn('mask_relation', array('uid' => $pid), 'pid',1);
                    if ($ppid){
                        findlevel($ppid,$order['order_num'],$nickname);
                        // echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
                    }
            }

            $result = array(
                'return_code' => 'SUCCESS',
                'return_msg' => 'OK'
            );
            echo array2xml($result);
            exit;
//            echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
//            die();
        }
        $result = array(
            'return_code' => 'SUCCESS',
            'return_msg' => 'OK'
        );
        echo array2xml($result);
        exit;
    }
    $result = array(
        'return_code' => 'SUCCESS',
        'return_msg' => 'OK'
    );
    echo array2xml($result);
    exit;
    //修改库存量
}else{
    //订单已经处理过了
    $result = array(
        'return_code' => 'SUCCESS',
        'return_msg' => 'OK'
    );
    echo array2xml($result);
    exit;
}
//金银卡都找
function findlevel($uid,$ordernum,$nickname){
    //间推中的银卡记录
    $ykdata['rtype']=3;
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
    $jkdata['rtype']=4;
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
        $isava4=pdo_get('mask_record', array('ruid'=>$uid,'rordernumber'=>$ordernum,'rmoney'=>30));
        if (empty($isava4)){
            pdo_insert('mask_record',$ykdata);
        }
        $fpid=pdo_getcolumn('mask_relation', array('uid' => $uid), 'pid',1);
        if ($fpid){
            //银卡找到，就往上只找金卡了
            findkinklevel($fpid,$ordernum,$nickname);
        }
    }else if($ponelevel==3){
        //金卡
        $isava5=pdo_get('mask_record', array('ruid'=>$uid,'rordernumber'=>$ordernum,'rmoney'=>30));
        if (empty($isava5)){
            pdo_insert('mask_record',$ykdata);
        }
        $isava6=pdo_get('mask_record', array('ruid'=>$uid,'rordernumber'=>$ordernum,'rmoney'=>40));
        if (empty($isava6)){
            pdo_insert('mask_record',$jkdata);
        }
        //echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    }else{
        $fpid=pdo_getcolumn('mask_relation', array('uid' => $uid), 'pid',1);
        if ($fpid){
            findlevel($fpid,$ordernum,$nickname);
        }
    }

}
//只找金卡
function findkinklevel($uid,$ordernum,$nickname){
    //间推中的金卡记录
    $jkdata['rtype']=4;
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
        $isava7=pdo_get('mask_record', array('ruid'=>$uid,'rordernumber'=>$ordernum,'rmoney'=>40));
        if (empty($isava7)){
            pdo_insert('mask_record',$jkdata);
        }
    }else{
        $fpid=pdo_getcolumn('mask_relation', array('uid' => $uid), 'pid',1);
        if ($fpid){
            findkinklevel($fpid,$ordernum,$nickname);
        }
    }
}