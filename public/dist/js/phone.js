var submit = document.querySelector('#submit'),
  sendCode = document.querySelector('#sendCode'),
     phone = document.querySelector('#phone'),
     code  = document.querySelector('#code');

submit.addEventListener('click', function () {
    weui.form.validate('#form', function (error) {
        if (!error) {
            axios.post('/account/savephone', {
                phone: phone.value,
                code: code.value
            })
            .then(function (response) {
                console.log(response.data);
                if (response.data.code != 0) {
                    weui.topTips(response.data.msg);
                } else {
                    weui.toast('手机号更新成功', 1000);
                    setTimeout(function () {
                        location.href = '/account.html';
                    }, 1000);
                }
            })
            .catch(function (error) {
                console.log(error);
            });
        }
    });
});

sendCode.addEventListener('click', function () { // 发送验证码
    var re = /^\d{11}$/;

    if (! re.test(phone.value)) {
        weui.topTips('请输入正确的手机号！');
        return;
    }

    axios.post('/account/sendcode', {
        phone: phone.value
    })
    .then(function (response) {
        console.log(response.data);
        if (response.data.code != 0) {
            weui.topTips(response.data.msg);
        } else {
            weui.toast('发送成功', 1000);
        }
    })
    .catch(function (error) {
        console.log(error);
    });
});