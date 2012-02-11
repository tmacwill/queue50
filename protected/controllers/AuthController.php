<?php

require_once 'protected/controllers/BaseController.php';

class AuthController extends BaseController {
    public function __construct($id, $module = null) {
        parent::__construct($id, $module);

        $this->css('auth.css');
    }

    public function actionAuthenticate() {
        $url = isset($_GET['return']) ? $_GET['return'] : '';
        $ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

        // check if credentials are valid
        if (!isset($_POST['email'], $_POST['password'])) {
            if ($ajax)
                echo json_encode(array('success' => false));
            else
                $this->redirect("auth/login?return=$url");

            exit;
        }

        // save and redirect user to desired page
        if (($user = $this->cs50->users->authenticate($_POST['email'], $_POST['password']))) {
            $_SESSION['user'] = $user;

            if (isset($_GET['return']))
                $this->redirect($_GET['return']);
            else if ($ajax)
                echo json_encode($user);
            else
                $this->redirect('/');
        }

        // login failed
        else {
            if ($ajax)
                echo json_encode(array('success' => false));
            else
                $this->redirect("auth/login?return=$url");
        }
    }

    public function actionIndex() {
    }

    /**
     * Display login form
     *
     */
    public function actionLogin() {
        $this->render('login', array());
    }

    /**
     * Log the user out
     *
     */
    public function actionLogout() {
        session_destroy();

        if (isset($_REQUEST['return']))
            $this->redirect($_REQUEST['return']);
        else if (isset($_REQUEST['json']))
            echo json_encode(array('success' => true));
        else
            $this->redirect('/');
    }
}
