OPServ
======

BWC OpServ

Current Project - AAR Page.  When loading an AAR the database spikes to 100% CPU and virtually freezes as it tries to load the OpServ page.

Goals:

1) AAR Page have 1 long list of members with check marks so they can be marked as attended or not (aka remove the break downs; RSVP'd etc).

2) A search box where people can type in the names; and in some java/ajax output - it will let those names also be marked as attended.

3) Less than 10 second load.

======
Recommended Query:

SELECT members.id AS memberId, username, status FROM members, operation_attendees WHERE  operation_attendees.member_id=members.id AND operation_id='". $row['id'] ."'
