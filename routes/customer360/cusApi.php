<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

//load db operation file
require __DIR__ . '/cusApiDbOps.php';

//api to get feedback when pass reference id as input
$app->post('/get-feedback', function (Request $request, Response $response) {

    //when all parameters are passed
    if(!haveEmptyParameters(array('reference_id'), $request, $response)){

        //get request data from body
        $request_data = $request->getParsedBody();

        //get reference_id
        $reference_id = $request_data['reference_id'];

        try {

            //create object for DB operation class
            $db = new cusApiDbOps; 
            //call the DB opearation function
            $data = $db->getFeedbackFromReferenceId($reference_id);

            //create an empty array
            $response_data = array();

            //set response data when success
            if($data){//when records exits
                $response_data['error'] = false;
                $response_data['data'] = $data;
                $response_data['message'] = "";
            }else{//when records does not exits
                $response_data['error'] = true;
                $response_data['data'] = "";
                $response_data['message'] = "Not found data for ".$reference_id;
            }
            
              

        } catch (Exception $err) {

            //set response data when error occured
            $response_data['error'] = true;
            $response_data['data'] = "";
            $response_data['message'] = $err->getMessage();

        }

        //set encoded response data array to response body
        $response->getBody()->write(json_encode($response_data));

        //return response
        return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(200);

    }

    //return response when missing parameters[response body pass from 'haveEmptyParameters' function]
    return $response
    ->withHeader('Content-type', 'application/json')
    ->withStatus(200);

});

//check for empty parameters
function haveEmptyParameters($required_params, $request, $response){
    $error = false; 
    $error_params = '';
    $request_params = $request->getParsedBody(); 

    //check avalability for every params
    foreach($required_params as $param){
        if(!isset($request_params[$param]) || strlen($request_params[$param])<=0){
            $error = true; 
            $error_params .= $param . ', ';
        }
    }

    //when error is true return response with error message
    if($error){
        $error_detail = array();
        $error_detail['error'] = true; 
        $error_detail['message'] = 'Required parameters ' . substr($error_params, 0, -2) . ' are missing or empty';
        //set error for response body
        $response->write(json_encode($error_detail));
    }

    //return error [when error is not occured]
    return $error; 
}

    
    