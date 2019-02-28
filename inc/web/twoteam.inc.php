<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();

if($_GPC['keywords']){
    $op=$_GPC['keywords'];
    $where="%$op%";
}else{
    $where='%%';
}
$pageindex = max(1, intval($_GPC['page']));
$pagesize=10;
if($_GPC['types']=='find'){
    $list=pdo_getall('mask_user', array('id'=>$op,'uniacid'=>$_W['uniacid']));
    $pager = pagination(1, $pageindex, $pagesize);
}else if (empty($_GPC['keywords'])){
    $oneteam=pdo_fetchall("select * from " . tablename("mask_relation") . " r"  . " left join " . tablename("mask_user") .
        " u on r.uid=u.id"." where r.uniacid={$_W['uniacid']} and u.level in (1,2,3,4,5) and r.pid={$_GPC['uid']}" );
    $twoteamarrforpage=array();
    $twoteamarr=array();
    foreach ($oneteam as $k=>$v){
        $getteam=pdo_fetchall("select * from " . tablename("mask_relation") . " r"  . " left join " . tablename("mask_user") .
            " u on r.uid=u.id"." where r.uniacid={$_W['uniacid']} and r.pid={$v['uid']} and u.level in (1,2,3,4,5) and (u.nickname LIKE :name || u.user_tel LIKE :name || u.id LIKE :name)",array(':name'=>$where));
        $getteamforpage=pdo_fetchall("select * from " . tablename("mask_relation") . " r"  . " left join " . tablename("mask_user") .
            " u on r.uid=u.id"." where r.uniacid={$_W['uniacid']} and r.pid={$v['uid']} and u.level in (1,2,3,4,5) and (u.nickname LIKE :name || u.user_tel LIKE :name || u.id LIKE :name)".
            " LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize,array(':name'=>$where));
        if ($getteamforpage){
            foreach ($getteamforpage as $k=>$v){
                array_push($twoteamarrforpage,$v);
            }
        }
        if ($getteam){
            foreach ($getteam as $k=>$v){
                array_push($twoteamarr,$v);
            }
        }
    }
    $list=$twoteamarrforpage;
    $pager = pagination(count($twoteamarr), $pageindex, $pagesize);
}

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
include $this->template('web/twoteam');