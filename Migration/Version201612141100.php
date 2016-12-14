<?php


namespace DoctrineMigrations;


use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version201612141100 extends AbstractMigration
{

    public function up(Schema $schema)
    {
        $table = $schema->getTable('plg_mailmaga_template');

        if (!$table->hasColumn('html_body')) {
            $table->addColumn('html_body', 'text', array('notnull' => false,));
        }
    }

    public function down(Schema $schema)
    {
    }
}