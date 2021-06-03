// Ajax
var ajax = {
    post: function(url, data, callback){
        $.post(url, data, callback);
    }
}
// 确认框
var modalConfirm = new bootstrap.Modal(document.getElementById('modal-confirm'));
var modal = {
    confirmCallback: function(){
        modalConfirm.hide();
    },
    confirm: function(callback) {
        modal.confirmCallback = function(){
            callback();
            modalConfirm.hide();
        };
        modalConfirm.show();
    }
}
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
    // 全选反选
    $('.checkBoxChoose').on('click', function(){
        var checked = $(this).prop('checked');
        $(this).parents('table').find('.checkBoxItem').prop('checked', checked);
    });
    // 单选框
    $('.radio-item').on('change', function(){
        var target = $(this).data('target');
        $('input[name=' + target + ']').val($(this).val());
    });
    // 警告确认
    $('.btn-modal-confirm').on('click', function(ev){
        var href = $(this).attr('href');
        modal.confirm(function(){
            window.location.href = href;
        });
        return false;
    });
});