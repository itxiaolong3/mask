<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$item=pdo_get('mask_order',array('id'=>$_GPC['id']));

include $this->template('web/ydorderinfo');