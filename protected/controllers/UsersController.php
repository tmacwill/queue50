<?php

require_once 'protected/controllers/BaseController.php';

class UsersController extends BaseController {
    public function __construct($id, $module = null) {
        parent::__construct($id, $module);
    }

    public function actionStaff($id) {
        echo CJSON::encode(array('staff' => $this->cs50->suite($id)->subjects('answer')));
        exit;
    }
}
