import Grid from '../../../../admin-dev/themes/new-theme/js/components/grid/grid';
import FiltersResetExtension from '../../../../admin-dev/themes/new-theme/js/components/grid/extension/filters-reset-extension';
import SortingExtension from '../../../../admin-dev/themes/new-theme/js/components/grid/extension/sorting-extension';
import RevertRowActionExtension from './components/RevertRowActionExtension';


import Importer from "../../../../admin-dev/themes/new-theme/js/pages/import-data/Importer"
import Uploader from "./components/Uploader";

const $ = window.$;

$(() => {
    // Setup Grid Panel
    const priceImportLogGrid = new Grid('priceimportlog')
    priceImportLogGrid.addExtension(new FiltersResetExtension())
    priceImportLogGrid.addExtension(new SortingExtension())
    priceImportLogGrid.addExtension(new RevertRowActionExtension())

    // Setup Import Panel
    var importer = new Importer()
    var uploader = new Uploader()
    $(document).on('click', '.js-process-import', (e) => importHandler(e));
    $(document).on('click', '.js-abort-import', () => importer.requestCancelImport());
    $(document).on('click', '.js-close-modal', () => {
        importer.progressModal.hide()
        location.reload()
    });
    $(document).on('click', '.js-continue-import', () => importer.continueImport());
    $(document).on('change', '.js-import-file', () => { uploader.uploadFile(); } );

    function importHandler(e) {
        e.preventDefault();

        let configuration = {};

        // Collect the configuration from the form into an array.
        $('#import-prices-form').find(
            'input[name=skip]:checked, select[name^=type_value], #csv, #iso_lang, #entity,' +
            '#truncate, #match_ref, #regenerate, #forceIDs, #sendemail,' +
            '#separator, #multiple_value_separator, #price_tin'
        ).each((index, $input) => {
            configuration[$($input).attr('name')] = $($input).val();
        });

        importer.import(
            $(e.currentTarget).data('import_url'),
            configuration
        );
    }
});
