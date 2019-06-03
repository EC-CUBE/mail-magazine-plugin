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
        $repository = $app['orm.em']->getRepository("\Plugin\MailMagazine\Entity\MailMagazineSendHistory");

        $entities = $repository->createQueryBuilder('sh')
            ->select('sh.id, sh.search_data')
            ->orderBy('sh.id', 'ASC')
            ->getQuery()
            ->getArrayResult();

        foreach ($entities as $entity) {
            $formData = unserialize(base64_decode($entity['search_data']));

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

            $qb = $repository->createQueryBuilder('sh');
            $qb->update("\Plugin\MailMagazine\Entity\MailMagazineSendHistory", 'sh')
                ->set('sh.search_data', $qb->expr()->literal($json))
                ->where('sh.id = :id')
                ->setParameter('id', $entity['id'])
                ->getQuery()
                ->execute();
        }
    }

    public function down(Schema $schema)
    {
    }
}
