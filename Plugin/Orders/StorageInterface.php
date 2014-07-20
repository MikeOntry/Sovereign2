<?php
namespace Phoebe\Plugin\Orders;

interface StorageInterface
{
    public function logorderCall($nick, $order); // log when user calls for orders
    public function getcallLog($date, $order); // get the call log between now and $date
    public function addChannel($channel, $key); // add channel
    public function removeChannel($channel); // remove channel
    public function findChannel($channel); // Get info about a channel
    public function getUser($nickname); // Get info about a user
    public function isAdmin($nickname); // Is user an admin?
    public function isQM($nickname); // Is user a QM?
    public function getOrderset($order=NULL); // Get info about an orderset
    public function getOrders($order, $channel=NULL); // Get orders for a orderset
    public function updateOrder($order, $nickname, $update, $priority); // Update an order set
    public function getChannels(); // Get autojoin channels
    public function getAdmins(); // Get all admins
    public function getQMs(); // Get all QMs
    public function getordersetUsers($order); // Get users of an order set
    public function getordersetChannels($order); // Get channels of an order set
    public function addAdmin($nickname, $install=false); // Add an admin
    public function addQM($nickname); // Add a QM
    public function addorderUser($nickname, $order); //Add user to an order set
    public function addorderChannel($channel, $order); // Add an order set to channel
    public function addOrder($order); // Create a new order set
    public function removeAdmin($nickname); // Remove an admin
    public function removeQM($nickname); // Remove a QM
    public function removeorderUser($nickname, $order); // Remove user from an order set
    public function removeorderChannel($channel, $order); // Remove an order set from channel
    public function removeOrder($order, $nickname, $priority=NULL); // Remove an order set or an orderset priority    
}
