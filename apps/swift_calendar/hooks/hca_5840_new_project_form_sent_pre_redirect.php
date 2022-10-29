<?php if (!defined('DB_CONFIG')) die(); 

$time_now = time();
$shift = 28800;//+ 8 hours
$property_name = $form_data['property_name'] != '' ? $form_data['property_name'] : 'N/A';
$unit_number = $form_data['unit_number'] != '' ? $form_data['unit_number'] : 'N/A';
$location = $form_data['location'] != '' ? $form_data['location'] : 'N/A';
$mois_source = $form_data['mois_source'] != '' ? $form_data['mois_source'] : 'N/A';
$symptoms = $form_data['symptoms'] != '' ? $form_data['symptoms'] : 'N/A';
$event_text = 'Property: '.$property_name."\n".', unit#: '.$unit_number.', location: '.$location.'. Source: '.$form_data['mois_source'].'. Symptoms: '.$form_data['symptoms'].'. ';

if ($form_data['mois_inspection_date'] > $time_now)
{
	$mois_inspection_date = $form_data['mois_inspection_date'] + $shift;
	$event_subject = 'Moisture Inspection Date';
	
	if ($User->get('sm_calendar_outlook_email') != '')
		sm_calendar_outlook_create_event($mois_inspection_date, $event_subject, $event_text);
}

if ($form_data['asb_test_date'] > $time_now)
{
	$asb_test_date = $form_data['asb_test_date'] + $shift;
	$event_subject = 'Asbestos Test Date';
	$event_text .= 'Vendor: '.($form_data['asb_vendor'] != '' ? $form_data['asb_vendor'] : 'N/A');
	
	if ($User->get('sm_calendar_outlook_email') != '')
		sm_calendar_outlook_create_event($asb_test_date, $event_subject, $event_text);
}

if ($form_data['rem_start_date'] > $time_now)
{
	$rem_start_date = $form_data['rem_start_date'] + $shift;
	$event_subject = 'Remediation Start Date';
	$event_text .= 'Vendor: '.($form_data['rem_vendor'] != '' ? $form_data['rem_vendor'] : 'N/A');
	
	if ($User->get('sm_calendar_outlook_email') != '')
		sm_calendar_outlook_create_event($rem_start_date, $event_subject, $event_text);
}

if ($form_data['cons_start_date'] > $time_now)
{
	$cons_start_date = $form_data['cons_start_date'] + $shift;
	$event_subject = 'Construction Start Date';
	$event_text .= 'Vendor: '.($form_data['cons_vendor'] != '' ? $form_data['cons_vendor'] : 'N/A');
	
	if ($User->get('sm_calendar_outlook_email') != '')
		sm_calendar_outlook_create_event($cons_start_date, $event_subject, $event_text);
}

if ($form_data['rem_end_date'] > $time_now)
{
	$rem_end_date = $form_data['rem_end_date'] + $shift;
	$event_subject = 'Remediation End Date';
	$event_text .= 'Vendor: '.($form_data['rem_vendor'] != '' ? $form_data['rem_vendor'] : 'N/A');
	
	if ($User->get('sm_calendar_outlook_email') != '')
		sm_calendar_outlook_create_event($rem_end_date, $event_subject, $event_text);
}

if ($form_data['cons_end_date'] > $time_now)
{
	$cons_end_date = $form_data['cons_end_date'] + $shift;
	$event_subject = 'Construction End Date';
	$event_text .= 'Vendor: '.($form_data['cons_vendor'] != '' ? $form_data['cons_vendor'] : 'N/A');
	
	if ($User->get('sm_calendar_outlook_email') != '')
		sm_calendar_outlook_create_event($cons_end_date, $event_subject, $event_text);
}

