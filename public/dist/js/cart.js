$(function() {
    var $cart_clean       = $('#cart-clean'),
        $cart_remove      = $('.cart-remove'),
        $checkout         = $('#checkout'),
        $pay_again        = $('#pay-again'),
        $pay_back         = $('#pay-back');

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
    $pay_again
        .on('click', function (e) {
            location.reload();
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
        hideActionSheet();

        $.post('/addOrder', function(response) {
            var response = $.parseJSON(response);
            console.log(response);

            if (response.code != 0) {
                alert(response.msg);
                return;
            }

            var data = response.data;
            console.log(data);

            WeixinJSBridge.invoke(
                'getBrandWCPayRequest', {
                    "appId"     : data.appId,
                    "timeStamp" : data.timeStamp + "",  
                    "nonceStr"  : data.nonceStr,
                    "package"   : data.package, 
                    "signType"  : data.signType,
                    "paySign"   : data.paySign
                },
                function(res) {     
                    if (res.err_msg == "get_brand_wcpay_request:ok" ) {
                        //TO DO
                        $pay_back.fadeIn(200);
                    }
                }
            );
        });
    });

});