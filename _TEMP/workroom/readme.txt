
    About OpenGoo 1.3
    =================

    OpenGoo is a free and open source WebOffice, project management and collaboration
    tool, licensed under the Affero GPL 3 license.

    visit:
        * http://www.opengoo.org/
        * http://forums.opengoo.org/
        * http://sourceforge.net/projects/opengoo

    contact:
        * contact@opengoo.org


    System requirements
    ===================

    OpenGoo requires a web server, PHP (5.0 or greater) and MySQL (InnoDB
    support recommended). The recommended web server is Apache.

    OpenGoo is not PHP4 compatible and it will not run on PHP versions prior
    to PHP5.

    Recommended:

    PHP 5.2+
    MySQL 4.1+ with InnoDB support
    Apache 2.0+

        * PHP    : http://www.php.net/
        * MySQL  : http://www.mysql.com/
        * Apache : http://www.apache.org/

    Alternatively, if you just want to test OpenGoo and you don't care about security
    issues with your files, you can download XAMPP, which includes all that is needed
    by OpenGoo (Apache, PHP 5, MySQL) in a single download.
    You can configure MySQL to support InnoDB by commenting or removing
    the line 'skip-innodb' in the file '<INSTALL_DIR>/etc/my.cnf'.

        * XAMPP  : http://www.apachefriends.org/en/xampp


    Installation
    ============

    1. Download OpenGoo - http://www.opengoo.org/
    2. Unpack and upload to your web server
    3. Direct your browser to the public/install directory and follow the installation
    procedure

    You should be finished in a matter of minutes.
    
    WARNING: Default memory limit por PHP is 8MB. As a new OpenGoo install consumes about 10 MB,
    administrators could get a message similar to "Allowed memory size of 8388608 bytes exhausted".
    This can be solved by setting "memory_limit=32" in php.ini.    


    Upgrade instructions
    ====================
    
    1. Backup you current installation (important!)
    2. Download OpenGoo 1.3-rc2 - http://www.opengoo.org/
    3. Unpack into your OpenGoo installation, overwriting your previous files and folders,
    	but keeping your config, upload and public/files folders.
    5. Go to <your_opengoo>/public/upgrade in your browser and choose to upgrade
    	from your current version to 1.3-rc2.
    6. Refresh your browser or clear its cache to load new javascript, css and images.   

    
	Open Source Libraries 
	=====================
	
	The following open source libraries and applications have been adapted to work with OpenGoo:
	- ActiveCollab 0.7.1 - http://www.activecollab.com
	- ExtJs - http://www.extjs.com
	- Reece Calendar - http://sourceforge.net/projects/reececalendar
	- Swift Mailer - http://www.swiftmailer.org
	- Open Flash Chart - http://teethgrinder.co.uk/open-flash-chart
	- Slimey - http://slimey.sourceforge.net
	- FCKEditor - http://www.fckeditor.net
	- JSSoundKit - http://jssoundkit.sourceforge.net
	- PEAR - http://pear.php.net


	Changelog
	=========

	Since 1.3-rc2
	-------------
	- usability: Weblinks view now shows URL.
	- bugfix: Fixed error about billing id when closing timeslot.
	- bugfix: Large images would cause a memory exhausted error in the documents listing.
	- bugfix: Some tags were not being displayed in the TagPanel in Chinese and other languages.
	- bugfix: Linking objects to comments wasn't working.
	- bugfix: Http errors are no longer displayed when polling.
	- bugfix: Fixed occasional error about attributes.php.
	- bugfix: Corrected a problem when displaying revisions on the trash.
	- bugfix: General timeslots' billing information is now also included in reports.
	- system: Share action was removed.
	- system: New hooks added.

	Since 1.3-rc
	------------
	- bugfix: Rollbacked change in cookie handling because it caused too many problems.
	- langs: Updated translations: it_it, ru_ru, pt_br, zh_cn, zh_tw.

	Since 1.3-beta
	--------------
	- usability: Emails with no subject can't be opened in Overview->View as list	
	- usability: Added link to RSS feed for a workspace's recent activity in the dashboard description widget 		
	- usability: Monthly view scroll, didn't mantain the toolbar like the others views	
	- usability: Tooltip in calendar monthly view doesn't show description	
	- usability: Changed how an error in tasks filter is displayed.	
	- usability: Errors when polling for reminders are no longer shown to the user
	- usability: The 'All' checkbox when editing a user's permissions was moved to a more adequate place.

	- langs: Missing langs [view weblink], [new share notification (file, weblink, milestone)]
	- langs: Added first version of Traditional Chinese translation zh_tw.
	- langs: Renamed es_uy to es_la (updated automatically when you perform upgrade procedure).

	- bugfix: filtering by unclassified emails showed no emails	
	- bugfix: Fixed an issue with cookies that didn't allow you to login to two OpenGoo installations on the same server, or an OpenGoo installation and a Joomla installation	
	- bugfix: Fixed some resizing problems in IE in calendar and tasks.		
	- bugfix: Share: An alert is shown when adding an email to the share list	
	- bugfix: Slimey always saves as a new document	

	Since 1.2.1
	-----------
	- feature: Billing Module. Allows defining hourly rates for users and workspaces. You can generate a report showing how much should be billed for some client. You also get charts on the dashboard comparing billing amounts by user.
	- feature: Reminders. Define email reminders or popup reminders for events, tasks and milestones. Needs a properly configured cron job.
	- feature: Share with an external user easily.
	- feature: New dashboard widget with workspace info.
	
	- usability: Sort weblinks by date as well as name.
	- usability: Improved display of documents' contents. HTML documents are shown with its CSS. Long documents are shown with a scrollbar, so that it doesn't displace comments and revisions out of the view.
	- usability: Weblinks view. This allows to comment on web links.
	- usability: User is automatically subscribed to objects when using quick adds.
	- usability: Corrected tabindex order in forms in Firefox.
	- usability: Added button 'classify' next to title at mail classification view.	
	- usability: Title in calendar daily view was made localizable.
	- usability: Calendar filters are remembered.
	- usability: Now you can see the state of attendance to an event in the monthly view.
	- usability: User filters in calendar filter also tasks and milestones.
	- usability: When adding objects to a template the custom properties are now also copied.
	
	- system: Email content is now stored as a file, which allows receiving larger emails when using filesystem storage.
	- system: Removed table eventtypes.
	- system: Separate calendar filter menu, user combo (unificated with tasks) and status combo.
	- system: Substituted imap functions for PEAR's Net_IMAP functions, which allows for greater compatibilty for using IMAP accounts.
	- system: External company users are no longer shown other external company users.
	 
	- bugfix: Email comments weren't listed.
	- bugfix: SSL for outgoing connections was not correctly supported in email accounts.
	- bugfix: Reduced the number of CSS files in OpenGoo to bypass a limitation in IE that won't let you include more than 32 CSS files in one page.
	- bugfix: search in a workspace returned no results but search in all workspaces returned results from that workspace.
	- bugfix: Total task execution time report included task pause time as well as task time.
