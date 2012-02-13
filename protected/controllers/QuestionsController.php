<?php

require_once 'protected/controllers/BaseController.php';

class QuestionsController extends BaseController {
    var $model = 'Question';
    private $memcache;

    public function __construct($id, $module = null) {
        parent::__construct($id, $module);

        // connect to memcache
        $this->memcache = new Memcache;
        $this->memcache->connect('localhost', 11211);

        // controller-wide css
        $this->css('questions.css');

        // controller-wide js
        $this->js('lib/socket.io.min.js');
    }

    /**
     * Add a question to a suite
     * @param $id ID of suite
     *
     */
    public function actionAdd($id) {
        // set question defaults
        $_POST['anonymous'] = 0;
        $_POST['answered'] = 0;
        $_POST['ask_timestamp'] = date('Y-m-d H:i:s');
        $_POST['suite_id'] = $id;
        $_POST['student_id'] = 1;

        unset($_POST['label']);

        // add question object to database
        parent::actionAdd();

        // TODO: add to question_labels table

        // make request to live server to inform clients to refresh queue (since question has been added)
        $ch = curl_init("http://localhost:3000/questions/add/$id");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);
        curl_close($ch);
    }

    public function actionCourse($id) {
        // TEMP
        $user_id = 1;

        $questions = Question::model()->findAll(array(
            'condition' => 'student_id = :student_id',
            'limit' => 10,
            'order' => 'ask_timestamp desc',
            'params' => array('student_id' => $user_id)
        ));

        $this->js('questions/course.js');
        $this->render('course', array(
            'suite_id' => $id,
            'questions' => $questions
        ));
    }

    /**
     * Fetch the queue for a suite
     * @param $id ID of suite to fetch queue for
     * 
     */
    public function actionQueue($id) {
        // check cache for questions and return if not empty
        $questions = $this->memcache->get("queue?suite_id=$id");
        if ($questions !== false && $questions !== null) {
            echo CJSON::encode($questions);
            exit;
        }

        // calculate timestamps for today and tomorrow to limit search
        $yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));
        $tomorrow = date('Y-m-d H:i:s', strtotime('+1 day'));

        // fetch all unanswered questions from within 48 hours
        $questions = Question::model()->findAll(array(
            'condition' => 'suite_id = :suite_id AND answered = :answered AND ask_timestamp >= :yesterday ' . 
                'AND ask_timestamp <= :tomorrow',
            'order' => 'ask_timestamp desc',
            'params' => array(
                'suite_id' => $id,
                'answered' => 0,
                'yesterday' => $yesterday,
                'tomorrow' => $tomorrow
            )
        ));

        // cache and return questions
        $this->memcache->set("queue?suite_id=$id", $questions);
        echo CJSON::encode($questions);
        exit;
    }
}
