<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$info=pdo_get('mask_goodmy',array('gID'=>$_GPC['id']));
if(!empty($info)){
    $info['Images']=explode(',',$info['Images']);
}
$type=pdo_getall('mask_goodtype',array('uniacid'=>$_W['uniacid'],'id >'=>'1'));
if(!$type){
    message('请先添加分类',$this->createWebUrl('addgoodtype',array()),'error');
}
include $this->template('web/editallgood');