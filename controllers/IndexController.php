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
//
            if(!empty($location)){  //  검색어를 입력한 경우
                $tmp->result = locationSearch($location, $pageNo);
                $json_result = json_decode($tmp->result);
                $total_page = $json_result->meta->total_count;
                $documents = $json_result->documents;
                $len = count($documents);

                if($len != 0) { //  검색 결과 값이 존재할때
                    for ($i = 0; $i < $len; $i++) {
//                        $res->result[$i]["no"] = ($i + 1);
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
//                $res->result[0]->no = "1";
//                $res->result[0]->region_1depth_name = "경기";
//                $res->result[0]->region_2depth_name = "화성시";
//                $res->result[0]->region_3depth_name = "동탄1동";
//                $res->result[0]->address_name = "경기 화성시 동탄1동";
//                $res->result[0]->tm_x = "127.0719158955";
//                $res->result[0]->tm_y = "37.206522874281";
//
//                $res->result[1]->no = "2";
//                $res->result[1]->region_1depth_name = "경기";
//                $res->result[1]->region_2depth_name = "화성시";
//                $res->result[1]->region_3depth_name = "동탄2동";
//                $res->result[1]->address_name = "경기 화성시 동탄2동";
//                $res->result[1]->tm_x = "127.0719158955";
//                $res->result[1]->tm_y = "37.206522874281";
//            $res->isSuccess = TRUE;
//            $res->code = 100;
//            $res->message = "읍/면/동 주소 검색 성공";
//            echo json_encode($res, JSON_NUMERIC_CHECK| JSON_UNESCAPED_UNICODE);
//            break;

        /*
                 * API No. 2
                 * API Name : 즐겨찾기 해당 동네 이름 or GPS의 x, y 기준으로 측정 값 조회 API
                 * 마지막 수정 날짜 : 20.02.20
        */
        case "dustValue":
            if(isset($_GET["region"])){    //  즐겨찾기 해당 동네 이름 받기
//                $region = $_GET["region"];
//
//
//                $tmp_region = locationSearch($region);
//                $json_result = json_decode($tmp_region);
//                $tm_x = $json_result->documents[0]->x;
//                $tm_y = $json_result->documents[0]->y;
//
////                $res->result = $json_result;
////                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
////                return;
//
//                $tmp->result = transFormation($tm_x, $tm_y);
//                $json_result = json_decode($tmp->result);
//
//                $x = $json_result->documents[0]->x;
//                $y = $json_result->documents[0]->y;
//
//                $accessLogs->addInfo(json_encode("value log 1 = ".$x." ".$y, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
////                $res->result = $x.$y;
////                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
////                return;
//                if($x === NULL and $y === NULL){
//                    $res->result = "주소를 정확히 입력해주세요.";
//                    $res->isSuccess = FALSE;
//                    $res->code = 200;
//                    $res->message = "측정 값 조회 실패";
//                    echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
//                    break;
//                }
//                $tmp->result = findNearStation($x, $y); //  가까운 측정소 검색
//                $station_result = json_decode($tmp->result);
//
//                $stationName = $station_result->list[0]->stationName;   //  처음 시작은 가장 가까운 측정소로 시작
//                $accessLogs->addInfo(json_encode("value log 2 = ".$stationName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//
//                $tmp->result = findDust($stationName);
//                $json_result = json_decode($tmp->result);
//
//////                $res->result = $json_result;
//////                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
//////                return;
//
//
//                $accessLogs->addInfo(json_encode("value log 2 = ".khaiValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//                $res->result->total_value = StationValue($json_result,  $station_result, khaiValue);
//                if($res->result->total_value === null){
//                    $res->result->total_value = StationValue2($json_result,  $station_result, khaiValue);
//                }
//                $accessLogs->addInfo(json_encode("value log 2 = ".$res->result->total_value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//
//                $accessLogs->addInfo(json_encode("value log 2 = ".pm10Value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//                $res->result->pm10_value = StationValue($json_result,  $station_result, pm10Value);
//                if($res->result->pm10_value === null){
//                    $res->result->pm10_value = StationValue2($json_result,  $station_result, pm10Value);
//                }
//                $accessLogs->addInfo(json_encode("value log 2 = ".$res->result->pm10_value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//
//                $accessLogs->addInfo(json_encode("value log 2 = ".pm25Value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//                $res->result->pm25_value = StationValue($json_result,  $station_result, pm25Value);
//                if($res->result->pm25_value === null){
//                    $res->result->pm25_value = StationValue2($json_result,  $station_result, pm25Value);
//                }
//                $accessLogs->addInfo(json_encode("value log 2 = ".$res->result->pm25_value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//
//                $accessLogs->addInfo(json_encode("value log 2 = ".no2Value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//                $res->result->no2_value = StationValue($json_result, $station_result, no2Value);
//                if($res->result->no2_value === null){
//                    $res->result->no2_value = StationValue2($json_result,  $station_result, no2Value);
//                }
//                $accessLogs->addInfo(json_encode("value log 2 = ". $res->result->no2_value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//
//                $accessLogs->addInfo(json_encode("value log 2 = ".o3Value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//                $res->result->o3_value = StationValue($json_result,  $station_result, o3Value);
//                if($res->result->o3_value === null){
//                    $res->result->o3_value = StationValue2($json_result,  $station_result, o3Value);
//                }
//                $accessLogs->addInfo(json_encode("value log 2 = ".$res->result->o3_value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//
//                $accessLogs->addInfo(json_encode("value log 2 = ".coValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//                $res->result->co_value = StationValue($json_result,  $station_result, coValue);
//                if($res->result->co_value === null){
//                    $res->result->co_value = StationValue2($json_result,  $station_result, coValue);
//                }
//                $accessLogs->addInfo(json_encode("value log 2 = ".$res->result->co_value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//
//                $accessLogs->addInfo(json_encode("value log 2 = ".so2Value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//                $res->result->so2_value = StationValue($json_result,  $station_result, so2Value);
//                if($res->result->so2_value === null){
//                    $res->result->so2_value = StationValue2($json_result,  $station_result, so2Value);
//                }
//                $accessLogs->addInfo(json_encode("value log 2 = ".$res->result->so2_value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                $res->result->total_value = "2";
                $res->result->pm10_value = "23";
                $res->result->pm25_value = "16";
                $res->result->no2_value = "0.022";
                $res->result->o3_value = "0.03";
                $res->result->co_value = "0.3";
                $res->result->so2_value = "0.2";

                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "측정 값 조회 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
                break;
            } else if (isset($_GET["x"]) && isset($_GET["y"])){    //  GPS의 x, y 값을 받을 경우
//                $tm_x = $_GET["x"];
//                $tm_y = $_GET["y"];
//
//                $tmp->result = transFormation($tm_x, $tm_y);
//                $json_result = json_decode($tmp->result);
//
//                $x = $json_result->documents[0]->x;
//                $y = $json_result->documents[0]->y;
//
//                if($x === NULL and $y === NULL){
//                    $res->result = "주소를 정확히 입력해주세요.";
//                    $res->isSuccess = FALSE;
//                    $res->code = 200;
//                    $res->message = "측정 값 조회 실패";
//                    echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
//                    break;
//                }
//
//                $tmp->result = findNearStation($x, $y);
//                $station_result = json_decode($tmp->result);
//
//
//                $stationName = $station_result->list[0]->stationName;
//
//                $tmp->result = findDust($stationName);
//                $json_result = json_decode($tmp->result);
//
//                $res->result->total_value = StationValue($json_result,  $station_result, khaiValue);
//                $res->result->pm10_value = StationValue($json_result,  $station_result, pm10Value);
//                $res->result->pm25_value = StationValue($json_result,  $station_result, pm25Value);
//                $res->result->no2_value = StationValue($json_result, $station_result, no2Value);
//                $res->result->o3_value = StationValue($json_result,  $station_result, o3Value);
//                $res->result->co_value = StationValue($json_result,  $station_result, coValue);
//                $res->result->so2_value = StationValue($json_result,  $station_result, so2Value);
//
//
//                $res->isSuccess = TRUE;
//                $res->code = 100;
//                $res->message = "측정 값 조회 성공";
//                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
//                break;
                $res->result->total_value = "1";
                $res->result->pm10_value = "13";
                $res->result->pm25_value = "26";
                $res->result->no2_value = "0.011";
                $res->result->o3_value = "0.08";
                $res->result->co_value = "0.5";
                $res->result->so2_value = "0.1";

                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "측정 값 조회 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
                break;
            } else {
                $res->isSuccess = false;
                $res->code = 200;
                $res->message = "측정 등급 조회 실패";
                break;
            }

        /*
                 * API No. 3
                 * API Name :  즐겨찾기 해당 동네 이름 or GPS의 x, y 기준으로 측정 등급 조회 API
                 * 마지막 수정 날짜 : 20.02.19
        */
        case "dustGrade":
            if(isset($_GET["region"])){    //  즐겨찾기 해당 동네 이름 받기
                $region = $_GET["region"];
//
//                $tmp_region = locationSearch($region);
//                $json_result = json_decode($tmp_region);
//                $tm_x = $json_result->documents[0]->x;
//                $tm_y = $json_result->documents[0]->y;
//
//                $tmp->result = transFormation($tm_x, $tm_y);
//                $json_result = json_decode($tmp->result);
//
//                $x = $json_result->documents[0]->x;
//                $y = $json_result->documents[0]->y;
//
//                if($x === NULL and $y === NULL){
//                    $res->result = "주소를 정확히 입력해주세요.";
//                    $res->isSuccess = FALSE;
//                    $res->code = 200;
//                    $res->message = "측정 값 조회 실패";
//                    echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
//                    break;
//                }
//
//                $accessLogs->addInfo(json_encode("value log 1 = ".$x." ".$y, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//                $tmp->result = findNearStation($x, $y); //  가까운 측정소 검색
//                $station_result = json_decode($tmp->result);
//
//                $stationName = $station_result->list[0]->stationName;   //  처음 시작은 가장 가까운 측정소로 시작
//                $accessLogs->addInfo(json_encode("value log 2 = ".$stationName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//                $tmp->result = findDust($stationName);
//                $json_result = json_decode($tmp->result);
//
//                $pm10_grade = StationGrade($json_result,  $station_result, pm10Grade1h);
//                $pm25_grade = StationGrade($json_result,  $station_result, pm25Grade1h);
//                $res->result->total_grade = StationGrade($json_result,  $station_result, khaiGrade);
//                $res->result->pm10_grade = StationGrade($json_result,  $station_result, pm10Grade1h);
//                $res->result->pm25_grade = StationGrade($json_result,  $station_result, pm25Grade1h);
//                $res->result->no2_grade = StationGrade($json_result,  $station_result, no2Grade);
//                $res->result->o3_grade = StationGrade($json_result,  $station_result, o3Grade);
//                $res->result->co_grade = StationGrade($json_result,  $station_result, coGrade);
//                $res->result->so2_grade = StationGrade($json_result,  $station_result, so2Grade);
//
//                if(($pm10_grade) < ($pm25_grade)){     //  미세먼지와 초미세먼지 등급 중에 큰 것이 선택
//                    $res->result->current_status_grade = $pm25_grade;  //  grade 수가 적은 것이 공기 상태가 더 좋은 것
//                } else {
//                    $res->result->current_status_grade = $pm10_grade;
//                }
//
//                $res->isSuccess = TRUE;
//                $res->code = 100;
//                $res->message = "측정 등급 조회 성공";
//                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
//                break;
                $res->result->total_grade = "2";
                $res->result->pm10_grade = "1";
                $res->result->pm25_grade = "1";
                $res->result->no2_grade = "1";
                $res->result->o3_grade = "2";
                $res->result->co_grade = "1";
                $res->result->so2_grade = "1";
                $res->result->current_status_grade = "1";


                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "측정 등급 조회 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
                break;
            } else if (isset($_GET["x"]) && isset($_GET["y"])) {    //  GPS의 x, y 값을 받을 경우
//                $tm_x = $_GET["x"];
//                $tm_y = $_GET["y"];
//
//                $tmp->result = transFormation($tm_x, $tm_y);
//                $json_result = json_decode($tmp->result);
//
//                $x = $json_result->documents[0]->x;
//                $y = $json_result->documents[0]->y;
//                if($x === NULL and $y === NULL){
//                    $res->result = "주소를 정확히 입력해주세요.";
//                    $res->isSuccess = FALSE;
//                    $res->code = 200;
//                    $res->message = "측정 값 조회 실패";
//                    echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
//                    break;
//                }
//
//
//                $tmp->result = findNearStation($x, $y);
//                $station_result = json_decode($tmp->result);
//
//                $stationName = $station_result->list[0]->stationName;
//
//                $tmp->result = findDust($stationName);
//                $json_result = json_decode($tmp->result);
//
//                $pm10_grade = StationGrade($json_result, $station_result, pm10Grade1h);
//                $pm25_grade = StationGrade($json_result, $station_result, pm25Grade1h);
//                $res->result->total_grade = StationGrade($json_result, $station_result, khaiGrade);
//                $res->result->pm10_grade = StationGrade($json_result,  $station_result, pm10Grade1h);
//                $res->result->pm25_grade = StationGrade($json_result,  $station_result, pm25Grade1h);
//                $res->result->no2_grade = StationGrade($json_result,  $station_result, no2Grade);
//                $res->result->o3_grade = StationGrade($json_result,  $station_result, o3Grade);
//                $res->result->co_grade = StationGrade($json_result,  $station_result, coGrade);
//                $res->result->so2_grade = StationGrade($json_result,  $station_result, so2Grade);
//
//                if(($pm10_grade) < ($pm25_grade)){     //  미세먼지와 초미세먼지 등급 중에 큰 것이 선택
//                    $res->result->current_status_grade = $pm25_grade;  //  grade 수가 적은 것이 공기 상태가 더 좋은 것
//                } else {
//                    $res->result->current_status_grade = $pm10_grade;
//                }
//
//
//                $res->isSuccess = TRUE;
//                $res->code = 100;
//                $res->message = "측정 등급 조회 성공";
//                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
//                break;

                $res->result->total_grade = "1";
                $res->result->pm10_grade = "2";
                $res->result->pm25_grade = "3";
                $res->result->no2_grade = "4";
                $res->result->o3_grade = "1";
                $res->result->co_grade = "2";
                $res->result->so2_grade = "3";
                $res->result->current_status_grade = "2";


                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "측정 등급 조회 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
                break;
            } else {
                $res->isSuccess = false;
                $res->code = 200;
                $res->message = "측정 등급 조회 실패";
                break;
            }

        /*
                 * API No. 4
                 * API Name : 즐겨찾기 해당 동네 이름 or GPS의 x, y 기준으로 기타 사항 조회 API
                 * 마지막 수정 날짜 : 20.02.19
        */
        case "dustEtc":
            if(isset($_GET["region"])){    //  즐겨찾기 해당 동네 이름 받기
//                $region = $_GET["region"];
//                $tmp_region = preg_split('/\s+/', $region);
//                $region_len = count($tmp_region)-1;
//                $region_2depth_name = $tmp_region[$region_len-1];
//                $region_3depth_name = $tmp_region[$region_len];
//
//                $tmp_region = locationSearch($region);
//                $json_result = json_decode($tmp_region);
//                $tm_x = $json_result->documents[0]->x;
//                $tm_y = $json_result->documents[0]->y;
//
//                $tmp->result = transFormation($tm_x, $tm_y);
//                $json_result = json_decode($tmp->result);
//
//                $x = $json_result->documents[0]->x;
//                $y = $json_result->documents[0]->y;
//                if($x === NULL and $y === NULL){
//                    $res->result = "주소를 정확히 입력해주세요.";
//                    $res->isSuccess = FALSE;
//                    $res->code = 200;
//                    $res->message = "측정 값 조회 실패";
//                    echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
//                    break;
//                }
//                $tmp->result = findNearStation($x, $y); //  가까운 측정소 검색
//                $station_result = json_decode($tmp->result);
//
//                $stationName = $station_result->list[0]->stationName;   //  처음 시작은 가장 가까운 측정소로 시작
//
//                $tmp->result = findDust($stationName);
//                $json_result = json_decode($tmp->result);
//
//                $res->result->region_2depth_name = $region_2depth_name;
//                if($region_3depth_name === NULL){
//                    $res->result->region_3depth_name = '';
//                } else {
//                    $res->result->region_3depth_name = $region_3depth_name;
//                }
//
//
//                $now = time();
//                $five_minutes = 60 * 5;
//                $offset = $now % $five_minutes;
//                $five_block = $now - $offset;
//                $res->result->current_time = date("Y-m-d H:i", $five_block); //  현재시간, 5분마다 최신화
//
//                $res->result->update_time = $json_result->list[0]->dataTime;
//                $res->result->pm10_mang_name = StationMang($json_result,  $station_result, pm10Value);
//                $res->result->pm25_mang_name = StationMang($json_result,  $station_result, pm25Value);
//                $res->result->pm10_station = StationName($json_result,  $station_result, pm10Value);
//                $res->result->pm25_station = StationName($json_result, $station_result, pm25Value);
//                $res->result->no2_station = StationName($json_result,  $station_result, no2Value);
//                $res->result->o3_station = StationName($json_result,  $station_result, o3Value);
//                $res->result->co_station = StationName($json_result,  $station_result, coValue);
//                $res->result->so2_station = StationName($json_result,  $station_result, so2Value);
//
//                $res->isSuccess = TRUE;
//                $res->code = 100;
//                $res->message = "기타 사항 조회 성공";
//                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
//                break;
                $res->result->region_2depth_name = "화성시";
                $res->result->region_3depth_name = "동탄1동";
                $res->result->current_time = "2020-02-27 17:25";
                $res->result->update_time = "2020-02-27 13:00";
                $res->result->pm10_mang_name = "도시대기";
                $res->result->pm25_mang_name = "도시대기";
                $res->result->pm10_station = "청계동";
                $res->result->pm25_station = "청계동";
                $res->result->no2_station = "청계동";
                $res->result->o3_station = "청계동";
                $res->result->co_station = "청계동";
                $res->result->so2_station = "청계동";

                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "기타 사항 조회 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
                break;
            } else if (isset($_GET["x"]) && isset($_GET["y"])) {    //  GPS의 x, y 값을 받을 경우
//                $tm_x = $_GET["x"];
//                $tm_y = $_GET["y"];
//
//                $tmp->result = transFormation($tm_x, $tm_y);
//                $json_result = json_decode($tmp->result);
//
//                $x = $json_result->documents[0]->x;
//                $y = $json_result->documents[0]->y;
//                if($x === NULL and $y === NULL){
//                    $res->result = "주소를 정확히 입력해주세요.";
//                    $res->isSuccess = FALSE;
//                    $res->code = 200;
//                    $res->message = "측정 값 조회 실패";
//                    echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
//                    break;
//                }
//                $tmp->result = FindLocation($tm_x, $tm_y);
//                $location_result = json_decode($tmp->result);
//
//                $tmp->result = findNearStation($x, $y);
//                $station_result = json_decode($tmp->result);
//
//                $stationName = $station_result->list[0]->stationName;
//
//                $tmp->result = findDust($stationName);
//                $json_result = json_decode($tmp->result);
//
//                $res->result->region_2depth_name = $location_result->documents[0]->region_2depth_name;
//                if ($location_result->documents[0]->region_3depth_name === NULL){
//                    $res->result->region_3depth_name = '';
//                } else {
//                    $res->result->region_3depth_name = $location_result->documents[0]->region_3depth_name;
//                }
//
//
//                $now = time();
//                $five_minutes = 60*5;
//                $offset = $now % $five_minutes;
//                $five_block = $now - $offset;
//                $res->result->current_time = date("Y-m-d H:i", $five_block); //  현재시간, 5분마다 최신화
//
//                $res->result->update_time = $json_result->list[0]->dataTime;
//                $res->result->pm10_mang_name = StationMang($json_result,  $station_result, pm10Value);
//                $res->result->pm25_mang_name = StationMang($json_result,  $station_result, pm25Value);
//                $res->result->pm10_station = StationName($json_result,  $station_result, pm10Value);
//                $res->result->pm25_station = StationName($json_result,  $station_result, pm25Value);
//                $res->result->no2_station = StationName($json_result,  $station_result, no2Value);
//                $res->result->o3_station = StationName($json_result,  $station_result, o3Value);
//                $res->result->co_station = StationName($json_result,  $station_result, coValue);
//                $res->result->so2_station = StationName($json_result, $station_result, so2Value);
//
//                $res->isSuccess = TRUE;
//                $res->code = 100;
//                $res->message = "기타 사항 조회 성공";
//                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
//                break;
                $res->result->region_2depth_name = "서울시";
                $res->result->region_3depth_name = "서초구";
                $res->result->current_time = "2020-02-27 10:20";
                $res->result->update_time = "2020-02-27 15:00";
                $res->result->pm10_mang_name = "도시대기";
                $res->result->pm25_mang_name = "도시대기";
                $res->result->pm10_station = "서초구";
                $res->result->pm25_station = "서초구";
                $res->result->no2_station = "서초구";
                $res->result->o3_station = "서초구";
                $res->result->co_station = "서초구";
                $res->result->so2_station = "서초구";
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "기타 사항 조회 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
                break;
            }else {
                $res->isSuccess = false;
                $res->code = 200;
                $res->message = "측정 등급 조회 실패";
                break;
            }

        /*
                 * API No. 5
                 * API Name : 모든 측정소 미세먼지, 초미세먼지, 현재 등급 조회 API (지도)
                 * 마지막 수정 날짜 : 20.02.22
        */
        case "map":
            http_response_code(200);
            $res->result = allMaps();
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "모든 측정소 정보 조회 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
                 * API No. 6
                 * API Name : 해당 측정소 미세먼지, 초미세먼지, 현재 등급 조회 API (지도)
                 * 마지막 수정 날짜 : 20.02.22
        */
        case "mapDetail":
            http_response_code(200);
            $res->result = mapDetail($vars["mapNo"]);
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "해당 측정소 정보 조회 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
                 * API No. 7
                 * API Name : 공지사항 조회 API (팝업)
                 * 마지막 수정 날짜 : 20.02.24
        */
        case "notice":
            http_response_code(200);
            $res->result = notice();
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "공지사항 조회 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
                 * API No. 8
                 * API Name : 안양대연구소 영상 조회 API
                 * 마지막 수정 날짜 : 20.02.24
        */
        case "anyangUniversity":
            http_response_code(200);

            $area = $_GET["area"];

            $KoreanPeninsulaPm10 = 'http://www.webairwatch.com/kaq/modelimg_case4/PM10.09KM.Animation.gif';
            $KoreanPeninsulaPm2_5 = 'http://www.webairwatch.com/kaq/modelimg_case4/PM2_5.09KM.Animation.gif';

            $EastAsiaPm10 = 'http://www.webairwatch.com/kaq/modelimg_case4/PM10.27KM.Animation.gif';
            $EastAsiaPm2_5 = 'http://www.webairwatch.com/kaq/modelimg_case4/PM2_5.27KM.Animation.gif';

            if($area == 1 || $area == ''){
                $res->result->KoreaPeninsulaPm10 = $KoreanPeninsulaPm10;
                $res->result->KoreaPeninsulaPm2_5 = $KoreanPeninsulaPm2_5;
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "안양대연구소 한반도 조회 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            } else if ($area == 2){
                $res->result->EastAsiaPm10 = $EastAsiaPm10;
                $res->result->EastAsiaPm2_5 = $EastAsiaPm2_5;
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "안양대연구소 동아시아 조회 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            } else {
                $res->isSuccess = false;
                $res->code = 200;
                $res->message = "안양대연구소 조회 실패";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }
            
        /*
                 * API No. 9
                 * API Name : 일본기상청 영상 조회 API
                 * 마지막 수정 날짜 : 20.02.24
        */
        case "japanMeteorologicalAgency":   //  반환된 imgUrl 을 가공해서 보여줘야 함
            http_response_code(200);
            for($i=24; $i<70; $i+=3){ // 영상이 img 파일로 연속, imgUrl을 반환
                $res->result[] = 'https://static.tenki.jp/static-images/pm25/'. $i. '/japan-detail/large.jpg';
            }

            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "일본기상청 조회 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;
            
        /*
                 * API No. 10
                 * API Name : 시간별 예보 조회 API
                 * 마지막 수정 날짜 : 20.02.24
        */
        case "hourForecast":
            http_response_code(200);
            date_default_timezone_set('Asia/Seoul');

            $timestamp = strtotime("Now");
            $now = date("H", $timestamp);
            $end_time = $now+13;
            $max_size = 24;
            if($end_time > 23){ //  하루가 넘어가서 오전12시 부터 다시 보여야 하는 경우
                $re_time = $end_time-23;
                $i=1;
                for($j=$now+2; $j<$max_size; $j++){
                    $res->result[] = hourForecast($j);
                    $i++;
                }
                for($j=1; $j<$re_time; $j++){
                    $res->result[] = hourForecast($j);
                }
            } else {
                for($i=$now+2; $i<=$end_time; $i++){
                    $res->result[] = hourForecast($i);
                }
            }
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "시간별 예보 조회 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
                 * API No. 11
                 * API Name : 일별 예보 조회 API
                 * 마지막 수정 날짜 : 20.02.25
        */
        case "dayForecast":
            http_response_code(200);
            date_default_timezone_set('Asia/Seoul');

            $timestamp = strtotime("Now");
            $now = date("H", $timestamp);
            $day = date("Y-m-d", $timestamp);

            $yoils = array('일','월','화','수','목','금','토');
            $yoil = $yoils[date('w', strtotime($day))];

            $tmp->result = timeDistance($now, $yoil);
            $tmp_encode = json_encode($tmp->result);
            $tmp_decode = json_decode($tmp_encode);
            $row_no = $tmp_decode->no;
            $end_row = $row_no+14;
            $max_size = 21;
            if($end_row > $max_size){   //  일요일 저녁 넘어가면 월요일 아침으로
                $re_row_no = $end_row-$max_size;

                for($i=$row_no; $i<=$max_size; $i++){
                    $res->result[] = dayForecast($i);
                }
                for($j=1; $j<$re_row_no; $j++){
                    $res->result[] = dayForecast($j);
                }
            } else {
                for($i=$row_no; $i<$end_row; $i++){
                    $res->result[] = dayForecast($i);
                }
            }

            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "일별 예보 조회 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        case "deeplink":
            http_response_code(200);
            $res->result = deeplink();
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "딥 링크 조회 성공";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        case "rtdbGet":
            http_response_code(200);
            $res->result = rtdbGet();
            $res->isSuccess = TRUE;
            $res->code = 100;
            $res->message = "rtdb 조회 성공";

            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        case "rtdbPatch":
            http_response_code(200);
            $boolean_result = rtdbPatch($req->title, $req->content, $req->version);
            if($boolean_result == true){
                $res->result = "최신화 완료";
                $res->isSuccess = TRUE;
                $res->code = 100;
                $res->message = "rtdb 수정 성공";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            } else {
                $res->result = "버전을 필수로 입력해주세요.";
                $res->isSuccess = false;
                $res->code = 200;
                $res->message = "rtdb 수정 실패";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                break;
            }

    }



} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}
