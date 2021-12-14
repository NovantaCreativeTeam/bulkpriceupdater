<?php

namespace Novanta\BulkPriceUpdater\Controller\Admin\Configure;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;

class ConfigureController extends FrameworkBundleAdminController {
    
    public function indexAction(Request $request) {
        return $this->render(
            '@Modules/bulkpriceupdater/views/templates/admin/configure/configure.html.twig',
            []
        );
    }

}