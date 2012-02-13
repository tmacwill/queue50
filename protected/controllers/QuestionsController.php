<?php

require_once 'protected/controllers/BaseController.php';

class QuestionsController extends BaseController {
    var $model = 'Question';

    public function __construct($id, $module = null) {
        parent::__construct($id, $module);

        $this->css('questions.css');
    }

    /**
     * Add a question to a suite
     * @param $id ID of suite
     *
     */
    public function actionAdd($id) {
        $_POST['anonymous'] = 0;
        $_POST['answered'] = 0;
        $_POST['ask_timestamp'] = date('Y-m-d H:i:s');
        $_POST['suite_id'] = $id;
        $_POST['student_id'] = 1;

        unset($_POST['label']);

        parent::actionAdd();
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
            'course_id' => $id,
            'questions' => $questions
        ));
    }
}
