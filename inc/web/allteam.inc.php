<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$leveltype=isset($_GPC['leveltype'])?$_GPC['leveltype']:0;
if($_GPC['keywords']){
    $op=$_GPC['keywords'];
    $where="%$op%";
}else{
    $where='%%';
}

/*	$sql="select *  from " . tablename("mask_user") ." WHERE  name LIKE :name  and uniacid=:uniacid";
$list=pdo_fetchall($sql,array(':name'=>$where,'uniacid'=>$_W['uniacid']));*/
$pageindex = max(1, intval($_GPC['page']));
$pagesize=10;

if ($leveltype){
    $wherelevel=' level='.$leveltype;
}else{
    $wherelevel=' level in (1,2,3,4,5) ';
}
$sql="select *  from " . tablename("mask_user") ." WHERE  ".$wherelevel." and (nickname LIKE :name || user_tel LIKE :name || id LIKE :name) and uniacid=:uniacid order by  level desc";
$select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
$list = pdo_fetchall($select_sql,array(':uniacid'=>$_W['uniacid'],':name'=>$where));
foreach ($list as $k=>$v){
    $nodeal = pdo_fetch("SELECT sum(rmoney) as con FROM ".tablename('mask_record').
        " WHERE  ruid ={$v['id']} and rsettlement=0 and rtype <> 7 ");
    $deal = pdo_fetch("SELECT sum(rmoney) as con FROM ".tablename('mask_record').
        " WHERE ruid ={$v['id']} and rsettlement=1 and rtype <> 7 ");
    //查询已退款的待结算记录
    $tknodeal = pdo_fetch("SELECT sum(rmoney) as cons FROM ".tablename('mask_record').
        " WHERE  ruid ={$v['id']} and rtype <> 7 and rstate=1 and rsettlement=0 ");
    //待结算和已结算
    $list[$k]['nosettlement']=number_format($nodeal['con']-$tknodeal['cons'],2);
    $list[$k]['settlement']=number_format($deal['con'],2);
    //总收益
    $allrecord = pdo_fetch("SELECT sum(rmoney) as con FROM ".tablename('mask_record')." WHERE  ruid ={$v['id']} and  rtype <> 7 and rtype<>9");
    $list[$k]['alltotal']=number_format($allrecord['con']-$tknodeal['cons'],2);
    //剩余大米
    $tixian=pdo_fetch("SELECT sum(rsqmoney) as con FROM ".tablename('mask_record').
        " WHERE ruid ={$v['id']} and rtype = 7 and risrefu=0 ");
    $yuepay=pdo_fetch("SELECT sum(rmoney) as con FROM ".tablename('mask_record')." WHERE ruid ={$v['id']} and rtype = 9 ");
    $truemoney=number_format($deal['con']-$tixian['con']-$yuepay['con'],2);
    $list[$k]['dami']=$truemoney;
    $list[$k]['tixian']=number_format($tixian['con'],2);
    $list[$k]['yuepay']=number_format($yuepay['con'],2);
}
$timewhere='';
if($_GPC['time']){
    $start=$_GPC['time']['start'];
    $end=$_GPC['time']['end'];
    $timewhere.=" and raddtime >='{$start}' and raddtime<='{$end}'";
}
//总待结算和已结算
$nodeal = pdo_fetch("SELECT sum(rmoney) as con FROM ".tablename('mask_record').
    " WHERE  rsettlement=0 and rtype <> 7 ".$timewhere);
$deal = pdo_fetch("SELECT sum(rmoney) as con FROM ".tablename('mask_record').
    " WHERE rsettlement=1 and rtype <> 7 ".$timewhere);
//查询已退款的待结算记录
$tknodeal = pdo_fetch("SELECT sum(rmoney) as cons FROM ".tablename('mask_record').
    " WHERE  rtype <> 7 and rstate=1 and rsettlement=0 ".$timewhere);
$allnosettlement=number_format($nodeal['con']-$tknodeal['cons'],2);
$allsettlement=number_format($deal['con'],2);
//总剩余大米
$tixian=pdo_fetch("SELECT sum(rsqmoney) as con FROM ".tablename('mask_record').
    " WHERE rtype = 7 and risrefu=0 ".$timewhere);
$yuepay=pdo_fetch("SELECT sum(rmoney) as con FROM ".tablename('mask_record')." WHERE rtype = 9 ".$timewhere);
$truemoney=number_format($deal['con']-$tixian['con']-$yuepay['con'],2);
$alldami=$truemoney;
//总累计收益
$allrecord = pdo_fetch("SELECT sum(rmoney) as con FROM ".tablename('mask_record')." WHERE rtype <> 7 and rtype<>9".$timewhere);
$allalltotal=number_format($allrecord['con']-$tknodeal['cons'],2);
//总提现
$alltixian=number_format($tixian['con'],2);
//余额支付
$yuepay1=number_format($yuepay['con'],2);

$total=pdo_fetchcolumn("select count(*) from " . tablename("mask_user") .
    " WHERE ".$wherelevel." and  (nickname LIKE :name || user_tel LIKE :name || id LIKE :name) and uniacid=:uniacid ",array(':uniacid'=>$_W['uniacid'],':name'=>$where));
$pager = pagination($total, $pageindex, $pagesize);
//	if($_GPC['id']){
//		$res4=pdo_delete("mask_user",array('u_id'=>$_GPC['id']));
//		if($res4){
//		 message('删除成功！', $this->createWebUrl('user'), 'success');
//		}else{
//			  message('删除失败！','','error');
//		}
//	}
if($_GPC['op']=='changelevel'){
    $getlevel=$_GPC['level'];
    //更改订单操作
    $rst=pdo_update('mask_user',array('level'=>$getlevel),array('id'=>$_GPC['id']));
    if ($rst){
        echo json_encode(array('code'=>1));
    }else{
        echo json_encode(array('code'=>0));
    }
}
if(checksubmit('submit2')){
    $res=pdo_update('mask_user',array('wallet +='=>$_GPC['reply']),array('id'=>$_GPC['id2']));
    if($res){
        $data['money']=$_GPC['reply'];
        $data['user_id']=$_GPC['id2'];
        if($_GPC['reply']<0){
            $data['type']=2;
            $data['note']='后台扣分';
        }else{
            $data['type']=1;
            $data['note']='后台充值';
        }
        $data['time']=date('Y-m-d H:i:s');
        $res2=pdo_insert('mask_qbmx',$data);
        if($res2){
            message('充值成功！', $this->createWebUrl('user'), 'success');
        }else{
            message('充值失败！','','error');
        }
    }
}
if(checksubmit('submit3')){
    if($_GPC['reply']<0){
        $data['type']=2;
        $data['note']='后台扣分';
    }else{
        $data['type']=1;
        $data['note']='后台充值';
    }
    if($_GPC['reply']!=0){
        $res=pdo_update('mask_user',array('total_score +='=>$_GPC['reply']),array('id'=>$_GPC['id3']));
    }

    if($res){
        $data['score']=abs($_GPC['reply']);
        $data['user_id']=$_GPC['id3'];
        $data['cerated_time']=date('Y-m-d H:i:s');
        $data['uniacid']=$_W['uniacid'];//小程序id
        $res2=pdo_insert('mask_integral',$data);
        if($res2){
            message('充值成功！', $this->createWebUrl('user'), 'success');
        }else{
            message('充值失败！','','error');
        }
    }
}
if(checksubmit('submit4')){
    $res=pdo_update('mask_user',array('user_address '=>''),array('uniacid'=>$_W['uniacid']));
    if($res){
        message('清空成功', $this->createWebUrl('user'), 'success');
    }else{
        message('清空失败！','','error');
    }

}
include $this->template('web/allteam');