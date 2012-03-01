<?php

require_once 'protected/controllers/BaseController.php';

class QuestionsController extends BaseController {
    var $model = 'Question';

    private $memcache;
    private $redis;

    const STATE_IN_BALANCER = 0;
    const STATE_IN_QUEUE = 1;
    const STATE_IN_HELP = 2;
    const STATE_DISPATCHED = 3;
    const STATE_DONE = 4;

    public function __construct($id, $module = null) {
        parent::__construct($id, $module);

        // connect to memcache
        $this->memcache = new Memcache;
        $this->memcache->connect('localhost', 11211);

        // connect to redis
        $this->redis = new Predis\Client;   

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
        $balancer = $this->getStatus($id, 'balancer');

        // set question defaults
        $_POST['anonymous'] = 0;
        $_POST['state'] = ($balancer) ? self::STATE_IN_BALANCER : self::STATE_IN_QUEUE;
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

        // load balancer enabled, so notify live server
        if ($balancer)
            $this->notifyLive("questions/add/$id");

        // load balancer disabled, so notify live server and ipad
        else {
            // question has gone immediately to queue, so update action timestamp
            $question->action_timestamp = date('Y-m-d H:i:s');
            $question->save();

            $this->notifyLive("questions/toQueue/{$question->suite_id}/$id");
            $this->notifyPad('queue_suite_id_' . $question->suite_id, '"alert":"New Question!","type":"refresh"');
        }

        echo json_encode(array(
            'destination' => $question->state,
            'id' => $question->id,
            'success' => true
        ));
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
     * Return whether or not the balancer is in use
     *
     * @param $id Suite ID
     *
     */
    public function actionBalancerEnabled($id) {
        echo json_encode(array(
            'success' => true, 
            'balancer' => $this->getStatus($id, 'balancer')));
        exit;
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
     * Disable the balancer
     *
     * @param $id Suite ID
     *
     */
    public function actionDisableBalancer($id) {
        $this->setStatus($id, 'balancer', 0);

        echo json_encode(array('success' => true, 'balancer' => 0));
        exit;
    }

    /**
     * Disable the queue
     *
     * @param $id Suite ID
     *
     */
    public function actionDisableQueue($id) {
        $this->setStatus($id, 'queue', 0);

        echo json_encode(array('success' => true, 'queue' => 0));
        exit;
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
            'state' => self::STATE_DISPATCHED
        ), array('in', 'id', $question_ids));

        // relay dispatch notifications through live server
        $question_ids = implode(',', $question_ids);
        $question = Question::model()->findByPk($question_ids[0]);
        $this->notifyLive("questions/dispatch/{$question->suite_id}/$question_ids");

        // notify ipod push notification server
        $this->notifyPod('question_user_id_' . $staff_id, '"alert":"New Question!","ids":[' . $question_ids  . ']');

        // invalidate cache since a question has been removed
        $this->memcache->delete("queue?suite_id={$question->suite_id}");
        echo json_encode(array('success' => true));
        exit;
    }

    /**
     * TF is done with a question
     *
     */
    public function actionDone() {
        // get question from database
        $question = Question::model()->findByPk($_POST['id']);
        $question->done_timestamp = date('Y-m-d H:i:s');
        $question->state = self::STATE_DONE;

        // persist question
        if (!$question->save()) {
            echo json_encode(array('success' => false));
            exit;
        }

        echo json_encode(array('success' => true));
        exit;
    }

    /**
     * Enable the load balancer
     *
     * @param $id Suite ID
     *
     */
    public function actionEnableBalancer($id) {
        $this->setStatus($id, 'balancer', 0);

        echo json_encode(array('success' => true, 'balancer' => 1));
        exit;
    }

    /**
     * Enable the queue
     *
     * @param $id Suite ID
     *
     */
    public function actionEnableQueue($id) {
        $this->setStatus($id, 'queue', 0);

        echo json_encode(array('success' => true, 'queue' => 1));
        exit;
    }

    /**
     * Get info for questions
     *
     * @param $id Comma-separated list of IDs
     *
     */
    public function actionGet($id) {
        $ids = explode(',', $id);

        // get questions from database
        $questions = Question::model()->with('labels')->findAllByAttributes(array(
            'id' => $ids
        ));

        // get associated students and staff
        $student_ids = array_map(function ($e) { return $e->student_id; }, $questions);
        $staff_ids = array_map(function ($e) { return $e->staff_id; }, $questions);
        $users = $this->cs50->users(array_merge($student_ids, $staff_ids));

        // associate question metadata
        foreach ($questions as &$question) {
            $question->student = isset($users[$question->student_id]) ? $users[$question->student_id] : null;
            $question->staff = isset($users[$question->staff_id]) ? $users[$question->staff_id] : null;
        }

        echo $this->json('question', $questions,
            'id, suite_id, title, question, anonymous, ask_timestamp, ' .
            'state, labels, student, staff');
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
     * Get the history for a single staff member
     *
     * @param $id ID of staff member
     *
     */
    public function actionHistory($id) {
        // get all questions dispatched to TF
        $questions = Question::model()->with('labels')->findAll(array(
            'condition' => 'staff_id = :staff_id',
            'order' => 'ask_timestamp desc',
            'params' => array(
                'staff_id' => $id
            )
        ));

        // get students for returned questions
        $student_ids = array_map(function($e) { return $e->student_id; }, $questions);
        $students = $this->cs50->users($student_ids);

        // associate students with question
        foreach ($questions as $question)
            $question->student = $students[$question->student_id];

        echo $this->json('questions', $questions,
            'id, suite_id, title, question, state, labels, student', true);
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
            'id, suite_id, student_id, staff_id, title, question, anonymous, ask_timestamp, state, labels, student', 
            true);
        exit;
    }

    /**
     * Return whether or not the queue is open
     *
     * @param $id Suite ID
     *
     */
    public function actionQueueEnabled($id) {
        echo json_encode(array(
            'success' => true, 
            'queue' => $this->getStatus($id, 'queue')));
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
        $question->state = self::STATE_IN_HELP;

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
        $question->state = self::STATE_IN_QUEUE;

        // persist question
        if (!$question->save()) {
            echo json_encode(array('success' => false));
            exit;
        }

        // notify live server so student gets update and push server so ipad gets update
        $this->notifyLive("questions/toQueue/{$question->suite_id}/$id");
        $this->notifyPad('queue_suite_id_' . $question->suite_id, '"alert":"New Question!","type":"refresh"');

        // invalidate cache since a new question has been added
        //$this->memcache->delete("queue?suite_id={$question->suite_id}");

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
                'state' => self::STATE_IN_BALANCER,
                'yesterday' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'tomorrow' => date('Y-m-d H:i:s', strtotime('+1 day'))
            )
        ));

        return $questions;
    }

    /**
     * Get info about the current status of the app
     * 
     * @param $key Status key
     * @return Value describing app status
     *
     */
    public function getStatus($id, $key) {
        // get value from redis
        $value = $this->redis->hget("status?suite_id=$id", $key);

        // redis is empty, so determine default value
        if ($value === null) {
            if ($key == 'balancer')
                return 1;
            else if ($key == 'queue')
                return 0;
        }

        // redis not empty, so return that
        return $value;
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
     * Notify the iPad push notification server
     *
     */
    public function notifyPad($channel, $data) {
        $data = '{"channel":"' . $channel . '","type":"ios","data":{' . $data . '}}';

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
     * Notify the iPod push notification server
     *
     */
    public function notifyPod($channel, $data) {
        $data = '{"channel":"' . $channel . '","type":"ios","data":{' . $data . '}}';

        $ch = curl_init("https://api.parse.com/1/push");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'X-Parse-Application-Id: Fqi2knNxzqGBqBjhll97l8bsbV2D4rF0Fvia3Fs5',
            'X-Parse-REST-API-Key: ePTokndsBJMbIYoTO3kcWjdH0TH1e0PHrBGfXHSi'
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
        //$questions = $this->memcache->get("queue?suite_id=$id");
        //if ($questions !== false && $questions !== null) 
            //return array('questions' => $questions, 'changed' => false);

        // fetch all questions in the queue from within 48 hours
        $questions = Question::model()->with('labels')->findAll(array(
            'condition' => 't.suite_id = :suite_id AND state = :state AND ask_timestamp >= :yesterday ' . 
                'AND ask_timestamp <= :tomorrow',
            'order' => 'ask_timestamp asc',
            'params' => array(
                'suite_id' => $id,
                'state' => self::STATE_IN_QUEUE,
                'yesterday' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'tomorrow' => date('Y-m-d H:i:s', strtotime('+1 day'))
            ),
        ));

        // get students for returned questions
        $student_ids = array_map(function($e) { return $e->student_id; }, $questions);
        $students = $this->cs50->users($student_ids);

        // associate students with question
        foreach ($questions as $question)
            $question->student = $students[$question->student_id];

        // cache and return questions
        //$this->memcache->set("queue?suite_id=$id", $questions);
        return array('questions' => $questions, 'changed' => true);
    }

    /**
     * Set info about the current status of the app
     * 
     * @param $key Key into status array
     *
     */
    public function setStatus($id, $key, $value) {
        $this->redis->hset("status?suite_id=$id", $key, $value);
    }
}
