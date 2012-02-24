<?php

class Arrival extends CActiveRecord {
    public static function model($className=__CLASS__) {
        return parent::model($className);
    }

    public function relations() {
        return array(
        );
    }

    public function rules() {
        return array(
            array('staff_id', 'required'),
            array('timestamp', 'safe')
        );
    }

    public function tableName() {
        return 'arrivals';
    }
}
