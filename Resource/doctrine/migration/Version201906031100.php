<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Eccube\Application;

class Version201906031100 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $app = Application::getInstance();
        $entityManager = $app['orm.em'];
        // Retrieve PDO instance
        $pdo = $entityManager->getConnection()->getWrappedConnection();
        $stmt = $pdo->prepare("SELECT send_id, search_data FROM plg_send_history;");
        $stmt->execute();
        foreach ($stmt as $row) {
            $serializedData = str_replace('DoctrineProxy\__CG__\Eccube\Entity\Member', 'DoctrineProxy\__CG__\Eccube\Entity\Xxxxxx', base64_decode($row['search_data']));
            $formData = unserialize($serializedData);
            // unserializeしたデータからJSONに変換
            $formDataArray = $formData;
            $formDataArray['pref'] = ($formData['pref'] != null) ? $formData['pref']->toArray() : null;
            $formDataArray['sex'] = array();
            foreach ($formData['sex'] as $value) {
                $formDataArray['sex'][] = $value->toArray();
            }
            $formDataArray['customer_status'] = array();
            foreach ($formData['customer_status'] as $value) {
                $formDataArray['customer_status'][] = $value->toArray();
            }
            unset($formDataArray['buy_category']);

            $json = json_encode($formDataArray);

            // search_dataをUPDATEする
            $stmt = $pdo->prepare("UPDATE plg_send_history SET search_data = :search_data WHERE send_id = :send_id;");
            $stmt->execute(array(':search_data' => $json, ':send_id' => $row['send_id']));
        }
    }

    public function down(Schema $schema)
    {
    }
}
