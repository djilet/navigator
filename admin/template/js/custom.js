function ConfirmHref(msg, title, a) {
    msg = msg.replace(/%Title%/g, title);
    ModalConfirm(msg, function () {
        window.location.href = $(a).attr('href');
    });
}

function doAction(action, form) {
    if (form.length){
        let input = form.find('[name=Action]');
        if (input.length){
            input.val(input);
        }
        else{
            input = $('<input type="hidden" name="Action" value="' + action + '">');
            form.append(input);
        }

        form.submit();
        input.remove();
    }
}