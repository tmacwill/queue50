<?php

class Label extends CActiveRecord {
    public static function model($className=__CLASS__) {
        return parent::model($className);
    }

    public function relations() {
        return array(
        );
    }

    public function rules() {
        return array(
            array('suite_id, name', 'required')
        );
    }

    public function tableName() {
        return 'labels';
    }
}
