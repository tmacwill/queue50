<?php

class Question extends CActiveRecord {
    public static function model($className=__CLASS__) {
        return parent::model($className);
    }

    public function relations() {
        return array(
            'labels' => array(self::MANY_MANY, 'Label', 'question_labels(question_id, label_id)')
        );
    }

    public function rules() {
        return array(
            array('suite_id, student_id, question, anonymous, ask_timestamp, answered', 'required'),
            array('staff_id, dispatch_timestamp', 'safe')
        );
    }

    public function tableName() {
        return 'questions';
    }
}