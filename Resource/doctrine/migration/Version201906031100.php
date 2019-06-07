<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Eccube\Application;
use Eccube\Common\Constant;
use Symfony\Component\Yaml\Yaml;

class Version201906031100 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        if (version_compare(Constant::VERSION, '3.0.9', '>=')) {
            $app = Application::getInstance();
            $entityManager = $app['orm.em'];
            // Retrieve PDO instance
            $pdo = $entityManager->getConnection()->getWrappedConnection();
        } else {
            $pdo = $this->getPDO();
        }

        $stmt = $pdo->prepare('SELECT send_id, search_data FROM plg_send_history;');
        $stmt->execute();
        foreach ($stmt as $row) {
            $formData = $this->unserializeWrapper($row, 'search_data');
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

    private function unserializeWrapper($dataArray, $key) {
        $serializedData = base64_decode($dataArray[$key]);
        $serializedData = str_replace('DoctrineProxy\__CG__\Eccube\Entity\Member', '__Workaround_\__CG__\Eccube\Entity\Member', $serializedData);
        $serializedData = str_replace('DoctrineProxy\__CG__\Eccube\Entity\Customer', '__Workaround_\__CG__\Eccube\Entity\Customer', $serializedData);
        return unserialize($serializedData);
    }

    private function getPDO()
    {
        $config_file = __DIR__ . '/../../../../../../app/config/eccube/database.yml';
        $config = Yaml::parse(file_get_contents($config_file));

        $pdo = null;
        try {
            $pdo = \Doctrine\DBAL\DriverManager::getConnection($config['database'], new \Doctrine\DBAL\Configuration());
            $pdo->connect();
        } catch (\Exception $e) {
            $pdo->close();
            return null;
        }

        return $pdo;
    }

}
