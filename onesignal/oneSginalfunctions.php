<?php
require_once('vendor/autoload.php');
require_once('Connections/coop.php');

//use DateTime;
use onesignal\client\api\DefaultApi;
use onesignal\client\Configuration;
use onesignal\client\model\GetNotificationRequestBody;
use onesignal\client\model\Notification;
use onesignal\client\model\StringMap;
use onesignal\client\model\Player;
use onesignal\client\model\UpdatePlayerTagsRequestBody;
use onesignal\client\model\ExportPlayersRequestBody;
use onesignal\client\model\Segment;
use onesignal\client\model\FilterExpressions;
use PHPUnit\Framework\TestCase;
//use GuzzleHttp;

const APP_ID = '181231b5-d452-401c-b0c3-ab268a379b32';
const APP_KEY_TOKEN = 'YzFlYTE2YWEtZTk4Ni00MTgzLTgwOGQtMmI4ZDQ0MTIzNzE0';
//const USER_KEY_TOKEN = '<YOUR_USER_KEY_TOKEN>';

$config = Configuration::getDefaultConfiguration()
    ->setAppKeyToken(APP_KEY_TOKEN);
//  ->setUserKeyToken(USER_KEY_TOKEN);

$apiInstance = new DefaultApi(
    new GuzzleHttp\Client(),
    $config
);


function createNotificationGeneral($enContent): Notification
{
    global $coop;
    global $database_coop;

    mysqli_select_db($coop, $database_coop);
    $sql = "INSERT INTO tbl_notification (subject,message,coop_id,date) VALUES ('Notification','{$enContent}',-1,now())";
    $query = mysqli_query($coop, $sql);


    $content = new StringMap();
    $content->setEn($enContent);

    $notification = new Notification();
    $notification->setAppId(APP_ID);

    $notification->setContents($content);
    $notification->setIncludedSegments(['Subscribed Users']);
    //$notification->setIncludePlayerIds(['e30bb2e6-2429-4eed-99c7-cc2ca1c7a4ad']);

    return $notification;
}

function createNotificationPlayer($enContent, $player, $coopid): Notification
{

    global $coop;
    global $database_coop;

    mysqli_select_db($coop, $database_coop);
    $sql = "INSERT INTO tbl_notification (subject,message,coop_id,date) VALUES ('Notification','{$enContent}','{$coopid}',now())";
    $query = mysqli_query($coop, $sql);


    $content = new StringMap();
    $content->setEn($enContent);

    $notification = new Notification();
    $notification->setAppId(APP_ID);

    $notification->setContents($content);

    //$notification->setIncludePlayerIds(['e30bb2e6-2429-4eed-99c7-cc2ca1c7a4ad']);
    $notification->setIncludePlayerIds([$player]);
    return $notification;
}


