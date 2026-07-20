<?php

namespace Pyz\Zed\Sales\Communication\Table;

class OrdersTable
{
    public const string COL_ID_SALES_ORDER = 'id_sales_order';

    protected const COL_CREATED_AT = 'created_at';

    private const int BUTTON_LIMIT = 10;

    protected const string URL_ORDER_DETAIL = '/sales/detail';

    protected const string COL_OK = 'ok';

    protected const string TEMPLATE_PATH = '/Sales/_partials/table.twig';

    protected const string ASSET_PATH = '/assets/img/icon.png';

    /**
     * @uses \Pyz\Zed\Sales\Communication\Controller\DetailController::indexAction()
     */
    protected const string URL_ORDER_LIST = '/sales/list';
}
