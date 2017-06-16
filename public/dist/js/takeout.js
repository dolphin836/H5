function mode()
{
    var radio  = document.getElementsByName('mode');
    var mode   = "transaction";
    for (i = 0; i < radio.length; i++) {  
        if (radio[i].checked) {  
            mode = radio[i].value; 
        } 
    }  

    return mode;
}

var amount  = document.querySelector('#amount').innerText;

var takeout = document.querySelector('#takeout');

takeout.addEventListener('click', function () {
    weui.dialog({
        title    : '确认提现信息',
        content  : '提现金额：' + amount,
        className: 'custom-classname',
        buttons  : [{
            label  : '取消',
            type   : 'default',
            onClick: function () {

            }
        }, {
            label  : '确定',
            type   : 'primary',
            onClick: function () {
                axios.post('/recommend/out', {
                    mode: mode()
                })
                .then(function (response) {
                    console.log(response.data);
                    if (response.data.code != 0) {
                        alert(response.data.msg);
                        return;
                    }

                    location.reload();
                })
                .catch(function (error) {
                    console.log(error);
                });
            }
        }]
    });
});