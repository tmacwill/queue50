<?php

require_once 'protected/controllers/BaseController.php';

class QuestionsController extends BaseController {
    var $model = 'Question';

    public function __construct($id, $module = null) {
        parent::__construct($id, $module);

        $this->css('questions.css');
    }

    public function actionCourse($id) {
        $this->render('course');
    }
}
