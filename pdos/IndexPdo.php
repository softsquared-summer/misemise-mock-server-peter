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

function favoritePost($region_2depth_name, $region_3depth_name, $tm_x, $tm_y)   //  즐겨찾기 추가
{

    $pdo = pdoSqlConnect();
    $query = "INSERT into favorites (region_2depth_name, region_3depth_name, tm_x, tm_y)  VALUES (?,?,?,?);";
    $st = $pdo->prepare($query);
    $st->execute([$region_2depth_name, $region_3depth_name, $tm_x, $tm_y]);

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

    $count = $st->rowCount();   //  DELETE 에 영향을 받는 rows 개수를 파악하여 0보다 크면 true, 그 외 false 리턴
    if ($count > 0) {
        return true;
    } else {
        return false;
    }

    $setAutoIncrementQuery1 = "ALTER TABLE favorites AUTO_INCREMENT=1;";    //  auto_increment 변수 1로 설정
    $setAutoIncrementQuery2 = "SET @COUNT = 0;";
    $setAutoIncrementQuery3 = "UPDATE favorites SET no = @COUNT:=@COUNT+1;";    //  favorites 테이블 재정렬
    $st = $pdo->prepare($setAutoIncrementQuery1);
    $st->execute();
    $st = $pdo->prepare($setAutoIncrementQuery2);
    $st->execute();
    $st = $pdo->prepare($setAutoIncrementQuery3);
    $st->execute();
    $st = $pdo->prepare($setAutoIncrementQuery1);   //  추후에 추가될 즐겨찾기를 위해서 auto_increment 변수 1로 설정
    $st->execute();

    $st = null;
    $pdo = null;
}