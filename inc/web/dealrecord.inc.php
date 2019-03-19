<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$keywords=$_GPC['keywords'];
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
	foreach ($list as $k=>$v){
	    if ($v['rstate']){
	        $list[$k]['rmoney']='-'.$v['rmoney'];
        }
    }
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
if(checksubmit('export_submit', true)) {
    $time=date("Y-m-d");
    $time="'%$time%'";
    $start=$_GPC['time']['start'];
    $end=$_GPC['time']['end'];

    $uid=$_GPC['keywords'];
    if (empty($uid)){
        message('请搜索指定用户导出','','error');die();
    }
    $where1=' WHERE ruid=:ruid ';
    $data1[':ruid']=$uid;
    $count = pdo_fetchcolumn("SELECT count(*) FROM ".tablename('mask_record') .$where1."  ORDER BY raddtime DESC",$data1);
    //$count = pdo_fetchcolumn("SELECT count(*) FROM ".tablename('mask_record') .$where1." and raddtime >='{$start}' and raddtime<='{$end}' ORDER BY raddtime DESC",$data1);

    $pagesize = ceil($count/5000);
    $header = array(
        'ruid'=>'用户ID',
        'rmoney' => '金额',
        'rordernumber' => '订单号',
        'rcomment' => '备注',
        'rsettlement' => '是否结算',
        'raddtime' => '时间',
    );

    $keys = array_keys($header);
    $html = "\xEF\xBB\xBF";
    foreach ($header as $li) {
        $html .= $li . "\t ,";
    }
    $html .= "\n";
    for ($j = 1; $j <= $pagesize; $j++) {
        //$sql="SELECT * FROM ".tablename('mask_record') . $where1."  and raddtime >='{$start}' and raddtime<='{$end}' limit ". ($j - 1) * 5000 . ",5000 ";
        $sql="SELECT * FROM ".tablename('mask_record') . $where1."  limit ". ($j - 1) * 5000 . ",5000 ";
        $list = pdo_fetchall($sql,$data1);
        foreach ($list as $kk=>$vv){
            if ($vv['rtype']==8){
                $list[$kk]['rmoney']=number_format($vv['rmoney'],2);
            }else{
                $list[$kk]['rmoney']=intval($vv['rmoney']);
            }

        }
    }
    if (!empty($list)) {
        $size = ceil(count($list) / 500);
        for ($i = 0; $i < $size; $i++) {
            $buffer = array_slice($list, $i * 500, 500);
            $user = array();
            foreach ($buffer as $k =>$row) {
                if($row['rsettlement']==0){
                    $row['rsettlement']='待结算';
                }else{
                    $row['rsettlement']='已结算';
                }
                //$row['sff']=$row['rsqmoney']-$row['rmoney'];
                foreach ($keys as $key) {
                    $data5[] = $row[$key];
                }
                $user[] = implode("\t ,", $data5) . "\t ,";
                unset($data5);
            }
            $html .= implode("\n", $user) . "\n";
        }
    }

    header("Content-type:text/csv");
    header("Content-Disposition:attachment; filename=个人记录表.csv");
    echo $html;
    exit();
}
include $this->template('web/dealrecord');