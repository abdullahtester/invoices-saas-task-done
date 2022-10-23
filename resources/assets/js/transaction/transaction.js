document.addEventListener('turbo:load', loadTransaction);

function loadTransaction() {
    initializeSelect2Transaction()
}

function initializeSelect2Transaction() {
    if(!select2NotExists('#paymentModeFilter')){
        return false;
    }
    removeSelect2Container(["#paymentModeFilter"])
    
    $('#paymentModeFilter').select2({
        placeholder: 'Select Payment Method',
        allowClear: false,
    });
}

listenClick('#resetFilter', function () {
    $('#paymentModeFilter').select2({
        placeholder: 'Select Payment Method',
        allowClear: false,
    });
    $('#paymentModeFilter').val(0).trigger('change');
});

listenClick('.show-payment-notes', function (event) {
    let paymentId = $(event.currentTarget).attr('data-id');
    paymentData(paymentId);
});

function paymentData(paymentId) {
    $.ajax({
        url: route('payment-notes.show', paymentId),
        type: 'GET',
        success: function (result) {
            if (result.success) {
                $('#paymentNotesModal').appendTo('body').modal('show');
                if (result.data != null){
                    $('#showClientNotesId').text(result.data);
                }
            }
        },
        error: function (result) {
            displayErrorMessage(result.responseJSON.message);
        },
    });
};
