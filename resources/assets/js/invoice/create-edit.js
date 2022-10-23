let discountType = null;
let momentFormat = '';

document.addEventListener('turbo:load', loadCreateEditInvoice)

function loadCreateEditInvoice () {
    $('input:text:not([readonly="readonly"])').first().blur();
    initializeSelect2CreateEditInvoice()
    loadSelect2ClientData()
    loadSelect2CurrencyData()

    momentFormat = convertToMomentFormat(currentDateFormat);

    if ($('#invoiceNote').val() == true || $('#invoiceTerm').val() == true) {
        $('#addNote').hide();
        $('#removeNote').show();
        $('#noteAdd').show();
        $('#termRemove').show();
    } else {
        $('#removeNote').hide();
        $('#noteAdd').hide();
        $('#termRemove').hide();
    }
    if ($('#invoiceRecurring').val() == true) {
        $('.recurring').show();
    } else {
        $('.recurring').hide();
    }
    if ($('#formData_recurring-1').prop('checked')) {
        $('.recurring').hide();
    }
    if ($('#discountType').val() != 0) {
        $('#discount').removeAttr('disabled');
    } else {
        $('#discount').attr('disabled', 'disabled');
    }
    calculateTax();
    calculateAndSetInvoiceAmount();
}
function loadSelect2ClientData() {
    if (!$('#client_id').length && !$('#discountType').length) {
        return
    }

    $('#client_id,#discountType,#status,#templateId').select2();
}

function loadSelect2CurrencyData() {
    if (!$('#invoiceCurrencyType').length) {
        return
    }

    $('#invoiceCurrencyType').select2();
}

listenChange('#invoiceCurrencyType', function () {
    let currencyId = $(this).val();
    
    $.ajax({
        url: route('invoices.get-currency', currencyId),
        type: 'get',
        dataType: 'json',
        success: function (result) {
            if (result.success) {
               $('.invoice-selected-currency').text(result.data)
            }
        }, error: function (result) {
            displayErrorMessage(result.responseJSON.message);
        },
    });
});


function initializeSelect2CreateEditInvoice () {
    if (!select2NotExists('.product')) {
        return false
    }
    removeSelect2Container(['.product'])

    $('.product').select2({
        tags: true,
    });

    $('.tax').select2({
        placeholder: 'Select TAX',
    });

    $('#client_id').focus();
    let dueDateFlatPicker = $("#due_date").flatpickr({
        defaultDate: moment().add(1, 'days').toDate(),
        dateFormat: currentDateFormat,
        "locale": getUserLanguages,
    });

    let editDueDateFlatPicker = $('#editDueDate').flatpickr({
        dateFormat: currentDateFormat,
        defaultDate: moment($('#editDueDate').val()).format(momentFormat),
        "locale": getUserLanguages,
    });

    $('#invoice_date').flatpickr({
        defaultDate: new Date(),
        dateFormat: currentDateFormat,
        "locale": getUserLanguages,
        onChange: function (selectedDates, dateStr, instance) {
            let minDate = moment($('#invoice_date').val(), momentFormat).
                add(1, 'days').
                format(momentFormat);
            if (typeof dueDateFlatPicker != 'undefined') {
                dueDateFlatPicker.set('minDate', minDate);
            }
        },
        onReady: function () {
            let minDate = moment(new Date).add(1, 'days').format(momentFormat);
            if (typeof dueDateFlatPicker != 'undefined') {
                dueDateFlatPicker.set('minDate', minDate);
            }
        },
    });

    $('#editInvoiceDate').flatpickr({
        dateFormat: currentDateFormat,
        defaultDate: moment($('#editInvoiceDate').val()).format(momentFormat),
        "locale": getUserLanguages,
        onChange: function () {
            let minDate = moment($('#editInvoiceDate').val(),momentFormat).
                add(1, 'days').
                format(momentFormat);
            if (typeof editDueDateFlatPicker != 'undefined') {
                editDueDateFlatPicker.set('minDate', minDate);
            }
        },
        onReady: function () {
            let minDate = moment($('#editInvoiceDate').val(),momentFormat).
                add(1, 'days').format(momentFormat);
            if (typeof editDueDateFlatPicker != 'undefined') {
                editDueDateFlatPicker.set('minDate', minDate);
            }
        },
    });
}

listenKeyup('#invoiceId', function () {
    return $('#invoiceId').val(this.value.toUpperCase());
});

listenClick('#addNote',function (){
    $('#addNote').hide();
    $('#removeNote').show();
    $('#noteAdd').show();
    $('#termRemove').show();
});
listenClick('#removeNote',function (){
    $('#addNote').show();
    $('#removeNote').hide();
    $('#noteAdd').hide();
    $('#termRemove').hide();
    $('#note').val('');
    $('#term').val('');
});

listenClick('#formData_recurring-0',function (){
    if ($("#formData_recurring-0").prop("checked")) {
        $('.recurring').show();
    }else{
        $('.recurring').hide();
    }
});
listenClick('#formData_recurring-1', function () {
    if ($("#formData_recurring-1").prop("checked")) {
        $('.recurring').hide();
    }
});

listenChange('#discountType', function () {
    discountType = $(this).val();
    $('#discount').val(0);
    if (discountType == 1 || discountType == 2) {
        $('#discount').removeAttr('disabled');
        if (discountType == 2) {
            let value = $('#discount').val();
            $('#discount').val(value.substring(0, 2));
        }
    } else {
        $('#discount').attr('disabled', 'disabled');
        $('#discount').val(0);
        $('#discountAmount').text('0');
    }
    calculateDiscount();
});

window.isNumberKey = (evt, element) => {
    let charCode = (evt.which) ? evt.which : event.keyCode;

    return !((charCode !== 46 || $(element).val().indexOf('.') !== -1) &&
        (charCode < 48 || charCode > 57));
};

listenClick('#addItem', function () {
    let data = {
        'products': JSON.parse($('#products').val()),
        'taxes': JSON.parse($('#taxes').val()),
    };

    let invoiceItemHtml = prepareTemplateRender(
        '#invoiceItemTemplate', data)

    $('.invoice-item-container').append(invoiceItemHtml);
    $('.taxId').select2({
        placeholder: 'Select TAX',
        multiple: true,
    });
    $('.productId').select2({
        placeholder: 'Select Product or Enter free text',
        tags: true,
    });
    resetInvoiceItemIndex();
});

const resetInvoiceItemIndex = () => {
    let index = 1;
    $('.invoice-item-container>tr').each(function () {
        $(this).find('.item-number').text(index);
        index++;
    });
    if (index - 1 == 0) {
        let data = {
            'products': JSON.parse($('#products').val()),
            'taxes': JSON.parse($('#taxes').val()),
        };
        let invoiceItemHtml = prepareTemplateRender(
            '#invoiceItemTemplate', data);
        $('.invoice-item-container').append(invoiceItemHtml);
        $('.productId').select2();
        $('.taxId').select2({
            placeholder: 'Select TAX',
            multiple: true,
        });
    }
    calculateTax();
};

listenClick('.delete-invoice-item', function () {
    $(this).parents('tr').remove();
    resetInvoiceItemIndex();
    calculateAndSetInvoiceAmount();
});

listenChange('.product', function () {
    let productId = $(this).val();
    if (isEmpty(productId)) {
        productId = 0;
    }
    let element = $(this);
    $.ajax({
        url: route('invoices.get-product', productId),
        type: 'get',
        dataType: 'json',
        success: function (result) {
            if (result.success) {
                let price = '';
                $.each(result.data, function (id, productPrice) {
                    if (id === productId) price = productPrice;
                });
                element.parent().parent().find('td .price').val(price);
                element.parent().parent().find('td .qty').val(1);
                $('.price').trigger('keyup');
            }
        }, error: function (result) {
            displayErrorMessage(result.responseJSON.message);
        },
    });
});

listenChange('.tax', function () {
    let tax = $(this).val();
    let total_tax = 0;
    $.each(tax, function (index, value) {
        total_tax += parseFloat(value);
    });
    let qty = $(this).parent().siblings().find('.qty').val();
    let rate = $(this).parent().siblings().find('.price').val();
    rate = parseFloat(removeCommas(rate));
    let amount = calculateAmount(qty, rate, total_tax);
    calculateTax();
    $(this).
        parent().
        siblings('.item-total').
        text(addCommas((amount.toFixed(2)).toString()));
    calculateAndSetInvoiceAmount();
});

listenKeyup('.qty', function () {
    let qty = $(this).val();
    let tax = $(this).parent().siblings().find('.tax').val();
    let total_tax = 0;
    $.each(tax, function (index, value) {
        total_tax += +value;
    });
    let rate = $(this).parent().siblings().find('.price').val();
    rate = parseFloat(removeCommas(rate));
    let amount = calculateAmount(qty, rate, total_tax);
    calculateTax();
    $(this).
        parent().
        siblings('.item-total').
        text(addCommas((amount.toFixed(2)).toString()));
    calculateAndSetInvoiceAmount();
});

listenKeyup('.price', function () {
    let rate = $(this).val();
    rate = parseFloat(removeCommas(rate));
    //let tax = parseFloat($(this).parent().siblings().find('.tax').val());
    let tax = $(this).parent().siblings().find('.tax').val();
    let total_tax = 0;
    $.each(tax, function (index, value) {
        total_tax += +value;
    });
    let qty = $(this).parent().siblings().find('.qty').val();
    let amount = calculateAmount(qty, rate, total_tax);
    calculateTax();
    $(this).
        parent().
        siblings('.item-total').
        text(addCommas((amount.toFixed(2)).toString()));
    calculateAndSetInvoiceAmount();
});

const calculateAmount = (qty, rate,tax) => {
    if (qty > 0 && rate > 0) {
        let price = qty * rate;
        let allTax = price + (price * tax) / 100;
        if (isNaN(allTax)) {
            return price;
        }
        return allTax;
    } else {
        return 0;
    }
};

const calculateTax = () => {
    let taxData = [];

    $('.qty').each(function () {
        let qty = $(this).val();
        let price = $(this).parent().next().children().val();
        let tax = $(this).parent().next().next().children().val();
        let total_tax = 0;
        $.each(tax, function (index, value) {
            total_tax += +value;
        });
        let itemTax = qty * price * total_tax / 100;
        taxData.push(itemTax);
    });

    $('#totalTax').empty();
    $('#totalTax').append(number_format(taxData.reduce((a, b) => a + b, 0)));
};

const calculateAndSetInvoiceAmount = () => {
    let totalAmount = 0;
    $('.invoice-item-container>tr').each(function () {
        let itemTotal = $(this).find('.item-total').text();
        itemTotal = removeCommas(itemTotal);
        itemTotal = isEmpty($.trim(itemTotal)) ? 0 : parseFloat(itemTotal);
        totalAmount += itemTotal;
    });

    totalAmount = parseFloat(totalAmount);
    if (isNaN(totalAmount)) {
        totalAmount = 0;
    }
    $('#total').text(addCommas(totalAmount.toFixed(2)));

    //set hidden input value
    $('#total_amount').val(totalAmount);

    calculateDiscount();
};

const calculateDiscount = () => {
    let discount = $('#discount').val();
    discountType = $('#discountType').val();
    let itemAmount = [];
    let i=0;
    $(".item-total").each(function () {
        itemAmount[i++] = $.trim(removeCommas($(this).text()));
    })
    $.sum = function(arr) {
        var r = 0;
        $.each(arr, function(i, v) {
            r += +v;
        });
        return r;
    }

    let totalAmount = $.sum(itemAmount);

    $('#total').text(number_format(totalAmount));
    if (isEmpty(discount) || isEmpty(totalAmount)) {
        discount = 0;
    }
    let discountAmount = 0;
    let finalAmount = totalAmount - discountAmount;
    if (discountType == 1) {
        discountAmount = discount;
        finalAmount = totalAmount - discountAmount;
    } else if (discountType == 2) {
        discountAmount = (totalAmount * discount / 100);
        finalAmount = totalAmount - discountAmount;
    }
    $('#finalAmount').text(number_format(finalAmount));
    $('#total_amount').val(finalAmount.toFixed(2));
    $('#discountAmount').text(number_format(discountAmount));
};

listen('keyup', '#discount', function (){
    let value = $(this).val();
    if (discountType == 2 && value > 100) {
        displayErrorMessage('On Percentage you can only give maximum 100% discount');
        $(this).val(value.slice(0, -1));

        return false;
    }
    calculateDiscount();
})

listenClick('#saveAsDraft,#saveAndSend', function (event) {
    event.preventDefault();
    let tax_id = [];
    let i = 0;
    let tax = [];
    let j = 0;
    $('.tax-tr').each(function () {
        let data = $(this).find('.tax option:selected').map(function () {
            return $(this).data('id');
        }).get();
        if (data != '') {
            tax_id[i++] = data;
        } else {
            tax_id[i++] = 0;
        }

        let val = $(this).find('.tax option:selected').map(function () {
            return $(this).val();
        }).get();

        if (val != '') {
            tax[j++] = val;
        } else {
            tax[j++] = 0;
        }
    });

    let invoiceStates = $(this).data('status');
    let myForm = document.getElementById('invoiceForm');
    let formData = new FormData(myForm);
    formData.append('status', invoiceStates);
    formData.append('tax_id', JSON.stringify(tax_id));
    formData.append('tax', JSON.stringify(tax));

    if (invoiceStates == 1) {
        swal({
            title: 'Send Invoice !',
            text: 'Are you sure want to send this invoice to client ?',
            icon: 'warning',
            buttons: ["Yes, Send", "No, Cancel"],
        }).then(function (willSend) {
            if (!willSend) {
                screenLock();
                $.ajax({
                    url: route('invoices.store'),
                    type: 'POST',
                    dataType: 'json',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function () {
                        startLoader();
                    },
                    success: function (result) {
                        displaySuccessMessage(result.message)
                        Turbo.visit(route('invoices.index'))
                    }, error: function (result) {
                        displayErrorMessage(result.responseJSON.message);
                    },
                    complete: function () {
                        stopLoader();
                        screenUnLock();
                    },
                });
            }
        });
    } else {
        screenLock();
        $.ajax({
            url: route('invoices.store'),
            type: 'POST',
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                startLoader();
            },
            success: function (result) {
                displaySuccessMessage(result.message)
                Turbo.visit(route('invoices.index'))
            }, error: function (result) {
                displayErrorMessage(result.responseJSON.message);
            },
            complete: function () {
                stopLoader();
                screenUnLock();
            },
        });
    }
});

listenClick('#editSaveAndSend,#editSave', function (event) {
        event.preventDefault();
        let invoiceStatus = $(this).data('status');
        let tax_id = [];
        let i = 0;
        let tax = [];
        let j = 0;
        $('.tax-tr').each(function () {
            let data = $(this).find('.tax option:selected').map(function () {
                return $(this).data('id');
            }).get();
            if (data != '') {
                tax_id[i++] = data;
            } else {
                tax_id[i++] = 0;
            }

            let val = $(this).find('.tax option:selected').map(function () {
                return $(this).val();
            }).get();

            if (val != '') {
                tax[j++] = val;
            } else {
                tax[j++] = 0;
            }
        });

        let formData = $('#invoiceEditForm').serialize() + '&invoiceStatus=' +
            invoiceStatus + '&tax_id=' + JSON.stringify(tax_id) + '&tax=' +
            JSON.stringify(tax);
        if (invoiceStatus == 1) {
            swal({
                title: 'Send Invoice !',
                text: 'Are you sure want to send this invoice to client ?',
                icon: 'warning',
                buttons: ["Yes, Send", "No, Cancel"],
            }).then(function (willSend) {
                if (!willSend) {
                    screenLock();
                    $.ajax({
                        url: $('#invoiceUpdateUrl').val(),
                        type: 'PUT',
                        dataType: 'json',
                        data: formData,
                        beforeSend: function () {
                            startLoader();
                        },
                        success: function (result) {
                            displaySuccessMessage(result.message)
                            Turbo.visit(route('invoices.index'))
                        }, error: function (result) {
                            displayErrorMessage(result.responseJSON.message);
                        },
                        complete: function () {
                            stopLoader();
                            screenUnLock();
                        },
                    });
                }
            });
        } else if (invoiceStatus == 0) {
            screenLock();
            $.ajax({
                url: $('#invoiceUpdateUrl').val(),
                type: 'PUT',
                dataType: 'json',
                data: formData,
                beforeSend: function () {
                    startLoader();
                },
                success: function (result) {
                    displaySuccessMessage(result.message)
                    Turbo.visit(route('invoices.index'))
                }, error: function (result) {
                    displayErrorMessage(result.responseJSON.message);
                },
                complete: function () {
                    stopLoader();
                    screenUnLock();
                },
            });
        }
    });
