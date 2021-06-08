// Ajax
var ajax = {
    post: function(url, data, callback){
        $.post(url, data, callback);
    }
}
// 确认框
if (window.bootstrap) {
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
}
$(function(){
    // 错误提示
    if (exception.length > 5) {
        errorinfo = JSON.parse(exception);
        if (errorinfo[2].length) {
            toastr.error('[' + errorinfo[2][2] + ']: ' + errorinfo[2][1], errorinfo[1]);
        } else {
            toastr.error(errorinfo[1]);
        }
    }
    // 菜单折叠
    $('.navbar-menu').on('click', '.dropdown', function(){
        console.log($(this).text());
        $(this).parents('.navbar-menu').toggleClass('active');
    });
    // 全选反选
    $('table').on('click', '.checkBoxChoose', function(){
        var checked = $(this).prop('checked');
        $(this).parents('table').find('.checkBoxItem').prop('checked', checked);
    });
    $('table').on('click', '.checkBoxItem', function(){
        var id = $(this).val();
        var checked = $(this).prop('checked');
        var $table = $(this) .parents('table');
        var fn = function(id){
            $table.find('.checkBoxItem[data-parent=' + id + ']').each(function(idx, ele){
                $(ele).prop('checked', checked);
                fn($(ele).val());
            });
        }
        fn(id);
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