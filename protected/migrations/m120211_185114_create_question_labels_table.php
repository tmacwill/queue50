<?php

class m120211_185114_create_question_labels_table extends CDbMigration {
	public function up() {
        $this->createTable('question_labels', array(
            'id' => 'pk',
            'question_id' => 'int not null',
            'label_id' => 'int not null'
        ));
	}

	public function down() {
        $this->dropTable('question_labels');
	}
}
