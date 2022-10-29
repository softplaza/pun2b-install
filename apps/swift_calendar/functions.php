<?php 

function sm_calendar_outlook_create_event($start_time, $subject='', $description='')
{
	global $User, $Config;
	
	$from_name = $User->get('realname');
	$from_address = $Config->get('o_webmaster_email');
	
	$to_name = $User->get('realname');
	$to_address = $User->get('sm_calendar_outlook_email') != '' ? $User->get('sm_calendar_outlook_email') : '';
	
	//	$startTime = "01/21/2021 11:00:00";
	//	$endTime = "01/21/2021 11:30:00";
	$duration = 3600;
	$startTime = date('m/d/Y H:i:s', $start_time);
	$endTime = date('m/d/Y H:i:s', ($start_time + $duration));
	
	$subject = ($subject != '') ? $subject : 'Reminder for event';
	$description = ($description != '') ? $description : 'Reminder for event';
	
	$location = "Los Angeles";
//	$location = 'San Diego';
	$parse_domain = parse_url(BASE_URL);
	$domain = isset($parse_domain['host']) ? $parse_domain['host'] : BASE_URL;
	
	//Create Email Headers
	$mime_boundary = "----Meeting Booking----".MD5(TIME());
	
	$headers = "From: ".$from_name." <".$from_address.">\n";
	$headers .= "Reply-To: ".$from_name." <".$from_address.">\n";
	$headers .= "MIME-Version: 1.0\n";
	$headers .= "Content-Type: multipart/alternative; boundary=\"$mime_boundary\"\n";
	$headers .= "Content-class: urn:content-classes:calendarmessage\n";
	
	//Create Email Body (HTML)
	$message = "--$mime_boundary\r\n";
	$message .= "Content-Type: text/html; charset=UTF-8\n";
	$message .= "Content-Transfer-Encoding: 8bit\n\n";
	$message .= "<html>\n";
	$message .= "<body>\n";
	
	$message .= $description;
	
	$message .= "</body>\n";
	$message .= "</html>\n";
	$message .= "--$mime_boundary\r\n";
	
	//Event setting
	$ical = 'BEGIN:VCALENDAR' . "\r\n" .
	'PRODID:-//Microsoft Corporation//Outlook 10.0 MIMEDIR//EN' . "\r\n" .
	'VERSION:2.0' . "\r\n" .
	'METHOD:REQUEST' . "\r\n" .
	'BEGIN:VTIMEZONE' . "\r\n" .
	'TZID:Eastern Time' . "\r\n" .
	'BEGIN:STANDARD' . "\r\n" .
	'DTSTART:20091101T020000' . "\r\n" .
	'RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=1SU;BYMONTH=11' . "\r\n" .
	'TZOFFSETFROM:-0400' . "\r\n" .
	'TZOFFSETTO:-0500' . "\r\n" .
	'TZNAME:EST' . "\r\n" .
	'END:STANDARD' . "\r\n" .
	'BEGIN:DAYLIGHT' . "\r\n" .
	'DTSTART:20090301T020000' . "\r\n" .
	'RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=2SU;BYMONTH=3' . "\r\n" .
	'TZOFFSETFROM:-0500' . "\r\n" .
	'TZOFFSETTO:-0400' . "\r\n" .
	'TZNAME:EDST' . "\r\n" .
	'END:DAYLIGHT' . "\r\n" .
	'END:VTIMEZONE' . "\r\n" .	
	'BEGIN:VEVENT' . "\r\n" .
	'ORGANIZER;CN="'.$from_name.'":MAILTO:'.$from_address. "\r\n" .
	'ATTENDEE;CN="'.$to_name.'";ROLE=REQ-PARTICIPANT;RSVP=TRUE:MAILTO:'.$to_address. "\r\n" .
	'LAST-MODIFIED:' . date("Ymd\TGis") . "\r\n" .
	'UID:'.date("Ymd\TGis", strtotime($startTime)).rand()."@".$domain."\r\n" .
	'DTSTAMP:'.date("Ymd\TGis"). "\r\n" .
	'DTSTART;TZID=Pacific Standard Time:'.date("Ymd\THis", strtotime($startTime)). "\r\n" .
	'DTEND;TZID=Pacific Standard Time:'.date("Ymd\THis", strtotime($endTime)). "\r\n" .
	'TRANSP:OPAQUE'. "\r\n" .
	'SEQUENCE:1'. "\r\n" .
	'SUMMARY:' . $subject . "\r\n" .
	'LOCATION:' . $location . "\r\n" .
	'CLASS:PUBLIC'. "\r\n" .
	'PRIORITY:5'. "\r\n" .
	'BEGIN:VALARM' . "\r\n" .
	'TRIGGER:-PT15M' . "\r\n" .
	'ACTION:DISPLAY' . "\r\n" .
	'DESCRIPTION:Reminder' . "\r\n" .
	'END:VALARM' . "\r\n" .
	'END:VEVENT'. "\r\n" .
	'END:VCALENDAR'. "\r\n";
	$message .= 'Content-Type: text/calendar;name="meeting.ics";method=REQUEST'."\n";
	$message .= "Content-Transfer-Encoding: 8bit\n\n";
	$message .= $ical;
	
	if ($to_address != '')
		mail($to_address, $subject, $message, $headers);
}




