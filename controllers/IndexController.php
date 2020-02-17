<?php
require 'function.php';

const JWT_SECRET_KEY = "TEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEY";

$res = (Object)Array();
$tmp = (Object)Array();
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
        case "locationSearch":
            http_response_code(200);

            $location = $_GET["location"];

            if(!empty($location)){  //  검색어를 입력한 경우
                $tmp->result = locationSearch($location);
                $json_result = json_decode($tmp->result);

                $documents = $json_result->documents;
                $len = count($documents);

                if($len != 0) { //  검색 결과 값이 존재할때
                    for ($i = 0; $i < $len; $i++) {
                        $res->result[$i]["no"] = ($i + 1);
                        if (($json_result->documents[$i]->address) != null) {
                            $res->result[$i]["region_1depth_name"] = $json_result->documents[$i]->address->region_1depth_name;
                            $res->result[$i]["region_2depth_name"] = $json_result->documents[$i]->address->region_2depth_name;
                            $to_region_2depth = $json_result->documents[$i]->address->region_1depth_name . " " . $json_result->documents[$i]->address->region_2depth_name;
                            if (($json_result->documents[$i]->address->region_3depth_name) != "") {
                                $res->result[$i]["region_3depth_name"] = $json_result->documents[$i]->address->region_3depth_name;
                                $res->result[$i]["address_name"] = $to_region_2depth . " " . $json_result->documents[$i]->address->region_3depth_name;
                            } else {
                                $res->result[$i]["region_3depth_name"] = $json_result->documents[$i]->address->region_3depth_h_name;
                                $res->result[$i]["address_name"] = $to_region_2depth . " " . $json_result->documents[$i]->address->region_3depth_h_name;
                            }
                        } else {
                            $res->result[$i]["region_1depth_name"] = $json_result->documents[$i]->road_address->region_1depth_name;
                            $res->result[$i]["region_2depth_name"] = $json_result->documents[$i]->road_address->region_2depth_name;
                            $res->result[$i]["region_3depth_name"] = $json_result->documents[$i]->road_address->region_3depth_name;
                            $res->result[$i]["address_name"] = $json_result->documents[$i]->road_address->region_1depth_name . " " . $json_result->documents[$i]->road_address->region_2depth_name . " " . $json_result->documents[$i]->road_address->region_3depth_name;
                        }
                        $res->result[$i]["tm_x"] = $json_result->documents[$i]->x;
                        $res->result[$i]["tm_y"] = $json_result->documents[$i]->y;
                    }
                    $res->isSuccess = TRUE;
                    $res->code = 100;
                    $res->message = "읍/면/동 주소 검색 성공";
                } else {    //  검색 결과 값이 존재하지 않을 때
                    $res->isSuccess = TRUE;
                    $res->code = 101;
                    $res->message = "검색된 결과가 없습니다. 검색어를 다시 입력해주세요.";
                }
            } else {    //  검색어를 입력하지 않은 경우
                $res->isSuccess = False;
                $res->code = 200;
                $res->message = "검색어를 입력하지 않았습니다. 검색어를 입력해주세요.";
            }

            echo json_encode($res, JSON_NUMERIC_CHECK| JSON_UNESCAPED_UNICODE);
            break;

        /*
                 * API No. 2
                 * API Name : 즐겨찾기 추가 API
                 * 마지막 수정 날짜 : 20.02.18
        */
        case "favoritePost":
            $cnt = favoriteCnt();
            if($cnt < 6){   //  즐겨찾기 수 최대 6개
                $res->result = favoritePost($req->region_2depth_name, $req->region_3depth_name, $req->tm_x, $req->tm_y);
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "즐겨찾기에 추가 성공";
            } else {
                $res->isSuccess = FALSE;
                $res->code = 200;
                $res->message = "즐겨찾기에 추가 실패";
            }
            echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
            break;

        /*
                 * API No. 3
                 * API Name : 즐겨찾기 삭제 API
                 * 마지막 수정 날짜 : 20.02.18
        */
        case "favoriteDelete":
            $favoriteNo = $_GET["favoriteNo"];
            $tmp->result = favoriteDelete($favoriteNo);
            if($tmp->result != false){
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "즐겨찾기 삭제 성공";
            } else {    //  존재하지 않는 즐겨찾기 숫자 입력시 실패
                $res->isSuccess = FALSE;
                $res->code = 200;
                $res->message = "즐겨찾기 삭제 실패";
            }
            echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
            break;

    }

} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}
