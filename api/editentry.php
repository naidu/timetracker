<?php

require __DIR__ . '/../vendor/autoload.php';
require_once('../initialize.php');
import('../form.Form');
import('../ttOrgHelper');
import('../ttUser');
import('../ttTimeHelper');

use \Firebase\JWT\JWT;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
//check if its post request
if (strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') {
  throw new Exception('Only POST requests are allowed');
}
//check if format is json
$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (stripos($content_type, 'application/json') === false) {
  throw new Exception('Content-Type must be application/json');
}

$secret_key = SECRET_KEY;

$body = file_get_contents("php://input");
$object = json_decode($body);
$jwt = $object->jwt;
$id = $object->id;
$date = $object->date;
$user_id = $object->user_id;
$client = $object->client;
$project = $object->project;
$task = $object->task;
$start = $object->start;
$finish =$object->finish;
$duration = $object->duration;

$isLoggedIn = false;
//check fot token verification
if ($jwt) {
  try {
    error_log($jwt);
    $decoded = JWT::decode($jwt, $secret_key, array('HS256'));
    $isLoggedIn = true;
    $userId = $decoded->data->id;
    $user = new ttUser(null, $userId);
    if ($isLoggedIn) {
      
      if($duration){
        if($date==""){
          $success_response = ['success' => false, 'error' => 'enter the date' ];
          $response = json_encode($success_response);
          print_r($response);
        }
        else{
          if ($start)
          {
            $success_response = ['success' => false, 'error' => 'remove start time while duration is given' ];
            $response = json_encode($success_response);
            print_r($response);
          }
          else{
            $record=ttTimeHelper::updateEntry(array(
              'id' => $id,
              'date' => $date,
              'user_id' => $user_id,
              'client' => $client,
              'project' => $project,
              'task' => $task,
              'start' => $start,
              'finish' => $finish,
              'duration' => $duration
            ));
          }
          
        }
      }
      else{

        if($start==""||$date==""){
          $success_response = ['success' => false, 'error' => 'date or start time not entered' ];
          $response = json_encode($success_response);
          print_r($response);
        }
        else{
          $record=ttTimeHelper::update(array(
            'id' => $id,
            'date' => $date,
            'user_id' => $user_id,
            'client' => $client,
            'project' => $project,
            'task' => $task,
            'start' => $start,
            'finish' => $finish,
            'duration' => $duration
          ));
        }
      }
      if($record){
        $success_response = ['success' => $record, 'update done' => 'true' ];
        $response = json_encode($success_response);
        print_r($response);
      }
      else
      {
        $success_response = ['success' => false, 'error' => 'wrong values entered' ];
        $response = json_encode($success_response);
        print_r($response);
      }
    }
  } catch (Exception $e) {
    $isLoggedIn = false;
    http_response_code(401);
    print_r(json_encode(array(
      "message" => "Access denied.",
      "error" => $e->getMessage()
    )));
  }
}
exit();
