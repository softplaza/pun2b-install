<?php

class SwiftMessenger
{
    function send($text, $phone_number)
    {
        global $Config;


        $sid = $Config->get('o_swift_messenger_sid');
        $token = $Config->get('o_swift_messenger_token');
        $client = new Twilio\Rest\Client($sid, $token);

		// Use the client to do fun stuff like send text messages!
		$client->messages->create(
			// the number you'd like to send the message to
			'+'.$phone_number,
			[
				// A Twilio phone number you purchased at twilio.com/console
				'from' => '+'.$Config->get('o_swift_messenger_number'),
				// the body of the text message you'd like to send
				'body' => $text
			]
		);
    }
}
