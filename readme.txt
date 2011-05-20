=== Learninglog ===
Contributors: Tom, andrea.cantieni
Tags: wpmu, buddypress, school, learning, teaching, learninglog
Requires at least: 3.0
Tested up to: 3.1.2
Stable tag: 2.1.1

Learninglog offers teachers and learners advanced functions to use WordPress as a learning tool.

== Description ==

Learninglog is a free, open source plugin for WordPress. It offers teachers and learners advanced functions to use WordPress as a learning tool which includes

 * an administration interface for teachers to set up learninglogs for students
 * the possibility for teachers to create assignments for groups or individuals
 * easy ways to collect and review answers from individual learninglogs
 * advanced privacy settings for each learninglog entry, that can be labeled private, visible for a specific group or visible for  the www
 * a simplicity toggle, to switch advanced wordpress functions on or off, more user friendly, especially for young learners

Learninglog is a project of the [Institute for Media and Schools](http://www.schwyz.phz.ch/en/research-and-development/) at University of Teacher Education Central Switzerland, PHZ Schwyz.

== Installation ==
Learninglog requires WordPress 3.0 or higher (with **multisite enabled**) and BuddyPress 1.2.4.1 or higher installed.

Learninglog is tested up to WordPress 3.1.2 (with multisite enabled) and BuddyPress 1.2.8 installed.

= Make sure you have WordPress 3.0 or higher (with multisite enabled) and BuddyPress 1.2.4.1 or higher properly installed on your server. From this point on, there are at least three different installation scenarios: =


= A) Installation through the plugins sub panel in the admin section of the super admin =

1. See http://codex.wordpress.org/Plugins_Add_New_SubPanel
1. Make your FTP credentials to the server hosting your website available
1. Log in as super admin to your WordPress website
1. Navigate to the super (network) admin administration section
1. Navigate to 'Plugins' > 'Add New'
1. Search the Plugin by the 'learninglog' keyword
1. Click 'Install Now'
1. Enter your FTP credentials if asked for
1. Click 'Activate Plugin'

= B) Installation through an FTP client =

1. See http://codex.wordpress.org/FTP_Clients
1. Configure your FTP client to connect to the server hosting your website
1. Download the plugin from the WordPress plugins website to your desktop
1. Unzip the downloaded file
1. Navigate with your FTP client to the wp-content/plugins directory of your WordPress installation
1. Move the unzipped directory from your desktop to the wp-content/plugins directory pointed by the FTP client
1. Activate the plugin through the 'Plugins' menu in WordPress

= C) Installation through svn via ssh (for advanced users only) =

1. Log in via ssh to the server hosting your website
1. Change to the wp-content/plugins directory of your WordPress installation
1. Make directory 'learninglog'
1. Change to the just created directory
1. Type 'svn co http://plugins.svn.wordpress.org/learninglog/trunk/ .' and hit enter
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==
= Are there themes included? =
Yes. There are two BuddyPress compatible themes included. You can activate them through the themes section of the super (network) admin menu.
One is called 'Learninglog Home Template' and is intended to be set as the current theme for the super admin.
The second is called 'Learninglog User Template' and is intended to be set as the current theme for all the other users/blogs.

= What is the 'Admin CSS' component? =
The 'Admin CSS' component is by default deactivated and can be activated by the super admin in the 'Admin learninglog' section. By doing so, the administration area of all users/blogs are adjusted to the design of the learninglog theme (see above).

= What is the SidebarLeft sidebar? =
If the 'Learninglog Home Template' is set as the current theme (see above), widgets can be placed on the left side of the home screen, as for exampple pages or plain text.

== Screenshots ==
1. Admin learninglog (as super (network) admin)
1. Create new assignment (as teacher)
1. Answer to an assignment (as student)

== Changelog ==
= 2.1.1 =
* admin menus are now compatible with the WordPress 3.1 network admin screen
* fixed a lot of bugs (var definitions) to prevent php warnings
* disabled admin menu bar in themes
* added preview images for themes
* fixed a bug in pagination of the group's home section
* fixed a bug not showing correct blog info on the bottom of the group's home section

= 2.1.0.1 =
* fixed a bug relating to the 'visible from' feature

= 2.1 = 
* Initial release.

== Demo ==
http://learninglog.org
