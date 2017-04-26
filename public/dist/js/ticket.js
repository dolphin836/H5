$(function() {
    $('.weui-navbar__item').on('click', function () { // Tab 切换
        $(this).addClass('weui-bar__item_on').siblings('.weui-bar__item_on').removeClass('weui-bar__item_on');
        
        $.each($(".weui-tab__panel_page"), function() {
            if ( $(this).css("display") == "none" ) {
                $(this).css("display", "block");
            } else {
                $(this).css("display", "none");
            }
        });
    });
});
