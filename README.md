Sovereign2
==========

The Sovereign bot remade as a plugin for the Phoebe IRC bot skeleton.

Original Sovereign IRC bot: https://github.com/kparaju/Sovereign

Installation
==========

First install [Phoebe](https://github.com/stil/phoebe)

Then copy the `Plugin` folder to your Phoebe installation at `/vendor/stil/phoebe/src/Phoebe`.

Now copy `SovereignBot.php` to the root of your Phoebe installation.

Edit `SovereignBot.php` with your Bots information such as nick and password, IRC network and port

````php
$rizon = new Connection();
$rizon->setServerHostname('irc.rizon.net');
$rizon->setServerPort(6667);
$rizon->setNickname('Sovereign');
$rizon->setUsername('Sovereign');
$rizon->setRealname('Sovereign');
...
...
...
$events->addSubscriber(new NickServPlugin("password"));
````

Run `SovereignBot.php`

To set up your bot for the first time send this PM to your bot: `@install #channel channelpassword` (Channel password is optional)

The nick that sends this message will be set as a superadmin and have full control of the bot.

The bot will join the channel given in the message.

Commands
==========
````
@<orderset> - Show orders in an orderset

@join #channel <optional chan password> - Joins the channel (with password option)

@part #channel - Parts channel and removes from autojoin list

@calllog <orderset> <optional date string> - List the who, when, and how many times an orderset was viewed (date string examples: yesterday, -2 days, April 1 2014)

@adduser <nick> orderset - Adds a user (orderset admin) to an orderset

@deleteuser <nick> orderset - Delete a user from an orderset

@listusers orderset - List the users in an orderset

@addchan #channel orderset - Adds a channel to an orderset

@deletechan #channel orderset - Delete a channel from an orderset

@listchan orderset - List the channels in an orderset

@addadmin <nick> - Add an admin

@deleteadmin <nick> - Delete an admin

@listadmins - Lists all the admins

@addqm <nick> - Add a qm

@deleteqm <nick> - Delete a qm

@listqms - Lists all the qms

@addorderset orderset - Adds an orderset

@deleteorderset orderset - Deletes an orderset

@listordersets - List order sets

@help - This
````
