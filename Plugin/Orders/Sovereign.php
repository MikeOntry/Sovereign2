<?php
namespace Phoebe\Plugin\Orders;

use Phoebe\Event\Event;
use Phoebe\Formatter;
use Phoebe\Plugin\PluginInterface;

class Sovereign implements PluginInterface
{
    protected $myWhois;
    protected $myUserInfo;
    protected $commandMessage;
    protected $writeStream;
    protected $isIdent;
    protected $myOrders;
    protected $mySupply;
    
    public static function getSubscribedEvents()
    {
        return [
            'irc.received.001' => ['joinChannels'],
            'irc.received.PRIVMSG' => ['checkIdent']
        ];
    }
    
     public function __construct($myWhois, $myUserInfo, $myOrders, $mySupply)
    {
        $this->myWhois = $myWhois;
        $this->myUserInfo = $myUserInfo;
        $this->myOrders = $myOrders;
        $this->mySupply = $mySupply;
    }
    
    public function joinChannels(Event $event){
        $autojoins = $this->myOrders->getChannels();
        foreach($autojoins as $a){
            $event->getWriteStream()->ircJoin($a['name'], $a['key']);
        }
    }
    
    public function whoisResult($result){
        if(isset($result[307]['params'][3])){         
            $this->isIdent = $result[307]['params'][3];
        }else{
            $this->isIdent = "nope";
        }
        $this->commands($this->commandMessage);
    }
    
    public function getWhois(){
        if($this->isIdent == "has identified for this nick"){
          return true;
        }else{
          return false;
        }
    }  
    
    public function checkIdent(Event $event){
        $msg = $event->getMessage();
        $this->commandMessage = $msg;
        $this->writeStream = $event->getWriteStream();
        $nick = $msg["nick"];
        $channel = $msg['params']["receivers"];
        if (strpos($msg["params"]["text"], "@") === 0) {
            $this->myWhois->whois($nick,$event->getWriteStream(),function($info){$this->whoisResult($info);});
        }else{
            return;
        }
    }
        
    public function commands($msg)
    {
        $nick = $msg["nick"];
        $channel = $msg['params']["receivers"];
        if(strpos($msg["params"]["text"], "@install") === 0){
            if($this->getWhois() == true){
                $command = str_replace("@","",$msg["params"]["text"]);
                $command = explode(" ", $command);
                if($command[0] == "install"){
                    if(!isset($command[1])){
                        return;
                    }
                    if(!isset($command[2])){
                        $key = "";
                    }else{
                        $key = $command[2];
                    }
                    $check = $this->myOrders->addAdmin($nick,true);
                    if($check == false){
                        $this->writeStream->ircPrivmsg(
                            $nick,
                            'Nice try! Bot is already installed'
                        );
                        return;
                    }
                    $this->myOrders->addChannel($command[1],$key);
                    $this->writeStream->ircJoin($command[1], $key);
                    $this->writeStream->ircPrivmsg(
                        $nick,
                        'Bot installed!'
                    );
                }
            }
        }
            
        if ($msg->isInChannel() && strpos($msg["params"]["text"], "@") === 0) {
            $command = str_replace("@","",$msg["params"]["text"]);
            $command = explode(" ", $command);
            if($this->getWhois() == false){
                $ordercall = $this->myOrders->getOrderset($command[0]);
                if($ordercall != false){
                    $orders = $this->myOrders->getOrders($command[0], $channel);
                    if(!$orders){
                        return;
                    }
                    foreach($orders as $send){
                        $color = "blue";
                        $text = "white";
                        if($send['priority'] == "1"){
                            $color = "red";
                            $text = "black";
                        }
                        if($send['priority'] == "2"){
                            $color = "yellow";
                            $text = "black";
                        }
                        if($send['priority'] == "3"){
                            $color = "lime";
                            $text = "black";
                        }
                        $info = explode(" ", $send['info']);
                        if(strpos($info[1],"http") === 0){
                            $region = $info[0];
                            $link = $info[1];
                            unset($info[0]);
                            unset($info[1]);
                            $info = implode(" ", $info);
                            $this->writeStream->ircNotice(
                                $nick,
                                '::'.Formatter::bold(Formatter::color(' Priority '.$send['priority'].' ', $text, $color)).':: '.Formatter::color($region, 'red').' | '.Formatter::color($link, 'blue').' | '.$info
                            );
                            
                        }else{
                            $this->writeStream->ircNotice(
                                $nick,
                                '::'.Formatter::bold(Formatter::color(' Priority '.$send['priority'].' ', $text, $color)).':: '.$send['info']
                            );
                        }
                    }
                    $this->myOrders->logorderCall($nick, $command[0]);
                    return;
                }else{
                    return;
                }
            }
        }else{
            return;
        }
        
        $ordercall = $this->myOrders->getOrderset($command[0]);
        if($ordercall != false){
            $orders = $this->myOrders->getOrders($command[0], $channel);
            if(!$orders){
                return;
            }
            foreach($orders as $send){
                $color = "blue";
                $text = "white";
                if($send['priority'] == "1"){
                    $color = "red";
                    $text = "black";
                }
                if($send['priority'] == "2"){
                    $color = "yellow";
                    $text = "black";
                }
                if($send['priority'] == "3"){
                    $color = "lime";
                    $text = "black";
                }
                $info = explode(" ", $send['info']);
                if(strpos($info[1],"http") === 0){
                    $region = $info[0];
                    $link = $info[1];
                    unset($info[0]);
                    unset($info[1]);
                    $info = implode(" ", $info);
                    $this->writeStream->ircNotice(
                        $nick,
                        '::'.Formatter::bold(Formatter::color(' Priority '.$send['priority'].' ', $text, $color)).':: '.Formatter::color($region, 'red').' | '.Formatter::color($link, 'blue').' | '.$info
                    );
                    
                }else{
                    $this->writeStream->ircNotice(
                        $nick,
                        '::'.Formatter::bold(Formatter::color(' Priority '.$send['priority'].' ', $text, $color)).':: '.$send['info']
                    );
                }
            }
            $this->myOrders->logorderCall($nick, $command[0]);
            return;
        }
        
        
        // Mass ping to channel
        if($command[0] == "mass"){
            if(isset($command[1])){
                $users = $this->myUserInfo->getUsers($channel);
                $users = implode(" ", $users);
                unset($command[0]);
                $mass .= implode(" ",$command);
                $this->writeStream->ircPrivmsg(
                    $channel,
                    $users
                );
                $this->writeStream->ircPrivmsg(
                    $channel,
                    $mass
                );
            }
            return;
        }
        
        if($command[0] == "calllog"){
            if(!isset($command[1])){
                return;
            }
            if($this->myOrders->isAdmin($nick) || $this->myOrders->isQM($nick)){
                $order = $command[1];
                if(!isset($command[2])){
                    $date = "now";
                }else{
                    unset($command[0]);
                    unset($command[1]);
                    $date = implode(" ",$command);
                }
                $log = $this->myOrders->getcallLog($date, $order);
                if($log === false){
                    $this->writeStream->ircNotice(
                        $nick,
                        'No records to show'
                    );
                    return;
                }else{
                    foreach($log as $l){
                        $this->writeStream->ircNotice(
                            $nick,
                            $l['date'].': '.$l['nick'].': '.$l['count']
                        );
                    }
                    return;
                }
            }
        }

        if($command[0] == "join"){
            if(!isset($command[1])){
                return;
            }
            if(!isset($command[2])){
                $key = "";
            }else{
                $key = $command[2];
            }
            if($this->myOrders->isAdmin($nick)){
                $this->myOrders->addChannel($command[1], $key);
                $this->writeStream->ircJoin($command[1], $key);
                return;
            }
        }
        
        if($command[0] == "part"){
            if(!isset($command[1])){
                return;
            }
            if($this->myOrders->isAdmin($nick)){
                $this->myOrders->removeChannel($command[1]);
                $this->writeStream->ircPart($command[1]);
                return;
            }
        }
        
        if($command[0] == "update"){
            if(!isset($command[1])){
                return;
            }else{
                $order = $command[1];
                if($command[2] == "clear"){
                    if(!isset($command[3])){
                        $priority = NULL;
                        $send = "Removed orderset ".$order;
                    }else{
                        $priority = $command[3];
                        $send = "Removed priority ".$priority." from ".$order;
                    }
                    if(!$this->myOrders->removeOrder($order, $nick, $priority)){
                        $this->writeStream->ircNotice(
                            $nick,
                            'This update is not valid'
                        );
                        return;
                    }
                    $this->writeStream->ircNotice(
                        $nick,
                        $send
                    );
                    return;
                }
                if(isset($command[2]) && isset($command[3])){
                    
                    $priority = $command[2];
                    unset($command[0]);
                    unset($command[1]);
                    unset($command[2]);
                    $update = implode(" ", $command);
                    if(!$this->myOrders->updateOrder($order, $nick, $update, $priority)){
                        $this->writeStream->ircNotice(
                            $nick,
                            'This update is not valid'
                        );
                        return;
                    }
                    $this->writeStream->ircNotice(
                        $nick,
                        'Updated orderset '.$order.' priority '.$priority
                    );
                    return;
                }
            }
        }
        
        if($command[0] == "adduser"){
            if(!isset($command[1]) || !isset($command[2])){
                return;
            }
            if($this->myOrders->isAdmin($nick)){
                if(!$this->myOrders->addorderUser($command[1], $command[2])){
                    $this->writeStream->ircNotice(
                        $nick,
                        'Could not add user to this orderset'
                    );
                }else{
                    $this->writeStream->ircNotice(
                        $nick,
                        'Added user '.$command[1].' to orderset '.$command[2]
                    );
                }
            }
            return;
        }
        
        if($command[0] == "deleteuser"){
            if(!isset($command[1]) || !isset($command[2])){
                return;
            }
            if($this->myOrders->isAdmin($nick)){
                if(!$this->myOrders->removeorderUser($command[1], $command[2])){
                    $this->writeStream->ircNotice(
                        $nick,
                        'Could not remove user from this orderset'
                    );
                }else{
                    $this->writeStream->ircNotice(
                        $nick,
                        'Removed user '.$command[1].' from orderset '.$command[2]
                    );
                }
            }
            return;
        }
        
        if($command[0] == "listusers"){
            if(!isset($command[1])){
                return;
            }
            if($this->myOrders->isAdmin($nick)){
                if(!$users = $this->myOrders->getordersetUsers($command[1])){
                    $this->writeStream->ircNotice(
                        $nick,
                        'Could not find a list of users'
                    );
                }else{
                    $userlist = "";
                    foreach($users as $u){
                        $userlist .= $u." ";
                    }
                    $this->writeStream->ircNotice(
                        $nick,
                        'List of users: '.$userlist
                    );
                }
            }
            return;
        }
        
        if($command[0] == "addchan"){
            if(!isset($command[1]) || !isset($command[2])){
                return;
            }
            if($this->myOrders->isAdmin($nick)){
                if(!$this->myOrders->addorderChannel($command[1], $command[2])){
                    $this->writeStream->ircNotice(
                        $nick,
                        'Could not add channel to this orderset'
                    );
                }else{
                    if(!isset($command[3])){
                        $key = "";
                    }else{
                        $key = $command[3];
                    }
                    $this->myOrders->addChannel($command[1], $key);
                    $this->writeStream->ircJoin($command[1]);
                    $this->writeStream->ircNotice(
                        $nick,
                        'Added channel '.$command[1].' to orderset '.$command[2]
                    );
                }
            }
            return;
        }
        
        if($command[0] == "deletechan"){
            if(!isset($command[1]) || !isset($command[2])){
                return;
            }
            if($this->myOrders->isAdmin($nick)){
                if(!$this->myOrders->removeorderChannel($command[1], $command[2])){
                    $this->writeStream->ircNotice(
                        $nick,
                        'Could not remove channel from this orderset'
                    );
                }else{
                    $this->writeStream->ircNotice(
                        $nick,
                        'Removed channel '.$command[1].' from orderset '.$command[2]
                    );
                }
            }
            return;
        }
        
        if($command[0] == "listchan"){
            if(!isset($command[1])){
                return;
            }
            if($this->myOrders->isAdmin($nick)){
                if(!$channels = $this->myOrders->getordersetChannels($command[1])){
                    $this->writeStream->ircNotice(
                        $nick,
                        'Could not find a list of channels'
                    );
                }else{
                    $channellist = "";
                    foreach($channels as $c){
                        $channellist .= $c." ";
                    }
                    $this->writeStream->ircNotice(
                        $nick,
                        'List of channels: '.$channellist
                    );
                }
            }
            return;
        }
        
        if($command[0] == "addadmin"){
            if(!isset($command[1])){
                return;
            }
            if($this->myOrders->isAdmin($nick)){
                if(!$this->myOrders->addAdmin($command[1])){
                    $this->writeStream->ircNotice(
                        $nick,
                        'This user is already an admin'
                    );
                    return;
                }
                $this->writeStream->ircNotice(
                    $nick,
                    'Added '.$command[1].' to admin'
                );
            }
            return;
        }
        
        if($command[0] == "addqm"){
            if(!isset($command[1])){
                return;
            }
            if($this->myOrders->isAdmin($nick)){
                if(!$this->myOrders->addQM($command[1])){
                    $this->writeStream->ircNotice(
                        $nick,
                        'This user is already a QM'
                    );
                    return;
                }
                $this->writeStream->ircNotice(
                    $nick,
                    'Added '.$command[1].' as a QM'
                );
            }
            return;
        }
        
        if($command[0] == "deleteadmin"){
            if(!isset($command[1])){
                return;
            }
            if($this->myOrders->isAdmin($nick)){
                if(!$this->myOrders->isAdmin($command[1])){
                    $this->writeStream->ircNotice(
                        $nick,
                        'Can not remove this user from admin'
                    );
                    return;
                }
                
                if(!$this->myOrders->removeAdmin($command[1])){
                    $this->writeStream->ircNotice(
                        $nick,
                        'Can not remove this user from admin'
                    );
                    return;
                }
                $this->writeStream->ircNotice(
                    $nick,
                    'Removed '.$command[1].' from admin'
                );
            }
            return;            
        }
        
        if($command[0] == "deleteqm"){
            if(!isset($command[1])){
                return;
            }
            if($this->myOrders->isAdmin($nick)){
                if(!$this->myOrders->isQM($command[1])){
                    $this->writeStream->ircNotice(
                        $nick,
                        'Can not remove this user from QM list'
                    );
                    return;
                }
                
                if(!$this->myOrders->removeQM($command[1])){
                    $this->writeStream->ircNotice(
                        $nick,
                        'Can not remove this user from QM list'
                    );
                    return;
                }
                $this->writeStream->ircNotice(
                    $nick,
                    'Removed '.$command[1].' from QM list'
                );
            }
            return;            
        }
        
        if($command[0] == "listadmins"){
            if($this->myOrders->isAdmin($nick)){
                $admins = $this->myOrders->getAdmins();
                $list = "";
                foreach($admins as $a){
                    $list .= $a['nick']." ";
                }
                $this->writeStream->ircNotice(
                    $nick,
                    'List of admins: '.$list
                );
            }
            return;
        }
        
        if($command[0] == "listqms"){
            if($this->myOrders->isAdmin($nick) || $this->myOrders->isQM($nick)){
                $admins = $this->myOrders->getQMs();
                $list = "";
                foreach($admins as $a){
                    $list .= $a['nick']." ";
                }
                $this->writeStream->ircNotice(
                    $nick,
                    'List of QMs: '.$list
                );
            }
            return;
        }
        
        if($command[0] == "addorderset"){
            if(!isset($command[1])){
                return;
            }
            $notallowed = ['addqm','deleteqm','listqms','calllog','install','update','mass','join','part','adduser','deleteuser','listusers','addchan','deletechan','listchan','addadmin','deleteadmin','listadmins','addorderset','deleteorderset','listordersets','help'];
            if(in_array($command[1], $notallowed)){
                $this->writeStream->ircNotice(
                    $nick,
                    'Orderset name not allowed'
                );
                return;
            }
            if($this->myOrders->isAdmin($nick)){
                if($this->myOrders->addOrder($command[1])){
                    $this->writeStream->ircNotice(
                        $nick,
                        'Added the orderset '.$command[1]
                    );
                }else{
                    $this->writeStream->ircNotice(
                        $nick,
                        'Orderset name not allowed'
                    );
                }
                return;
            }            
        }
        
        if($command[0] == "deleteorderset"){
            if(!isset($command[1])){
                return;
            }
            if($this->myOrders->isAdmin($nick)){        
                if(!$this->myOrders->removeOrder($command[1], $nick)){
                    $this->writeStream->ircNotice(
                        $nick,
                        'Could not remove this orderset'
                    );
                    return;
                }
                $this->writeStream->ircNotice(
                    $nick,
                    'Removed orderset '.$command[1]
                );
            }
            return;
        }
        
        if($command[0] == "listordersets"){
            if($this->myOrders->isAdmin($nick)){
                if(!$ordersets = $this->myOrders->getOrderset()){
                    $this->writeStream->ircNotice(
                        $nick,
                        'No ordersets to list'
                    );
                    return;
                }
                $list = "";
                foreach($ordersets as $a){
                    $list .= $a['name']." ";
                }
                $this->writeStream->ircNotice(
                    $nick,
                    'List of ordersets: '.$list
                );
            }
            return;
        }
        
        if($command[0] == "help"){
            if($this->myOrders->isAdmin($nick)){
                $this->writeStream->ircNotice(
                    $nick,
                    '@join #channel <optional chan password> - Joins the channel (with password option)'
                );
                $this->writeStream->ircNotice(
                    $nick,
                    '@part #channel - Parts channel and removes from autojoin list'
                );
                $this->writeStream->ircNotice(
                    $nick,
                    '@calllog <orderset> <optional date string> - List the who, when, and how many times an orderset was viewed (date string examples: yesterday, -2 days, April 1 2014)'
                );
                $this->writeStream->ircNotice(
                    $nick,
                    '@adduser <nick> orderset - Adds a user (orderset admin) to an orderset'
                );
                $this->writeStream->ircNotice(
                    $nick,
                    '@deleteuser <nick> orderset - Delete a user from an orderset'
                );
                $this->writeStream->ircNotice(
                    $nick,
                    '@listusers orderset - List the users in an orderset'
                );
                $this->writeStream->ircNotice(
                    $nick,
                    '@addchan #channel orderset - Adds a channel to an orderset'
                );
                $this->writeStream->ircNotice(
                    $nick,
                    '@deletechan #channel orderset - Delete a channel from an orderset'
                );
                $this->writeStream->ircNotice(
                    $nick,
                    '@listchan orderset - List the channels in an orderset'
                );
                $this->writeStream->ircNotice(
                    $nick,
                    '@addadmin <nick> - Add an admin'
                );
                $this->writeStream->ircNotice(
                    $nick,
                    '@deleteadmin <nick> - Delete an admin'
                );
                $this->writeStream->ircNotice(
                    $nick,
                    '@listadmins - Lists all the admins'
                );
                $this->writeStream->ircNotice(
                    $nick,
                    '@addqm <nick> - Add a qm'
                );
                $this->writeStream->ircNotice(
                    $nick,
                    '@deleteqm <nick> - Delete a qm'
                );
                $this->writeStream->ircNotice(
                    $nick,
                    '@listqms - Lists all the qms'
                );
                $this->writeStream->ircNotice(
                    $nick,
                    '@addorderset orderset - Adds an orderset'
                );
                $this->writeStream->ircNotice(
                    $nick,
                    '@deleteorderset orderset - Deletes an orderset'
                );
                $this->writeStream->ircNotice(
                    $nick,
                    '@listordersets - List order sets'
                );
                $this->writeStream->ircNotice(
                    $nick,
                    '@help - This'
                );
            }
            return;
        }
    }
}
