$cs(function(){
	new FastClick(document.getElementById("filter"));
	new FastClick(document.getElementById("mask"));
	function listFilter(){
		this.wrap=$cs(".filter_outer");
		this.nav=$cs(".nav_filter");
		this.box=$cs(".f_box");
		this.mask=$cs("#mask");
		this.area="filter-area";
		this.more="filter-more";
		this._top=this.wrap.offset().top;
		this.filterID="";
		this.filterDiv=null;
		//var catentry=____json4fe.catentry;this.morelink="";
		this.preUrl="http://"+window.location.hostname;
		this.isShow=false;
		this.cateArr=[]
	}
	listFilter.prototype={
		init:function(){
			var _this=this;
			this.docSwipe();
			_this.nav.find("li").on("click",
			function(e){
				e.stopPropagation();
				e.preventDefault();
				if($cs(this).hasClass("select")){_this.close(e);
				return;
			}
			var id=$cs(this).find("a").attr("data-id");
			var $csbox=$cs("#"+id);
			$cs(this).addClass("select").siblings().removeClass("select");
			_this.box.addClass("hide");			
			if(id==_this.area||id==_this.more){
				_this.filterDiv=$cs("#"+id);
				_this.filterDiv.removeClass("hide")
			}else{
				_this.filterDiv=_this.box.filter("[id^="+id+"]");
				_this.filterDiv.each(function(){
					if(!$cs(this).attr("dis")){
						$cs(this).removeClass("hide");
						/*$('.maintype').removeClass("hide");
						$('.subtype').addClass("hide");
						$('.thistype').removeClass("hide");*/
					}			
				})
			}
			if(id==_this.more){
				$cs(".f_more_content").each(function(){
					if(parseInt($cs(this).css("left"))==0){
						$cs(this).removeClass("hide");
					}
				})
			}
			_this.mask.show();
			_this.isShow = true;
			$cs("body").addClass("filter-fixed")
		});
		_this.mask.on("click", 
            function(e) {
                _this.close(e)
            });
            this.mask.on("touchstart, touchmove", 
            function(e) {
                e.stopPropagation();
                e.preventDefault()
            });
		this.wrap.on("touchstart, touchmove", 
            function(e) {
                e.stopPropagation();
                e.preventDefault()
            });
            this.box.each(function() {
                var id = $cs(this).attr("id");
                var cateid = $cs(this).attr("cateid");
                switch (id) {
                case _this.area:
                    var $csinner = $cs(this).find("ul");
                    if ($csinner.length > 1) {
                        $csinner.eq(0).addClass("current")
                    }
                    _this.conSwipe($cs(this));
                    _this.areaMenu($cs(this));
                    break;
                case _this.more:
                    _this.conSwipe($cs(this));
                    _this.moreMenu($cs(this));
                    break;
                default:
                    _this.conSwipe($cs(this));
                    if ($cs(this).hasClass("f_more_content")) break;
                    if ($cs(this).find('li[class="selected"]').length) {
                        if (cateid && _this.box.filter('[catepid="' + cateid + '"]').length) {
                            $cs(this).find(".f_box_inner").addClass("current")
                        }
                    }
                    _this.cateArr.push({
                        catepid: $cs(this).attr("catepid"),
                        cateid: $cs(this).attr("cateid"),
                        obj: $cs(this)
                    });
                    _this.subMenu($cs(this));
                    break
                }
            })
        },
		moreMenu: function(obj) {
            var _this = this;
            var $csbox = obj;
            var $cstype = $csbox.find(".js_more_type");
            var $csback = $cs(".btn_back");
            var $cscur = $cs(".f_more_content");
            var $cscurBox;
            var $csspan;
            $cstype.find("a").on("click", 
            function() {
                $csspan = $cs(this).find("span");
                var id = $cs(this).attr("data-id");
                if (!id) return;
                $cscurBox = $cs("#" + id);
                $cs(".f_more_content").addClass("hide");
                $cscurBox.removeClass("hide");
                $csbox.animate({
                    left: "-100%"
                },
                300, "ease-in-out");
                $cscurBox.animate({
                    left: "0"
                },
                300, "ease-out-in");
                $csback.show()
            });
            $cscur.find("li").on("click",
			function(e) {
                e.stopPropagation();
                e.preventDefault();
                var $csa = $cs(this).find("a");
                var liIndex = $cs(this).index();
                $cs(this).addClass("selected").siblings().removeClass("selected");
                if (!_this.moreLink) {
                    _this.moreLink = window.location.href
                }
                var url = _this.moreLink;
				var jId = $cscurBox.attr("id").replace("filter-", "");
				url += '&'+jId+'='+$csa.attr('value');
				_this.moreLink = url;
                $cs(".btn_submit a").on("click", function() {
					if (_this.moreLink) {
						window.location = _this.moreLink;
						return false
                    }
				})
                $cs(this).addClass("selected").siblings().removeClass("selected");
                $csspan.text($cs(this).text());
                $csbox.animate({
                    left: "0"
                },
                300, "ease-out-in");
                $cscurBox.animate({
                    left: "100%"
                },
                300, "ease-in-out");
                return false
            });
			$csback.on("click", 
            function() {
                $csbox.animate({
                    left: "0"
                },
                300, "ease-out-in");
                $cscurBox.animate({
                    left: "100%"
                },
                300, "ease-in-out")
            })
        },
		subMenu: function(obj, callback) {
            var _this = this;
            var $csbox = obj;
            $csbox.find("li").on("click", 
            function(e) {
                var cateid = $csbox.attr("cateid");
                var url = $cs(this).find("a").attr("href");
                $cs(this).parents(".f_box_inner").addClass("current");
                $cs(this).addClass("selected").siblings().removeClass("selected");
                if (cateid) {
                    e.stopPropagation();
                    e.preventDefault();
                    _this.subClick(obj, this)
                }
            })
        },
		subClick: function(obj, li) {
            var _this = this;
            var $csbox = obj;
            var cateid = $csbox.attr("cateid");
            var $csa = $cs(li).find("a");
            var url = $csa.attr("href");
            var numArr = $csbox.attr("id").split("-");
            var idType = numArr[1];
            var num = numArr[numArr.length - 1];
            var idNme = "";
            var ajaxUrl = url + (url.indexOf("?") > -1 ? "&": "?") + "filter=0";
            if (isNaN(num)) {
                num = 2;
                numArr.push(num);
                if (_this.filterDiv.length == 3) {
                    _this.filterDiv.last().addClass("hide").attr("dis", "1")
                }
            } else {
                num++;
                numArr[numArr.length - 1] = num
            }
            if (num > 3) {
                window.location = url;
                return
            }
            idNme = numArr.join("-");
            $cs.ajax({
                type: "get",
                dataType: "json",
                url: ajaxUrl,
                success: function(o) {
                    if (!o) {
                        window.location = url;
                        return
                    }
                    var unitparas = o.unitparas;
                    var list;
                    for (var i = 0; i < unitparas.length; i++) {
                        if (unitparas[i].pid == cateid) {
                            for (var k = 0; k < _this.cateArr.length; k++) {
                                var cateObj = _this.cateArr[k];
                                if (cateObj.cateid == unitparas[i].id && cateObj.obj.attr("id").indexOf(idType) < 0) {
                                    window.location = url;
                                    return
                                }
                            }
                            list = unitparas[i];
                            break
                        }
                    }
                    if (!list) {
                        if (_this.filterDiv.length > 1) {
                            _this.filterDiv.each(function(i) {
                                $csbox = $cs(_this.filterDiv[i]);
                                if ($csbox.attr("catepid") == cateid) {
                                    $csbox.attr("dis", "1");
                                    cateid = $csbox.attr("cateid");
                                    $csbox.addClass("hide")
                                }
                            })
                        }
                        window.location = url;
                        return
                    }
                    var data = list.data;
                    var listHtml = '<div class="f_box_inner"><ul>';
                    for (var i = 0; i < data.length; i++) {
                        var _curUrl = data[i].url;
                        var nextParent = "";
                        _curUrl = _this.endUrl(_curUrl);
                        listHtml += '<li><a href="' + _curUrl + '" ' + nextParent + ">" + data[i].txt + "</a></li>"
                    }
                    listHtml += "</ul></div>";
                    var $cstarget;
                    for (var i = 0; i < _this.cateArr.length; i++) {
                        var cateObj = _this.cateArr[i];
                        if (cateObj.catepid == cateid) {
                            $cstarget = cateObj.obj;
                            break
                        }
                    }
                    if (!$cstarget) {
                        $csbox.parent().append('<div class="f_box" catepid="' + list.pid + '" cateid="' + list.id + '" id="' + idNme + '"></div>');
                        $cstarget = $cs("#" + idNme);
                        _this.filterDiv.push($cstarget);
                        _this.box = $cs(".f_box");
                        _this.cateArr.push({
                            catepid: list.pid,
                            cateid: list.id,
                            obj: $cstarget
                        })
                    } else {
                        $cstarget.removeClass("hide")
                    }
                    $cstarget.html(listHtml);
                    _this.subMenu($cstarget);
                    _this.conSwipe($cstarget)
                },
                error: function() {
                    window.location = url;
                    return
                }
            })
        },
		endUrl: function(url) {
            if (url.indexOf("filter=0") > -1) {
                if (url.indexOf("&") > -1) {
                    url = url.replace(/&amp;/g, "&");
                    url = url.replace(/(filter=0&)|(&filter=0)/, "")
                } else {
                    url = url.replace("?filter=0", "")
                }
            }
            return url
        },
		areaMenu: function(obj) {
            var _this = this;
            //var cityname = ____json4fe.locallist[0].listname;
			//var url = "http://m.58.com/sublocals/?cityname=" + cityname + "&callback=?";
            var url = "";
            if (obj.find(".f_box_inner").length == 1) {
                obj.append('<div class="f_box_inner hide"></div>')
            }
            var targetArea = obj.find(".f_box_inner").last();
            obj.find("li").on("click", 
            function() {
                $cs(this).addClass("selected").siblings().removeClass("selected");
                $cs(this).parents(".f_box_inner").addClass("current")
            });
            $cs.ajax({
                dataType: "json",
                url: url,
                success: function(o) {
                    if (!o) return;
                    var data = $cs.parseJSON(o.datastr)[0];
                    var $csli = obj.find("ul").eq(0).find("li");
                    $csli.on("click", 
                    function(e) {
                        var $csa = $cs(this).find("a");
                        targetArea.removeClass("current");
                        var areaHtml = "<ul>";
                        var dateID = $csa.attr("data-id");
                        if (!dateID) {
                            targetArea.addClass("hide")
                        } else {
                            e.stopPropagation();
                            e.preventDefault();
                            var area = dateID.split("-")[1];
                            var street = data[area];
                            var allHref = $csa.attr("href");
                            targetArea.removeClass("hide");
                            areaHtml += '<li><a href="' + allHref + '">ȫ' + $cs(this).text() + "</a></li>";
                            for (var i = 0; i < street.length; i++) {
                                var _href = allHref.replace(area, street[i].listname);
                                areaHtml += '<li><a href="' + _href + '">' + street[i].name + "</a></li>"
                            }
                            areaHtml += "</ul>";
                            targetArea.html(areaHtml);
                            _this.conSwipe(targetArea);
                            targetArea.find("li").on("click", 
                            function() {
                                $cs(this).addClass("selected").siblings().removeClass("selected");
                                $cs(this).parents(".f_box_inner").addClass("current")
                            });
                            return false
                        }
                    })
                }
            })
        },
		docSwipe: function() {
            var _this = this;
            var b = 0;
            var _scroll = $cs(window).scrollTop();
            var _curScroll = $cs(window).scrollTop();
            $cs(window).on("scroll", 
            function() {
                if (_this.isShow) return;
                if ($cs(window).scrollTop() > _this._top * 2) {
                    if (b) return;
                    $cs("body").addClass("filter-fixed");
                    _this.wrap.css({
                        top: "42px"
                    });
                    _this.wrap.animate({
                        top: "0"
                    },
                    300, "ease-out");
                    b = 1
                } else {
                    b = 0;
                    $cs("body").removeClass("filter-fixed")
					_this.wrap.css({
                        top: "0px"
                    });
                }
            })
        },
		conSwipe: function(obj) {
            var _this = this;
            var $csul = obj.find("ul");
            $csul.on("touchstart", 
            function(e) {
                this.startY = e.targetTouches[0].screenY;
                this.startTop = this.y || 0;
                this.startTime = event.timeStamp;
                this.moved = false;
                this.wrapH = $cs(this).parents(".f_box_inner")[0].offsetHeight;
                if (!this.maxScrollY) {
                    this.scrollerHeight = this.offsetHeight;
                    this.maxScrollY = this.wrapH - this.scrollerHeight + 1
                }
                this._height = this._height || $cs(this).parent().height() - $cs(this).find("li").height() * $cs(this).find("li").length + 1;
                if (this.isInTransition) {
                    var matrix = window.getComputedStyle(this, null);
                    matrix = matrix["webkitTransform"].split(")")[0].split(", ");
                    this.y = matrix[13] || matrix[5];
                    this.y = Math.round(this.y);
                    this.startTop = Math.round(this.y);
                    $cs(this).css({
                        "-webkit-transform": "translate3d(0," + this.y + "px, 0)",
                        "-webkit-transition-duration": "0"
                    });
                    this.isInTransition = false
                }
            });
            $csul.on("touchmove", 
            function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.moved = true;
                this.y = e.targetTouches[0].screenY - this.startY + this.startTop;
                if (this.y > 0 || this.y < this.maxScrollY) {
                    var newY = this.y - (e.targetTouches[0].screenY - this.startY) * 2 / 3;
                    this.y = this.y > 0 ? 0: this.maxScrollY;
                    if (newY > 0 || newY < this.maxScrollY) {
                        this.y = newY
                    }
                }
                $cs(this).css({
                    "-webkit-transform": "translate3d(0," + this.y + "px, 0)",
                    "-webkit-transition-duration": "0"
                });
                this.isInTransition = false;
                var timeStamp = event.timeStamp;
                if (timeStamp - this.startTime > 300) {
                    this.startTime = timeStamp;
                    this.startY = e.targetTouches[0].screenY;
                    this.startTop = this.y
                }
            });
            $csul.on("touchend", 
            function(e) {
                var dist = e.changedTouches[0].screenY - this.startY;
                this.endTime = event.timeStamp;
                var duration = this.endTime - this.startTime;
                if (this.moved) {
                    e.preventDefault();
                    e.stopPropagation();
                    var newY = Math.round(e.changedTouches[0].screenY);
                    this.isInTransition = true;
                    if (this.y > 0 || this.y < this.maxScrollY) {
                        _this.scrollTo(this, this.y, this.maxScrollY, 600);
                        return
                    }
                    if (duration < 300) {
                        var move = _this.calculateMoment(this.y, this.startTop, duration, this.maxScrollY, this.wrapH);
                        this.y = move.destination;
                        var time = move.duration;
                        $cs(this).css({
                            "-webkit-transform": "translate3d(0, " + this.y + "px, 0)",
                            "transition-timing-function": "cubic-bezier(0.1, 0.3, 0.5, 1)",
                            "-webkit-transition-duration": time + "ms"
                        })
                    }
                    return
                }
            });
            $csul.on("transitionend", 
            function() {
                this.isInTransition = false;
                _this.scrollTo(this, this.y, this.maxScrollY, 600)
            })
        },
        scrollTo: function(obj, y, maxY, time) {
            if (y > 0 || maxY > 0) {
                y = 0
            } else if (y < maxY) {
                y = maxY
            }
            obj.isInTransition = true;
            $cs(obj).css({
                "-webkit-transform": "translate3d(0, " + y + "px, 0)",
                "transition-timing-function": "cubic-bezier(0.25, 0.46, 0.45, 0.94)",
                "-webkit-transition-duration": time + "ms"
            })
        },
		calculateMoment:function(current,start,time,lowerMargin,wrapperSize){var distance=current-start,speed=Math.abs(distance)/time,destination,duration;deceleration=6e-4;destination=current+speed*speed/(2*deceleration)*(distance<0?-1:1);duration=speed/deceleration;if(destination<lowerMargin){destination=wrapperSize?lowerMargin-wrapperSize/2.5*(speed/8):lowerMargin;distance=Math.abs(destination-current);duration=distance/speed}else if(destination>0){destination=wrapperSize?wrapperSize/2.5*(speed/8):0;distance=Math.abs(current)+destination;duration=distance/speed}return{destination:Math.round(destination),duration:duration}},
        close: function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.mask.hide();
            this.box.addClass("hide");
            this.nav.find("li").removeClass("select");
            this.isShow = false;
            if ($cs(window).scrollTop() < this._top) {
                $cs("body").removeClass("filter-fixed")
            }
        }
    };
    var filter = new listFilter;
    filter.init()
});