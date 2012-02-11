<?php

class m120211_182822_create_questions_table extends CDbMigration {
	public function up() {
        $this->createTable('questions', array(
            'id' => 'pk',
            'suite_id' => 'int not null',
            'student_id' => 'int not null',
            'staff_id' => 'int',
            'question' => 'varchar(8192) not null',
            'anonymous' => 'tinyint not null',
            'ask_timestamp' => 'datetime not null',
            'dispatch_timestamp' => 'datetime',
            'answered' => 'tinyint not null'
        ));
	}

	public function down() {
        $this->dropTable('questions');
	}
}