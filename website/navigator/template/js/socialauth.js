$(document).ready(function(){
    
    /*click vk*/
    $('.block-registration .modal__social-vk').click(function(e) {
        e.preventDefault();

        var dualScreenLeft = window.screenLeft !== undefined ? window.screenLeft : screen.left;
        var dualScreenTop = window.screenTop !== undefined ? window.screenTop : screen.top;

        var screenWidth = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
        var screenHeight = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;
        
        var width = 540;
        var height = 680;

        var left = ((screenWidth / 2) - (width / 2)) + dualScreenLeft;
        var top = ((screenHeight / 2) - (height / 2)) + dualScreenTop;

        var params = 'menubar=no,location=no,resizable=yes,scrollbars=yes,status=yes,width='+width+',height='+height+',left='+left+',top='+top;
        var newWin = window.open(project_path + 'profile/socialdata/vk/', "Авторизация в ВК", params);
        newWin.socialResultHundler = VKAuthResult;
        window.socialResultHundler = VKAuthResult;
    });
    
     /*click fb*/
    $('.block-registration .modal__social-fb').click(function(e) {
        e.preventDefault();
        FB.getLoginStatus(function(response) {
            if (response.status === 'connected') {
                FBAuthResult(response);
            } else {
                FB.login(FBAuthResult);
            }
        });
    });
});

function VKAuthResult(response) {
    if(response.session){
        var user = response.session.user;
        insertToFields(user.first_name, user.last_name, user.email, ckf_authSuccess);
    }
}

function FBAuthResult(response) {
    if (response.status === 'connected') {
        FB.api('/me?fields=first_name,last_name,email&locale=ru_RU', function(response) {
            if (!response.error) {
                insertToFields(response.first_name, response.last_name, response.email, ckf_authSuccess);
            }
        });
    }
}

function insertToFields(first_name, last_name, email, callback = null){
    var block = $('.wrap-block-registration .block-registration:first');
    block.find('input[name="RegisterForm[UserName][]"]').val(first_name);
    block.find('input[name="RegisterForm[UserLastName][]"]').val(last_name);
    block.find('input[name="RegisterForm[UserEmail][]"]').val(email);
    if (callback !== null) {
        callback(block);
    }
}

var ckf_authSuccess = function(block){
    if (typeof successAuthMessage != 'undefined') {
        var item = '<li>' + successAuthMessage + '</li>';
        block.find('.message-list').html(item);
    }
}

if (window.parent.socialResultHundler) {
    window.parent.socialResultHundler({"status":"true"});
    window.close();
}