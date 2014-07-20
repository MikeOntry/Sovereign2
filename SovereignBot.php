<?php
require __DIR__.'/vendor/autoload.php';

use Phoebe\ConnectionManager;
use Phoebe\Connection;
use Phoebe\Event\Event;
use Phoebe\Plugin\PingPong\PingPongPlugin;
use Phoebe\Plugin\NickServ\NickServPlugin;
use Phoebe\Plugin\AutoReconnect\AutoReconnectPlugin;
use Phoebe\Plugin\Whois\WhoisPlugin;
use Phoebe\Plugin\UserInfo\UserInfoPlugin;
use Phoebe\Plugin\Orders\OrdersPlugin;
use Phoebe\Plugin\Supply\SupplyPlugin;

date_default_timezone_set('America/Los_Angeles'); //EREPUBLIK TIME ZONE

$rizon = new Connection();
$rizon->setServerHostname('irc.rizon.net');
$rizon->setServerPort(6667);
$rizon->setNickname('Sovereign');
$rizon->setUsername('Sovereign');
$rizon->setRealname('Sovereign');

$events = $rizon->getEventDispatcher();

$myWhois = new WhoisPlugin();
$myUserInfo = new UserInfoPlugin();
$myOrders = new OrdersPlugin();
$mySupply = new SupplyPlugin();
$testPlugin = new Sovereign($myWhois, $myUserInfo, $myOrders, $mySupply);

$reconnect = function ($event) {
    $hostname = $event->getConnection()->getServerHostname();
    $event->getLogger()->debug(
        "Connection to $hostname lost, attempting to reconnect in 15 seconds.\n"
    );

    $event->getTimers()->setTimeout(
        function () use ($event) { 
            $event->getLogger()->debug("Reconnecting now...\n");
            $event->getConnectionManager()->addConnection(
                $event->getConnection()
            );
        },
        15
    );
};

$events->addSubscriber(new PingPongPlugin());
$events->addSubscriber(new NickServPlugin("password"));
$events->addSubscriber(new AutoReconnectPlugin());
$events->addSubscriber($myWhois);
$events->addSubscriber($myUserInfo);
$events->addSubscriber($myOrders);
$events->addSubscriber($testPlugin);
$events->addSubscriber($mySupply);

$events->addListener('irc.received.ERROR', $reconnect);
$events->addListener('connect.error', $reconnect);

$phoebe = new ConnectionManager();
$phoebe->addConnection($rizon);
$phoebe->run();
