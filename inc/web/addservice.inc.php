<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu2();
$storeid=$_COOKIE["storeid"];
$cur_store = $this->getStoreById($storeid);
$id=$_GPC['id'];
$item = pdo_get('mask_service',array('uniacid' => $_W['uniacid'],'id'=>$_GPC['id']));
if (checksubmit('submit')) {
    $data = array(
        'uniacid' =>$_W['uniacid'],
        'store_id' => $storeid,
        'time' => trim($_GPC['time']),
        'dateline' => TIMESTAMP,
        'num'=>$_GPC['num']
        );

    if (empty($id)) {
        pdo_insert('mask_service', $data);
    } else {
        unset($data['dateline']);
        pdo_update('mask_service', $data, array('id' => $id, 'uniacid' => $_W['uniacid']));
    }
    message('操作成功！', $this->createWebUrl('service', array('op' => 'display', 'storeid' => $storeid)), 'success');
}

include $this->template('web/addservice');