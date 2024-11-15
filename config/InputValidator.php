<?php

class InputValidator
{

    protected $pdo;

    public function __construct(PDO $pdo=null){
        $this->pdo = $pdo;
    }


    public function validteEngagementPoint($ep,$stg,$epc){
        $query = "SELECT * FROM cs_engagement_point WHERE id = :id and stage_id = :stage_id AND unique_code =:unique_code ";
        $stmt = $this->pdo->prepare($query);
        $params = array(
            "id"    => $ep,
            "stage_id" => $stg,
            "unique_code" => $epc
        );
        $stmt->execute($params);

        $result = $stmt->fetch();
        return (empty($result)) ? false : $result["id"];
    }


    public function validteChannel($chid){
        $query = "SELECT * FROM cs_channel WHERE id = :id ";
        $stmt = $this->pdo->prepare($query);
        $params = array(
            "id"    => $chid
        );
        $stmt->execute($params);

        $result = $stmt->fetch();
        return (empty($result)) ? false : $result["id"];
    }


    public function validteRef($ref,$ep ){
        $query = "SELECT * FROM cs_feedback WHERE number_of_quiz_assign_id = :id AND reference_id = :reference_id";
        $stmt = $this->pdo->prepare($query);
        $params = array(
            "id"    => $ep,
            "reference_id" => $ref
        );
        $stmt->execute($params);

        $result = $stmt->fetch();
        return (empty($result)) ? false : $result["id"];
    }

    public function validteEngagementPointStatus($ep){
        $query = "SELECT * FROM cs_engagement_point WHERE id = :id and status = :status";
        $stmt = $this->pdo->prepare($query);
        $params = array(
            "id"    => $ep,
            "status" => 1
        );
        $stmt->execute($params);

        $result = $stmt->fetch();
        return (empty($result)) ? false : $result["id"];
    }

}