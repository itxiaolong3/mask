<?php
global $_GPC, $_W;
$data['tid']=$_GPC['tid'];
$data['Title']=$_GPC['Title'];

if (!strstr($_GPC['Itemcover'],'http')){
    if(!empty($_GPC['Itemcover'])){
        $data['Itemcover']=$_W['attachurl'].$_GPC['Itemcover'];
    }
}else{
    $data['Itemcover']=$_GPC['Itemcover'];
}

$data['Price']=$_GPC['Price'];
$data['Statu']=$_GPC['Statu'];
$data['TotalQty']=$_GPC['TotalQty'];
$data['content']=$_GPC['content'];
$data['integral']=$_GPC['integral'];
$data['freight']=$_GPC['freight'];

$data['uniacid']=$_W['uniacid'];
//图片集
$images=$_GPC['Images'];
foreach ($images as $k=>$v){
    $geturl=$v;
    if (!strstr($geturl,'http')){
      if(!empty($geturl)){
        $images[$k]=$_W['attachurl'].$v;
    }
       
    }
}
$images=implode(',',$images);
$data['Images']=$images;
$getid=$_GPC['good_id'];
if(!$_GPC['Title']){
    message('请填写商品名称!','','error');
}
if(!$_GPC['Itemcover']){
    message('请上传商品封面!','','error');
}

if(!$_GPC['Price']||$_GPC['Price']<0){
    message('商品价格不可为空并且大于0','','error');
}
if (empty($getid)){
    $res=pdo_insert('mask_goodmy',$data);
    if (!empty($res)) {
        message('添加成功',$this->createWebUrl('allgood',array()),'success');
    } else {
        message("添加失败", referer(), 'error');
    }
}else{
    $res=pdo_update('mask_goodmy',$data,array('gID'=>$getid));
    if (!empty($res)) {
            message('编辑成功！', $this->createWebUrl('allgood'), 'success');
    } else {
        message("编辑失败", '', 'error');
    }
}