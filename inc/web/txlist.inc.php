<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
$type=empty($_GPC['type']) ? 'wait' :$_GPC['type'];
$state=empty($_GPC['state']) ? '0' :$_GPC['state'];
$pageindex = max(1, intval($_GPC['page']));
$pagesize=10;
$where=' WHERE  b.uniacid=:uniacid';
$data[':uniacid']=$_W['uniacid'];
if(isset($_GPC['keywords'])){
    $op=$_GPC['keywords'];
    $where.=" and b.name LIKE  concat('%', :name,'%') ";
    $data[':name']=$op;
    $type='all';
}
if($type!='all'){
    $where.= " and a.rsettlement=$state";
}
$sql="SELECT a.*,b.* FROM ".tablename('mask_record') .  " a"  . " left join " . tablename("mask_bankcard") . " b on a.rcardid=b.id ". $where." and a.rtype=7 and a.risrefu=0   ORDER BY a.raddtime DESC";
$total=pdo_fetchcolumn("SELECT count(*) FROM ".tablename('mask_record') .  " a"  . " left join " . tablename("mask_bankcard") . " b on a.rcardid=b.id".$where." a.rtype=7 and a.risrefu=0 ORDER BY a.raddtime DESC",$data);

$list=pdo_fetchall($sql,$data);
$select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
$list=pdo_fetchall($select_sql,$data);
$pager = pagination($total, $pageindex, $pagesize);


if($operation=='adopt'){//审核通过
    $id=$_GPC['id'];
    $res=pdo_update('mask_record',array('rsettlement'=>1,'rshtime'=>date('Y-m-d H:i:s')),array('rid'=>$id));
    if($res){
        message('审核成功',$this->createWebUrl('txlist',array()),'success');
    }else{
        message('审核失败','','error');
    }

}


// if($operation=='adopt'){//审核通过

//     $id=$_GPC['id'];
//     $list=pdo_get('mask_withdrawal',array('id'=>$_GPC['id']));
//     $user=pdo_get('mask_user',array('id'=>$list['user_id']));
//     $res=pdo_update('mask_withdrawal',array('state'=>2,'sh_time'=>date('Y-m-d H:i:s')),array('id'=>$id));
//     if($res){
//         message('审核成功',$this->createWebUrl('finance',array()),'success');
//     }else{
//         message('审核失败','','error');
//     }

// }


if($operation=='adopt2'){
    $id=$_GPC['id'];
    $list=pdo_get('mask_withdrawal',array('id'=>$_GPC['id']));
    $store=pdo_get('mask_store',array('id'=>$list['store_id']));
    $user=pdo_get('mask_user',array('id'=>$store['user_id']));

////////////////打款//////////////////////
    function arraytoxml($data){
        $str='<xml>';
        foreach($data as $k=>$v) {
            $str.='<'.$k.'>'.$v.'</'.$k.'>';
        }
        $str.='</xml>';
        return $str;
    }
    function xmltoarray($xml) {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $val = json_decode(json_encode($xmlstring),true);
        return $val;
    }
    function curl($param="",$url) {
        global $_GPC, $_W;
        $postUrl = $url;
        $curlPost = $param;
        $ch = curl_init();                                      //初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);                 //抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);                    //设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);            //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);                      //post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);           // 增加 HTTP Header（头）里的字段
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);        // 终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch,CURLOPT_SSLCERT,IA_ROOT . "/addons/mask/cert/".'apiclient_cert_' . $_W['uniacid'] . '.pem'); //这个是证书的位置绝对路径
        curl_setopt($ch,CURLOPT_SSLKEY,IA_ROOT . "/addons/mask/cert/".'apiclient_key_' . $_W['uniacid'] . '.pem'); //这个也是证书的位置绝对路径
        $data = curl_exec($ch);                                 //运行curl
        curl_close($ch);
        return $data;
    }
    $system=pdo_get('mask_system',array('uniacid'=>$_W['uniacid']));
    $psystem=pdo_get('mask_pay',array('uniacid'=>$_W['uniacid']));
    $data=array(
        'mch_appid'=>$system['appid'],//商户账号appid
        'mchid'=>$psystem['mchid'],//商户号
        'nonce_str'=>mt_rand(11111111,99999999),//随机字符串
        'partner_trade_no'=>time().rand(11111,99999),//商户订单号
        'openid'=>$user['openid'],//用户openid
        'check_name'=>'NO_CHECK',//校验用户姓名选项,
        're_user_name'=>$list['name'],//收款用户姓名
        'amount'=>$list['sj_cost']*100,//金额
        'desc'=>'提现打款',//企业付款描述信息
        'spbill_create_ip'=>$psystem['ip'],//Ip地址
    );

    $key=$psystem['wxkey'];///这个就是个API密码。32位的。。随便MD5一下就可以了
    // $key=md5($key);
    //var_dump($data);die;
    $data=array_filter($data);
    ksort($data);
    $str='';
    foreach($data as $k=>$v) {
        $str.=$k.'='.$v.'&';
    }
    $str.='key='.$key;
    $data['sign']=md5($str);
    $xml=arraytoxml($data);
    $url='https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
    $res=curl($xml,$url);
    $return=xmltoarray($res);
    if($return['result_code']=='SUCCESS'){
        pdo_update('mask_withdrawal',array('state'=>2,'sh_time'=>date('Y-m-d H:i:s')),array('id'=>$id));
        message('审核成功',$this->createWebUrl('finance',array()),'success');
    }else{
        if($return['err_code_des']){
            $message=$return['err_code_des'];
        }else{
            $message='请检查证书是否上传正确!';
        }
        message($return['err_code_des'],'','error');
    }
    // print_r($return);

////////////////打款//////////////////////

}



if($operation=='reject'){
    $id=$_GPC['id'];
    $res=pdo_update('mask_record',array('rsettlement'=>0,'risrefu'=>1,'rshtime'=>date('Y-m-d H:i:s')),array('rid'=>$id));
    //恢复金额
    $rsqmoney=$_GPC['rsqmoney'];
    $ruid=$_GPC['ruid'];
    if($res){
        //更新用户余额
        $walletrs=pdo_update('mask_user', array('wallet +=' => $rsqmoney), array('id' => $ruid));
        if ($walletrs){
            message('拒绝成功',$this->createWebUrl('txlist',array()),'success');
        }else{
            message('拒绝失败','','error');
        }

    }else{
        message('拒绝失败','','error');
    }
}
if($operation=='delete'){
    $id=$_GPC['id'];
    $res=pdo_delete('mask_withdrawal',array('id'=>$id));
    if($res){
        message('删除成功',$this->createWebUrl('txlist',array()),'success');
    }else{
        message('删除失败','','error');
    }

}
if(checksubmit('export_submit', true)) {
    $time=date("Y-m-d");
    $time="'%$time%'";
    $start=$_GPC['time']['start'];
    $end=$_GPC['time']['end'];

    $types=empty($_GPC['types']) ? 'wait' :$_GPC['types'];
    $where1=' WHERE  b.uniacid=:uniacid';
    $data1[':uniacid']=$_W['uniacid'];
    if($types=='all'){
        //所有
        $where1.= " and a.rsettlement in (0,1)";
    }else if($types=='now'){
        //已提现
        $where1.= " and a.rsettlement=1 ";
    }else{
        //待提现
        $where1.= " and a.rsettlement=0 ";
    }
    $count = pdo_fetchcolumn("SELECT count(*) FROM ".tablename('mask_record') .  " a"  . " left join " . tablename("mask_bankcard") . " b on a.rcardid=b.id".$where1."  and a.rtype=7 and a.risrefu=0 ORDER BY a.raddtime DESC",$data1);

    $pagesize = ceil($count/5000);
    //array_unshift( $names,  '活动名称');

    $header = array(
        'ruid'=>'用户ID',
        'rsqmoney' => '提现金额',
        'rmoney' => '实付用户金额',
        'sff' => '手续费',
        'cardnumber' => '账号',
        'name' => '户名',
        'openbranch' => '开户行',
        'branch' => '支行',
        'rsettlement' => '状态',
        'raddtime' => '提现时间',
    );

    $keys = array_keys($header);
    $html = "\xEF\xBB\xBF";
    foreach ($header as $li) {
        $html .= $li . "\t ,";
    }
    $html .= "\n";
    for ($j = 1; $j <= $pagesize; $j++) {
        $sql="SELECT a.*,b.* FROM ".tablename('mask_record') .  " a"  . " left join " . tablename("mask_bankcard") . " b on a.rcardid=b.id ". $where1." and a.rtype=7 and a.risrefu=0  and a.raddtime >='{$start}' and a.raddtime<='{$end}' limit ". ($j - 1) * 5000 . ",5000 ";
        $list = pdo_fetchall($sql,$data1);
        foreach ($list as $kk=>$vv){
            $list[$kk]['rmoney']=intval($vv['rmoney']);
            $list[$kk]['rsqmoney']=intval($vv['rsqmoney']);
        }
    }
    if (!empty($list)) {
        $size = ceil(count($list) / 500);
        for ($i = 0; $i < $size; $i++) {
            $buffer = array_slice($list, $i * 500, 500);
            $user = array();
            foreach ($buffer as $k =>$row) {
                if($row['rsettlement']==0){
                    $row['rsettlement']='待提现';
                }else{
                    $row['rsettlement']='已提现';
                }
                $row['sff']=$row['rsqmoney']-$row['rmoney'];
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
    header("Content-Disposition:attachment; filename=提现数据表.csv");
    echo $html;
    exit();
}
include $this->template('web/txlist');