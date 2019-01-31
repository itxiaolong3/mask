<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$storeid=$_COOKIE["storeid"];
$cur_store = $this->getStoreById($storeid);
$list = pdo_get('mask_goodtype',array('id'=>$_GPC['id']));
$data['typename']=$_GPC['typename'];
$data['uniacid']=$_W['uniacid'];
$data['addtime']=date('Y-m-d H:i:s',time());
		if(checksubmit('submit')){
			if($_GPC['id']==''){
				$res=pdo_insert('mask_goodtype',$data);
				if($res){
					message('添加成功',$this->createWebUrl('goodtype',array()),'success');
				}else{
					message('添加失败','','error');
				}
			}else{
				$res = pdo_update('mask_goodtype', $data, array('id' => $_GPC['id']));
				if($res){
					message('编辑成功',$this->createWebUrl('goodtype',array()),'success');
				}else{
					message('编辑失败','','error');
				}
			}
		}
include $this->template('web/addgoodtype');