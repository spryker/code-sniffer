<?php

namespace Pyz\Zed\Sales\Persistence;

class SalesRepository
{
    public function getQueries(): array
    {
        return [
            'SELECT GROUP_CONCAT(name) FROM spy_product',
            'SELECT IFNULL(fk_customer, 0) FROM spy_sales_order',
            'SELECT `name` FROM spy_product',
            'SELECT name FROM spy_product',
            'SELECT COALESCE(fk_customer, 0) FROM spy_sales_order',
        ];
    }
}
