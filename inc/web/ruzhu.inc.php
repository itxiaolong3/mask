<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();

$pageindex = max(1, intval($_GPC['page']));
$pagesize=10;
$where=' WHERE  uniacid=:uniacid';
$data[':uniacid']=$_W['uniacid'];
if($_GPC['keywords']){
    $op=$_GPC['keywords'];
    $where.=" and (name LIKE  concat('%', :name,'%') || phone LIKE  concat('%', :name,'%'))";
    $data[':name']=$op;
} 
  $sql="SELECT * FROM ".tablename('mask_areaagent') .  "  ". $where." ORDER BY id DESC";
  $total=pdo_fetchcolumn("SELECT count(*) FROM ".tablename('mask_areaagent') .  "".$where." ORDER BY id DESC",$data);

$list=pdo_fetchall( $sql,$data);
$select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
$list=pdo_fetchall($select_sql,$data);
$pager = pagination($total, $pageindex, $pagesize);

$operation=$_GPC['op'];
if($operation=='adopt'){//审核通过
    $id=$_GPC['id'];
    $types=$_GPC['type'];
    $res=pdo_update('mask_areaagent',array('state'=>1),array('id'=>$id));
    $uid=pdo_getcolumn('mask_areaagent', array('id' => $id), 'uid',1);
    if ($types==1){//市代
        $updatalevel=pdo_update('mask_user',array('level'=>4),array('id'=>$uid));
    }else if ($types==2){//省代
        $updatalevel=pdo_update('mask_user',array('level'=>5),array('id'=>$uid));
    }

    if($res){
        //更新身份
        message('审核成功',$this->createWebUrl('ruzhu',array()),'success');
    }else{
        message('审核失败','','error');
    }
}
if($operation=='reject'){
     $id=$_GPC['id'];
    $res=pdo_update('mask_areaagent',array('state'=>2),array('id'=>$id));
    $uid=pdo_getcolumn('mask_areaagent', array('id' => $id), 'uid',1);
     if($res){
         $updatalevel=pdo_update('mask_user',array('level'=>3),array('id'=>$uid));
        message('拒绝成功',$this->createWebUrl('ruzhu',array()),'success');
    }else{
        message('拒绝失败','','error');
    }
}
if($operation=='delete'){
     $id=$_GPC['id'];
     $res=pdo_delete('mask_areaagent',array('id'=>$id));
     if($res){
        message('删除成功',$this->createWebUrl('ruzhu',array()),'success');
    }else{
        message('删除失败','','error');
    }

}

include $this->template('web/ruzhu');