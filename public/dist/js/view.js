$(function() {
    var $sliderTrack      = $('#sliderTrack'),
        $sliderHandler    = $('#sliderHandler'),
        $sliderValue      = $('#sliderValue'),
        $checkout         = $('#checkout'),
        $addCart          = $('#addCart'),
        $add_cart_success = $('#add-cart-success'),
        $cart_count       = $('#cart-count'),
        $product_quantity = $("input[name=quantity]");

    var totalLen  = $('#sliderInner').width(),
        startLeft = 0,
        startX    = 0;
    // 选择数量
    $sliderHandler
        .on('touchstart', function (e) {
            startLeft = parseInt($sliderHandler.css('left')) * totalLen / 100;
            startX    = e.changedTouches[0].clientX;
        })
        .on('touchmove', function(e){
            var dist = startLeft + e.changedTouches[0].clientX - startX,
                percent;
            dist     = dist < 0 ? 0 : dist > totalLen ? totalLen : dist;
            percent  =  parseInt(dist / totalLen * 100);
            $sliderTrack.css('width', percent + '%');
            $sliderHandler.css('left', percent + '%');
            $sliderValue.text(percent);
            $product_quantity.val(percent);

            e.preventDefault();
        })
    ;
    // 加入购物车
    $addCart
        .on('click', function (e) {
            $.post('/cart/add', $('#product').serialize(), function(response) {
                console.log(response);
                $count = parseInt($cart_count.html()) + parseInt($product_quantity.val());
                if ($count >= 100) {
                    $count = '99+';
                }
                $cart_count.html($count);
                if ($cart_count.css('display') == 'none') {
                    $cart_count.css('display', 'block');
                }

                if ($add_cart_success.css('display') != 'none') return;

                $add_cart_success.fadeIn(100);

                setTimeout(function () {
                    $add_cart_success.fadeOut(100);
                }, 1000);
            });
        })
    ;
    // 立即购买
    $checkout
        .on('click', function (e) {
            $addCart.click();
            location.href = '../../cart.html';
        })
    ;
});