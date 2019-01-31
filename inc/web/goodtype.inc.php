<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$storeid=$_COOKIE["storeid"];
$cur_store = $this->getStoreById($storeid);
$list = pdo_getall('mask_goodtype',array('uniacid' => $_W['uniacid'],'id >'=>'1'));
if($_GPC['op']=='del'){
	$rst=pdo_getall('mask_goodmy',array('tid'=>$_GPC['id']));
		if(!$rst){
		$result = pdo_delete('mask_goodtype', array('id'=>$_GPC['id']));
		if($result){
			message('删除成功',$this->createWebUrl('goodtype',array()),'success');
		}else{
		message('删除失败','','error');
		}
	}else{
		message('该分类有商品无法删除','','error');
	}
}
include $this->template('web/goodtype');