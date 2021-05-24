$(function(){
    // 错误提示
    if (exception.length > 5) {
        exception = JSON.parse(exception);
        toastr.error(exception[1]);
    }
    // 菜单折叠
    $('.navbar-menu').on('click', '.dropdown', function(){
        console.log($(this).text());
        $(this).parents('.navbar-menu').toggleClass('active');
    });
});