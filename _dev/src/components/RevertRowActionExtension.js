import Importer from "../../../../../admin-dev/themes/new-theme/js/pages/import-data/Importer"

const $ = window.$;

/**
 * Class SubmitRowActionExtension handles submitting of row action
 */
export default class RevertRowActionExtension {
    /**
     * Extend grid
     *
     * @param {Grid} grid
     */
    extend(grid) {
        grid.getContainer().on('click', '.js-revert_import-row-action', (event) => {
            event.preventDefault();

            const $button = $(event.currentTarget);
            const confirmMessage = $button.data('confirm-message');

            if (confirmMessage.length && !confirm(confirmMessage)) {
                return;
            }

            let importer = new Importer()
            let configuration = {}

            configuration['id'] = $(event.currentTarget).data('id')
            importer.import(
                $(event.currentTarget).data('url'),
                configuration
            );
        });
    }
}
