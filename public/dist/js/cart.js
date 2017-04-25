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

    $('#wePay').on('click', function() {// 发起微信支付

    });

});