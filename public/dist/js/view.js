$(function() {
    var $checkout         = $('#checkout'),
        $addCart          = $('#addCart'),
        $add_cart_success = $('#add-cart-success'),
        $cart_count       = $('#cart-count-2'),
        $product_quantity = $("input[name=quantity]"),
        $option_value     = $("input[type=radio]"),
        $product_price    = $("#product_price"),
        $product_discount = $("#product_discount");

    var base_price = parseInt($product_price.html());

    var $decrease  = $('#decrease'),
        $increase  = $('#increase'),
        $number    = $('#quantity-number');

    $option_value
        .on('click', function (e) {
            var option_price = parseInt(this.getAttribute("title"));
            var real_price   = base_price + option_price;
            $product_price.html(real_price + '.00');
            var dis_price = real_price * 0.88;
            $product_discount.html(dis_price.toFixed(2));
        })
    ;
    // 选择数量
    $decrease
        .on('click', function (e) {
            var count = parseInt($product_quantity.val());
            if (count > 1) {
                count--;
            }
            $product_quantity.val(count);
            $number.html(count);
        })
    ;
    $increase
        .on('click', function (e) {
            var count = parseInt($product_quantity.val());
            count++;
            $product_quantity.val(count);
            $number.html(count);
        })
    ;
    // 加入购物车
    $addCart
        .on('click', function (e) {
            $.post('/cart/add', $('#product').serialize(), function(response) {
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
            $.post('/cart/add', $('#product').serialize(), function(response) {
                location.href = '../../cart.html';
            });
        })
    ;
});