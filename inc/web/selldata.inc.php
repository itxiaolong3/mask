<?php
global $_GPC, $_W;
$system=pdo_get('mask_system',array('uniacid'=>$_W['uniacid']));
$GLOBALS['frames'] = $this->getMainMenu();
$pageindex = max(1, intval($_GPC['page']));
$pagesize=8;
$type2=isset($_GPC['type2'])?$_GPC['type2']:'today';
$where=" where uniacid=:uniacid and state in (2,3,4) ";
$data[':uniacid']=$_W['uniacid'];
$findtime='';
if($_GPC['time']){
    $start=$_GPC['time']['start'];
    $end=$_GPC['time']['end'];
    $where.=" and time >='{$start}' and time<='{$end}'";
    $findtime=$start."到".$end;
}else{
    if($type2=='today'){
        $time=date("Y-m-d",time());
        $where.="  and time LIKE '%{$time}%' ";
        $findtime=$time;
    }
    if($type2=='yesterday'){
        $time=date("Y-m-d",strtotime("-1 day"));
        $where.="  and time LIKE '%{$time}%' ";
        $findtime=$time;
    }
    if($type2=='week'){
        $time=strtotime(date("Y-m-d",strtotime("-7 day")));

        $where.=" and UNIX_TIMESTAMP(time) >".$time;
        $findtime=$time."到".date('Y-m-d',time());
    }
    if($type2=='month'){
        $time=date("Y-m");
        $where.="  and time LIKE '%{$time}%' ";
        $findtime='本月';
    }
}
$ordertype=$_GPC['is_show2'];
//ordertype，money nums allmoney findtime

switch ($ordertype){
    case 0:
        $sql1="SELECT sum(money) as allmoney FROM ".tablename('mask_order') .$where." and ordertype=1";
        $getallmoney1=pdo_fetch($sql1,$data);
        $sql2="SELECT count(*) as allcount FROM ".tablename('mask_order') .$where." and ordertype=1";
        $getallcount1=pdo_fetch($sql2,$data);
        $list[0]['ordertype']=1;
        $list[0]['nums']=$getallcount1['allcount'];
        $list[0]['allmoney']=$getallmoney1['allmoney'];
        $list[0]['findtime']=$findtime;


        $sql3="SELECT sum(money) as allmoney FROM ".tablename('mask_order') .$where." and ordertype=2";
        $getallmoney2=pdo_fetch($sql3,$data);
        $sql4="SELECT count(*) as allcount FROM ".tablename('mask_order') .$where." and ordertype=2";
        $getallcount2=pdo_fetch($sql4,$data);
        $list[1]['ordertype']=2;
        $list[1]['nums']=$getallcount2['allcount'];
        $list[1]['allmoney']=$getallmoney2['allmoney'];
        $list[1]['findtime']=$findtime;

        $sql5="SELECT sum(money) as allmoney FROM ".tablename('mask_order') .$where." and ordertype=3";;
        $getallmoney3=pdo_fetch($sql5,$data);
        $sql6="SELECT count(*) as allcount FROM ".tablename('mask_order') .$where." and ordertype=3";
        $getallcount3=pdo_fetch($sql6,$data);
        $list[2]['ordertype']=3;
        $list[2]['nums']=$getallcount3['allcount'];
        $list[2]['allmoney']=$getallmoney3['allmoney'];
        $list[2]['findtime']=$findtime;

        break;
    case 1:
        $where.=" and ordertype=".$ordertype;
        $sql1="SELECT sum(money) as allmoney FROM ".tablename('mask_order') .$where;
        $getallmoney=pdo_fetch($sql1,$data);
        $sql2="SELECT count(*) as allcount FROM ".tablename('mask_order') .$where;
        $getallcount=pdo_fetch($sql2,$data);
        $list[0]['ordertype']=1;
        $list[0]['nums']=$getallcount['allcount'];
        $list[0]['allmoney']=$getallmoney['allmoney'];
        $list[0]['findtime']=$findtime;
        break;
    case 2:
        $where.=" and ordertype=".$ordertype;
        $sql1="SELECT sum(money) as allmoney FROM ".tablename('mask_order') .$where;
        $getallmoney=pdo_fetch($sql1,$data);
        $sql2="SELECT count(*) as allcount FROM ".tablename('mask_order') .$where;
        $getallcount=pdo_fetch($sql2,$data);
        $list[0]['ordertype']=2;
        $list[0]['nums']=$getallcount['allcount'];
        $list[0]['allmoney']=$getallmoney['allmoney'];
        $list[0]['findtime']=$findtime;
        break;
    case 3:
        $where.=" and ordertype=".$ordertype;
        $sql1="SELECT sum(money) as allmoney FROM ".tablename('mask_order') .$where;
        $getallmoney=pdo_fetch($sql1,$data);
        $sql2="SELECT count(*) as allcount FROM ".tablename('mask_order') .$where;
        $getallcount=pdo_fetch($sql2,$data);
        $list[0]['ordertype']=3;
        $list[0]['nums']=$getallcount['allcount'];
        $list[0]['allmoney']=$getallmoney['allmoney'];
        $list[0]['findtime']=$findtime;
        break;
}


if(checksubmit('export_submit', true)) {
    $time=date("Y-m-d");
    $time="'%$time%'";
    $start=$_GPC['time']['start'];
    $end=$_GPC['time']['end'];

    $count = pdo_fetchcolumn("SELECT COUNT(*) FROM". tablename("mask_order")." WHERE uniacid={$_W['uniacid']} and state=2 and time >='{$start}' and time<='{$end}'");
    $pagesize = ceil($count/5000);
    //array_unshift( $names,  '活动名称');

    $header = array(
        'ids'=>'序号',
        'order_num' => '订单号',
        'name' => '联系人',
        'tel' => '联系电话',
        'address' => '联系地址',
        'time' => '下单时间',
        'money' => '金额',
        'state' => '订单状态',
        'pay_type' => '支付方式',
        'goods' => '商品'

    );

    $keys = array_keys($header);
    $html = "\xEF\xBB\xBF";
    foreach ($header as $li) {
        $html .= $li . "\t ,";
    }
    $html .= "\n";
    for ($j = 1; $j <= $pagesize; $j++) {
        $sql = "select a.* from " . tablename("mask_order")."  a"  . "   WHERE a.uniacid={$_W['uniacid']} and a.state=2 and a.time >='{$start}' and a.time<='{$end}' limit " . ($j - 1) * 5000 . ",5000 ";
        $list = pdo_fetchall($sql);
    }
    if (!empty($list)) {
        $size = ceil(count($list) / 500);
        for ($i = 0; $i < $size; $i++) {
            $buffer = array_slice($list, $i * 500, 500);
            $user = array();
            foreach ($buffer as $k =>$row) {
                $row['ids']= $k+1;
                if($row['state']==1){
                    $row['state']='待付款';
                }elseif($row['state']==2){
                    $row['state']='等待发货';
                }elseif($row['state']==3){
                    $row['state']='等待送达';
                }elseif($row['state']==4){
                    $row['state']='完成';
                }elseif($row['state']==5){
                    $row['state']='已评价';
                }elseif($row['state']==6){
                    $row['state']='已取消';
                }elseif($row['state']==7){
                    $row['state']='已拒绝';
                }elseif($row['state']==8){
                    $row['state']='退款中';
                }elseif($row['state']==9){
                    $row['state']='退款成功';
                }elseif($row['state']==10){
                    $row['state']='退款失败';
                }
                if($row['pay_type']==0){
                    $row['pay_type']='微信支付';
                }

                $good=pdo_getall('mask_order_goods',array('order_id'=>$row['id']));
                $date6='';
                for($i=0;$i<count($good);$i++){

                    if($good[$i]['spec']){
                        $date6 .=$good[$i]['name'].'('.$good[$i]['spec'].')*一共'.$good[$i]['number']."  件";
                    }else{
                        $date6 .=$good[$i]['name'].'*一共'.$good[$i]['number']."  件";
                    }

                }
                $row['goods']=$date6;
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
    header("Content-Disposition:attachment; filename=销售统计数据表.csv");
    echo $html;
    exit();
}


include $this->template('web/selldata');