;(function($){
	$.control=function(element,options){
		this.$elm=$(element);
		this.index=0;
		this.count=this.$elm.find("li").size();	//����
		this.number=Math.floor(this.count/10);	//10�ı���
		this.rnumber=this.count%10;				//����
		this.options=$.extend({},$.fn.control.defaults,options||{});
		this.init();
	}
	$.control.prototype={
		preloadImages:function(a){
			var e = [], b = [], d = a.length;
			for (var c = d - 1; c >= 0; c--) {
				e[c] = new Image();
				e[c].src = a[c];
			}
			//e = null;
		},
		init:function(){
			var that=this;
			var p=that.getUrlParame();
			var imglist=[];
			for(k = 0; k < that.count; k++){
				imglist[k]=that.$elm.find("li").eq(k).attr("rel");
			}
			that.preloadImages(imglist);
			$("#photo_main").html('<em><i></i><img src="http://www.mopcms.com/static/images/icon_loading.gif" alt="" id="photo_see"/></em>');
			that.index =p=(p ==null ? that.$elm.find("li").eq(0).attr("id") : p );
			$(that.options.s_left).click(function(){
				if(!that.$elm.is(":animated") && that.getLeft(that.$elm) > -1*((that.number-1)*10*that.options.step+(that.rnumber)*that.options.step)){
					that.$elm.animate({left:"-="+that.options.step+"px"});
				}
			})
			$(that.options.s_right).click(function(){
				if(!that.$elm.is(":animated") && that.getLeft(that.$elm)<0){
					that.$elm.animate({left:"+="+that.options.step+"px"});
				}
			})
			$(that.$elm).find("li").each(function(i){
				$(this).click(function(){
					var _self=this;
					that.index=i;
					$(that.$elm).find("li").removeClass("on");
					$(this).addClass("on");
					$("#photo_see").attr("src",$(_self).attr("rel"));
					$("#photo_des").html($(_self).attr("title"));
					var c=Math.floor(that.index/10)==0?1:Math.floor(that.index/10);	//10�ı���
					var d=that.index%10;				//����
					if(i>=10&&c>=1){
						that.$elm.stop(true).animate({left:(c*-1*that.options.step*10)+"px"});
					}
					if(i<10){
						that.$elm.stop(true).animate({left:0});
					}
					$("#photoIndex").html(i+1);
					$("#viewOrig").attr("href",$(that.$elm).find("li").eq(i).attr("viewOrig"));
				})
			}).eq(that.$elm.find("li[id="+p+"]").index()).click();
			$(that.options.b_left).click(function(){
				that.index=that.index+1;
				$("#photoIndex").html(that.index);
				if(that.index<that.count){
					$(that.$elm).find("li").eq(that.index).click();
				}else{
					that.index=0;
					$(that.$elm).find("li").eq(that.index).click();
				}
				window.location.hash = "p="+that.$elm.find("li").eq(that.index).attr("id");
				$("#viewOrig").attr("href",$(that.$elm).find("li").eq(that.index).attr("viewOrig"));
			})
			$(that.options.b_right).click(function(){
				that.index=that.index-1;
				$("#photoIndex").html(that.index);
				if(that.index<0){
					that.index=that.count-1;
					$(that.$elm).find("li").eq(that.index).click();
				}else{
					$(that.$elm).find("li").eq(that.index).click();
				}
				window.location.hash = "p="+that.$elm.find("li").eq(that.index).attr("id");
				$("#viewOrig").attr("href",$(that.$elm).find("li").eq(that.index).attr("viewOrig"));
			})
		},
		getLeft:function(obj){
			return parseInt(obj.css("left"));
		},
		getUrlParame:function(){
			var hash=window.location.hash;
			if(hash.search("#")==-1){
				return null;
			}
			var prame=hash.split('#')[1].split('=')[1];
			return prame;
		}
		
	}
	 

	 

	//������չ
	$.fn.control=function(options){
		return new $.control(this,options);
	}
	
	//����Ĭ�ϲ���
	$.fn.control.defaults={
		step:86,
		b_left:"",//��ͼ���ҿ�����
		b_right:"",//��ͼ���ҿ�����
		s_left:"",//Сͼ���ҿ�����
		s_right:""//Сͼ���ҿ�����
	}
})(jQuery)