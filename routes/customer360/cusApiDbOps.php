<?php 
//error_reporting(0);

    class cusApiDbOps{

        private $con; 

        function __construct(){

            $db = new DB($_ENV['DB_HOST'],$_ENV['DB_USERNAME'],$_ENV['DB_PASSWORD'],$_ENV['DB_NAME']);
            $this->con = $db->connect();              
		
        }

        public function getFeedbackFromReferenceId($referenceId){
                
            $sql =" SELECT  cs_feedback.id AS feedback_id, cs_feedback.reference_id AS feedback_reference_id ,
                            cs_engagement_point.e_point AS engagement_point,  cs_feedback.date AS feedback_date, 
                            cs_feedback.cus_mobile AS customer_mobile,
                            ratingQuiz.quiz AS rating_quiz, cs_quiz_type.quiz_type AS rating_answer, 
                            satisfactionQuiz.quiz AS satisfaction_level_quiz, 
                            cs_answer_pool.answer AS satisfaction_level_answer, 
                            recommandationQuiz.quiz AS recommendation_quiz,
                            cs_feedback.recommendation_answer_id AS recommendation_answer
                    FROM  cs_feedback
                    INNER JOIN cs_engagement_point
                    ON cs_feedback.number_of_quiz_assign_id = cs_engagement_point.id
                    INNER JOIN cs_quiz_pool ratingQuiz
                    ON cs_feedback.rating_quiz_id = ratingQuiz.id
                    INNER JOIN cs_quiz_pool satisfactionQuiz
                    ON cs_feedback.satisfaction_level_quiz_id = satisfactionQuiz.id
                    INNER JOIN cs_quiz_pool recommandationQuiz
                    ON cs_feedback.recommendation_quiz_id = recommandationQuiz.id
                    INNER JOIN cs_quiz_type 
                    ON cs_feedback.rating_answer_id = cs_quiz_type.id
                    INNER JOIN (
                            quiz_answers
                            INNER JOIN cs_answer_pool
                            ON quiz_answers.answer_id = cs_answer_pool.id
                            )
                    ON cs_feedback.satisfaction_level_answer_id = quiz_answers.id
                    WHERE cs_feedback.reference_id = ?";	

            $con_comp=  $this->con->prepare($sql);
            
            if($con_comp->execute([$referenceId]))
            {

                $feedback = $con_comp->fetch(PDO::FETCH_ASSOC);
                return $feedback;
                   
            }
            else
            {

               return FAILED;

            }

			
        }

       
    }