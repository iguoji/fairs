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
    data: function(type, parent, target, selector, callback){
        var container = selector.split(' ').slice(0, -1).join(' ');
        switch (type) {
            case 1:
                $(container + ' select[name=province]').html('<option value="">请选择省份</option>');
                $(container + ' select[name=city]').html('<option value="">请选择城市</option>');
                $(container + ' select[name=county]').html('<option value="">请选择区县</option>');
                $(container + ' select[name=town]').html('<option value="">请选择乡镇</option>');
                $(container + ' select[name=village]').html('<option value="">请选择村庄</option>');
                break;
            case 2:
                $(container + ' select[name=city]').html('<option value="">请选择城市</option>');
                $(container + ' select[name=county]').html('<option value="">请选择区县</option>');
                $(container + ' select[name=town]').html('<option value="">请选择乡镇</option>');
                $(container + ' select[name=village]').html('<option value="">请选择村庄</option>');
                break;
            case 3:
                $(container + ' select[name=county]').html('<option value="">请选择区县</option>');
                $(container + ' select[name=town]').html('<option value="">请选择乡镇</option>');
                $(container + ' select[name=village]').html('<option value="">请选择村庄</option>');
                break;
            case 4:
                $(container + ' select[name=town]').html('<option value="">请选择乡镇</option>');
                $(container + ' select[name=village]').html('<option value="">请选择村庄</option>');
                break;
            case 5:
                $(container + ' select[name=village]').html('<option value="">请选择村庄</option>');
                break;
            default:
                break;
        }


        ajax.post('/region/data', {type: type, parent: parent}, function(res){
            if (res && res.code == 200) {
                var html = '';
                switch (type) {
                    case 1:
                        html += '<option value="">请选择国家</option>';
                        break;
                    case 2:
                        html += '<option value="">请选择省份</option>';
                        break;
                    case 3:
                        html += '<option value="">请选择城市</option>';
                        break;
                    case 4:
                        html += '<option value="">请选择区县</option>';
                        break;
                    case 5:
                        html += '<option value="">请选择乡镇</option>';
                        break;
                    case 6:
                        html += '<option value="">请选择村庄</option>';
                        break;
                    default:
                        break;
                }
                for (let i = 0; i < res.data.length; i++) {
                    const ele = res.data[i];
                    if (target == ele.id) {
                        html += '<option selected value="' + ele.id + '">' + ele.name + '</option>';
                    } else {
                        html += '<option value="' + ele.id + '">' + ele.name + '</option>';
                    }
                }
                $(selector).html(html);
                callback && callback(res.data);
            } else {
                toastr.error(res && res.message ? res.message : '很抱歉、服务器繁忙！');
            }
        });
    },
    countrys: function(selector){
        region.data(1, '', '', selector);

    },
    provinces: function(country, selector){
        region.data(2, country, '', selector);
    },
    citys: function(province, selector){
        region.data(3, province, '', selector);
    },
    countys: function(city, selector){
        region.data(4, city, '', selector);
    },
    towns: function(county, selector){
        region.data(5, county, '', selector);
    },
    villages: function(town, selector){
        region.data(6, town, '', selector);
    },
    selected: function(data, container){
        if (data.country) {
            // 国家
            region.data(1, '', data.country, container + ' select[name=country]', function(countrys){
                // 省份
                if (countrys && countrys.length) {
                    region.data(2, data.country, data.province, container + ' select[name=province]', function(provinces){
                        // 城市
                        if (provinces && provinces.length) {
                            region.data(3, data.province, data.city, container + ' select[name=city]', function(citys){
                                // 区县
                                if (citys && citys.length) {
                                    region.data(4, data.city, data.county, container + ' select[name=county]', function(countys){
                                        // 乡镇
                                        if (countys && countys.length) {
                                            region.data(5, data.county, data.town, container + ' select[name=town]', function(towns){
                                                // 村落
                                                if (towns && towns.length) {
                                                    region.data(6, data.town, data.village, container + ' select[name=village]');
                                                }
                                            });
                                        }
                                    });
                                }
                            });
                        }
                    });
                }
            });
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
        var format = $(ele).data('format');
        var enableTime = $(ele).data('enable-time');
        window.flatpickrs[name] = $(ele).flatpickr({
            dateFormat: format && format.length ? format : 'Y-m-d H:i:S',
            enableTime: enableTime == 'false' || enableTime == false ? false : true,
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



    // 省市区级联
    if ($('.region select[name=country]').length) {
        region.countrys('.region select[name=country]');
    }
    $('.region select[name=country]').on('change', function(){
        region.provinces($(this).val(), '.region select[name=province]');
    });
    $('.region select[name=province]').on('change', function(){
        region.citys($(this).val(), '.region select[name=city]');
    });
    $('.region select[name=city]').on('change', function(){
        region.countys($(this).val(), '.region select[name=county]');
    });
    $('.region select[name=county]').on('change', function(){
        region.towns($(this).val(), '.region select[name=town]');
    });
    $('.region select[name=town]').on('change', function(){
        region.villages($(this).val(), '.region select[name=village]');
    });




    // 图片上传
    $('.avatar-upload').on('change', 'input[type=file]', function(){
        var file = $(this)[0].files[0];
        $parent = $(this).parents('.avatar-upload');
        ajax.upload('/file/upload', {file: file}, function(res){
            if (res && res.code == 200) {
                $parent.css('backgroundImage', 'url(' + res.data.url + ')');
                $parent.parents('.avatar-container').find('.avatar-input').val(res.data.url);
            } else {
                toastr(res && res.message ? res.message : '很抱歉、服务器繁忙！');
            }
        });
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