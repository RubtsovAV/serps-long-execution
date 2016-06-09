Anti-captcha.com
============

This code show how you can use the captcha solver with the Google client.

Before you can use this example you need:

1.  Registration on https://anti-captcha.com/
2.  Copy the api key from here https://anti-captcha.com/panel/settings/account
3.  Copy the PHP code from here https://anti-captcha.com/code/base64.txt

* I recommend that you to create IP-whitelist here https://anti-captcha.com/panel/settings/security

```php
$serpsConfig = [
    'client' => [
        'Google' => [

            /**
             * callable  
             *   Required option for captcha solver.
             *   Called when the search engine need solve the CAPTCHA.
             *   In first argument the image data string.
             *   Must return the answer string.
             */
            'captchaSolver' => function ($imageData) {
                // It's your api key from here https://anti-captcha.com/panel/settings/account
                $anticaptchaKey = 'PASTE_HERE_YOUR_ANTICAPTCHA_KEY';
            
                $imageFile = tmpfile();
                fwrite($imageFile, $imageData);
                $imageFileMeta = stream_get_meta_data($imageFile);
                $imageFileName = $imageFileMeta['uri'];
                
                
                $captchaAnswer = recognize($imageFileName, $anticaptchaKey, false);
                
                fclose($imageFile);
                return (string) $captchaAnswer;
            }
        ]
    ]
];

```
