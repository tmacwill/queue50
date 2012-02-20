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
        $_POST['state'] = 0;
        $_POST['ask_timestamp'] = date('Y-m-d H:i:s');
        $_POST['suite_id'] = $id;
        $_POST['student_id'] = 1;

        // add question object to database
        $question = new Question;
        $question->attributes = $_POST;
        if (!$question->save()) {
            echo json_encode(array('success' => false));
            exit;
        }

        // add associated labels
        foreach ($_POST['labels'] as $label)
            Yii::app()->db->createCommand()->insert('question_labels', array(
                'label_id' => $label,
                'question_id' => $question->id,
            ));

        // make request to live server to inform clients to refresh queue (since question has been added)
        $ch = curl_init("http://localhost:3000/questions/add/$id");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);
        curl_close($ch);

        echo json_encode(array(
            'success' => true, 
            'id' => $question->id
        ));
        exit;
    }

    /**
     * Display the load balancer
     * @param $id ID of suite to balance
     *
     */
    public function actionBalance($id) {
        // fetch all questions in the load balancer 
        $questions = $this->loadBalancer($id);

        $this->js('questions/balance.js');
        $this->render('balance', array(
            'suite_id' => $id,
            'questions' => $questions
        ));
    }

    /**
     * UI for asking a question
     * @param $id ID of suite to ask question in 
     *
     */
    public function actionCourse($id) {
        // TEMP
        $user_id = 1;

        // fetch this user's question history
        $questions = Question::model()->findAll(array(
            'condition' => 'student_id = :student_id',
            'limit' => 10,
            'order' => 'ask_timestamp desc',
            'params' => array('student_id' => $user_id)
        ));

        // get labels for this suite
        $labels = Label::model()->findAllByAttributes(array(
            'suite_id' => $id
        ));

        $this->js('questions/course.js');
        $this->render('course', array(
            'labels' => $labels,
            'questions' => $questions,
            'suite_id' => $id
        ));
    }

    /**
     * Dispatch a question to a TF
     * @param $id ID of question to dispatch
     *
     */
    public function actionDispatch($id) {
        // TEMP
        $user_id = 1;

        // update state of question
        $question = Question::model()->findByPk($id);
        $question->dispatch_timestamp = date('Y-m-d H:i:s');
        $question->state = 3;

        // persist question
        if (!$question->save()) {
            echo json_encode(array('success' => false));
            exit;
        }

        // make request to live server to inform clients to refresh queue (since question has been added)
        $ch = curl_init("http://localhost:3000/questions/dispatch/{$question->suite_id}/$id");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);
        curl_close($ch);

        // invalidate cache since a question has been removed
        $this->memcache->delete("queue?suite_id=$id");
        echo json_encode(array('success' => true));
        exit;
    }

    /**
     * Fetch the load balancer for a suite
     * @param $id ID of suite to fetch load balancer for
     * 
     */
    public function actionLoadBalancer($id) {
        echo CJSON::encode($this->loadBalancer($id));
        exit;
    }

    /**
     * Fetch the queue for a suite
     * @param $id ID of suite to fetch queue for
     * 
     */
    public function actionQueue($id) {
        $questions = $this->queue($id);
        echo $this->json('questions', $questions['questions'], 
            'id, suite_id, student_id, staff_id, title, question, anonymous, ask_timestamp, state, labels, student');
        exit;
    }

    /**
     * Send a question to help
     *
     */
    public function actionSendToHelp() {
        // make sure id is given
        if (!isset($_POST['id'])) {
            echo json_encode(array('success' => false));
            exit;
        }

        // update state of question
        $id = $_POST['id'];
        $question = Question::model()->findByPk($id);
        $question->action_timestamp = date('Y-m-d H:i:s');
        $question->state = 2;

        // persist question
        if (!$question->save()) {
            echo json_encode(array('success' => false));
            exit;
        }

        // inform live server question has been sent to help
        $ch = curl_init("http://localhost:3000/questions/toHelp/{$question->suite_id}/$id");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);
        curl_close($ch);

        echo json_encode(array('success' => true));
        exit;
    }

    /**
     * Send a question to the queue
     *
     */
    public function actionSendToQueue() {
        // make sure id is given
        if (!isset($_POST['id'])) {
            echo json_encode(array('success' => false));
            exit;
        }

        // update state of question
        $id = $_POST['id'];
        $question = Question::model()->findByPk($id);
        $question->action_timestamp = date('Y-m-d H:i:s');
        $question->state = 1;

        // persist question
        if (!$question->save()) {
            echo json_encode(array('success' => false));
            exit;
        }

        // inform live server question has been sent to queue
        $ch = curl_init("http://localhost:3000/questions/toQueue/{$question->suite_id}/$id");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);
        curl_close($ch);

        // invalidate cache since a new question has been added
        $this->memcache->delete("queue?suite_id={$question->suite_id}");

        echo json_encode(array('success' => true));
        exit;
    }

    /**
     * Get the load balance questions for a suite
     * @param $id ID of suite to get load balancer for
     * @return Array of questions in the load balancer
     *
     */
    public function loadBalancer($id) {
        // fetch all questions in the load balancer from within 48 hours
        $questions = Question::model()->findAll(array(
            'condition' => 'suite_id = :suite_id AND state = :state AND ask_timestamp >= :yesterday ' . 
                'AND ask_timestamp <= :tomorrow',
            'order' => 'ask_timestamp asc',
            'params' => array(
                'suite_id' => $id,
                'state' => 0,
                'yesterday' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'tomorrow' => date('Y-m-d H:i:s', strtotime('+1 day'))
            )
        ));

        return $questions;
    }

    /**
     * Get the queue for a suite
     * @param $id ID of suite to get queue for
     * @return Array of questions in the queue
     *
     */
    public function queue($id) {
        // check cache for questions and return if not empty
        $questions = $this->memcache->get("queue?suite_id=$id");
        if ($questions !== false && $questions !== null) 
            return array('questions' => $questions, 'changed' => false);

        // fetch all questions in the queue from within 48 hours
        $questions = Question::model()->with('labels')->findAll(array(
            'condition' => 't.suite_id = :suite_id AND state = :state AND ask_timestamp >= :yesterday ' . 
                'AND ask_timestamp <= :tomorrow',
            'order' => 'ask_timestamp asc',
            'params' => array(
                'suite_id' => $id,
                'state' => 1,
                'yesterday' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'tomorrow' => date('Y-m-d H:i:s', strtotime('+1 day'))
            ),
        ));

        // get all students who have asked a question
        $student_ids = array_map(function($e) { return $e->student_id; }, $questions);
        $students = $this->cs50->users($student_ids);

        // associate students with question
        $result = array();
        foreach ($questions as &$question) {
            $q = $question->attributes;
            $q['student'] = $students[$question->student_id];
            $result[] = $q;
        }

        // cache and return questions
        $this->memcache->set("queue?suite_id=$id", $result);
        return array('questions' => $result, 'changed' => true);
    }
}
