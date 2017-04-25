$(function() {
    var $cart_clean       = $('#cart-clean'),
        $cart_remove      = $('.cart-remove'),
        $checkout         = $('#checkout');

    // 清空购物车
    $cart_clean
        .on('click', function (e) {
            $.get('/cart/clean', function(response){
                location.reload();
            })
        })
    ;
    // 移除单个商品
    $cart_remove
        .on('click', function (e) {
            var server = '/cart/clean/' + this.getAttribute("title");
            $.get(server, function(response){
                location.reload();
            })
        })
    ;
    // 结算
    $checkout
        .on('click', function (e) {
        
        })
    ;

    //
    var $iosActionsheet = $('#iosActionsheet');
    var $iosMask        = $('#iosMask');

    function hideActionSheet() {
        $iosActionsheet.removeClass('weui-actionsheet_toggle');
        $iosMask.fadeOut(200);
    }

    $iosMask.on('click', hideActionSheet);

    $('#iosActionsheetCancel').on('click', hideActionSheet);

    $checkout.on("click", function() {
        $iosActionsheet.addClass('weui-actionsheet_toggle');
        $iosMask.fadeIn(200);
    });

    $('#weixinPay').on('click', function() {// 发起微信支付
        $.ajax({
            type     : 'POST',
            url      : '/addOrder',
            data     : { name: '11' },
            dataType : 'json',
            timeout  : 2000,
            context  : $('body'),
            success  : function(data){
                console.log(response);
                console.log(typeof response);
            },
            error: function(xhr, type){
                alert('Ajax error!')
            }
        })


        // $.post('/addOrder', function(response) {
        //     console.log(response);
        //     console.log(typeof response);
        //     var json = $.parseJSON(response);
        //     console.log(typeof json);
        //     console.log(json.code);

        //     // if (response.code != 0) {
        //     //     alert(response.msg);
        //     //     return;
        //     // }

        //     // var data = response.data;

        //     // WeixinJSBridge.invoke(
        //     //     'getBrandWCPayRequest', {
        //     //         "appId"     : data.appId,
        //     //         "timeStamp" : data.timeStamp + "",  
        //     //         "nonceStr"  : data.nonceStr,
        //     //         "package"   : data.package, 
        //     //         "signType"  : data.signType,
        //     //         "paySign"   : data.paySign
        //     //     },
        //     //     function(res) {     
        //     //         if (res.err_msg == "get_brand_wcpay_request:ok" ) {
        //     //             //TO DO
        //     //         }
        //     //     }
        //     // );
        // });
    });

});