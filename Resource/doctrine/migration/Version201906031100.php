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
        // 3.0.8以前はgetInstance関数が無いことを考慮する
        if (version_compare(Constant::VERSION, '3.0.9', '>=')) {
            $app = Application::getInstance();
            $entityManager = $app['orm.em'];
            $pdo = $entityManager->getConnection()->getWrappedConnection();
        } else {
            // 直接DBに接続してPDOを生成
            $pdo = $this->getPDO();
        }

        // search_dataをserializeされたデータからjson形式に変換する
        $stmt = $pdo->prepare('SELECT send_id, search_data FROM plg_send_history;');
        $stmt->execute();
        foreach ($stmt as $row) {
            $formData = $this->unserializeWrapper(base64_decode($row['search_data']));

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

    /**
     * 互換性のないEntityを取り除いた状態でUnserialiseを実行する
     * Member,Customerは "__php_incomplete_class"となる
     *
     * @param array $serializedData Base64でエンコードされたシリアライズデータ
     * @return mixed unserializeしたデータ
     */
    private function unserializeWrapper($serializedData) {
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
