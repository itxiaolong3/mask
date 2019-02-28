<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();

if($_GPC['keywords']){
    $op=$_GPC['keywords'];
    $where="%$op%";
}else{
    $where='%%';
}
$getuid=$_GPC['uid'];
$nickname=pdo_get('mask_user', array('id'=>$getuid,'uniacid'=>$_W['uniacid']),array('nickname'));
/*	$sql="select *  from " . tablename("mask_user") ." WHERE  name LIKE :name  and uniacid=:uniacid";
$list=pdo_fetchall($sql,array(':name'=>$where,'uniacid'=>$_W['uniacid']));*/
$pageindex = max(1, intval($_GPC['page']));
$pagesize=10;
if($_GPC['types']=='up'){
    //上级查询
    $pid=pdo_getcolumn('mask_relation', array('uid' => $_GPC['uid']), 'pid',1);
    if ($pid){
        //有推荐人
        $list=pdo_getall('mask_user', array('id'=>$pid,'uniacid'=>$_W['uniacid']));
        $pager = pagination(1, $pageindex, $pagesize);
    }else{
        //无推荐人
        $list=array();
        $pager = pagination(0, $pageindex, $pagesize);
    }
}else if($_GPC['types']=='find'){
    $list=pdo_getall('mask_user', array('id'=>$op,'uniacid'=>$_W['uniacid']));
    $pager = pagination(1, $pageindex, $pagesize);
}else{
    //一级团队合伙人数量
    $hcountwhere=" WHERE r.uniacid=:uniacid and r.pid=:pid and u.level>0";
    $countdatas[':uniacid']=$_W['uniacid'];
    $countdatas[':pid']=$_GPC['uid'];
    $hehuocount=pdo_fetchcolumn("select count(*) from " . tablename("mask_relation") . " r"  . " left join " . tablename("mask_user") . " u on r.uid=u.id".$hcountwhere,$countdatas);
/////////
    $sql="select *  from " . tablename("mask_relation") ."r". " left join " . tablename("mask_user") .
        " u on r.uid=u.id"." WHERE  u.level in (1,2,3,4,5) and (u.nickname LIKE :name || u.user_tel LIKE :name || u.id LIKE :name)
 and r.uniacid=:uniacid and r.pid={$_GPC['uid']} order by  u.level desc";
    $select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
    $list = pdo_fetchall($select_sql,array(':uniacid'=>$_W['uniacid'],':name'=>$where));
    $total=pdo_fetchcolumn("select count(*) from " . tablename("mask_relation")."r". " left join " . tablename("mask_user") .
        " u on r.uid=u.id" . " WHERE u.level in (1,2,3,4,5) and  (u.nickname LIKE :name || u.user_tel LIKE :name || u.id LIKE :name) and u.uniacid=:uniacid and r.pid={$_GPC['uid']} ",array(':uniacid'=>$_W['uniacid'],':name'=>$where));
    $pager = pagination($total, $pageindex, $pagesize);
}

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
include $this->template('web/oneteam');