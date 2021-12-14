<?php

/**
 * 2007-2021 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2021 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

$autoloadPath = dirname(__FILE__) . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

if (!defined('_PS_VERSION_')) {
    exit;
}

class BulkPriceUpdater extends Module
{
    public function __construct()
    {
        $this->name = 'bulkpriceupdater';
        $this->tab = 'pricing_promotion';
        $this->version = '0.9.0';
        $this->author = 'Novanta';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->trans('Bulk Price Updater', [], 'Modules.Bulkpriceupdater.Admin');
        $this->description = $this->trans('Add functionality to bulk update product prices', [], 'Modules.Bulkpriceupdater.Admin');
        $this->confirmUninstall = $this->trans('Do you want to uninstall module?', [], 'Modules.Bulkpriceupdater.Admin');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        return parent::install() &&
            $this->installTables() &&
            $this->installTabs();
    }

    public function uninstall()
    {
        return parent::uninstall() &&
            $this->uninstallTables() &&
            $this->uninstallTabs();
    }

    private function installTables()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'price_import_log` (
            `id_price_import` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `file` VARCHAR(255) NOT NULL,
            `skip_rows` INT(10) NOT NULL,
            `column_separator` VARCHAR(10) NOT NULL,
            `status` VARCHAR(10) NOT NULL,
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_price_import`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        return Db::getInstance()->execute($sql);
    }

    private function uninstallTables()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'price_import_log`';
        return Db::getInstance()->execute($sql);
    }

    private function installTabs()
    {
        $parentTabName = $this->trans('Bulk Price Updater', [], 'Modules.Bulkpriceupdater.Admin');
        $parentTab = $this->addTab($parentTabName, 'BulkPriceUpdaterParent', 'AdminCatalog');

        if ($parentTab) {
            $exportTab = $this->addTab($this->trans('Export', [], 'Modules.Bulkpriceupdater.Admin'), 'BulkPriceUpdaterExport', 'BulkPriceUpdaterParent');
            $importTab = $this->addTab($this->trans('Import', [], 'Modules.Bulkpriceupdater.Admin'), 'BulkPriceUpdaterImport', 'BulkPriceUpdaterParent');
        }

        return $parentTab && $exportTab && $importTab;
    }

    private function uninstallTabs()
    {
        $tabRepository = SymfonyContainer::getInstance()->get('prestashop.core.admin.tab.repository');
        $tabId = (int) $tabRepository->findOneIdByClassName('BulkPriceUpdaterParent');
        if (!$tabId) {
            return true;
        }

        $tab = new Tab($tabId);

        return $tab->delete();
    }

    private function addTab($name, $className, $parentClassName)
    {
        $tabRepository = SymfonyContainer::getInstance()->get('prestashop.core.admin.tab.repository');
        $tabId = (int) $tabRepository->findOneIdByClassName($className);
        if (!$tabId) {
            $tabId = null;
        }

        $tab = new Tab($tabId);
        $tab->active = 1;
        $tab->class_name = $className;
        $tab->name = array();
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = $this->trans($name, [], 'Modules.Bulkpriceupdater.Admin', $lang['locale']);
        }

        $tab->id_parent = (int) $tabRepository->findOneIdByClassName($parentClassName);
        $tab->module = $this->name;

        return $tab->save();
    }

    public function getContent()
    {
        Tools::redirectAdmin(SymfonyContainer::getInstance()->get('router')->generate('admin_bulkpriceupdater_configure_index'));
    }

    public function isUsingNewTranslationSystem()
    {
        return true;
    }
}
