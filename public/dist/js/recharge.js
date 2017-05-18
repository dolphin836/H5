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

function getAmount()
{
    var radio  = document.getElementsByName('amount');
    var amount = 0;
    for (i = 0; i < radio.length; i++) {  
        if (radio[i].checked) {  
            amount = radio[i].value; 
        } 
    }  

    return amount;
}


var submit = document.querySelector('#submit');

submit.addEventListener('click', function () {
    if (is_weixin()) {
        weui.actionSheet([
            {
                label: "<img src='https://act.weixin.qq.com/static/cdn/img/wepayui/0.1.1/wepay_logo_default_green_500x126.png' style='width:160px;height:40px;'>",
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
                    //
                    axios.post('/account/weixin', {
                        amount: getAmount()
                    })
                    .then(function (response) {
                        console.log(response.data);
                        if (response.data.code != 0) {
                            alert(response.data.msg);
                            return;
                        }

                        var data = response.data.data;

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
                                    location.href = '/account/transaction.html';
                                }
                            }
                        );
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
                                location.href = '/account/transaction.html';
                            }
                        }]
                    });

                    axios.post('/account/zhi', {
                        amount: getAmount()
                    })
                    .then(function (response) {
                        console.log(response.data);
                        if (response.data.code != 0) {
                            alert(response.data.msg);
                            return;
                        }

                        var el     = document.createElement("form");
                        var attr   = document.createAttribute("id");
                        attr.value = "zhiPay";
                        el.setAttributeNode(attr);
                        var attr   = document.createAttribute("action");
                        attr.value = "https://openapi.alipay.com/gateway.do?charset=utf-8";
                        el.setAttributeNode(attr);
                        var attr   = document.createAttribute("method");
                        attr.value = "POST";
                        el.setAttributeNode(attr);
                        document.body.appendChild(el);

                        var data = response.data.data;

                        var attr, el;
                        for(var i in data) {
                            el         = document.createElement("input");
                            attr       = document.createAttribute("type");
                            attr.value = "hidden";
                            el.setAttributeNode(attr);
                            attr       = document.createAttribute("name");
                            attr.value = i;
                            el.setAttributeNode(attr);
                            attr       = document.createAttribute("value");
                            attr.value = data[i];
                            el.setAttributeNode(attr);
                            document.querySelector('#zhiPay').appendChild(el);
                        }

                        document.querySelector('#zhiPay').submit();
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