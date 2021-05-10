$(function(){
    // 显示密码
    $('.togglePassword').on('click', function(){
        $input = $(this).parents('.input-group').find('input');
        if ($input.attr('type') == 'text') {
            $input.attr('type', 'password');
        } else {
            $input.attr('type', 'text');
        }
    });
});