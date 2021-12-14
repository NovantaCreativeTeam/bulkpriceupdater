export default class Uploader {
    constructor() {
        $(document).on('click', '.js-change-import-file-btn', () => this.changeImportFileHandler());
        this.toggleSelectedFile();
    }

    /**
   * Check if selected file names exists and if so, then display it
   */
    toggleSelectedFile() {
        let selectFilename = $('#csv').val();
        if (selectFilename.length > 0) {
            this.showImportFileAlert(selectFilename);
            this.hideFileUploadBlock();
        }
    }

    uploadFile() {
        this.hideImportFileError();
        this.disableProcessImportButton();

        $.growl.notice({ title: "Attendere..", message: "Caricamento file in corso.." });

        const $input = $('#file');
        const uploadedFile = $input.prop('files')[0];

        const maxUploadSize = $input.data('max-file-upload-size');
        if (maxUploadSize < uploadedFile.size) {
            this.showImportFileError(uploadedFile.name, uploadedFile.size, 'File is too large');
            return;
        }

        const data = new FormData();
        data.append('file', uploadedFile);

        return $.ajax({
            type: 'POST',
            url: $('.js-import-form').data('file-upload-url'),
            data: data,
            cache: false,
            contentType: false,
            processData: false,
        }).then(response => {
            if (response.error) {
                this.showImportFileError(uploadedFile.name, uploadedFile.size, response.error);
                return;
            }

            let filename = response.file.name;

            $('.js-import-file-input').val(filename);

            this.showImportFileAlert(filename);
            this.hideFileUploadBlock();
            this.enableProcessImportButton();
        });
    }

    hideImportFileError() {
        const $alert = $('.js-import-file-error');
        $alert.addClass('d-none');
    }

    showImportFileError(fileName, fileSize, message) {
        const $alert = $('.js-import-file-error');

        const fileData = fileName + ' (' + this.humanizeSize(fileSize) + ')';

        $alert.find('.js-file-data').text(fileData);
        $alert.find('.js-error-message').text(message);
        $alert.removeClass('d-none');
    }

    showImportFileAlert(filename) {
        $('.js-import-file-alert').removeClass('d-none');
        $('.js-import-file').text(filename);
    }

    hideFileUploadBlock() {
        $('.js-file-upload-input-wrapper').addClass('d-none');
    }

    changeImportFileHandler() {
        this.hideImportFileAlert();
        this.showFileUploadBlock();
    }

    showFileUploadBlock() {
        $('.js-file-upload-input-wrapper').removeClass('d-none');
    }

    hideImportFileAlert() {
        $('.js-import-file-alert').addClass('d-none');
    }

    disableProcessImportButton() {
        $('.js-process-import').attr('disabled', true);
    }

    enableProcessImportButton() {
        $('.js-process-import').removeAttr('disabled');
    }
}