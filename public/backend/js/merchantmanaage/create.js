"use strict";
$(document).ready(function(){
    $('#merchant').change(function(){

        var merchant=$(this).val();
        $.ajax({
            type : 'POST',
            url : $(this).data('url'),
            data : {'merchant_id':merchant},
            dataType : "html",
            success : function (data) {

                $('#merchant_account').html(data);
            }
        });

        $('#invoice').html('');
        paymentAmount();
         
    });

    $( "#merchant" ).select2({
        ajax: {
            url: $('#mercant_url').data('url'),
            type: "POST",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term,
                    searchQuery: true
                };
            },
            processResults: function (response) {
                console.log(response);
                return {

                    results: response
                };
            },
            cache: true
        }

    });


    $('#isprocess').change(function(){
        if($('#isprocess').is(':checked')){
             $('.process').show();
        }else{
            $('.process').hide();
        }
    });
    
   
    $( "#invoice" ).select2({
        ajax: {
            url: $("#invoice").data('url'),
            type: "POST",
            dataType: 'json',
            delay: 250, 
            data: function (params) {
                return {
                    search: params.term,
                    searchQuery: true,
                    merchant_id:$("select#merchant option").filter(":selected").val()
                };
            },
            processResults: function (response) { 
                return { 
                    results: response
                };
            },
            cache: true
        }
    });

    $('#invoice').change(function(){
        paymentAmount();
    });
    $('.select2-selection__choice__remove').click(function(){
        paymentAmount();
    });

    function paymentAmount(){
        var multiple_invoice_id = $('#invoice').val(); 
        var merchant_id=$("select#merchant option").filter(":selected").val();
        $.ajax({
            type : 'POST',
            url : invoice_amount_url,
            data : {
                merchant_id:merchant_id,
                multiple_invoice_id:multiple_invoice_id
            },
            dataType : "json",
            success : function (response) { 
                console.log(response);
                $('#amount').val(response);
            }
        });

    }

});
