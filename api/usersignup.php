<?php

require __DIR__ . '/../vendor/autoload.php';
require_once('../initialize.php');
import('../form.Form');
import('../ttOrgHelper');
import('../ttUser');

//header section for post request

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//check if the method is post

if (strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') {
  throw new Exception('Only POST requests are allowed');
}
//check if content type is json
$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (stripos($content_type, 'application/json') === false) {                                                                                                                                                          throw new Exception('Content-Type must be application/json');                                                                                                                                                    }

//insert values inside variables

$body = file_get_contents("php://input");
$object = json_decode($body);

$cl_manager_name = $object->manager_name;
$cl_manager_login = $object->manager_login;
$cl_password1 = $object->password1;
$cl_password2 = $object->password2;
$cl_manager_email = $object->manager_email;
$cl_group_name = $object->group_name;
$cl_currency = $object->currency;
$cl_lang = $object->language;
try {
  $fields = array(

    'user_name' => $cl_manager_name,
    'login' => $cl_manager_login,
    'password1' => $cl_password1,
    'password2' => $cl_password2,
    'email' => $cl_manager_email,
    'group_name' => $cl_group_name,
    'currency' => $cl_currency,
    'lang' => $cl_lang);

  //insert data into the database
  import('ttRegistrator');
  $registrator = new ttRegistrator($fields, $err);
  $registrator->register();

//check for error
  if ($err->no()) {
    $success_response = ['success' => true, 'user' => $cl_manager_name];
    $response = json_encode($success_response);
    print_r($response);
    exit();
  }
  else
  {
    $success_response = ['success' => false, 'data' => $err];
    $response = json_encode($success_response);
    print_r($response);
    exit();
  }
} catch (Exception $e) {                                                                                                                                                                                             $isLoggedIn = false;
  http_response_code(401);
  print_r(json_encode(array(
    "message" => "Access denied.",
    "error" => $e->getMessage()
  )));

}
