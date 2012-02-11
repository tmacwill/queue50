<?php

class m120211_184447_create_labels_table extends CDbMigration {
	public function up() {
        $this->createTable('labels', array(
            'id' => 'pk',
            'suite_id' => 'int not null',
            'name' => 'varchar(255) not null'
        ));
	}

	public function down() {
        $this->dropTable('labels');
	}
}
