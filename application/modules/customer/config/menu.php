<?php

// module name
$HmvcMenu["customer"] = array(
    //set icon
    "icon"           => "<i class='typcn typcn-user'></i>
", 
 //group level name
     "customer_list" => array(
        //menu name
            "controller" => "customer_info",
            "method"     => "index",
            "url"        => "customer/customer-list",
            "permission" => "read"
        
     ),
    "wakeup_call_list" => array(
        //menu name
            "controller" => "customer_info",
            "method"     => "wakeup_call",
            "url"        => "customer/wakeup-call",
            "permission" => "read"
        
    ),
    "compose_sms" => array(
        //menu name
            "controller" => "sms_compose",
            "method"     => "index",
            "url"        => "dashboard/sms_compose",
            "permission" => "read"
        
    ),
    "sms_history" => array(
        //menu name
            "controller" => "sms_compose",
            "method"     => "history",
            "url"        => "dashboard/sms_compose/history",
            "permission" => "read"
        
    ),
    "sms_templates" => array(
        //menu name
            "controller" => "sms_compose",
            "method"     => "quick_templates",
            "url"        => "dashboard/sms_compose/quick_templates",
            "permission" => "read"
        
    )
);
   

 