function multiLevel() {
    var res = navigator.userAgent.toLowerCase().match(/android|iphone/);
    var self = this;
    var evalue, identifier, drawID, validform, css, iswap;
    this.init = function(option) {
        self.evalue = (option.evalue == undefined) ? 0 : option.evalue;
        self.identifier = option.identifier;
        self.drawID = option.drawID;
        self.validform = option.validform;
        self.defaultName = option.defaultName;
        self.css = option.css;
        self.basehost = option.basehost;
        self.iswap = option.iswap;
        if(self.identifier == undefined || self.drawID == undefined) {
            alert("identifier和drawID不能为空");
            return false;
        }
        self.ajax_get_enums_list();
    }
    this.ajax_get_enums_list = function() {
        $.getJSON(
            self.basehost + "/main.php?mod=ajax&ac=multilevel&identifier=" + self.identifier,
            function(result) {
                if(result.code != 100) {
                    alert(result.msg);
                }
                if(self.evalue == 0 || self.get_reid(result.data, self.evalue) == -1) {
                    self.draw_html(result.data);
                } else {
                    self.draw_html_by_evalue(result.data, self.evalue);
                }
                self.draw_input();
                if($('select').selected != undefined) {
                    if(res != 'android' && res != 'iphone') {
                        $('#' + self.identifier + '_span select').chosen({
                            allow_single_deselect: true,
                            disable_search_threshold: 10,
                            search_contains: true,
                        });
                    }
                }
            });
    };
    this.draw_input = function() {
        $("#" + self.drawID).append('<input type="hidden" name="' + self.identifier + '" id="' + self.identifier + '" value="' + self.evalue + '"/>');
    };
    this.draw_html = function(infolist, reid, parentreid) {
        if(reid == undefined) {
            $.each(infolist, function(k, v) {
                if(v.parentid == 0) {
                    reid = v.id;
                    return false;
                }
            });
        }
        parentreid = (parentreid == undefined) ? 'top' : parentreid;

        if(reid == -1 || reid == '') {
            self.remove_span(parentreid);
        }
        var html_str = '';
        html_str += '<span id="span_' + self.identifier + parentreid + '">';
        if(self.iswap==1) {
            html_str += '<div class="weui_cells"><div class="weui_cell weui_cell_select"><div class="weui_cell_bd weui_cell_primary">';
        }
        html_str += ' <select name="' + self.identifier + '_' + parentreid + '" id="' + self.identifier + '_' + parentreid + '" ' + self.validform + ' class="weui_select ' + self.css + '">';
        html_str += '<option value="">请选择..</option>';

        var option_str = "";
        $.each(infolist, function(k, v) {
            if(v.parentid == reid) {
                option_str += '<option value="' + v.id + '">' + v.name + '</option>';
            }
        });
        html_str += option_str;
        html_str += '</select>';
        if(self.iswap==1) {
            html_str += '</div></div></div>';
        }
        html_str += '</span>';
        if(reid != 0) {
            self.remove_span(parentreid);
        }
        if(option_str) {
            $("#" + self.drawID).append(html_str);
            $("#" + self.identifier + "_" + parentreid).change(function() {
                var _reid = $(this).val();
                self.draw_html(infolist, _reid, reid);
            });
        }
        $(":input[name='" + self.identifier + "']").val(reid == -1 ? parentreid : reid);
        if($('#' + self.identifier + '_span select').selected != undefined) {
            if(res != 'android' && res != 'iphone') {
                $('#' + self.identifier + '_span select').chosen({
                    allow_single_deselect: true,
                    disable_search_threshold: 10,
                    search_contains: true,
                });
            }
        }
    };
    this.draw_html_by_evalue = function(infolist, evalue) {
        var reid = self.get_reid(infolist, evalue);
        var parentreid = (reid == 0) ? 'top' : self.get_reid(infolist, reid);
        var html_str = "";
        var i = self.check_subid(infolist, evalue);
        html_str += '<span class="enums_menu" id="span_' + self.identifier + parentreid + '">';
        if(self.iswap==1) {
            html_str += '<div class="weui_cells"><div class="weui_cell weui_cell_select"><div class="weui_cell_bd weui_cell_primary">';
        }
        if(self.defaultName) {
            html_str += ' <select name="' + self.identifier + '_' + parentreid + '" id="' + self.identifier + '_' + parentreid + '" ' + self.validform + ' class="weui_select ' + self.css + '">';
            html_str += '<option value="">' + self.defaultName + '</option>';
        } else {

            html_str += '<select name="' + self.identifier + '_' + parentreid + '" id="' + self.identifier + '_' + parentreid + '" ' + self.validform + ' class="weui_select ' + self.css + '">';
            html_str += '<option value="">请选择..</option>';
        }

        var option_str = "";
        var option_num = 0;
        $.each(infolist, function(k, v) {
            if(v.parentid == reid) {
                option_num++;
                if(v.id == evalue) {
                    option_str += '<option value="' + v.id + '" selected="selected">' + v.name + '</option>';
                } else {
                    option_str += '<option value="' + v.id + '">' + v.name + '</option>';
                }
            }
        });
        html_str += option_str;
        html_str += '</select>';
        if(self.iswap==1) {
            html_str += '</div></div></div>';
        }
        html_str += '</span>';
        if(option_str) {
            $("#" + self.drawID).prepend(html_str);

            $("#" + self.identifier + "_" + parentreid).change(function() {
                var _reid = $(this).val();
                self.draw_html(infolist, _reid, reid);
            });
        }
        if(parentreid != 'top') {
            self.draw_html_by_evalue(infolist, reid);
        }
        if($('#' + self.identifier + '_span select').selected != undefined) {
            if(res != 'android' && res != 'iphone') {
                $('#' + self.identifier + '_span select').chosen({
                    allow_single_deselect: true,
                    disable_search_threshold: 10,
                    search_contains: true,
                });
            }
        }
    };
    this.get_reid = function(infolist, evalue) {
        var reid = -1;
        $.each(infolist, function(k, v) {
            if(v.id == evalue) {
                reid = v.parentid;
            }
        });
        return reid;
    };
    this.check_subid = function(infolist, evalue) { // 判断是否存在下级菜单
        var reid = 0;
        $.each(infolist, function(k, v) {
            if(v.parentid == evalue) {
                reid = 1;
            }
        });
        return reid;
    };
    this.remove_span = function(parentreid) {
        $("#span_" + self.identifier + parentreid).nextAll("span").remove();
        $("#span_" + self.identifier + parentreid).remove();
    };
}