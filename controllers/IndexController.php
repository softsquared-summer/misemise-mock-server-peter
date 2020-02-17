<?php
require 'function.php';

const JWT_SECRET_KEY = "TEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEY";

$res = (Object)Array();
header('Content-Type: json');
$req = json_decode(file_get_contents("php://input"));
try {
    addAccessLogs($accessLogs, $req);
    switch ($handler) {
        case "index":
            echo "misemise API Server";
            break;
        case "ACCESS_LOGS":
            //            header('content-type text/html charset=utf-8');
            header('Content-Type: text/html; charset=UTF-8');
            getLogs("./logs/access.log");
            break;
        case "ERROR_LOGS":
            //            header('content-type text/html charset=utf-8');
            header('Content-Type: text/html; charset=UTF-8');
            getLogs("./logs/errors.log");
            break;
        /* ******************   MiseMise   ****************** */
        /*
                 * API No. 1
                 * API Name : 즐겨찾기 하기 위해서 읍/면/동 검색 API
                 * 마지막 수정 날짜 : 20.02.17
        */
        case "location_search":
            http_response_code(200);
            $tmp = (Object)Array();
            $location = $_GET["location"];

            $tmp->result = location_search($location);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "테스트 성공";

            $json_result = json_decode($tmp->result);

            $documents = $json_result->documents;
            $len = count($documents);
            echo $len;

            for($i=0; $i<$len; $i++){
                $res->result[$i]["no"] = ($i+1);
                if(($json_result->documents[$i]->address) != null){
                    $to_region_2depth = $json_result->documents[$i]->address->region_1depth_name . " " . $json_result->documents[$i]->address->region_2depth_name;
                    if(($json_result->documents[$i]->address->region_3depth_name) != ""){
                        $res->result[$i]["address_name"] = $to_region_2depth. " " . $json_result->documents[$i]->address->region_3depth_name;
                    } else{
                        $res->result[$i]["address_name"] = $to_region_2depth. " " . $json_result->documents[$i]->address->region_3depth_h_name;
                    }
                } else {
                    $res->result[$i]["address_name"] = $json_result->documents[$i]->road_address->region_1depth_name. " " . $json_result->documents[$i]->road_address->region_2depth_name . " " . $json_result->documents[$i]->road_address->region_3depth_name;
                }
            }

            echo json_encode($res, JSON_NUMERIC_CHECK| JSON_UNESCAPED_UNICODE);
            break;

    }
} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}
