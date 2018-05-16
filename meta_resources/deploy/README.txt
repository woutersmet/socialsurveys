README

=== Stuff to set up the site on a new windows machine (for running locally) ===

You should have an apache server with PHP 5.x and MySQL (e.g. WAMP)

THE BIG CONFIG FILE TO OPEN: configuration.inc.php

Place all the source files in a folder called tabignite. In WAMP it would be c:/wamp/www/tabignite typically

All images and temporary template files will be stored in tabignite_storage

So should be here: c:/wamp/www/tabignite/tabignite_storage
This folder should contain 2 subfolders: 'compiledpages' and 'productimages'

=== SVN ===
The UNFUDDLE REPOSITORY is here: http://whatshop.unfuddle.com/svn/whatshop_whatshop/

In NetBeans, you can go to tools > plugins... And install the 'integrated windows SVN bundle' or something

See this for more: http://netbeans.org/kb/docs/ide/subversion.html#checking

IMPORTANT: The subfolders of tabignite_storage ('compiledpages' and 'productimages') are not in the svn repository (ignored), so if you
start from an SVN checkout you will have to create them manually or al you get is blank pages!

=== SSH'ing on hostgator and commit hooks ===
You can enable SSH'ing yourself on hostgator billing panel somewhere under 'domains' I think: https://gbclient.hostgator.com/login/relog/

Then you can use PUTTY the windows client to ssh into the IP address 50.116.86.183 (found in cpanel), port 22222
(article: http://support.hostgator.com/articles/getting-started/how-do-i-get-and-use-ssh-access)

Shared hosting has an installed SVN client, so we have a folder 'whatshopdev' that has domain dev.myshoppingtab.com pointed
to it (you can point subdomains to folders in cpanel).

Unfuddle (whatshop.unfuddle.com) allows to have a 'commit hook', it does a request after each commit to a certain
url you give, I put commithook.php there so it svn updates that dev folder after each commit:

//updating repository here...
$shell_output = shell_exec ('svn up 2>&1');
$latestcommit = substr(shell_exec('svn log -v -r HEAD'), 74);

=== Creating a local domain like tabignite.localhost ===
SOURCE: http://www.trailheadinteractive.com/creating_multiple_virtual_sites_a_single_apache_install_windows_xp
ALSO CHECK OUT THIS FOR MULTIPLE VIRTUAL HOSTS: http://apptools.com/phptools/virtualhost.php
1. Find your <strong>hosts</strong> file (on Windows it's on c:/windows/system32/drivers/etc/ folder) and add that
the domain <strong>tabignite.localhost</strong> should also point to the local IP 127.0.0.1. The result should look something like this:

<pre>
127.0.0.1       localhost

127.0.0.1       local.myshoppingtab.com
</pre>

2. Add a VirtualHost directive in the <strong>httpd.conf</strong> file (which configures how Apache runs). What 
you add should look something like this:

# Tells Apache to serve Client 1's pages to "tabignite.localhost"
# Duplicate and modify this block to add another client

#for multiple hosts also add this one:
NameVirtualHost 127.0.0.1

<VirtualHost 127.0.0.1>

	# The name to respond to
	ServerName local.myshoppingtab.com

	# Folder where the files live
	DocumentRoot "C:/wamp/www/tabignite/"

	# A few helpful settings...
	<Directory "C:/wamp/www/tabignite">
		allow from all
		order allow,deny

		# Enables .htaccess files for this site
		AllowOverride All
	</Directory>
	
	# Apache will look for these two files, in this order, if no file is 	specified in the URL
	DirectoryIndex index.html index.php
</VirtualHost> 

=== DEPLOYING OVER FTP ===
First we need a local fresh copy from which to transfer to the live site. 
We do this by running an SVN update from our repository (so we don't include uncommited changes from our working folder)

initial checkout: http://whatshop.unfuddle.com/svn/whatshop_whatshop/


In Windows: easy to do over FTP using FTPSync
This requires 2 files: a bat file to run in windows, and a .ini file that configures the program for your
specific FTP sync needs

I put it in c:\wamp\deploy folder

See the files in meta_resources for my version of these files, you'll have to change some paths and passwords
note that you fill in pass=xxxxx for your php password, and then after first run it will replace this with
pass= (empty) and EPass=XXXX (something obfuscated) for security.

=== Clean URLs ===
Mod_rewrite should be enabled etc etc and .httpaccess file in root and all

=== EMAIL ===
in windows sending mail from localhost is annoying. In php.ini look for 
SMTP = localhost

and change it to your ISP provider SMTP service. For telenet this is out.telenet.be. From address should also 
change or telenet might complain. Final result in php.ini looks something like this:

;@WOUTER I CHANGED THIS BASED ON  source: http://roshanbh.com.np/2007/12/sending-e-mail-from-localhost-in-php-in-windows-environment.html
; original:  SMTP = localhost
; For Win32 only.
; http://php.net/smtp
SMTP = uit.telenet.be
; http://php.net/smtp-port
smtp_port = 25

; For Win32 only.
; http://php.net/sendmail-from
sendmail_from = wouter@telenet.be

==== MYSQL DATABASE ====

Create a database called [whatever you called it] in Mysql

Run the Queries from DB export.txt to create the necessary tables and fill them with some data (ok this isnt needed but whatever)

Go to configuration.inc.php and double-check the database credentials and paths

To check if it worked, go to something like http://localhost/mysite (depends on your hosts configuration in apache)

INSERTING SOMEBODY WITH PASSWORD: test

INSERT INTO `users` (`firstname`, `lastname`, `email`, `emailstatus`, `password`, `rights`, `pricingplan`, `lastloggedin`, `dateadded`, `dayadded`) VALUES
('Wouter', 'Smet', 'something@gmail.com', 'CONFIRMED', '3002e313fb93e1da8dca4397803c39de', 'ADMIN', 'PROFESSIONAL', NOW(), NOW(), DATE(NOW()))