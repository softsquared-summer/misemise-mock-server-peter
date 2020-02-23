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
                 * 마지막 수정 날짜 : 20.02.23
        */
        case "locationSearch":
            http_response_code(200);

            $location = $_GET["location"];
            $pageNo = $_GET["pageNo"];

            if(!empty($location)){  //  검색어를 입력한 경우
                $tmp->result = locationSearch($location);
                $json_result = json_decode($tmp->result);
                $total_page = $json_result->meta->total_count;
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
                    $res->totalPageNo = ceil($total_page/30);
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
                 * API Name : 즐겨찾기 해당 동네 이름 or GPS의 x, y 기준으로 측정 값 조회 API
                 * 마지막 수정 날짜 : 20.02.20
        */
        case "dustValue":
            if($_GET["region"]){    //  즐겨찾기 해당 동네 이름 받기
                $region = $_GET["region"];
                $tmp_region = preg_split('/\s+/', $region);
                $region_2depth_name = $tmp_region[0];
                $region_3depth_name = $tmp_region[1];

                $tmp_region = locationSearch($region);
                $json_result = json_decode($tmp_region);
                $tm_x = $json_result->documents[0]->x;
                $tm_y = $json_result->documents[0]->y;

                $tmp->result = transFormation($tm_x, $tm_y);
                $json_result = json_decode($tmp->result);

                $x = $json_result->documents[0]->x;
                $y = $json_result->documents[0]->y;

                $tmp->result = findNearStation($x, $y); //  가까운 측정소 검색
                $station_result = json_decode($tmp->result);

                $stationName = $station_result->list[0]->stationName;   //  처음 시작은 가장 가까운 측정소로 시작

                $tmp->result = fineDust($stationName);
                $json_result = json_decode($tmp->result);

                $res->result->total_value = StationValue($json_result,  $station_result, khaiValue);
                $res->result->pm10_value = StationValue($json_result,  $station_result, pm10Value);
                $res->result->pm25_value = StationValue($json_result,  $station_result, pm25Value);
                $res->result->no2_value = StationValue($json_result, $station_result, no2Value);
                $res->result->o3_value = StationValue($json_result,  $station_result, o3Value);
                $res->result->co_value = StationValue($json_result,  $station_result, coValue);
                $res->result->so2_value = StationValue($json_result,  $station_result, so2Value);


                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "측정 값 조회 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
                break;
            } else if ($_GET["x"] && $_GET["y"]){    //  GPS의 x, y 값을 받을 경우
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

                $res->result->total_value = StationValue($json_result,  $station_result, khaiValue);
                $res->result->pm10_value = StationValue($json_result,  $station_result, pm10Value);
                $res->result->pm25_value = StationValue($json_result,  $station_result, pm25Value);
                $res->result->no2_value = StationValue($json_result, $station_result, no2Value);
                $res->result->o3_value = StationValue($json_result,  $station_result, o3Value);
                $res->result->co_value = StationValue($json_result,  $station_result, coValue);
                $res->result->so2_value = StationValue($json_result,  $station_result, so2Value);


                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "측정 값 조회 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
                break;
            }

        /*
                 * API No. 5
                 * API Name :  즐겨찾기 해당 동네 이름 or GPS의 x, y 기준으로 측정 등급 조회 API
                 * 마지막 수정 날짜 : 20.02.19
        */
        case "dustGrade":
            if($_GET["region"]){    //  즐겨찾기 해당 동네 이름 받기
                $region = $_GET["region"];
                $tmp_region = preg_split('/\s+/', $region);
                $region_2depth_name = $tmp_region[0];
                $region_3depth_name = $tmp_region[1];

                $tmp_region = locationSearch($region);
                $json_result = json_decode($tmp_region);
                $tm_x = $json_result->documents[0]->x;
                $tm_y = $json_result->documents[0]->y;

                $tmp->result = transFormation($tm_x, $tm_y);
                $json_result = json_decode($tmp->result);

                $x = $json_result->documents[0]->x;
                $y = $json_result->documents[0]->y;

                $tmp->result = findNearStation($x, $y); //  가까운 측정소 검색
                $station_result = json_decode($tmp->result);

                $stationName = $station_result->list[0]->stationName;   //  처음 시작은 가장 가까운 측정소로 시작

                $tmp->result = fineDust($stationName);
                $json_result = json_decode($tmp->result);

                (int)$pm10_grade = StationGrade($json_result,  $station_result, pm10Grade1h);
                (int)$pm25_grade = StationGrade($json_result,  $station_result, pm25Grade1h);
                $res->result->total_grade = StationGrade($json_result,  $station_result, khaiGrade);
                $res->result->pm10_grade = StationGrade($json_result,  $station_result, pm10Grade1h);
                $res->result->pm25_grade = StationGrade($json_result,  $station_result, pm25Grade1h);
                $res->result->no2_grade = StationGrade($json_result,  $station_result, no2Grade);
                $res->result->o3_grade = StationGrade($json_result,  $station_result, o3Grade);
                $res->result->co_grade = StationGrade($json_result,  $station_result, coGrade);
                $res->result->so2_grade = StationGrade($json_result,  $station_result, so2Grade);

                if((int)($pm10_grade) < (int)($pm25_grade)){     //  미세먼지와 초미세먼지 등급 중에 큰 것이 선택
                    $res->result->current_status_grade = (int)$pm25_grade;  //  grade 수가 적은 것이 공기 상태가 더 좋은 것
                } else {
                    $res->result->current_status_grade = (int)$pm10_grade;
                }

                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "측정 등급 조회 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
                break;
            } else if ($_GET["x"] && $_GET["y"]) {    //  GPS의 x, y 값을 받을 경우
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

                (int)$pm10_grade = StationGrade($json_result, $station_result, pm10Grade1h);
                (int)$pm25_grade = StationGrade($json_result, $station_result, pm25Grade1h);
                $res->result->total_grade = StationGrade($json_result, $station_result, khaiGrade);
                $res->result->pm10_grade = StationGrade($json_result,  $station_result, pm10Grade1h);
                $res->result->pm25_grade = StationGrade($json_result,  $station_result, pm25Grade1h);
                $res->result->no2_grade = StationGrade($json_result,  $station_result, no2Grade);
                $res->result->o3_grade = StationGrade($json_result,  $station_result, o3Grade);
                $res->result->co_grade = StationGrade($json_result,  $station_result, coGrade);
                $res->result->so2_grade = StationGrade($json_result,  $station_result, so2Grade);

                if((int)($pm10_grade) < (int)($pm25_grade)){     //  미세먼지와 초미세먼지 등급 중에 큰 것이 선택
                    $res->result->current_status_grade = (int)$pm25_grade;  //  grade 수가 적은 것이 공기 상태가 더 좋은 것
                } else {
                    $res->result->current_status_grade = (int)$pm10_grade;
                }


                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "측정 등급 조회 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
                break;
            }

        /*
                 * API No. 6
                 * API Name : 즐겨찾기 해당 동네 이름 or GPS의 x, y 기준으로 기타 사항 조회 API
                 * 마지막 수정 날짜 : 20.02.19
        */
        case "dustEtc":
            if($_GET["region"]){    //  즐겨찾기 해당 동네 이름 받기
                $region = $_GET["region"];
                $tmp_region = preg_split('/\s+/', $region);
                $region_2depth_name = $tmp_region[0];
                $region_3depth_name = $tmp_region[1];

                $tmp_region = locationSearch($region);
                $json_result = json_decode($tmp_region);
                $tm_x = $json_result->documents[0]->x;
                $tm_y = $json_result->documents[0]->y;

                $tmp->result = transFormation($tm_x, $tm_y);
                $json_result = json_decode($tmp->result);

                $x = $json_result->documents[0]->x;
                $y = $json_result->documents[0]->y;

                $tmp->result = findNearStation($x, $y); //  가까운 측정소 검색
                $station_result = json_decode($tmp->result);

                $stationName = $station_result->list[0]->stationName;   //  처음 시작은 가장 가까운 측정소로 시작

                $tmp->result = fineDust($stationName);
                $json_result = json_decode($tmp->result);

                $res->result->region_2depth_name = $region_2depth_name;
                $res->result->region_3depth_name = $region_3depth_name;

                $now = time();
                $five_minutes = 60 * 5;
                $offset = $now % $five_minutes;
                $five_block = $now - $offset;
                $res->result->current_time = date("Y-m-d H:i", $five_block); //  현재시간, 5분마다 최신화

                $res->result->update_time = $json_result->list[0]->dataTime;
                $res->result->pm10_mang_name = StationMang($json_result,  $station_result, pm10Value);
                $res->result->pm25_mang_name = StationMang($json_result,  $station_result, pm25Value);
                $res->result->pm10_station = StationName($json_result,  $station_result, pm10Value);
                $res->result->pm25_station = StationName($json_result, $station_result, pm25Value);
                $res->result->no2_station = StationName($json_result,  $station_result, no2Value);
                $res->result->o3_station = StationName($json_result,  $station_result, o3Value);
                $res->result->co_station = StationName($json_result,  $station_result, coValue);
                $res->result->so2_station = StationName($json_result,  $station_result, so2Value);

                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "기타 사항 조회 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
                break;
            } else if ($_GET["x"] && $_GET["y"]) {    //  GPS의 x, y 값을 받을 경우
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
                $res->result->pm10_mang_name = StationMang($json_result,  $station_result, pm10Value);
                $res->result->pm25_mang_name = StationMang($json_result,  $station_result, pm25Value);
                $res->result->pm10_station = StationName($json_result,  $station_result, pm10Value);
                $res->result->pm25_station = StationName($json_result,  $station_result, pm25Value);
                $res->result->no2_station = StationName($json_result,  $station_result, no2Value);
                $res->result->o3_station = StationName($json_result,  $station_result, o3Value);
                $res->result->co_station = StationName($json_result,  $station_result, coValue);
                $res->result->so2_station = StationName($json_result, $station_result, so2Value);

                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "기타 사항 조회 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
                break;
            }

        /*
                 * API No. 7
                 * API Name : 즐겨찾기 조회 API
                 * 마지막 수정 날짜 : 20.02.20
        */
        case "favoriteGet":
            if ($_GET["x"] && $_GET["y"]){
                $x = $_GET["x"];
                $y = $_GET["y"];
                $res->result[0]["no"] = 1;
                $res->result[0]["region_2depth_name"] = "GPS";
                $res->result[0]["region_3depth_name"] = "현재 위치";

                $tmp->result = transFormation($x, $y);
                $json_result = json_decode($tmp->result);

                $x = $json_result->documents[0]->x;
                $y = $json_result->documents[0]->y;

                $tmp->result = findNearStation($x, $y);
                $station_result = json_decode($tmp->result);

                $stationName = $station_result->list[0]->stationName;

                $tmp->result = fineDust($stationName);
                $json_result = json_decode($tmp->result);

                (int)$pm10_grade = StationGrade($json_result,  $station_result, pm10Grade1h);
                (int)$pm25_grade = StationGrade($json_result, $station_result, pm25Grade1h);

                if((int)($pm10_grade) < (int)($pm25_grade)){    //  미세먼지와 초미세먼지 등급 중에 큰 것이 선택
                    $res->result[0]["current_status_grade"] = (int)$pm25_grade;  //  grade 수가 적은 것이 공기 상태가 더 좋은 것
                } else {
                    $res->result[0]["current_status_grade"] = (int)$pm10_grade;
                }


                $tmp->result = favoriteGet();
                $favorite_encode = json_encode($tmp->result);
                $favorite_decode = json_decode($favorite_encode);
                $cnt = count($favorite_decode);

                for($i=0; $i<$cnt; $i++){
                    $res->result[$i+1]["no"] = $favorite_decode[$i]->no+1;
                    $res->result[$i+1]["region_2depth_name"] = $favorite_decode[$i]->region_2depth_name;
                    $res->result[$i+1]["region_3depth_name"] = $favorite_decode[$i]->region_3depth_name;

                    $tm_x = $favorite_decode[$i]->tm_x;
                    $tm_y = $favorite_decode[$i]->tm_y;

                    $tmp->result = transFormation($tm_x, $tm_y);
                    $json_result = json_decode($tmp->result);

                    $x = $json_result->documents[0]->x;
                    $y = $json_result->documents[0]->y;

                    $tmp->result = findNearStation($x, $y);
                    $station_result = json_decode($tmp->result);

                    $stationName = $station_result->list[0]->stationName;

                    $tmp->result = fineDust($stationName);
                    $json_result = json_decode($tmp->result);

                    (int)$pm10_grade = StationGrade($json_result,  $station_result, pm10Grade1h);
                    (int)$pm25_grade = StationGrade($json_result,  $station_result, pm25Grade1h);

                    if((int)($pm10_grade) < (int)($pm25_grade)){    //  미세먼지와 초미세먼지 등급 중에 큰 것이 선택
                        $res->result[$i]["current_status_grade"] = (int)$pm25_grade;  //  grade 수가 적은 것이 공기 상태가 더 좋은 것
                    } else {
                        $res->result[$i]["current_status_grade"] = (int)$pm10_grade;
                    }
                }
            } else {
                $tmp->result = favoriteGet();
                $favorite_encode = json_encode($tmp->result);
                $favorite_decode = json_decode($favorite_encode);
                $cnt = count($favorite_decode);

                for($i=0; $i<$cnt; $i++){
                    $res->result[$i]["no"] = $favorite_decode[$i]->no;
                    $res->result[$i]["region_2depth_name"] = $favorite_decode[$i]->region_2depth_name;
                    $res->result[$i]["region_3depth_name"] = $favorite_decode[$i]->region_3depth_name;

                    $tm_x = $favorite_decode[$i]->tm_x;
                    $tm_y = $favorite_decode[$i]->tm_y;

                    $tmp->result = transFormation($tm_x, $tm_y);
                    $json_result = json_decode($tmp->result);

                    $x = $json_result->documents[0]->x;
                    $y = $json_result->documents[0]->y;

                    $tmp->result = findNearStation($x, $y);
                    $station_result = json_decode($tmp->result);

                    $stationName = $station_result->list[0]->stationName;

                    $tmp->result = fineDust($stationName);
                    $json_result = json_decode($tmp->result);

                    (int)$pm10_grade = StationGrade($json_result,  $station_result, pm10Grade1h);
                    (int)$pm25_grade = StationGrade($json_result,  $station_result, pm25Grade1h);

                    if((int)($pm10_grade) < (int)($pm25_grade)){    //  미세먼지와 초미세먼지 등급 중에 큰 것이 선택
                        $res->result[$i]["current_status_grade"] = (int)$pm25_grade;  //  grade 수가 적은 것이 공기 상태가 더 좋은 것
                    } else {
                        $res->result[$i]["current_status_grade"] = (int)$pm10_grade;
                    }
                }
            }

            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "즐겨찾기 조회 성공";

            echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
            break;

        /*
                 * API No. 8
                 * API Name : 모든 측정소 미세먼지, 초미세먼지, 현재 등급 조회 API (지도)
                 * 마지막 수정 날짜 : 20.02.22
        */
        case "map":
            http_response_code(200);
            $all_station = map();
            $map_encode = json_encode($all_station);
            $map_decode = json_decode($map_encode);

//            for($i=0; $i<count($map_decode); $i++){
            for($i=0; $i<10; $i++){

                $tm_x = $map_decode[$i]->x;
                $tm_y = $map_decode[$i]->y;
                $stationName = $map_decode[$i]->station_name;

                $tmp->result = transFormation($tm_x, $tm_y);
                $json_result = json_decode($tmp->result);
                $x = $json_result->documents[0]->x;
                $y = $json_result->documents[0]->y;

                $tmp->result = fineDust($stationName);
                $json_result = json_decode($tmp->result);

                $pm10_value = MapValue($json_result, pm10Value);
                $pm25_value = MapValue($json_result, pm25Value);
                (int)$pm10_grade = MapValue($json_result, pm10Grade1h);
                (int)$pm25_grade = MapValue($json_result, pm25Grade1h);

                $res->result[$i]["no"] = $map_decode[$i]->no;
                $res->result[$i]["station_name"] = $map_decode[$i]->station_name;
                $res->result[$i]["pm10_value"] = MapValue($json_result, pm10Grade1h);
                $res->result[$i]["pm25_value"] = MapValue($json_result, pm25Grade1h);
                if((int)($pm10_grade) < (int)($pm25_grade)){    //  미세먼지와 초미세먼지 등급 중에 큰 것이 선택
                    $res->result[$i]["current_status_grade"] = (int)$pm25_grade;  //  grade 수가 적은 것이 공기 상태가 더 좋은 것
                } else {
                    $res->result[$i]["current_status_grade"] = (int)$pm10_grade;
                }
            }
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "모든 측정소 정보 조회 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
                 * API No. 9
                 * API Name : 해당 측정소 미세먼지, 초미세먼지, 현재 등급 조회 API (지도)
                 * 마지막 수정 날짜 : 20.02.22
        */
        case "mapDetail":
            http_response_code(200);
            $tmp->result = mapDetail($vars["mapNo"]);
            $map_encode = json_encode($tmp->result);
            $map_decode = json_decode($map_encode);

            $tm_x = $map_decode->x;
            $tm_y = $map_decode->y;
            $stationName = $map_decode->station_name;

            $tmp->result = transFormation($tm_x, $tm_y);
            $json_result = json_decode($tmp->result);
            $x = $json_result->documents[0]->x;
            $y = $json_result->documents[0]->y;

            $tmp->result = fineDust($stationName);
            $json_result = json_decode($tmp->result);

            $pm10_value = MapValue($json_result, pm10Value);
            $pm25_value = MapValue($json_result, pm25Value);
            (int)$pm10_grade = MapValue($json_result, pm10Grade1h);
            (int)$pm25_grade = MapValue($json_result, pm25Grade1h);

            $res->result["no"] = $map_decode->no;
            $res->result["station_name"] = $stationName;
            $res->result["pm10_value"] = MapValue($json_result, pm10Grade1h);
            $res->result["pm25_value"] = MapValue($json_result, pm25Grade1h);
            if((int)($pm10_grade) < (int)($pm25_grade)){    //  미세먼지와 초미세먼지 등급 중에 큰 것이 선택
                $res->result["current_status_grade"] = (int)$pm25_grade;  //  grade 수가 적은 것이 공기 상태가 더 좋은 것
            } else {
                $res->result["current_status_grade"] = (int)$pm10_grade;
            }

            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "해당 측정소 정보 조회 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
                 * API No. 10
                 * API Name : 공지사항 조회 API (팝업)
                 * 마지막 수정 날짜 : 20.02.24
        */
        case "notice":
            http_response_code(200);
            $res->result = notice();
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "테스트 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

    }



} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}
