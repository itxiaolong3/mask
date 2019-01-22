<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$area=pdo_getall('mask_areatype',array('uniacid'=>$_W['uniacid']),array(),'','Cid asc');
$operation = empty($_GPC['op']) ? 'display' : trim($_GPC['op']);
$where="WHERE a.uniacid=:uniacid";
$data[':uniacid']=$_W['uniacid'];
    if($_GPC['keywords']){
    	$where .=" and a.Name LIKE :name ";
    	$op=$_GPC['keywords'];
        $data[':name']="%$op%";
    }
    if($_GPC['area']){
    	$where .=" and c.Cid=:cid";
    	$data[':cid']=$_GPC['area'];
    }
$pageindex = max(1, intval($_GPC['page']));
$pagesize=10;
$sql="select a.*,c.Name as areaname,d.Name as fname from " . tablename("mask_lanmu") . " a"
    . " left join " . tablename("mask_areatype") . " c on c.Cid=a.tid "
    . " left join " . tablename("mask_warehome") . " d on d.fid=a.fid "
    .$where." order by a.number asc";
$select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
$total=pdo_fetchcolumn("select count(*) from " . tablename("mask_lanmu") . " a " .$where." order by a.number asc",$data);
$pager = pagination($total, $pageindex, $pagesize);
$list=pdo_fetchall($select_sql,$data);	
if($operation=='delete'){
	$res=pdo_delete('mask_lanmu',array('QsID'=>$_GPC['id']));
	if($res){
		message('删除成功！', $this->createWebUrl('store'), 'success');
	}else{
		message('删除失败！','','error');
	}
}
if($_GPC['is_open']){
    $getstatu=$_GPC['is_open'];
    if ($_GPC['is_open']==2){
        $getstatu=0;
    }
   $res=pdo_update('mask_lanmu',array('HasNewItems'=>$getstatu),array('QsID'=>$_GPC['updid']));
    if($res){
        message('修改成功！', $this->createWebUrl('store'), 'success');
    }else{
        message('修改失败！','','error');
    } 
}
include $this->template('web/store');