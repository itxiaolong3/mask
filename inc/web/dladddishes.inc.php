<?php
global $_GPC, $_W;
$action = 'start';
//$GLOBALS['frames'] = $this->getMainMenu2();
$storeid=$_COOKIE["storeid"];
$uid=$_COOKIE["uid"];
$cur_store = $this->getStoreById($storeid);
$GLOBALS['frames'] = $this->getNaveMenu($storeid, $action,$uid);
$info=pdo_get('mask_goods',array('id'=>$_GPC['id']));

$type=pdo_getall('mask_type',array('uniacid'=>$_W['uniacid'],'store_id'=>$storeid),array(),'','order_by asc');
if(!$type){
	message('请先添加分类',$this->createWebUrl2('dladddishestype',array()),'error');
}
$dytag=pdo_getall('mask_dytag',array('uniacid'=>$_W['uniacid'],'store_id'=>$storeid),array(),'','sort asc');
include $this->template('web/dladddishes');