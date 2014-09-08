Activity stream local plugin
============================

This plugin catches course content creation and update events (\core\event\course_module_created, \core\event\course_module_updated) and sends new messages about the events. It does this by queueing a master message for each event in table {message_culactivity_stream_q} in real time. A cron job then runs every 5 mins and loops through unsent messages in the queue.  It sends individual messages to users and updates the status of the queued master message.

It checks first if the update is currently visible to the user and does not send a message if it is not.


Maintainer
----------

The local plugin has been written and is currently maintained by Amanda Doughty.


Documentation
-------------

Documentation will be provided at [the page at Moodle wiki](http://docs.moodle.org/en/Activity_Stream_local)
if the local plugin makes it into the Moodle plugins directory.