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


        /*
                 * API No. 4
                 * API Name : 즐겨찾기 해당 번호의 측정 값 조회 API
                 * 마지막 수정 날짜 : 20.02.19
        */
        case "dustValue":
            if($_GET["favoriteNo"]) {   //  즐겨찾기 일 경우
                $favoriteNo = $_GET["favoriteNo"];
                $tmp->result = getXY($favoriteNo);  // favoriteNo 를 통해 x, y 좌표 얻어옴
                $favorite_encode = json_encode($tmp->result);
                $favorite_decode = json_decode($favorite_encode);
                $tm_x = $favorite_decode->tm_x;
                $tm_y = $favorite_decode->tm_y;

                $tmp->result = transFormation($tm_x, $tm_y);
                $json_result = json_decode($tmp->result);

                $x = $json_result->documents[0]->x;
                $y = $json_result->documents[0]->y;

                $tmp->result = findNearStation($x, $y); //  가까운 측정소 검색
                $station_result = json_decode($tmp->result);

                $stationName = $station_result->list[0]->stationName;   //  처음 시작은 가장 가까운 측정소로 시작

                $tmp->result = fineDust($stationName);
                $json_result = json_decode($tmp->result);

                $res->result->total_value = StationValue($json_result, $res->result, $station_result, khaiValue);
                $res->result->pm10_value = StationValue($json_result, $res->result, $station_result, pm10Value);
                $res->result->pm25_value = StationValue($json_result, $res->result, $station_result, pm25Value);
                $res->result->no2_value = StationValue($json_result, $res->result, $station_result, no2Value);
                $res->result->o3_value = StationValue($json_result, $res->result, $station_result, o3Value);
                $res->result->co_value = StationValue($json_result, $res->result, $station_result, coValue);
                $res->result->so2_value = StationValue($json_result, $res->result, $station_result, so2Value);


                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "상세 미세먼지 조회 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
                break;
            } else {    //  GPS의 x, y 값을 받을 경우
                $tm_x = $_GET["x"];
                $tm_y = $_GET["y"];

                $tmp->result = transFormation($tm_x, $tm_y);
                $json_result = json_decode($tmp->result);

                $x = $json_result->documents[0]->x;
                $y = $json_result->documents[0]->y;

                $tmp->result = findNearStation($x, $y);
                $station_result = json_decode($tmp->result);

                $stationName = $station_result->list[0]->stationName;

                $tmp->result = fineDust($stationName);
                $json_result = json_decode($tmp->result);

                $res->result->total_value = StationValue($json_result, $res->result, $station_result, khaiValue);
                $res->result->pm10_value = StationValue($json_result, $res->result, $station_result, pm10Value);
                $res->result->pm25_value = StationValue($json_result, $res->result, $station_result, pm25Value);
                $res->result->no2_value = StationValue($json_result, $res->result, $station_result, no2Value);
                $res->result->o3_value = StationValue($json_result, $res->result, $station_result, o3Value);
                $res->result->co_value = StationValue($json_result, $res->result, $station_result, coValue);
                $res->result->so2_value = StationValue($json_result, $res->result, $station_result, so2Value);


                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "상세 미세먼지 조회 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
                break;
            }

        /*
                 * API No. 5
                 * API Name : 즐겨찾기 해당 번호의 측정 등급 조회 API
                 * 마지막 수정 날짜 : 20.02.19
        */
        case "dustGrade":
            if($_GET["favoriteNo"]) {   //  즐겨찾기 일 경우
                $favoriteNo = $_GET["favoriteNo"];
                $tmp->result = getXY($favoriteNo);  // favoriteNo 를 통해 x, y 좌표 얻어옴
                $favorite_encode = json_encode($tmp->result);
                $favorite_decode = json_decode($favorite_encode);
                $tm_x = $favorite_decode->tm_x;
                $tm_y = $favorite_decode->tm_y;

                $tmp->result = transFormation($tm_x, $tm_y);
                $json_result = json_decode($tmp->result);

                $x = $json_result->documents[0]->x;
                $y = $json_result->documents[0]->y;

                $tmp->result = findNearStation($x, $y); //  가까운 측정소 검색
                $station_result = json_decode($tmp->result);

                $stationName = $station_result->list[0]->stationName;   //  처음 시작은 가장 가까운 측정소로 시작

                $tmp->result = fineDust($stationName);
                $json_result = json_decode($tmp->result);

                $res->result->total_grade = StationGrade($json_result, $res->result, $station_result, khaiGrade);
                $res->result->pm10_grade = StationGrade($json_result, $res->result, $station_result, pm10Grade1h);
                $res->result->pm25_grade = StationGrade($json_result, $res->result, $station_result, pm25Grade1h);
                $res->result->no2_grade = StationGrade($json_result, $res->result, $station_result, no2Grade);
                $res->result->o3_grade = StationGrade($json_result, $res->result, $station_result, o3Grade);
                $res->result->co_grade = StationGrade($json_result, $res->result, $station_result, coGrade);
                $res->result->so2_grade = StationGrade($json_result, $res->result, $station_result, so2Grade);

                if (($res->result->pm10_grade) < ($res->result->pm25_grade)) {    //  미세먼지와 초미세먼지 등급 중에 큰 것이 선택
                    $res->result->current_status_grade = $res->result->pm25_grade;  //  grade 수가 적은 것이 공기 상태가 더 좋은 것
                } else {
                    $res->result->current_status_grade = $res->result->pm10_grade;
                }

                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "상세 미세먼지 조회 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
                break;
            } else {    //  GPS의 x, y 값을 받을 경우
                $tm_x = $_GET["x"];
                $tm_y = $_GET["y"];

                $tmp->result = transFormation($tm_x, $tm_y);
                $json_result = json_decode($tmp->result);

                $x = $json_result->documents[0]->x;
                $y = $json_result->documents[0]->y;


                $tmp->result = findNearStation($x, $y);
                $station_result = json_decode($tmp->result);

                $stationName = $station_result->list[0]->stationName;

                $tmp->result = fineDust($stationName);
                $json_result = json_decode($tmp->result);


                $res->result->total_grade = StationGrade($json_result, $res->result, $station_result, khaiGrade);
                $res->result->pm10_grade = StationGrade($json_result, $res->result, $station_result, pm10Grade1h);
                $res->result->pm25_grade = StationGrade($json_result, $res->result, $station_result, pm25Grade1h);
                $res->result->no2_grade = StationGrade($json_result, $res->result, $station_result, no2Grade);
                $res->result->o3_grade = StationGrade($json_result, $res->result, $station_result, o3Grade);
                $res->result->co_grade = StationGrade($json_result, $res->result, $station_result, coGrade);
                $res->result->so2_grade = StationGrade($json_result, $res->result, $station_result, so2Grade);

                if(($res->result->pm10_grade) < ($res->result->pm25_grade)){    //  미세먼지와 초미세먼지 등급 중에 큰 것이 선택
                    $res->result->current_status_grade = $res->result->pm25_grade;  //  grade 수가 적은 것이 공기 상태가 더 좋은 것
                } else {
                    $res->result->current_status_grade = $res->result->pm10_grade;
                }

                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "상세 미세먼지 조회 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
                break;
            }

        /*
                 * API No. 6
                 * API Name : 즐겨찾기 해당 번호의 기타 사항 조회 API
                 * 마지막 수정 날짜 : 20.02.19
        */
        case "dustEtc":
            if($_GET["favoriteNo"]) {   //  즐겨찾기 일 경우
                $favoriteNo = $_GET["favoriteNo"];
                $tmp->result = getXY($favoriteNo);  // favoriteNo 를 통해 x, y 좌표 얻어옴
                $favorite_encode = json_encode($tmp->result);
                $favorite_decode = json_decode($favorite_encode);
                $tm_x = $favorite_decode->tm_x;
                $tm_y = $favorite_decode->tm_y;

                $tmp->result = transFormation($tm_x, $tm_y);
                $json_result = json_decode($tmp->result);

                $x = $json_result->documents[0]->x;
                $y = $json_result->documents[0]->y;

                $tmp->result = findNearStation($x, $y); //  가까운 측정소 검색
                $station_result = json_decode($tmp->result);

                $stationName = $station_result->list[0]->stationName;   //  처음 시작은 가장 가까운 측정소로 시작

                $tmp->result = fineDust($stationName);
                $json_result = json_decode($tmp->result);

                $res->result->region_2depth_name = $favorite_decode->region_2depth_name;
                $res->result->region_3depth_name = $favorite_decode->region_3depth_name;

                $now = time();
                $five_minutes = 60 * 5;
                $offset = $now % $five_minutes;
                $five_block = $now - $offset;
                $res->result->current_time = date("Y-m-d H:i", $five_block); //  현재시간, 5분마다 최신화

                $res->result->update_time = $json_result->list[0]->dataTime;

                $res->result->pm10_station = StationName($json_result, $res->result, $station_result, pm10Value);
                $res->result->pm25_station = StationName($json_result, $res->result, $station_result, pm25Value);
                $res->result->no2_station = StationName($json_result, $res->result, $station_result, no2Value);
                $res->result->o3_station = StationName($json_result, $res->result, $station_result, o3Value);
                $res->result->co_station = StationName($json_result, $res->result, $station_result, coValue);
                $res->result->so2_station = StationName($json_result, $res->result, $station_result, so2Value);

                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "상세 미세먼지 조회 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
                break;
            } else {    //  GPS의 x, y 값을 받을 경우
                $tm_x = $_GET["x"];
                $tm_y = $_GET["y"];

                $tmp->result = transFormation($tm_x, $tm_y);
                $json_result = json_decode($tmp->result);

                $x = $json_result->documents[0]->x;
                $y = $json_result->documents[0]->y;

                $tmp->result = FindLocation($tm_x, $tm_y);
                $location_result = json_decode($tmp->result);

                $tmp->result = findNearStation($x, $y);
                $station_result = json_decode($tmp->result);

                $stationName = $station_result->list[0]->stationName;

                $tmp->result = fineDust($stationName);
                $json_result = json_decode($tmp->result);

                $res->result->region_2depth_name = $location_result->documents[0]->region_2depth_name;
                $res->result->region_3depth_name = $location_result->documents[0]->region_3depth_name;

                $now = time();
                $five_minutes = 60*5;
                $offset = $now % $five_minutes;
                $five_block = $now - $offset;
                $res->result->current_time = date("Y-m-d H:i", $five_block); //  현재시간, 5분마다 최신화

                $res->result->update_time = $json_result->list[0]->dataTime;

                $res->result->pm10_station = StationName($json_result, $res->result, $station_result, pm10Value);
                $res->result->pm25_station = StationName($json_result, $res->result, $station_result, pm25Value);
                $res->result->no2_station = StationName($json_result, $res->result, $station_result, no2Value);
                $res->result->o3_station = StationName($json_result, $res->result, $station_result, o3Value);
                $res->result->co_station = StationName($json_result, $res->result, $station_result, coValue);
                $res->result->so2_station = StationName($json_result, $res->result, $station_result, so2Value);

                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "상세 미세먼지 조회 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
                break;
            }


    }



} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}
