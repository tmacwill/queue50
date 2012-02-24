<?php

require_once 'protected/controllers/BaseController.php';

class UsersController extends BaseController {
    public function __construct($id, $module = null) {
        parent::__construct($id, $module);
    }

    /**
     * Mark that a staff member has arrived
     * @param $id ID of staff member
     *
     */
    public function actionArrival() {
        // add arrival object to database
        $arrival = new Arrival;
        $arrival->staff_id = $_POST['id'];
        if (!$arrival->save()) {
            echo json_encode(array('success' => false));
            exit;
        }

        echo json_encode(array('success' => true));
        exit;
    }

    /**
     * Get the list of staff and if they are on duty today
     *
     */
    public function actionStaff($id) {
        echo CJSON::encode(array('staff' => $this->cs50->suite($id)->subjects('answer')));
        exit;
    }
}
