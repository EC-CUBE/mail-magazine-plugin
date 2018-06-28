<?php
namespace Plugin\MailMagazine\Entity;

use Eccube\Doctrine\Query\WhereClause;
use Eccube\Doctrine\Query\WhereCustomizer;
use Eccube\Repository\QueryKey;

class AdminCustomerQueryCustomizer extends WhereCustomizer
{
    /**
     * {@inheritdoc}
     *
     * @param array $params
     * @param $queryKey
     * @return WhereClause[]
     */
    protected function createStatements($params, $queryKey)
    {
        if (!isset($params['plg_mailmagazine_flg'])) {
            return [];
        }

        return [WhereClause::eq('c.mailmaga_flg', ':mailmaga_flg', [
            'mailmaga_flg' => $params['plg_mailmagazine_flg']
        ])];
    }


    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getQueryKey()
    {
        return QueryKey::CUSTOMER_SEARCH;
    }
}
