<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../config/Shortener.php';
require __DIR__ . '/../config/InputValidator.php';


// $app = AppFactory::create();

$app->post('/create-short-url', function (Request $request, Response $response) {

 
            $key = "tHeApAcHe6410111";

            $request_data = $request->getParsedBody();

            $e_point_code = $request_data['e_point_code'];
            $e_point_id = $request_data['e_point_id'];
            $unique_reference = $request_data['unique_reference'];
            $stage_id = $request_data['stage_id'];
            $channel_id = $request_data['channel_id'];
            $keep_time = $request_data['keep_time'];
            $cus_mobile = $request_data['cus_mobile'];


            try {

                $db = new DB($_ENV['DB_HOST'],$_ENV['DB_USERNAME'],$_ENV['DB_PASSWORD'],$_ENV['DB_NAME']);
                $con = $db->connect(); 
            
                $shortener = new Shortener($con);
                $validator = new InputValidator($con);
    
                $con = null;

                
                if (!$validator->validteEngagementPoint($e_point_id,$stage_id ,$e_point_code)){
                    throw new Exception("Invalid Engagement Point Details.");
                }
                if (!$validator->validteChannel($channel_id)){
                    throw new Exception("Invalid Channel Details.");
                }
                if ($validator->validteRef($unique_reference,$e_point_id)){
                    throw new Exception("Duplicate Reference Id.");
                }
                if (!$validator->validteEngagementPointStatus($e_point_id)){
                    throw new Exception("Disable Engagement Point.");
                }
                

                $url_end_part = $e_point_code.$unique_reference."/".$stage_id."/".$e_point_id."/".$channel_id;
                $enc_url_end_part = str_replace(['+','/','='], ['-','_',''],base64_encode(openssl_encrypt($url_end_part, "aes-128-ecb", $key, OPENSSL_RAW_DATA)));

                $longURL = $_ENV['FEEDBACK_APP_URL'].'customer/'.$enc_url_end_part;
                
                // Prefix of the short URL 
                $shortURL_Prefix = $_ENV['FEEDBACK_APP_URL'].'shortener/'; // with URL rewrite
                // $shortURL_Prefix = 'http://localhost/Dev_Apps/feedback/customer/?c='; // without URL rewrite
                
                
                    // Get short code of the URL
                    $shortCode = $shortener->urlToShortCode($longURL, $keep_time, $e_point_id,$e_point_code,$channel_id,$stage_id,$unique_reference,$cus_mobile);
                    
                    // Create short URL
                    $shortURL = $shortURL_Prefix.$shortCode;

                    $response_data['error'] = false;
                    $response_data['data'] = $shortURL;
                    $response_data['message'] = "";
                  
    
            } catch (Exception $err) {
    
                $response_data['error'] = true;
                $response_data['data'] = "";
                $response_data['message'] = $err->getMessage();
    
            }
    
    
        $response->getBody()->write(json_encode($response_data));
    
        return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(200);
    
    });

    function decrypt($data, $key) {
        return openssl_decrypt(base64_decode(str_replace(['-','_'], ['+','/'],$data)), "aes-128-ecb", $key, OPENSSL_RAW_DATA);
    }
    