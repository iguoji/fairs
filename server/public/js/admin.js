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
    // 时间日期
    Date.prototype.format = function(rule){
        rule = rule ? rule : 'Y-m-d H:i:s';

        let date = '';
        for (let _i_ = 0; _i_ < rule.length; _i_++) {
            const char = rule[_i_];
            switch (char) {
                case 'Y':
                    date += this.getFullYear();
                    break;
                case 'm':
                    var m = this.getMonth() + 1;
                    date += m < 10 ? '0' + m : m;
                    break;
                case 'd':
                    var d = this.getDate();
                    date += d < 10 ? '0' + d : d;
                    break;
                case 'H':
                    var h = this.getHours();
                    date += h < 10 ? '0' + h : h;
                    break;
                case 'i':
                    var i = this.getMinutes();
                    date += i < 10 ? '0' + i : i;
                    break;
                case 's':
                    var s = this.getSeconds();
                    date += s < 10 ? '0' + s : s;
                    break;
                default:
                    date += char;
                    break;
            }
        }

        return date;
    }
    if (window.flatpickr) {
        flatpickr.l10ns.default.months = {
            longhand: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
            shorthand: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月']
        }
        flatpickr.l10ns.default.weekdays = {
            longhand: ['日', '一', '二', '三', '四', '五', '六'],
            shorthand: ['日', '一', '二', '三', '四', '五', '六']
        }
        flatpickr.l10ns.default.firstDayOfWeek = 1;
        window.flatpickrs = [];
    }
    $('.flatpickr').each(function(idx, ele){
        var name = $(ele).attr('name');
        window.flatpickrs[name] = $(ele).flatpickr({
            dateFormat: 'Y-m-d H:i:S',
            enableTime: true,
            time_24hr: true,
        });
    });
    $('.flatpickr-quick').on('click', function(){
        let date = new Date();
        let time = date.getTime();
        let dates = [];
        switch ($(this).val()) {
            case 'today':
                dates.push(date.format('Y-m-d') + ' 00:00:00');
                dates.push(date.format('Y-m-d') + ' 23:59:59');
                break;
            case 'yesterday':
                date.setTime(time - 1000 * 60 * 60 * 24);
                dates.push(date.format('Y-m-d') + ' 00:00:00');
                dates.push(date.format('Y-m-d') + ' 23:59:59');
                break;
            case 'lastSevenDays':
                date.setTime(time - 1000 * 60 * 60 * 24 * 7);
                dates.push(date.format('Y-m-d') + ' 00:00:00');
                date.setTime(time);
                dates.push(date.format('Y-m-d') + ' 23:59:59');
                break;
            case 'nearly30Days':
                date.setTime(time - 1000 * 60 * 60 * 24 * 30);
                dates.push(date.format('Y-m-d') + ' 00:00:00');
                date.setTime(time);
                dates.push(date.format('Y-m-d') + ' 23:59:59');
                break;
            default:
                break;
        }

        let targets = $(this).data('target');
        targets = targets.split(',');
        for (let i = 0; i < dates.length && i < targets.length; i++) {
            window.flatpickrs[targets[i]].setDate(dates[i]);
        }
    });
    $('.flatpickr-clear').on('click', function(){
        let targets = $(this).data('target');
        targets = targets.split(',');
        for (let i = 0; i < targets.length; i++) {
            window.flatpickrs[targets[i]].clear();
        }
    });



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