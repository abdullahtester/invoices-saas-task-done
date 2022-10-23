let paymentTableName = ''

document.addEventListener('turbo:load', loadPayment);

function loadPayment() {
    initializeSelect2Payment()
    paymentTableName = '#tblPayments';
}

function initializeSelect2Payment() {
    if(!select2NotExists('#adminPaymentInvoiceId')){
        return false;
    }

    if ($("#adminPaymentInvoiceId").hasClass("select2-hidden-accessible")) {
        $('#adminPaymentInvoiceId .select2-container').remove();
    }

    $('#adminPaymentInvoiceId').select2({
        dropdownParent: $('#paymentModal')
    });
}

listenClick('.admin-payment-delete-btn', function (event) {
    let paymentId = $(event.currentTarget).attr('data-id');
    deleteItem(route('payments.destroy', paymentId), paymentTableName, 'Payments');
});

listenClick('.addPayment', function () {
    let currentDtFormat = currentDateFormat;
    $.ajax({
        url: route('get-current-date-format'),
        type: 'get',
        success: function (data) {
            currentDtFormat = data;
            $('#payment_date').flatpickr({
                defaultDate: new Date(),
                dateFormat: data,
                maxDate: new Date(),
                "locale": getUserLanguages,
            });
            $('#paymentModal').appendTo('body').modal('show');
        },
        error: function (result) {
            displayErrorMessage(result.responseJSON.message);
        },
    });

    setTimeout(function () {
        $('#adminPaymentInvoiceId').select2({
            dropdownParent: $('#paymentModal')
        });
    }, 200);
});

function getCurrentDateFormat() {
    let currentDtFormat = currentDateFormat;
    $.ajax({
        url: route('get-current-date-format'),
        type: 'get',
        success: function (data) {
            currentDtFormat = data;
            return currentDtFormat;
        },
        error: function (result) {
            displayErrorMessage(result.responseJSON.message);
            return currentDtFormat;
        },
    });

    return currentDtFormat;
}

listenHiddenBsModal('#paymentModal', function () {
    $('#adminPaymentInvoiceId').val(null).trigger('change');
    resetModalForm('#paymentForm');
});

listenSubmit('#paymentForm', function (e) {
    e.preventDefault();

    if ($('#payment_note').val().trim().length == 0) {
        displayErrorMessage('Note field is Required')
        return false
    }

    let btnSubmitEle = $(this).find('#btnPay');
    setAdminBtnLoader(btnSubmitEle);
    $.ajax({
        url: route('payments.store'),
        type: 'POST',
        data: $(this).serialize(),
        success: function (result) {
            if (result.success) {
                setAdminBtnLoader(btnSubmitEle);
                $('#paymentModal').modal('hide');
                displaySuccessMessage(result.message);
                livewire.emit('refreshDatatable');
                livewire.emit('resetPageTable');
            }
        },
        error: function (result) {
            displayErrorMessage(result.responseJSON.message);
        },
        complete: function () {
            setAdminBtnLoader(btnSubmitEle);
        },
    });
});

listenChange('.invoice', function () {
    let invoiceId = $(this).val();
    if (isEmpty(invoiceId)) {
        $('#due_amount').val(0);
        $('#paid_amount').val(0);
        return false;
    }
    $.ajax({
        url: route('payments.get-invoiceAmount', invoiceId),
        type: 'get',
        dataType: 'json',
        success: function (result) {
            if (result.success) {
                $('.invoice-currency-code').text(result.data.currencyCode);
                $('#due_amount').val(number_format(result.data.totalDueAmount));
                $('#paid_amount').val(number_format(result.data.totalPaidAmount));
            }
        }, error: function (result) {
            displayErrorMessage(result.responseJSON.message);
        },
    });
});

listenClick('.admin-payment-edit-btn', function (event) {
    let paymentId = $(event.currentTarget).attr('data-id');
    paymentRenderData(paymentId);
});

function paymentRenderData(paymentId) {
    $.ajax({
        url:route('payments.edit',paymentId),
        type: 'GET',
        beforeSend: function () {
            startLoader();
        },
        success: function (result) {
            if (result.success) {
                $('#edit_invoice_id').val(result.data.invoice.invoice_id);
                $('#edit_amount').val(result.data.amount);
                $('#edit_payment_date').flatpickr({
                    defaultDate: result.data.payment_date,
                    dateFormat: currentDateFormat,
                    maxDate: new Date(),
                    "locale": getUserLanguages,
                });
                $('.edit-invoice-currency-code').text(result.data.currencyCode);
                $('#edit_payment_note').val(result.data.notes);
                $('#paymentId').val(result.data.id);
                $('#transactionId').val(result.data.payment_id);
                $('#invoice').val(result.data.invoice_id);
                $('#totalDue_amount').val(number_format(result.data.DueAmount.original.data.totalDueAmount));
                $('#totalPaid_amount').val(number_format(result.data.DueAmount.original.data.totalPaidAmount));
                $('#editPaymentModal').appendTo('body').modal('show');
            }
        },
        error: function (result) {
            displayErrorMessage(result.responseJSON.message);
        },
        complete: function () {
            stopLoader();
        },
    });
};

listenSubmit('#editPaymentForm', function (event) {
    event.preventDefault();
    
    if ($('#edit_payment_note').val().trim().length == 0) {
        displayErrorMessage('Note field is Required')
        return false
    }

    const paymentId = $('#paymentId').val();
    $.ajax({
        url: route('payments.update', { payment: paymentId }),
        type: 'put',
        data: $(this).serialize(),
        beforeSend: function () {
            startLoader();
        },
        success: function (result) {
            if (result.success) {
                displaySuccessMessage(result.message);
                livewire.emit('refreshDatatable');
                livewire.emit('resetPageTable');
                $('#editPaymentModal').modal('hide');
                $('#tblPayments').DataTable().ajax.reload(null, false);
            }
        },
        error: function (result) {
            displayErrorMessage(result.responseJSON.message);
        },
        complete: function () {
            stopLoader();
        },
    });
});

listenChange('.transaction-approve', function () {
    let id = $(this).attr('data-id');
    let status = $(this).val();

    $.ajax({
        url: route('change-transaction-status', id),
        type: 'GET',
        data: {id: id, status: status},
        success: function (result) {
            displaySuccessMessage(result.message);
            livewire.emit('refreshDatatable');
            livewire.emit('resetPageTable');
        },
        error: function (result) {
            displayErrorMessage(result.responseJSON.message);
        },
    });
});
