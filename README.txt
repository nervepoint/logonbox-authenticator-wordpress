=== LogonBox Authenticator ===
Contributors: tanktarta
Donate link: https://logonbox.com/
Tags: authentication, authenticator, login, two-factor, username
Requires at least: 5.0
Tested up to: 6.0
Stable tag: 0.1
License: Apache-2.0
License URI: https://www.apache.org/licenses/LICENSE-2.0

Adds two-factor authentication for user and administrators via LogonBox Authenticator.

== Description ==
[See our video on how LogonBox Authenticator works.](https://www.logonbox.com/content/logonbox-authenticator/)

LogonBox Authenticator provides simple to use two-factor authentication for your users, armed with only a mobile app.

In order to use this plugin, you will require access to one of our on-prem or cloud products to act as a server for 
the authentication. All users that you wish to protect with 2FA must also exist on this product with identical email addresses.  

== Installation ==

See [our instructions](https://docs.logonbox.com/app/manpage/en/article/7737790).

== Frequently Asked Questions ==

= How do I get started with LogonBox Authenticator? =

Before installing the plugin, you'll need either your own separate LogonBox server, or your users will need accounts on our Cloud Directory service. To sign up for a free account at [LogonBox Directory](https://XXXXXXXXXXXXXXXXXXXX).

= Can I protect my own applications using LogonBox Authenticator? =

We have a growing number of libraries and extensions for various languages and frameworks available on GitHub, for example [Java](https://github.com/nervepoint/logonbox-authenticator-java), [Python](https://github.com/nervepoint/logonbox-authenticator-python), [Node/Javascript](https://github.com/nervepoint/logonbox-authenticator-nodejs). More to come! 

== Screenshots ==
1. LogonBox Authenticator adds a second factor authentication to your WordPress login. Your users log in normally with their WordPress username and password. Next they will have to pass LogonBox Authenticator,  Then they’ll be  challenged to complete secondary authentication via Duo Push, phone callback, or one-time passcodes generated via the Duo Mobile app or delivered via SMS.

2. The plugin has minimal configuration. You can use your own Directory server simply by supplying it's address here.   

== Changelog ==

= 0.1 =
* Initial public beta release!