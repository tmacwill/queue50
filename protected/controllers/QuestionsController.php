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
     *
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

        // relay request to load balancer through the live server
        $this->notifyLive("questions/add/$id");
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
     * 
     * @param $id Array of IDs of questions to dispatch
     *
     */
    public function actionDispatch() {
        // TEMP
        $user_id = 1;

        // make sure questions have been specified
        $question_ids = explode(',', $_POST['ids']);
        if (empty($question_ids)) {
            echo json_encode(array('success' => false));
            exit;
        }

        // assign questions to staff
        $staff_id = $_POST['staff_id'];
        Yii::app()->db->createCommand()->update('questions', array(
            'dispatch_timestamp' => date('Y-m-d H:i:s'),
            'staff_id' => $staff_id,
            'state' => 3
        ), array('in', 'id', $question_ids));

        // relay dispatch notifications through live server
        $question_ids = implode(',', $question_ids);
        $question = Question::model()->findByPk($question_ids[0]);
        $this->notifyLive("questions/dispatch/{$question->suite_id}/$question_ids");

        // invalidate cache since a question has been removed
        $this->memcache->delete("queue?suite_id={$question->suite_id}");
        echo json_encode(array('success' => true));
        exit;
    }

    /**
     * Get a single question
     *
     */
    public function actionGet($id) {
        // make sure object exists
        if (!Question::model()->exists('id = :id', array(':id' => $id))) {
            echo json_encode(array('success' => false));
            exit;
        }

        // get object from database
        $question = Question::model()->with('labels')->findByPk($id);
        $question->student = $this->cs50->user($question->student_id);
        if ($question->staff_id)
            $question->staff = $this->cs50->user($question->staff_id);

        echo $this->json('question', $question,
            'id, suite_id, title, question, anonymous, ask_timestamp, action_timestamp, dispatch_timestamp, state, labels, student, staff');
        exit;
    }

    /**
     * Fetch the load balancer for a suite
     *
     * @param $id ID of suite to fetch load balancer for
     * 
     */
    public function actionLoadBalancer($id) {
        echo CJSON::encode($this->loadBalancer($id));
        exit;
    }

    /**
     * Fetch the queue for a suite
     *
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
        $this->notifyLive("questions/toHelp/{$questions->suite_id}/$id");

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

        // notify live server so student gets update and push server so ipad gets update
        $this->notifyLive("questions/toQueue/{$question->suite_id}/$id");
        $this->notifyPush('queue_suite_id_' . $question->suite_id, 'refresh');

        // invalidate cache since a new question has been added
        $this->memcache->delete("queue?suite_id={$question->suite_id}");

        echo json_encode(array('success' => true));
        exit;
    }

    /**
     * Get the load balance questions for a suite
     *
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
     * Notify the live server
     *
     * @param $url URL to sent request to
     *
     */
    public function notifyLive($url) {
        $ch = curl_init("http://localhost:3000/$url");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Notify the push notification server
     *
     */
    public function notifyPush($channel, $data) {
        // omg stupid quotes, bug report submitted to parse
        $data = '{"channel":"' . $channel . '","type":"ios","data":{"alert":"' . $data . '"}}';

        $ch = curl_init("https://api.parse.com/1/push");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'X-Parse-Application-Id: IwrE84o8YUVtrIGvMsztIYQ4aYVrRvOpBidzWdk1',
            'X-Parse-REST-API-Key: 8FhCdrit7PhvcYsCaXJozeRnEFpqwsYPmwttfl1C'
        ));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Get the queue for a suite
     *
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
        foreach ($questions as $question)
            $question->student = $students[$question->student_id];

        // cache and return questions
        $this->memcache->set("queue?suite_id=$id", $questions);
        return array('questions' => $questions, 'changed' => true);
    }
}
