<?php
/** 
 * Class to create short URLs and decode shortened URLs
 * 
 * @author CodexWorld.com <contact@codexworld.com> 
 * @copyright Copyright (c) 2018, CodexWorld.com
 * @url https://www.codexworld.com
 */ 

/* This library is also exits on feedback project which is used  to get long url relevant to short url */
/** 
 * -----------------------------------------------------------------------------
 * if update this library please also update the library file in feedback 
 * ------------------------------------------------------------------------------
 **/
/*In this project this library used to create short url. */

class Shortener
{
    protected static $chars = "abcdfghjkmnpqrstvwxyz|ABCDFGHJKLMNPQRSTVWXYZ|0123456789";
    protected static $table = "short_urls";
    protected static $checkUrlExists = false;
    protected static $codeLength = 7;

    protected $pdo;
    protected $timestamp;

    public function __construct(PDO $pdo=null){
        $this->pdo = $pdo;
        $this->timestamp = date("Y-m-d H:i:s");
    }

    public function urlToShortCode($url, $keepTime, $epoint, $e_point_unique_code, $channel_id, $stage_id, $unique_reference, $cus_mobile){
        if(empty($url)){
            throw new Exception("No URL was supplied.");
        }

        if($this->validateUrlFormat($url) == false){
            throw new Exception("URL does not have a valid format.");
        }

        if(self::$checkUrlExists){
            if (!$this->verifyUrlExists($url)){
                throw new Exception("URL does not appear to exist.");
            }
        }

        $shortCode = $this->urlExistsInDB($url);
        if($shortCode == false){
            $shortCode = $this->createShortCode($url, $keepTime, $epoint, $e_point_unique_code, $channel_id, $stage_id, $unique_reference, $cus_mobile);
        }

        return $shortCode;
    }

    protected function validateUrlFormat($url){
        return filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED);
    }

    protected function verifyUrlExists($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch,  CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return (!empty($response) && $response != 404);
    }

    protected function urlExistsInDB($url){
        $query = "SELECT short_code FROM ".self::$table." WHERE long_url = :long_url LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $params = array(
            "long_url" => $url
        );
        $stmt->execute($params);

        $result = $stmt->fetch();
        return (empty($result)) ? false : $result["short_code"];
    }

    protected function createShortCode($url, $keepTime, $epoint, $e_point_unique_code, $channel_id, $stage_id, $unique_reference, $cus_mobile){
        $shortCode = $this->generateRandomString(self::$codeLength);
        $id = $this->insertUrlInDB($url, $shortCode, $keepTime, $epoint, $e_point_unique_code, $channel_id, $stage_id, $unique_reference, $cus_mobile);
        return $shortCode;
    }
    
    protected function generateRandomString($length = 6){
        $sets = explode('|', self::$chars);
        $all = '';
        $randString = '';
        foreach($sets as $set){
            $randString .= $set[array_rand(str_split($set))];
            $all .= $set;
        }
        $all = str_split($all);
        for($i = 0; $i < $length - count($sets); $i++){
            $randString .= $all[array_rand($all)];
        }
        $randString = str_shuffle($randString);
        return $randString;
    }

    protected function insertUrlInDB($url, $code, $keepTime, $epoint, $e_point_unique_code, $channel_id, $stage_id, $unique_reference, $cus_mobile){
        $query = "INSERT INTO ".self::$table." (long_url, short_code, created, keep_time, e_point_id, e_point_unique_code, channel_id , stage_id , unique_reference,cus_mobile) VALUES (:long_url, :short_code, :timestamp, :keep_time, :epoint, :e_point_unique_code, :channel_id, :stage_id, :unique_reference, :cus_mobile)";
        $stmnt = $this->pdo->prepare($query);
        $params = array(
            "long_url" => $url,
            "short_code" => $code,
            "timestamp" => $this->timestamp,
            "keep_time" => $keepTime,
            "epoint" => $epoint,
            "e_point_unique_code" => $e_point_unique_code,
            "channel_id" => $channel_id,
            "cus_mobile" => $cus_mobile,
            "stage_id" => $stage_id,
            "unique_reference" => $unique_reference
        );
        $stmnt->execute($params);

        return $this->pdo->lastInsertId();
    }
    
    public function shortCodeToUrl($code, $increment = true){
        if(empty($code)) {
            throw new Exception("No short code was supplied.");
        }

        if($this->validateShortCode($code) == false){
            throw new Exception("Short code does not have a valid format.");
        }

        $urlRow = $this->getUrlFromDB($code);
        if(empty($urlRow)){
            throw new Exception("Short code does not appear to exist.");
        }

        $urlExpried = $this->expireUrl($urlRow["keep_time"],$urlRow["created"]);

        if($increment == true){
            $this->incrementCounter($urlRow["id"]);
        }
        
        if($urlExpried){
            return false;
        }else{
            return $urlRow["long_url"];
        }
    }

    protected function validateShortCode($code){
        $rawChars = str_replace('|', '', self::$chars);
        return preg_match("|[".$rawChars."]+|", $code);
    }

    protected function getUrlFromDB($code){
        $query = "SELECT id, long_url, keep_time,created FROM ".self::$table." WHERE short_code = :short_code LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $params=array(
            "short_code" => $code
        );
        $stmt->execute($params);

        $result = $stmt->fetch();
        return (empty($result)) ? false : $result;
    }

    protected function incrementCounter($id){
        $query = "UPDATE ".self::$table." SET hits = hits + 1 WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $params = array(
            "id" => $id
        );
        $stmt->execute($params);
    }

    protected function expireUrl($keep_time, $created){

        $d1 = strtotime($created);
        $d2 = strtotime($this->timestamp);  

        $totalSecondsDiff = abs($d1-$d2);
        $totalMinutesDiff = $totalSecondsDiff/60;
        if($totalMinutesDiff > $keep_time){
            return true;
        }else{
            return false;
        }

    }
}