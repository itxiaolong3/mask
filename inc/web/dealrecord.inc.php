<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();

if($_GPC['keywords']){
	$op=$_GPC['keywords'];
	$where="%$op%";	
}else{
	$where='%%';
}

	/*	$sql="select *  from " . tablename("mask_user") ." WHERE  name LIKE :name  and uniacid=:uniacid";
	$list=pdo_fetchall($sql,array(':name'=>$where,'uniacid'=>$_W['uniacid']));*/
	$pageindex = max(1, intval($_GPC['page']));
	$pagesize=10;
	$sql="select *  from " . tablename("mask_record") ." WHERE  ruid LIKE :name || rordernumber LIKE :name || rbuyername LIKE :name order by  raddtime desc";
	$select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
	$list = pdo_fetchall($select_sql,array(':name'=>$where));
	$total=pdo_fetchcolumn("select count(*) from " . tablename("mask_record") ." WHERE  ruid LIKE :name || rordernumber LIKE :name || rbuyername LIKE :name ",array(':name'=>$where));
	$pager = pagination($total, $pageindex, $pagesize);
	if($_GPC['id']){
		$res4=pdo_delete("mask_user",array('u_id'=>$_GPC['id']));
		if($res4){
		 message('删除成功！', $this->createWebUrl('user'), 'success');
		}else{
			  message('删除失败！','','error');
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
include $this->template('web/dealrecord');