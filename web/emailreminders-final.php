# Automatic MRBS reminder e-mails, GPLv4, created by anselm at hoffmeister dot be, modified by carmean at sfu dot ca with help from others including Anselm, 
# updated for weekly reminders of all the bookings for the coming week by klaasschoutteten at gmail dot com with help from Anselm.
# See http://sourceforge.net/mailarchive/message.php?msg_id=25430126 for more information.  
# The following script is written to work with the 'db_ext' auth method, on condition that the external users database is actually the same one as the MRBS database...
# As db_ext, I installed the following registration system: http://daveismyname.com/login-and-registration-system-with-php-bp . MRBS was v1.4.11.
# The registration system allows people to register an account, and can easily be configured to work within MRBS. It uses crypt password hashing format. 
# The users are stored in the same MySQL database as the MRBS MySQL database, but in a MySQL table called members (rather than mrbs_users for the 'db' auth option).
# If you want this script to run with the 'db' auth option, you will have to change m.username, m.email, and members m to u.name, u.email and mrbs_users u respectively in $s on lines 42, 43, 45 and 49.
# You can test this code by running it from the website (e.g., the filename was emailreminders-final.php, and I ran it by (re)loading http://www.your.website.address/mrbs/emailreminders-final.php) ,
# but made it so all e-mails went to me (line 29) so that others would not be bothered.
# To run this script weekly, link it to a cron job which runs weekly at e.g. Monday 5 am (choose a time not interfering with daylight saving time changes)
# The code for a weekly cronjob at Monday 5 am is: 0 5 * * 1 wget –q  http://www.your.website.address/mrbs/emailreminders-final.php >/dev/null 2>&1

<html>
<head><title>Automatic MRBS reminder e-mails</title></head>
<body>
<?php
		function sendnotific ( $date, $mrbs, $email, $list ) {
				$mailtext = "Dear user,\n\n\n";
				$mailtext.= "Please be reminded of your reservations for this week:";
				$mailtext.= "\n".$list;
				$mailtext.= "\n\nIf you have changed plans and do not need one or more of ";
				$mailtext.= "these bookings, may we please ask you to cancel the respective booking(s).\n";
				$mailtext.= "http://www.your.website.address/\n\n\n";
				$mailtext.= "This is an automatic reminder mail sent by the ";
				$mailtext.= "MRBS. Please do not reply to this e-mail. ";
   
				mail($email, "[MRBS] Weekly reminder for your bookings",  		  				# to test "your.test@email.address" (do not forget the "-signs) or for real $email (without "-signs)
				$mailtext, "From: noreply@your.website.address.be\n".  							# the 'from' e-mail address where the reminder e-mails supposedly come from, can be non existing e.g. noreply@email.address
				"Precedence: bulk\nContent-Type: text/plain;".
				"charset=UTF-8");
       }


		setlocale(LC_ALL,"En-Us");
		$a = mysql_connect("host","username","password");  										# "MySQL Host", "MySQL database username", "MySQL database password"
		if ( ! $a ) { echo "ERR:mysql_connect failed\n"; exit(1); }
		mysql_select_db('databasename',$a) or die ("ERR:select_db failed\n"); 					# 'MySQL database name'
       
	$s = mysql_query("SELECT " .
	" e.id, e.start_time, e.end_time, e.description, " .
	" a.area_name, r.room_name, m.username, m.email".
	" FROM mrbs_entry e, mrbs_area a, mrbs_room r, members m".
	" WHERE (e.room_id=r.id) AND ".
	" (r.area_id=a.id) AND (m.username=e.create_by) ".
	" AND (e.start_time>(UNIX_TIMESTAMP()+(1*1))) ".
	" AND (e.start_time<(UNIX_TIMESTAMP()+(1*60*60*24*7))) ".									# weekly reminders
	" AND (e.type='I') ".																		# only internal reservations, do not remind people who made reservations for external people (since it is not the correct e-mail address anyway)
	" ORDER BY m.username, e.start_time");   													# " ORDER BY m.username, e.start_time");


		$list = "";
		$last = "----";
		$lastmail = "";
		$z = 0;
		while ( $a = mysql_fetch_row($s) ) {
			if ( $last != $a[6] ) {
				if ( $last != "----" ) {
					echo "reminder: ".$last.", ".$lastmail."\n";  
					sendnotific($z, $last, $lastmail, $list);
					}
                    $last = $a[6];
					$lastmail = $a[7];
					$list = "";
					$z = $a[1];
				}
				$list .= strftime("\n%a, %d %B %Y, %H:%M",
				$a[1]).": ".$a[4]." / ".$a[5]."\n";
		}
		if ( $last != "----" ) {
			echo "reminder: ".$last.", ".$lastmail."\n";
			sendnotific($z, $last, $lastmail, $list);
		}
?>
</body>
</html>