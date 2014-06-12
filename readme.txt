=== SetCron ===
Contributors: SetCron
Site: https://setcron.com/
Tags: admin, cronjob, cron, crontab, wp-cron, scheduled task, task scheduler, scheduled posts, web cron, plugin
Requires at least: 3.0.1
Tested up to: 3.9.x
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

SetCron allows you to schedule cronjobs on your wordpress admin panel. This service is provided for free by [https://www.setcron.com](https://www.setcron.com).

== Description ==

SetCron allows you to schedule cronjobs on your wordpress admin panel. SetCron works by pinging your designated urls periodically according to your settings via an automator. 

Features include:

* Full crontab functionality
* Timezone support
* Start / Stop settings
* Unlimited executions (Every minute)
* Email Reports
* Failure Notifications
* Execution Statistics

**A comparison of SetCron against alternatives**

|                                        | SetCron    | Linux Crontab  | User Triggered Cron          |
|----------------------------------------|------------|----------------|------------------------------|
| Accurate executions                    | Yes        | Yes            | Dependent on visitor traffic |
| Multiple Timezone Support              | Yes        | Need to script | Need to script               |
| Complexity                             | Low        | High           | High                         |
| Requires Server / Shell Command Access | No         | Yes            | No                           |
| Process                                | Background | Background     | User Page Request            |
| Monitor Performance                    | Yes        | Need to script | Need to script               |
| Email Notification                     | Yes        | Need to script | Need to script               |
	
This service is provided for free by SetCron.

For more information, check out [https://www.setcron.com](https://www.setcron.com).

== Installation ==

1. Upload the `setcron` folder to the `/wp-content/plugins/` directory or upload the setcron zip file directly at Plugins -> Add New -> Upload
2. Activate the Plugin through the 'Plugins' menu in WordPress.
3. Go to the `SetCron` menu.
4. You will see a notification to add an API key.
5. Go to the Settings -> SetCron and enter your API key
6. If you don't have an API key, you have to sign up at  [https://www.setcron.com](https://www.setcron.com).
7. Once you have enter a valid API key, you can add, edit and delete your scheduled tasks which will execute periodically according to the crontab setting of each task.


== Frequently Asked Questions ==

SetCron provides support and FAQs for all its users [here](https://www.setcron.com/help).
You can also post your request here at the support tab.

== Screenshots ==

1. `Install SetCron plugin.`
2. `Go to SetCron page.`
3. `Enter your API Key.`
4. `Now you are ready schedule cronjobs for your application.`

== Changelog ==

= 1.0 =
Initial commit