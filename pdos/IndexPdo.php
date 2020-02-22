<?php
/* ******************   MiseMise   ****************** */
function locationSearch($location) // 주소 검색 - KakaoAPI
{
    $path = "/v2/local/search/address.json";
    $api_server = 'https://dapi.kakao.com';
    $headers = array('Authorization: KakaoAK 0ffbef86df8174ccb10697480464f8dc ');
    $max_size = 30; // 카카오 API 주소 검색 최대치 : 30
    $opts = array(CURLOPT_URL => $api_server.$path."?query=".urlencode($location)."&size=".$max_size,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSLVERSION => true,
        CURLOPT_HEADER => false,
        CURLOPT_HTTPHEADER => $headers);
    $ch = curl_init();
    curl_setopt_array($ch, $opts);

    $response = curl_exec ($ch);

    curl_close ($ch);
    return $response;

}

function favoriteGet()  //  즐겨찾기 조회
{
    $pdo = pdoSqlConnect();
    $query = "SELECT no,
       region_2depth_name,
       region_3depth_name,
       tm_x,
       tm_y
FROM favorites;";

    $st = $pdo->prepare($query);
    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}

function favoritePost($region_2depth_name, $region_3depth_name, $tm_x, $tm_y)   //  즐겨찾기 추가
{

    $pdo = pdoSqlConnect();
    $query = "INSERT into favorites (region_2depth_name, region_3depth_name, tm_x, tm_y)  VALUES (?,?,?,?);";
    $st = $pdo->prepare($query);
    $st->execute([$region_2depth_name, $region_3depth_name, $tm_x, $tm_y]);

    setAutoIncrement();

    $st = null;
    $pdo = null;
}

function favoriteCnt()  //  즐겨찾기 수 6개 제한을 위한 함수
{
    $pdo = pdoSqlConnect();
    $query = "SELECT count(*) FROM favorites";

    $st = $pdo->prepare($query);
    $st->execute();
    return $st->fetchColumn();
}

function favoriteDelete($favoriteNo)    //  즐겨찾기 삭제
{
    $pdo = pdoSqlConnect();
    $query = "DELETE FROM favorites where no = ?;";
    $st = $pdo->prepare($query);
    $st->execute([$favoriteNo]);
    $count = $st->rowCount();   //  DELETE 에 영향을 받는 rows 개수를 파악

    setAutoIncrement();

    if ($count > 0) {
        return true;
    } else {
        return false;
    }

    $st = null;
    $pdo = null;
}

function setAutoIncrement() // auto_increment 변수 1로 설정 후 favorites 테이블 번호를 새로 부여하고 재정렬
{
    $pdo = pdoSqlConnect();
    $setAutoIncrementQuery1 = "ALTER TABLE favorites AUTO_INCREMENT=1;";
    $setAutoIncrementQuery2 = "SET @COUNT = 0;";
    $setAutoIncrementQuery3 = "UPDATE favorites SET no = @COUNT:=@COUNT+1;";
    $st = $pdo->prepare($setAutoIncrementQuery1);
    $st->execute();
    $st = $pdo->prepare($setAutoIncrementQuery2);
    $st->execute();
    $st = $pdo->prepare($setAutoIncrementQuery3);
    $st->execute();
    $st = $pdo->prepare($setAutoIncrementQuery1);
    $st->execute();
}


function transFormation($tm_x, $tm_y)   //  좌표계 WGS84 를 TM 으로 변환
{
    //https://dapi.kakao.com/v2/local/geo/transcoord.json?x=160710.37729270622&y=-4388.879299157299&input_coord=WTM&output_coord=WGS84
    $path = "/v2/local/geo/transcoord.json";
    $api_server = 'https://dapi.kakao.com';
    $coord = "&input_coord=WGS84&output_coord=TM";
    $headers = array('Authorization: KakaoAK 0ffbef86df8174ccb10697480464f8dc');

    $opts = array(CURLOPT_URL => $api_server.$path."?x=".$tm_x."&y=".$tm_y.$coord,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSLVERSION => true,
        CURLOPT_HEADER => false,
        CURLOPT_HTTPHEADER => $headers);
    $ch = curl_init();
    curl_setopt_array($ch, $opts);


    $response = curl_exec ($ch);

    curl_close ($ch);
    return $response;
}

function FindLocation($tm_x, $tm_y) //  x,y 값을 행정구역정보로 변환
{
    $path = "/v2/local/geo/coord2regioncode.json";
    $api_server = 'https://dapi.kakao.com';
    $headers = array('Authorization: KakaoAK 0ffbef86df8174ccb10697480464f8dc');

    $opts = array(CURLOPT_URL => $api_server.$path."?x=".$tm_x."&y=".$tm_y,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSLVERSION => true,
        CURLOPT_HEADER => false,
        CURLOPT_HTTPHEADER => $headers);
    $ch = curl_init();
    curl_setopt_array($ch, $opts);

    $response = curl_exec ($ch);

    curl_close ($ch);
    return $response;
}

function findNearStation($tm_x, $tm_y)  //  가까운 측정소 3개 검색
{
    //http://openapi.airkorea.or.kr/openapi/services/rest/MsrstnInfoInqireSvc/getNearbyMsrstnList?tmX=210895.593623738&tmY=411629.47873038985&ServiceKey=5SLS29uFgnvXyqTaiULbagIAgjy82u6Gd%2BZOOumtbOPC7K9JoS%2B4Vg10CR5I%2BA019DHMRccq1x%2B8DnBdMA%2B7bA%3D%3D&_returnType=json
    $api_server = 'http://openapi.airkorea.or.kr/openapi/services/rest/MsrstnInfoInqireSvc/getNearbyMsrstnList';
    $key = '5SLS29uFgnvXyqTaiULbagIAgjy82u6Gd%2BZOOumtbOPC7K9JoS%2B4Vg10CR5I%2BA019DHMRccq1x%2B8DnBdMA%2B7bA%3D%3D';
    $type = "&_returnType=json";

    $opts = array(CURLOPT_URL => $api_server."?tmX=".$tm_x."&tmY=".$tm_y."&ServiceKey=".$key.$type,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSLVERSION => true,
        CURLOPT_HEADER => false);

    $ch = curl_init();
    curl_setopt_array($ch, $opts);


    $response = curl_exec ($ch);

    curl_close ($ch);
    return $response;
}

function fineDust($stationName) //  측정소 이름으로 검색하여 상세 조회
{
    $api_server = 'http://openapi.airkorea.or.kr/openapi/services/rest/ArpltnInforInqireSvc/getMsrstnAcctoRltmMesureDnsty';
    $key = '5SLS29uFgnvXyqTaiULbagIAgjy82u6Gd%2BZOOumtbOPC7K9JoS%2B4Vg10CR5I%2BA019DHMRccq1x%2B8DnBdMA%2B7bA%3D%3D';
    $term = "month";
    $ver = "1.3";
    $type = "&_returnType=json";

    $opts = array(CURLOPT_URL => $api_server."?stationName=".$stationName."&dataTerm=".$term."&ServiceKey=".$key."&ver=".$ver.$type,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSLVERSION => true,
        CURLOPT_HEADER => false);

    $ch = curl_init();
    curl_setopt_array($ch, $opts);


    $response = curl_exec ($ch);

    curl_close ($ch);
    return $response;
}

function getXY($favoriteNo) //  즐겨찾기 번호의 x, y 좌표 반환
{
    $pdo = pdoSqlConnect();
    $query = "SELECT no,
       region_2depth_name,
       region_3depth_name,
       tm_x,
       tm_y
FROM favorites
where no = ?;";
    $st = $pdo->prepare($query);
    //    $st->execute([$param,$param]);
    $st->execute([$favoriteNo]);
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}

function StationValue($json_result, $station_result, $target){
    $Checking = "점검중";
    if(($json_result->list[0]->$target) != '-' && ($json_result->list[0]->$target) != ''){
        return $json_result->list[0]->$target;
    } else {    //  '-' 일 경우 다음으로 가까운 측정소에서 탐색
        $nextStationName = $station_result->list[1]->stationName;
        $nextResult = fineDust($nextStationName);
        $next_result = json_decode($nextResult);
        if(($next_result->list[0]->$target) != '-' && ($json_result->list[0]->$target) != ''){
            return $next_result->list[0]->$target;
        } else {    //  '-' 일 경우 다음으로 가까운 측정소에서 탐색
            $nextStationName = $station_result->list[2]->stationName;
            $nextResult2 = fineDust($nextStationName);
            $next_result2 = json_decode($nextResult2);
            if(($next_result2->list[0]->$target) != '-' & ($json_result->list[0]->$target) != ''){
                return $next_result2->list[0]->$target;
            } else {
                return $Checking;
            }
        }
    }
}

function StationGrade($json_result, $station_result, $target){    //  target의 grade 반환
    $Checking = "점검중";
    if(($json_result->list[0]->$target) != ''){
        return $json_result->list[0]->$target;
    } else {    //  '-' 일 경우 다음으로 가까운 측정소에서 탐색
        $nextStationName = $station_result->list[1]->stationName;
        $nextResult = fineDust($nextStationName);
        $next_result = json_decode($nextResult);
        if(($next_result->list[0]->$target) != ''){
            return $next_result->list[0]->$target;
        } else {    //  '-' 일 경우 다음으로 가까운 측정소에서 탐색
            $nextStationName = $station_result->list[2]->stationName;
            $nextResult2 = fineDust($nextStationName);
            $next_result2 = json_decode($nextResult2);
            if(($next_result2->list[0]->$target) != ''){
                return $next_result2->list[0]->$target;
            } else {
                return $Checking;
            }
        }
    }
}

function StationName($json_result, $station_result, $target){ //  target의 station name 반환
    $Checking = "점검중";
    if(($json_result->list[0]->$target) != '-'){
        return $station_result->list[0]->stationName;
    } else {    //  '-' 일 경우 다음으로 가까운 측정소에서 탐색
        $nextStationName = $station_result->list[1]->stationName;
        $nextResult = fineDust($nextStationName);
        $next_result = json_decode($nextResult);
        if(($next_result->list[0]->$target) != '-'){
            return $station_result->list[1]->stationName;
        } else {    //  '-' 일 경우 다음으로 가까운 측정소에서 탐색
            $nextStationName = $station_result->list[2]->stationName;
            $nextResult2 = fineDust($nextStationName);
            $next_result2 = json_decode($nextResult2);
            if(($next_result2->list[0]->$target) != '-'){
                return $station_result->list[2]->stationName;
            } else {
                return $Checking;
            }
        }
    }
}

function StationMang($json_result, $station_result, $target){ //  target의 station mang name 반환
    $Checking = "점검중";
    if(($json_result->list[0]->$target) != '-' && ($json_result->list[0]->mangName) != ''){
        return $json_result->list[0]->mangName;
    } else {    //  '-' 일 경우 다음으로 가까운 측정소에서 탐색
        $nextStationName = $station_result->list[1]->stationName;
        $nextResult = fineDust($nextStationName);
        $next_result = json_decode($nextResult);
        if(($next_result->list[0]->$target) != '-' && ($next_result->list[0]->mangName) != ''){
            return $next_result->list[0]->mangName;
        } else {    //  '-' 일 경우 다음으로 가까운 측정소에서 탐색
            $nextStationName = $station_result->list[2]->stationName;
            $nextResult2 = fineDust($nextStationName);
            $next_result2 = json_decode($nextResult2);
            if(($next_result2->list[0]->$target) != '-' && ($next_result2->list[0]->mangName) != ''){
                return $next_result2->list[0]->mangName;
            } else {
                return $Checking;
            }
        }
    }
}

function map()  //  데이터베이스에서 모든 측정소의 정보를 가져옴
{
    $pdo = pdoSqlConnect();
    $query = "Select no,
                    station_name,
                    x,
                    y
            from station;";
    $st = $pdo->prepare($query);
    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res;
}


function MapValue($json_result, $target)    //  측정소의 미세먼지, 초미세먼지, 등급을 반환
{
    $Checking = "점검중";
    if (($json_result->list[0]->$target) != '-' && ($json_result->list[0]->$target) != '') {
        return $json_result->list[0]->$target;
    } else {    //  '-' 또는 ' ' 일 경우 점검중
        return $Checking;
    }
}

function mapDetail($mapNo)
{
    $pdo = pdoSqlConnect();
    $query = "Select no,
                    station_name,
                    x,
                    y
            from station
            where no = ?;";

    $st = $pdo->prepare($query);
    $st->execute([$mapNo]);
    //    $st->execute();
    $st->setFetchMode(PDO::FETCH_ASSOC);
    $res = $st->fetchAll();

    $st = null;
    $pdo = null;

    return $res[0];
}