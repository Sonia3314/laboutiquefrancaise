<?php

namespace App\Classe;

use Mailjet\Client;
use Mailjet\Resources;

class Mail{

    private $api_key ='416a86e23f3180cee3bf727ecd1bd9a6';
    private $api_key_secret ='3a13f8fd19df765e5ae3b264040835eb'; 

    public function send($to_email, $to_name, $subject, $content){

        $mj = new Client($this->api_key, $this->api_key_secret, true, ['version' => 'v3.1']);

        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => "b14.sonia@gmail.com",
                        'Name' => "La Boutique Française"
                    ],
                    'To' => [
                        [
                            'Email' => $to_email,
                            'Name' => $to_name
                        ]
                    ],
                    'TemplateID' => 2412443,
                    'TemplateLanguage' => true,
                    'Subject' => $subject,
                    "Variables"=> [
                        'content' => $content,
                    ]
                ]
            ]
        ];
                
        $response = $mj->post(Resources::$Email, ['body' => $body]);
        $response->success();
            }
    }
