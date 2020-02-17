<?php
/* ******************   MiseMise   ****************** */
function location_search($location) // 주소 검색 - KakaoAPI
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
