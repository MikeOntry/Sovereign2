<?php
namespace Phoebe\Plugin\Orders;

use PDO;

class StorageSqlite implements StorageInterface
{
    protected $db;

    public function __construct()
    {
        if(file_exists('ordersbot.db')){
            try{
                $this->db = new PDO('sqlite:ordersbot.db');             
            }
            catch(PDOException $e)
            {
                echo $e->getMessage();
            }
        }else{
            try{
                $this->db = new PDO('sqlite:ordersbot.db');
                $this->db->exec('CREATE TABLE users(id INTEGER PRIMARY KEY AUTOINCREMENT,nick TEXT NOT NULL,admin INT(1) NOT NULL DEFAULT 0)');
                $this->db->exec('CREATE TABLE channels (id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT NOT NULL,key TEXT,autojoin INTEGER NOT NULL DEFAULT 1)');
                $this->db->exec('CREATE TABLE ordersets (id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT NOT NULL)');
                $this->db->exec('CREATE TABLE orderset_users (orderset_id INT(4) NOT NULL,nick_id INT(4) NOT NULL)');
                $this->db->exec('CREATE TABLE orderset_channels (orderset_id INT(4) NOT NULL,channel_id INT(4) NOT NULL)');
                $this->db->exec('CREATE TABLE orders (id INTEGER PRIMARY KEY AUTOINCREMENT,info TEXT NOT NULL,priority INT(4) NOT NULL,orderset_id INT(4) NOT NULL)');
                $this->db->exec('CREATE TABLE call_log (nick TEXT NOT NULL,orderset TEXT NOT NULL,date TEXT NOT NULL,count INTEGER NOT NULL)');
            }catch(PDOException $e){
                echo $e->getMessage();
            }
        }
    }
    
    public function logorderCall($nick, $order) // log when user calls for orders
    {
        $stmt = $this->db->prepare(
            'SELECT count FROM call_log WHERE nick = ? and date = date("now", "localtime") and orderset = ?'
        );
        $stmt->execute([$nick, $order]);
        if(!$count = $stmt->fetchAll(PDO::FETCH_COLUMN)){        
            $stmt = $this->db->prepare(
                'INSERT INTO call_log (nick, orderset, date, count) VALUES (?, ?, date("now", "localtime"), 1)'
            );
            $stmt->execute([$nick, $order]);
            return;
        }else{
            $stmt = $this->db->prepare(
                'UPDATE call_log SET count = ? WHERE nick = ? and date = date("now", "localtime") and orderset = ?'
            );
            $count[0]++;
            $stmt->execute([$count[0],$nick, $order]);
            return;
        }
    }
    
    public function getcallLog($date, $order) // get the call log between now and $date
    {
        $input = strtotime($date);
        if($input === false){
            return false;
        }else{
            $input = date("Y-m-d", $input);
        }
        $stmt = $this->db->prepare(
            'SELECT * FROM call_log WHERE orderset = ? AND date BETWEEN ? AND date("now", "localtime") ORDER BY nick'
        );
        $stmt->execute([$order, $input]);
        if(!$log = $stmt->fetchAll(PDO::FETCH_ASSOC)){
            return false;
        }
        return $log;
    }
    
    public function addChannel($channel, $key) // add channel
    {
        if(!$chaninfo = $this->findChannel($channel)){
            $stmt = $this->db->prepare(
                'INSERT INTO channels (name, key, autojoin) VALUES (?, ?, 1)'
            );
            $stmt->execute([$channel,$key]);
            return true;
        }else{
            return false;
        }
    }
    
    public function removeChannel($channel) // remove channel
    {
        $chaninfo = $this->findChannel($channel);
        if($chaninfo == false){
            return false;
        }else{
            $stmt = $this->db->prepare(
                'DELETE FROM channels WHERE name = ?'
            );
            $stmt->execute([$channel]);
            $stmt = $this->db->prepare(
                'DELETE FROM orderset_channels WHERE channel_id = ?'
            );
            $stmt->execute([(int) $chaninfo[0]]);
            return true;
        }
    }

    public function findChannel($channel) // Get info about a channel
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM channels WHERE name = ?'
        );
        $stmt->execute([$channel]);
        if(!$chanid = $stmt->fetchAll(PDO::FETCH_COLUMN)){
            return false;
        }
        $chaninfo[0] = $chanid[0];
        
        $stmt = $this->db->prepare(
            'SELECT orderset_id FROM orderset_channels WHERE channel_id = ?'
        );
        $stmt->execute([$chaninfo[0]]);
        if(!$ordersetid = $stmt->fetchAll(PDO::FETCH_COLUMN)){
            $chaninfo[1] = NULL;
        }else{
            $chaninfo[1] = $ordersetid;
        }
        return $chaninfo;
    }
    
    public function getUser($nickname) // Get info about a user
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE nick = ?');
        $stmt->execute([$nickname]);
        if(!$userinfo = $stmt->fetchAll(PDO::FETCH_ASSOC)){
            return false;
        }else{
            return $userinfo;
        }
    }

    public function isAdmin($nickname) // Is user an admin?
    {
        $stmt = $this->db->prepare(
            'SELECT admin FROM users '.
            'WHERE nick = ?'
        );
        $stmt->execute([$nickname]);
        if(!$isadmin = $stmt->fetch(PDO::FETCH_COLUMN)){
            return false;
        }else{
            if($isadmin[0] == 1 || $isadmin[0] == 2){
                return true;
            }else{
                return false;
            }
        }
    }
    
    public function isQM($nickname) // Is user a QM?
    {
        $stmt = $this->db->prepare(
            'SELECT admin FROM users '.
            'WHERE nick = ?'
        );
        $stmt->execute([$nickname]);
        if(!$qm = $stmt->fetch(PDO::FETCH_COLUMN)){
            return false;
        }else{
            if($qm[0] == 3){
                return true;
            }else{
                return false;
            }
        }
    }
    
    public function getOrderset($order=NULL) // Get info about an orderset
    {
        if($order != NULL){
            $stmt = $this->db->prepare(
                'SELECT * FROM ordersets WHERE name = ?'
            );
            $stmt->execute([$order]);
            $orderinfo = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if(empty($orderinfo)){
                return false;
            }else{
                return $orderinfo;
            }
        }else{
            $stmt = $this->db->prepare(
                'SELECT * FROM ordersets'
            );
            $stmt->execute();
            $orderinfo = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if(empty($orderinfo)){
                return false;
            }else{
                return $orderinfo;
            }
        }
    }

    public function getOrders($order, $channel=NULL) // Get orders for a orderset
    {        
        $orderinfo = $this->getOrderset($order);
        
        if($channel == NULL){
            $channels[0] = $orderinfo[0]['id'];
            $channel = $orderinfo[0]['id'];
        }else{
            $channels = $this->getordersetChannels($order);
        }
        if(!in_array(strtolower($channel), array_map('strtolower', $channels))){
            return false;
        }else{
            $stmt = $this->db->prepare(
                'SELECT * FROM orders WHERE orderset_id = ? ORDER BY priority'
            );
            $stmt->execute([(int) $orderinfo[0]['id']]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $orders;
        }
    }

    public function updateOrder($order, $nickname, $update, $priority) // Update an order set
    {
        $orderset = $this->getOrderset($order);
        if($orderset == false){
            return false;
        }
        $userinfo = $this->getUser($nickname);
        $stmt = $this->db->prepare(
            'SELECT nick_id FROM orderset_users WHERE orderset_id = ? AND nick_id = ?'
        );
        $stmt->execute([(int) $orderset[0]['id'], (int) $userinfo[0]['id']]);
        if(!$stmt->fetchAll(PDO::FETCH_ASSOC)){
            if(!$this->isAdmin($nickname)){
                return false;
            }
        }
        
        $stmt = $this->db->prepare(
            'UPDATE orders SET info = ? WHERE priority = ? AND orderset_id = ?'
        );
        $stmt->execute([$update, (int) $priority, (int) $orderset[0]['id']]);
        $check = $stmt->rowCount();
        if($check == 0){
            $stmt = $this->db->prepare(
                'INSERT INTO orders (info, priority, orderset_id) VALUES (?, ?, ?)'
            );
            $stmt->execute([$update, (int) $priority, (int) $orderset[0]['id']]);
        }
        
        return true;
    }

    public function getChannels() // Get autojoin channels
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM channels WHERE autojoin = 1'
        );
        $stmt->execute();
        $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if(empty($channels)){
            return false;
        }
        return $channels;
    }
    
    public function getAdmins() // Get all admins
    {
        $stmt = $this->db->prepare(
            'SELECT nick FROM users WHERE admin = 1 or admin = 2'
        );
        $stmt->execute();
        $admin = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $admin;
    }
    
    public function getQMs() // Get all QMs
    {
        $stmt = $this->db->prepare(
            'SELECT nick FROM users WHERE admin = 3'
        );
        $stmt->execute();
        $admin = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $admin;
    }
    
    public function getordersetUsers($order) // Get users of an order set
    {
        if(!$orderinfo = $this->getOrderset($order)){
            return false;
        }else{
            $stmt = $this->db->prepare(
                'SELECT nick_id FROM orderset_users WHERE orderset_id = ?'
            );
            $stmt->execute([(int) $orderinfo[0]['id']]);
            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if(empty($users)){
                return false;
            }
            $nicks = [];
            foreach($users as $u){
                $stmt = $this->db->prepare(
                    'SELECT nick FROM users WHERE id = ?'
                );
                $stmt->execute([(int) $u]);
                $foundUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $nicks[] = $foundUsers[0];
            }
            return $nicks;
        }
    }
    
    public function getordersetChannels($order) // Get channels of an order set
    {
        if(!$orderinfo = $this->getOrderset($order)){
            return false;
        }else{
            $stmt = $this->db->prepare(
                'SELECT channel_id FROM orderset_channels WHERE orderset_id = ?'
            );
            $stmt->execute([$orderinfo[0]['id']]);
            $channels = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if(empty($channels)){
                return false;
            }
            $chans = [];
            foreach($channels as $c){
                $stmt = $this->db->prepare(
                    'SELECT name FROM channels WHERE id = ?'
                );
                $stmt->execute([(int) $c]);
                $foundChans = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $chans[] = $foundChans[0];
            }
            return $chans;
        }
    }

    public function addAdmin($nickname, $install=false) // Add an admin
    {
        if($this->isAdmin($nickname) == true){
            return false;
        }
        
        if($install == false){
            if(!$this->getUser($nickname)){
                $stmt = $this->db->prepare(
                    'INSERT INTO users (nick, admin) VALUES (?, 1)'
                );
                $stmt->execute([$nickname]);
            }else{                
                $stmt = $this->db->prepare(
                    'UPDATE users SET admin = 1 WHERE nick = ?'
                );
                $stmt->execute([$nickname]);
            }
            return true;
        }
        if($install == true){
            $stmt = $this->db->prepare(
                'SELECT nick FROM users WHERE admin = 2'
            );
            $stmt->execute();
            $check = $stmt->fetch(PDO::FETCH_COLUMN);
            if($check != false){
                return false;
            }
            if(!$this->getUser($nickname)){
                $stmt = $this->db->prepare(
                    'INSERT INTO users (nick, admin) VALUES (?, 2)'
                );
                $stmt->execute([$nickname]);
            }else{
                $stmt = $this->db->prepare(
                    'UPDATE users SET admin = 2 WHERE nick = ?'
                );
                $stmt->execute([$nickname]);
            }
            return true;
        }
    }
    
    public function addQM($nickname) // Add a QM
    {
        if($this->isQM($nickname) == true){
            return false;
        }
        
        if(!$this->getUser($nickname)){
            $stmt = $this->db->prepare(
                'INSERT INTO users (nick, admin) VALUES (?, 3)'
            );
            $stmt->execute([$nickname]);
        }else{                
            $stmt = $this->db->prepare(
                'UPDATE users SET admin = 3 WHERE nick = ?'
            );
            $stmt->execute([$nickname]);
        }
        return true;
    }

    public function addorderUser($nickname, $order) //Add user to an order set
    {
        $orderinfo = $this->getOrderset($order);
        if($orderinfo == false){
            return false;
        }
        if(!$userinfo = $this->getUser($nickname)){
            $stmt = $this->db->prepare(
                'INSERT INTO users (nick) VALUES (?)'
            );
            $stmt->execute([$nickname]);
            $userinfo = $this->getUser($nickname);
            $stmt = $this->db->prepare(
                'SELECT * FROM orderset_users WHERE orderset_id = ? AND nick_id = ?'
            );
            $stmt->execute([(int) $orderinfo[0]['id'], (int) $userinfo[0]['id']]);
            $check = $stmt->fetch(PDO::FETCH_COLUMN);
            if($check != false){
                return false;
            }
            $stmt = $this->db->prepare(
                'INSERT INTO orderset_users (orderset_id, nick_id) VALUES (?, ?)'
            );
            $stmt->execute([(int) $orderinfo[0]['id'], (int) $userinfo[0]['id']]);
        }else{
            $stmt = $this->db->prepare(
                'SELECT * FROM orderset_users WHERE orderset_id = ? AND nick_id = ?'
            );
            $stmt->execute([(int) $orderinfo[0]['id'], (int) $userinfo[0]['id']]);
            $check = $stmt->fetch(PDO::FETCH_COLUMN);
            if($check != false){
                return false;
            }
            $stmt = $this->db->prepare(
                'INSERT INTO orderset_users (orderset_id, nick_id) VALUES (?, ?)'
            );
            $stmt->execute([(int) $orderinfo[0]['id'], (int) $userinfo[0]['id']]);
        }
        return true;
    }
    
    public function addorderChannel($channel, $order) // Add an order set to channel
    {        
        $orderinfo = $this->getOrderset($order);
        if($orderinfo == false){
            return false;
        }
        if(!$chaninfo = $this->findChannel($channel)){
            $stmt = $this->db->prepare(
                'INSERT INTO channels (name, autojoin) VALUES (?, 1)'
            );
            $stmt->execute([$channel]);
            $chaninfo = $this->findChannel($channel);
            $stmt = $this->db->prepare(
                'SELECT * FROM orderset_channels WHERE orderset_id = ? AND channel_id = ?'
            );
            $stmt->execute([(int) $orderinfo[0]['id'], (int) $chaninfo[0]]);
            $check = $stmt->fetch(PDO::FETCH_COLUMN);
            if($check != false){
                return false;
            }
            $stmt = $this->db->prepare(
                'INSERT OR REPLACE INTO orderset_channels (orderset_id, channel_id) VALUES (?, ?)'
            );
            $stmt->execute([(int) $orderinfo[0]['id'], (int) $chaninfo[0]]);
        }else{
            $stmt = $this->db->prepare(
                'SELECT * FROM orderset_channels WHERE orderset_id = ? AND channel_id = ?'
            );
            $stmt->execute([(int) $orderinfo[0]['id'], (int) $chaninfo[0]]);
            $check = $stmt->fetch(PDO::FETCH_COLUMN);
            if($check != false){
                return false;
            }
            $stmt = $this->db->prepare(
                'INSERT OR REPLACE INTO orderset_channels (orderset_id, channel_id) VALUES (?, ?)'
            );
            $stmt->execute([(int) $orderinfo[0]['id'], (int) $chaninfo[0]]);
        }
        return true;
    }
    
    public function addOrder($order) // Create a new order set
    {
        $ordersets = $this->getOrderset();
        foreach($ordersets as $o){
            if($o['name'] == $order){
                return false;
            }
        }      
        $stmt = $this->db->prepare(
            'INSERT INTO ordersets (name) VALUES (?)'
        );
        $stmt->execute([$order]);  
        return true;      
    }
    
    public function removeAdmin($nickname) // Remove an admin
    {
        $admin = $this->getUser($nickname);
        
        if($admin[0]['admin'] == '2'){
            return false;
        }
        
        if($admin !== false){
            $stmt = $this->db->prepare(
                'UPDATE users SET admin = 0 WHERE nick = ?'
            );
            $stmt->execute([$nickname]);
            return true;
        }else{
            return false;
        }
    }
    
    public function removeQM($nickname) // Remove a QM
    {
        $admin = $this->getUser($nickname);
        
        if($admin !== false){
            $stmt = $this->db->prepare(
                'UPDATE users SET admin = 0 WHERE nick = ?'
            );
            $stmt->execute([$nickname]);
            return true;
        }else{
            return false;
        }
    }
    
    public function removeorderUser($nickname, $order) // Remove user from an order set
    {
        $orderinfo = $this->getOrderset($order);
        if($orderinfo == false){
            return false;
        }
        if(!$userinfo = $this->getUser($nickname)){
            return false;
        }else{
            $stmt = $this->db->prepare(
                'DELETE FROM orderset_users WHERE nick_id = ? AND orderset_id = ?'
            );
            $stmt->execute([(int) $userinfo[0]['id'], (int) $orderinfo[0]['id']]);
            return true;
        }
    }
    
    public function removeorderChannel($channel, $order) // Remove an order set from channel
    {
        $orderinfo = $this->getOrderset($order);
        if($orderinfo == false){
            return false;
        }
        if(!$chaninfo = $this->findChannel($channel)){
            return false;
        }else{
            $stmt = $this->db->prepare(
                'DELETE FROM orderset_channels WHERE channel_id = ? AND orderset_id = ?'
            );
            $stmt->execute([(int) $chaninfo[0], (int) $orderinfo[0]['id']]);
            return true;
        }
    }
    
    public function removeOrder($order, $nickname, $priority=NULL) // Remove an order set or an orderset priority
    {
        $orders = $this->getOrders($order);
        if(!$orderset = $this->getOrderset($order)){
            return false;
        }
        $userinfo = $this->getUser($nickname);
        
        $stmt = $this->db->prepare(
            'SELECT nick_id FROM orderset_users WHERE orderset_id = ? AND nick_id = ?'
        );
        $stmt->execute([(int) $orderset[0]['id'], (int) $userinfo[0]['id']]);
        
        if(!$stmt->fetch(PDO::FETCH_COLUMN)){
            if(!$this->isAdmin($nickname)){
                return false;
            }
        }
        
        if($priority != NULL){
            $stmt = $this->db->prepare(
                'DELETE FROM orders WHERE priority = ? AND orderset_id = ?'
            );
            $stmt->execute([(int) $priority, (int) $orderset[0]['id']]);
            if($stmt->rowCount() == 0){
                return false;
            }
            $orders = $this->getOrders($order);
            $number = 1;
            foreach($orders as $o){
                $stmt = $this->db->prepare(
                    'UPDATE orders SET priority = ? WHERE id = ?'
                );
                $stmt->execute([(int) $number, (int) $o['id']]);
                $number++;
            }
        }else{
            $stmt = $this->db->prepare(
                'DELETE FROM orders WHERE orderset_id = ?'
            );
            $stmt->execute([(int) $orderset[0]['id']]);
            $stmt = $this->db->prepare(
                'DELETE FROM ordersets WHERE id = ?'
            );
            $stmt->execute([(int) $orderset[0]['id']]);
            $stmt = $this->db->prepare(
                'DELETE FROM orderset_users WHERE orderset_id = ?'
            );
            $stmt->execute([(int) $orderset[0]['id']]);
            $stmt = $this->db->prepare(
                'DELETE FROM orderset_channels WHERE orderset_id = ?'
            );
            $stmt->execute([(int) $orderset[0]['id']]);
        }
        
        return true;
    }
}
