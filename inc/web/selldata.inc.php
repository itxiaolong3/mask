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
        $findtime=date('Y-m-d',$time)."到".date('Y-m-d',time());
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
    $type2=isset($_GPC['type2'])?$_GPC['type2']:'today';
    $where=" where uniacid=:uniacid and state in (2,3,4) ";
    $data[':uniacid']=$_W['uniacid'];
    $findtime='';
    $list=array();
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
            $findtime=date('Y-m-d',$time)."到".date('Y-m-d',time());
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

    $count = count($list);
    $pagesize = ceil($count/5000);
    //array_unshift( $names,  '活动名称');

    $header = array(
        'ordertype'=>'数据类型',
        'nums' => '总单量',
        'allmoney' => '总金额',
        'findtime' => '日期'
    );

    $keys = array_keys($header);
    $html = "\xEF\xBB\xBF";
    foreach ($header as $li) {
        $html .= $li . "\t ,";
    }
    $html .= "\n";
    if (!empty($list)) {
        $size = ceil(count($list) / 500);
        for ($i = 0; $i < $size; $i++) {
            $buffer = array_slice($list, $i * 500, 500);
            $user = array();
            foreach ($buffer as $k =>$row) {
                if($row['ordertype']==1){
                    $row['ordertype']='399订单';
                }elseif($row['ordertype']==2){
                    $row['ordertype']='免费订单';
                }elseif($row['ordertype']==3){
                    $row['ordertype']='积分订单';
                }
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