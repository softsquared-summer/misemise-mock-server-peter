<?php
require './pdos/DatabasePdo.php';
require './pdos/IndexPdo.php';
require './vendor/autoload.php';

use \Monolog\Logger as Logger;
use Monolog\Handler\StreamHandler;

date_default_timezone_set('Asia/Seoul');
ini_set('default_charset', 'utf8mb4');

//에러출력하게 하는 코드
//error_reporting(E_ALL); ini_set("display_errors", 1);

//Main Server API
$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    /* ******************   MiseMise   ****************** */
    $r->addRoute('GET', '/', ['IndexController', 'index']);
    $r->addRoute('GET', '/locationSearch', ['IndexController', 'locationSearch']);  // 즐겨찾기에서 읍/면/동 검색 API

    $r->addRoute('GET', '/favorite', ['IndexController', 'favoriteGet']);   //  즐겨찾기 조회 API
    $r->addRoute('POST', '/favorite', ['IndexController', 'favoritePost']); //  즐겨찾기 추가 API
    $r->addRoute('DELETE', '/favorite', ['IndexController', 'favoriteDelete']); //  즐겨찾기 삭제 API

    $r->addRoute('GET', '/dust/value', ['IndexController', 'dustValue']);   //    즐겨찾기 해당 동네 이름 or GPS의 x, y 기준으로 측정 값 조회 API
    $r->addRoute('GET', '/dust/grade', ['IndexController', 'dustGrade']);   //    즐겨찾기 해당 동네 이름 or GPS의 x, y 기준으로 측정 등급 조회 API
    $r->addRoute('GET', '/dust/etc', ['IndexController', 'dustEtc']);   //    즐겨찾기 해당 동네 이름 or GPS의 x, y 기준으로 기타 사항 조회 API

    $r->addRoute('GET', '/map', ['IndexController', 'map']);    //  모든 측정소 미세먼지, 초미세먼지, 현재 등급 조회 API (지도)
    $r->addRoute('GET', '/map/{mapNo}', ['IndexController', 'mapDetail']);  //  해당 측정소 미세먼지, 초미세먼지, 현재 등급 조회 API (지도)

    $r->addRoute('GET', '/notice', ['IndexController', 'notice']);  //  공지사항 조회 API (팝업)

    $r->addRoute('GET', '/anyangUniversity', ['IndexController', 'anyangUniversity']);  //  안양대연구소 영상 조회 API
});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

// 로거 채널 생성
$accessLogs = new Logger('ACCESS_LOGS');
$errorLogs = new Logger('ERROR_LOGS');
// log/your.log 파일에 로그 생성. 로그 레벨은 Info
$accessLogs->pushHandler(new StreamHandler('logs/access.log', Logger::INFO));
$errorLogs->pushHandler(new StreamHandler('logs/errors.log', Logger::ERROR));
// add records to the log
//$log->addInfo('Info log');
// Debug 는 Info 레벨보다 낮으므로 아래 로그는 출력되지 않음
//$log->addDebug('Debug log');
//$log->addError('Error log');

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // ... 404 Not Found
        echo "404 Not Found";
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        echo "405 Method Not Allowed";
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        switch ($routeInfo[1][0]) {
            case 'IndexController':
                $handler = $routeInfo[1][1];
                $vars = $routeInfo[2];
                require './controllers/IndexController.php';
                break;
            case 'MainController':
                $handler = $routeInfo[1][1];
                $vars = $routeInfo[2];
                require './controllers/MainController.php';
                break;
            /*case 'EventController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/EventController.php';
                break;
            case 'ProductController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/ProductController.php';
                break;
            case 'SearchController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/SearchController.php';
                break;
            case 'ReviewController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/ReviewController.php';
                break;
            case 'ElementController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/ElementController.php';
                break;
            case 'AskFAQController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/AskFAQController.php';
                break;*/
        }

        break;
}
