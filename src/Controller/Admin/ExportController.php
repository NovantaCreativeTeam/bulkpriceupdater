<?php

namespace Novanta\BulkPriceUpdater\Controller\Admin;

use Novanta\BulkPriceUpdater\Domain\Product\Query\GetProductsForBulkPriceUpdate;
use Novanta\BulkPriceUpdater\Form\Admin\Export\ExportType;
use PrestaShopBundle\Component\CsvResponse;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;

class ExportController extends FrameworkBundleAdminController {
    
    public function indexAction(Request $request) {
        $export_form = $this->createForm(ExportType::class);
        $export_form->handleRequest($request);

        if($export_form->isSubmitted() && $export_form->isValid()) {

            $headers = [
                'id_product' => 'id_address',
                'id_product_attribute' => 'id_product_attribute',
                'referece' => 'reference',
                'name' => 'name',
                'combination' => 'combination',
                'price' => 'price',
                'price_new' => 'price_new'
            ];

            
            $form_data = $export_form->getData();
            $filters = [
                'id_supplier' => $form_data['supplier_ids'],
                'id_category' => $form_data['category_ids'],
                'only_active' => $form_data['only_active']
            ];

            $productQuery = new GetProductsForBulkPriceUpdate($filters);
            $products = $this->get('prestashop.core.query_bus')->handle($productQuery);

            $data = array();
            $locale = $this->get('prestashop.core.localization.locale.repository')->getLocale($this->getContext()->language->getLocale());

            if($products) {
                foreach ($products as $product) {
                    $data[] = array(
                        'id_product' => $product['id_product'],
                        'id_product_attribute' => $product['id_product_attribute'],
                        'referece' => $product['reference'],
                        'name' => $product['name'],
                        'combination' => $product['combination'],
                        'price' =>  $locale->formatNumber($product['price']),
                        'price_new' => ''
                    );
                }
    
                return (new CsvResponse())
                    ->setData($data)
                    ->setHeadersData($headers)
                    ->setFileName(($form_data['export_name'] ?? 'products') . '.csv');
            } else {
                $this->addFlash('warning', $this->trans('No products found for the specified filters, try another export', 'Modules.Bulkpriceupdater.Admin'));
            }
        }

        return $this->render(
            '@Modules/bulkpriceupdater/views/templates/admin/export/export.html.twig',
            [
                'layoutTitle' => $this->trans('Cart Exporter Configuration', 'Modules.Bulkpriceupdater.Admin'),
                'export_form' => $export_form->createView()
            ]);
    }
}