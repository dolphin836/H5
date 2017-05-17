function is_weixin()
{
    var ua = navigator.userAgent.toLowerCase();

    if(ua.match(/MicroMessenger/i) == "micromessenger")
    {
        return true;
    } 
    else 
    {
        return false;
    }
}

var submit = document.querySelector('#submit');

submit.addEventListener('click', function () {
    if (is_weixin()) {
        weui.actionSheet([
            {
                label: '微信支付',
                onClick: function () {
                    console.log('微信支付');
                }
            }
        ], [
            {
                label: '取消',
                onClick: function () {
                    console.log('取消');
                }
            }
        ], {
            className: 'weixin'
        });
    } else {
        weui.actionSheet([
            {
                label: "<img src='../dist/img/zhi.png' style='width:120px;height:54px;'>",
                onClick: function () {
                    weui.dialog({
                        title    : '支付结果',
                        content  : '您已经完成支付了吗？',
                        className: 'feedback',
                        buttons  : [{
                            label  : '重新支付',
                            type   : 'default',
                            onClick: function () {
                                location.reload();
                            }
                        },{
                            label  : '支付成功',
                            type   : 'primary',
                            onClick: function () {
                                location.href = '/account.html';
                            }
                        }]
                    });

                    axios.post('/account/zhi', {
                        amount: 0.01
                    })
                    .then(function (response) {
                        console.log(response.data);
                    })
                    .catch(function (error) {
                        console.log(error);
                    });
                }
            }
        ], [
            {
                label: '取消',
                onClick: function () {
                    console.log('取消');
                }
            }
        ], {
            className: 'zhi'
        });
    }
});