<?php
namespace Phoebe\Plugin\Orders;

use Phoebe\Event\Event;
use Phoebe\Plugin\PluginInterface;

class OrdersPlugin implements PluginInterface
{
    protected $storage;
    /**
     * Prepares storage object
     */
     
    public static function getSubscribedEvents()
    {
        return [
            'irc.received.PRIVMSG' => ['onPrivmsg']
        ];
    }
    
    public function __construct()
    {
        return $this->storage = new StorageSqlite();
    }
    
    public function onPrivmsg(){
        // Do nothing
    }
    
    public function logorderCall($nick, $order) // log when user calls for orders
    {
        return $this->storage->logorderCall($nick, $order);
    }
    
    public function getcallLog($date, $order) // get the call log between now and $date
    {
        return $this->storage->getcallLog($date, $order);
    }
    
    public function addChannel($channel, $key) // add channel
    {
        return $this->storage->addChannel($channel, $key);
    }
    
    public function removeChannel($channel) // remove channel
    {
        return $this->storage->removeChannel($channel);
    }
    
    public function findChannel($channel) // Get info about a channel
    {
        return $this->storage->findChannel($channel);
    }
    
    public function getUser($nickname) // Get info about a user
    {
        return $this->storage->getUser($nickname);
    }
    
    public function isAdmin($nickname) // Is user an admin?
    {
        return $this->storage->isAdmin($nickname);
    }
    
    public function isQM($nickname) // Is user a QM?
    {
        return $this->storage->isQM($nickname);
    }
    
    public function getOrderset($order=NULL) // Get info about an orderset
    {
        return $this->storage->getOrderset($order);
    }
    
    public function getOrders($order, $channel=NULL) // Get orders for a orderset
    {
        return $this->storage->getOrders($order, $channel);
    }
    
    public function updateOrder($order, $nickname, $update, $priority) // Update an order set
    {
        return $this->storage->updateOrder($order, $nickname, $update, $priority);
    }
    
    public function getChannels() // Get autojoin channels
    {
        return $this->storage->getChannels();
    }
    
    public function getAdmins() // Get all admins
    {
        return $this->storage->getAdmins();
    }
    
    public function getQMs() // Get all QMs
    {
        return $this->storage->getQMs();
    }
    
    public function getordersetUsers($order) // Get users of an order set
    {
        return $this->storage->getordersetUsers($order);
    }
    
    public function getordersetChannels($order) // Get channels of an order set
    {
        return $this->storage->getordersetChannels($order);
    }
    
    
    public function addAdmin($nickname, $install=false) // Add an admin
    {
        return $this->storage->addAdmin($nickname, $install);
    }
    
    public function addQM($nickname) // Add a QM
    {
        return $this->storage->addQM($nickname);
    }
    
    public function addorderUser($nickname, $order) //Add user to an order set
    {
        return $this->storage->addorderUser($nickname, $order);
    }
    
    public function addorderChannel($channel, $order) // Add an order set to channel
    {
        return $this->storage->addorderChannel($channel, $order);
    }
    
    public function addOrder($order) // Create a new order set
    {
        return $this->storage->addOrder($order);
    }
    
    public function removeAdmin($nickname) // Remove an admin
    {
        return $this->storage->removeAdmin($nickname);
    }
    
    public function removeQM($nickname) // Remove an admin
    {
        return $this->storage->removeQM($nickname);
    }
    
    public function removeorderUser($nickname, $order) // Remove user from an order set
    {
        return $this->storage->removeorderUser($nickname, $order);
    }
    
    public function removeorderChannel($channel, $order) // Remove an order set from channel
    {
        return $this->storage->removeorderChannel($channel, $order);
    }
    
    public function removeOrder($order, $nickname, $priority=NULL) // Remove an order set or an orderset priority
    {
        return $this->storage->removeOrder($order, $nickname, $priority);
    }
    
}
