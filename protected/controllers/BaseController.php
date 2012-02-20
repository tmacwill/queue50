<?php

class BaseController extends Controller {
    public $cs50;

    public function __construct($id, $module = null) {
        parent::__construct($id, $module);
        $this->layout = 'main';

        // load app-wide javascript
        $this->js('lib/jquery-1.7.1.min.js');
        $this->js('lib/jquery-ui-1.8.16.custom.min.js');
        $this->js('lib/bootstrap.min.js');
        $this->js('lib/underscore-min.js');
        $this->js('lib/backbone-min.js');
        $this->js('lib/date.js');
        $this->js('navbar.js');

        // load app-wide css
        $this->css('lib/bootstrap.min.css');
        $this->css('lib/bootstrap-responsive.min.css');
        $this->css('io/jquery-ui-1.8.16.custom.css');
        $this->css('global.css');

        // initialize auth library
        $this->cs50 = new CS50('queue', array(
            'cache' => array(
                'host' => 'localhost',
                'port' => 11211
            ),
            'master' => array(
                'db_name' => 'auth',
                'host' => 'localhost',
                'password' => 'crimson',
                'user' => 'root'
            )
        ));
    }

    /**
     * Add a new object
     *
     */
    public function actionAdd() {
        // if user only supplied one object, convert to array with only that object
        if (array_keys($_POST) !== range(0, count($_POST) - 1))
            $to_add = array($_POST);
        else
            $to_add = $_POST;

        $ids = array();
        foreach ($to_add as $attributes) {
            // instantiate model and set attributes from postdata
            $model = new $this->model;
            $model->attributes = $attributes;

            // persist model
            if (!$model->save()) {
                var_dump($model->getErrors());
                echo json_encode(array('success' => false));
                exit;
            }

            $ids[] = $model->id;
        }

        if (count($ids) == 1)
            echo json_encode(array('success' => true, 'id' => array_pop($ids)));
        else
            echo json_encode(array('success' => true, 'ids' => $ids));

        exit;
    }

    /**
     * Delete an object
     *
     */
    public function actionDelete($id) {
        // instantiate model
        $model = new $this->model;

        // delete objects with given ids
        $ids = explode(',', $id);
        if (!$model::model()->deleteAllByAttributes(array('id' => $ids))) {
            echo json_encode(array('success' => false));
            exit;
        }

        echo json_encode(array('success' => true));
        exit;
    }

    /**
     * Update the fields of an object
     *
     */
    public function actionEdit($id) {
        // instantiate model
        $model = new $this->model;

        // make sure object exists
        if (!$model::model()->exists('id = :id', array(':id' => $id))) {
            echo json_encode(array('success' => false));
            exit;
        }

        // get object from database
        $object = $model::model()->findByPk($id);
        $object->attributes = $_POST;

        // persist model
        if (!$object->save()) {
            echo json_encode(array('success' => false));
            exit;
        }

        echo json_encode(array('success' => true, 'id' => $object->id));
        exit;
    }

    /**
     * Get a single object
     *
     */
    public function actionGet($id) {
        // instantiate model
        $model = new $this->model;

        // make sure object exists
        if (!$model::model()->exists('id = :id', array(':id' => $id))) {
            echo json_encode(array('success' => false));
            exit;
        }

        // get object from database
        $object = $model::model()->findByPk($id);

        echo json_encode(array(
            'success' => true, 
            strtolower($this->model) => $object->getAttributes($object->safeAttributeNames)));
        exit;
    }

    /**
     * Add a CSS file to the view
     *
     */
    public function css($path) {
        Yii::app()->clientScript->registerCssFile('/css/' . $path);
    }

    /**
     * Make sure the user is logged in, and redirect accordingly if not
     *
     */
    public function enforceLogin() {
        $url = Yii::app()->request->requestUri;

        // check if user is logged in
        if (!isset($_SESSION['user'])) {
            // respond with json for ajax and redirect for http
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
                echo json_encode(array('success' => false));
            else
                $this->redirect("/auth/login?return=$url");

            exit;
        }
    }

    /**
     * Add a JavaScript file to the view
     *
     */
    public function js($path) {
        Yii::app()->clientScript->registerScriptFile('/js/' . $path);
    }

    /**
     * JSON-encode an object, including its relations
     * Modified from http://learnyii.blogspot.com/2011/07/yii-json-cjson-models-model-related.html
     *
     */
    public function json($key, $models, $attributeNames) {
        if (!is_array($models) || count($models) < 1)
            return CJSON::encode($models);

        $attributeNames = explode(',', $attributeNames);

        $rows = array();
        foreach ($models as $model) {
            $row = array();
            foreach ($attributeNames as $name) {
                $name = trim($name); 
                $row[$name] = CHtml::value($model, $name);
            }
            $rows[] = $row;
        }

        return CJSON::encode(array($key => $rows));
    }
}
