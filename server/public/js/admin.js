// Ajax
var ajax = {
    post: function(url, data, callback){
        $.post(url, data, callback);
    },
    upload: function(url, data, callback){
        var formData = new FormData();
        for (var key in data) {
            formData.append(key, data[key]);
        }
        $.ajax({
            url: url,
            type: 'POST',
            cache: false,
            data: formData,
            processData: false,
            contentType: false,
            dataType:"json",
            success : callback
        });
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
// 省市区级联
var region = {
    data: function(vdom, params, target, callback){
        let $container = $(vdom).parents('.region');
        switch (params.type) {
            case 2:
                $container.find('select[name=province]').html('<option value="">请选择省份</option>');
                $container.find('select[name=city]').html('<option value="">请选择城市</option>');
                $container.find('select[name=county]').html('<option value="">请选择区县</option>');
                break;
            case 3:
                $container.find('select[name=city]').html('<option value="">请选择城市</option>');
                $container.find('select[name=county]').html('<option value="">请选择区县</option>');
                break;
            case 4:
                $container.find('select[name=county]').html('<option value="">请选择区县</option>');
                break;
            default:
                break;
        }
        target = target && target.length ? target : $(vdom).data('default');
        ajax.post('/regions', params, function(res){
            if (res && res.code == 200) {
                var html = '';
                for (let i = 0; i < res.data.length; i++) {
                    const ele = res.data[i];
                    if (target == ele.id || res.data.length == 1) {
                        html += '<option selected value="' + ele.id + '">' + ele.name + '</option>';
                    } else {
                        html += '<option value="' + ele.id + '">' + ele.name + '</option>';
                    }
                }
                $(vdom).append(html);
                $(vdom).trigger('change');
                callback && callback(res.data);
            } else {
                toastr.error(res && res.message ? res.message : '很抱歉、服务器繁忙！');
            }
        });
    },
    provinces: function(vdom, country, target, callback){
        region.data(vdom, {
            type: 2,
            country: country,
        }, target, callback);
    },
    citys: function(vdom, province, country, target, callback){
        region.data(vdom, {
            type: 3,
            country: country,
            province: province,
        }, target, callback);
    },
    countys: function(vdom, city, province, country, target, callback){
        region.data(vdom, {
            type: 4,
            country: country,
            province: province,
            city: city,
        }, target, callback);
    },
    selected: function(data, container){
        // 省份
        region.provinces($(container + ' select[name=province]'), '86', data.province);
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
        var format = $(ele).data('format');
        var enableTime = $(ele).data('enable-time');
        var defaultValue = $(ele).data('default');
        window.flatpickrs[name] = $(ele).flatpickr({
            dateFormat: format && format.length ? format : 'Y-m-d H:i:S',
            enableTime: enableTime == 'false' || enableTime == false ? false : true,
            time_24hr: true,
            defaultDate: defaultValue && defaultValue.trim().length ? defaultValue : null,
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



    // 省市区级联
    $('.region select[name=province]').each(function(idx, ele){
        region.provinces(ele, '86');
    });
    $('.region').on('change', 'select', function(){
        let $container = $(this).parents('.region');
        let name = $(this).attr('name');
        let value = $(this).val();
        if (value.trim() == '') {
            switch (name) {
                case 'province':
                    $container.find('select[name=city] option:gt(0)').remove();
                    $container.find('select[name=county] option:gt(0)').remove();
                    break;
                case 'city':
                    $container.find('select[name=county] option:gt(0)').remove();
                    break;
            }
            return;
        }
        let country = '86';
        let province = $container.find('select[name=province]').val();
        let city = $container.find('select[name=city]').val();
        switch (name) {
            case 'province':
                region.citys($container.find('select[name=city]'), province, country);
                break;
            case 'city':
                region.countys($container.find('select[name=county]'), city, province, country);
                break;
            default:
                break;
        }
    });




    // 图片上传
    $('.avatar-upload').on('change', 'input[type=file]', function(){
        var files = $(this)[0].files;
        if (files.length) {
            $that = $(this);
            $parent = $(this).parents('.avatar-upload');
            ajax.upload('/file/upload', {file: files[0]}, function(res){
                $that.after($that.clone().val('').attr('index', Date.now())).remove();;
                if (res && res.code == 200) {
                    $parent.css('backgroundImage', 'url(' + res.data.url + ')');
                    $input = $parent.parents('.avatar-container').find('.avatar-input')
                    $input.val(res.data.url);
                    $input.trigger('change');
                } else {
                    toastr(res && res.message ? res.message : '很抱歉、服务器繁忙！');
                }
            });
        }
    });
    // 图片清除
    $('.avatar-upload-clear').on('click', function(){
        $container = $(this).parents('.avatar-container');
        var oldValue = $container.find('.avatar-input').val();
        if (oldValue.trim() == '') {
            return;
        }
        $container.find('.avatar-input').val('  ');
        $container.find('.avatar-input').trigger('change');
        $container.find('.avatar').css('backgroundImage', 'none');
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