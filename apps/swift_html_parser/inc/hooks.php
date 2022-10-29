<?php

if (!defined('DB_CONFIG')) die();

function swift_html_parser_IncludeCommon()
{
    global $Core, $User, $SwiftMailer;

    if ($User->is_guest())
    {
        $url = 'https://www.apple.com/shop/refurbished/iphone';
        $contents = file_get_contents($url);

        //$words = preg_split("/[\s,]+/", strip_tags($contents));
        //print_dump($words);
    
        if (preg_match("/iPhone 12 Pro/i", $contents))
        {
            $mail_message = [];
            $mail_message[] = 'iPhone 11 Pro found for you!';
            $mail_message[] = 'To view the deal follow this link: https://www.apple.com/shop/refurbished/iphone';
    
            $SwiftMailer = new SwiftMailer;
            $SwiftMailer->send('dvdidenko@gmail.com', 'iPhone 12 found', implode("\n\n", $mail_message));

            //$Core->add_warning('We found iPhone 12 Pro for you! <a href="https://www.apple.com/shop/refurbished/iphone" target="_blank">Go website</a>');
        }
    }
}

