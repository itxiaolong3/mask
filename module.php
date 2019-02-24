<?php
defined('IN_IA') or exit('Access Denied');

class maskModule extends WeModule
{
    

    public function welcomeDisplay()
    {   
        global $_GPC, $_W;
    	 if ($_W['role'] == 'operator') {
	        $url = $this->createWebUrl('peiz');
	        Header("Location: " . $url);
    	}else{
            $url = $this->createWebUrl('peiz');
	    	//$url = $this->createWebUrl('gaikuangdata');
	        Header("Location: " . $url);
    	}
    }
}