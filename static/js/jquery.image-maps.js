(function($) {
    jQuery.fn.imageMaps = function(setting) {
        var $container = this;
        if ($container.length == 0) return false;
		$container.each(function(){
			var container = $(this);
			var $images = container.find('img[ref=imageMaps]');
			$images.wrap('<div class="image-maps-conrainer" style="position:relative;"></div>').css('border','1px solid #ccc');
			$images.each(function(){
				var _img_conrainer = $(this).parent();
				_img_conrainer.append($.browser.msie ? $('<div class="position-conrainer" style="position:absolute"></div>').css({
					background:'#fff',
					opacity:0
				}) : '<div class="position-conrainer" style="position:absolute"></div>');
				var _img_offset = $(this).offset();
				var _img_conrainer_offset = _img_conrainer.offset();
				_img_conrainer.find('.position-conrainer').css({
					top: _img_offset.top - _img_conrainer_offset.top,
					left: _img_offset.left - _img_conrainer_offset.left,
					width:$(this).width(),
					height:$(this).height(),
					border:'1px solid transparent'
				});
				var map_name = $(this).attr('usemap').replace('#','');
				if(map_name !=''){
					var index = 1;
					var _position_conrainer = _img_conrainer.find('.position-conrainer');
					container.find('map[name='+map_name+']').find('area[shape=rect]').each(function(){
						var coord = $(this).attr('coords');
						coords = coord.split(',');
						_position_conrainer.append('<div ref="'+index+'" class="map-position" style="left:'+coords[0]+'px;top:'+coords[1]+'px;width:'+(coords[2]-coords[0])+'px;height:'+(coords[3]-coords[1])+'px;"><div class="map-position-bg"></div><span class="btn-edit" id="edit'+index+'" coords="'+coord+'" pointid="'+$(this).attr('pointid')+'">编辑</span><span class="delete">X</span><span class="resize"></span></div>');
						index++;
					});
				}
			});
			$('.btn-edit').click(function(){
				var pointid = $(this).attr('pointid');
				$('#pointid').val(pointid);
				getPointInfo(pointid);
				$('#responsive').modal();
				down_x = '';
			})
			
		});
		
		down_x = down_y = '';
		$('.position-conrainer').mousedown(function(event){
			down_x = event.pageX-226;
			down_y = event.pageY-149;
		});

		obj = '';
		$('.position-conrainer').mousemove(function(event){
			up_x = event.pageX-226;
			up_y = event.pageY-149;
			width = Math.abs(up_x - down_x);
			height = Math.abs(up_y - down_y);
			if(up_x > down_x && up_y > down_y){
				x = down_x;
				y = down_y;
			}
			else if(up_x > down_x && up_y < down_y){
				x = down_x;
				y = up_y;
			}
			else if(up_x < down_x && up_y > down_y){
				x = up_x;
				y = down_y;
			}
			else if(up_x < down_x && up_y < down_y){
				x = up_x;
				y = up_y;
			}
			if(down_x && down_y && up_x!=down_x && up_y!=down_y){
				if(!obj){
					var index = $(this).find('.map-position').length +1;
					$($(this).append('<div id="map'+index+'" ref="'+index+'" class="map-position" style="left:'+x+'px;top:'+y+'px;width:'+width+'px;height:'+height+'px;"><div class="map-position-bg"></div><span id="edit'+index+'" class="btn-edit" coords="'+x+','+y+','+(x+width)+','+(y+height)+'" pointid="">编辑</span><span class="delete">X</span><span class="resize"></span></div>'));
					obj = $('#map'+index);
				}else{
					obj.attr('style','left:'+x+'px;top:'+y+'px;width:'+width+'px;height:'+height+'px;');
					obj.find('.btn-edit').attr('coords',x+','+y+','+(x+width)+','+(y+height));
					$('.btn-edit').click(function(){
						//不同的热点让getPointInfo只执行一次，
						if(typeof(isload)=='undefined'){
							isload = 0;
						}
						ref = $(this).parent().attr('ref');
						$('#btnid').val(ref);
						$('#coords').val($(this).attr('coords'));
						$('#pointid').val($(this).attr('pointid'));
						if(isload==0){
							isload = ref;
							getPointInfo($(this).attr('coords'));
						}
						if(isload!=ref){
							isload = 0;
						}
						$('#responsive').modal();
						down_x = '';
					})
					$(this).click(function(){
						if(obj.length){
							obj.find('.map-position-bg').before('');
							$('#coords').val(x+','+y+','+(x+width)+','+(y+height));
							$('#pointid').val('');
							$('#btnid').val($(this).find('.map-position').length);
							getPointInfo('');
							$('#responsive').modal();
							down_x = '';
							obj = '';
							isload = 0;//添加完一个热点后，编辑上一个刚添加的热点，保证重新加载上一个热点的数据
						}
					});
				}
				bind_map_event();
				define_css();
			}
		});
		
		function getPointInfo(pointid){
			$('.pointinfo').val('');
			if(pointid){
				$.getJSON(cururl+'&do=pointinfo&pointid='+pointid+'&rand='+Math.random(), function(result){
					if(result.data){
						$('#title').val(result.data.title);
						$('#imgwidth').val(result.data.imgwidth);
						$('#imgheight').val(result.data.imgheight);
						$('#titlelen').val(result.data.titlelen);
						$('#summarylen').val(result.data.summarylen);
						$('#infonums').val(result.data.infonums);
						$('#coords').val(result.data.coords);
					}
				},'json');
			}
		}
		
		//绑定map事件
		function bind_map_event(){
			$('.position-conrainer .map-position .map-position-bg').each(function(){
				var map_position_bg = $(this);
				var conrainer = $(this).parent().parent();
				map_position_bg.unbind('mousedown').mousedown(function(event){
					map_position_bg.data('mousedown', true);
					map_position_bg.data('pageX', event.pageX);
					map_position_bg.data('pageY', event.pageY);
					map_position_bg.css('cursor','move');
					return false;
				}).unbind('mouseup').mouseup(function(event){
					pointid = map_position_bg.next().attr('pointid');
					coords = map_position_bg.next().attr('coords');
					$.get(cururl+'&do=editcoord&pointid='+pointid+'&coords='+coords+'&rand='+Math.random());
					map_position_bg.data('mousedown', false);
					map_position_bg.css('cursor','default');
					return false;
				});
				conrainer.mousemove(function(event){
					if (!map_position_bg.data('mousedown')) return false;
                    var dx = event.pageX - map_position_bg.data('pageX');
                    var dy = event.pageY - map_position_bg.data('pageY');
                    if ((dx == 0) && (dy == 0)){
                        return false;
                    }
					var map_position = map_position_bg.parent();
					var p = map_position.position();
					var left = p.left+dx;
					if(left <0) left = 0;
					var top = p.top+dy;
					if (top < 0) top = 0;
					var bottom = top + map_position.height();
					if(bottom > conrainer.height()){
						top = top-(bottom-conrainer.height());
					}
					var right = left + map_position.width();
					if(right > conrainer.width()){
						left = left-(right-conrainer.width());
					}
					map_position.css({
						left:left,
						top:top
					});
					
					map_position_bg.data('pageX', event.pageX);
					map_position_bg.data('pageY', event.pageY);
					
					bottom = top + map_position.height();
					right = left + map_position.width();
					
					coords = map_position_bg.next().attr('coords').split(',');
					coords[0] = left;
					coords[1] = top;
					coords[2] = right;
					coords[3] = bottom;
					map_position_bg.next().attr('coords',coords.join(','));
					
					return false;
				}).mouseup(function(event){
					map_position_bg.data('mousedown', false);
					map_position_bg.css('cursor','default');
					return false;
				});
			});
			$('.position-conrainer .map-position .resize').each(function(){
				var map_position_resize = $(this);
				var conrainer = $(this).parent().parent();
				map_position_resize.unbind('mousedown').mousedown(function(event){
					map_position_resize.data('mousedown', true);
					map_position_resize.data('pageX', event.pageX);
					map_position_resize.data('pageY', event.pageY);
					return false;
				}).unbind('mouseup').mouseup(function(event){
					pointid = map_position_resize.parent().find('.btn-edit').attr('pointid');
					coords = map_position_resize.parent().find('.btn-edit').attr('coords');
					$.get(cururl+'&do=editcoord&pointid='+pointid+'&coords='+coords+'&rand='+Math.random());
					map_position_resize.data('mousedown', false);
					return false;
				});
				conrainer.mousemove(function(event){
					if (!map_position_resize.data('mousedown')) return false;
                    var dx = event.pageX - map_position_resize.data('pageX');
                    var dy = event.pageY - map_position_resize.data('pageY');
                    if ((dx == 0) && (dy == 0)){
                        return false;
                    }
					var map_position = map_position_resize.parent();
					var p = map_position.position();
					var left = p.left;
					var top = p.top;
					var height = map_position.height()+dy;
					if((top+height) > conrainer.height()){
						height = height-((top+height)-conrainer.height());
					}
					if (height <20) height = 20;
					var width = map_position.width()+dx;
					if((left+width) > conrainer.width()){
						width = width-((left+width)-conrainer.width());
					}
					if(width <50) width = 50;
					map_position.css({
						width:width,
						height:height
					});
					
					map_position_resize.data('pageX', event.pageX);
					map_position_resize.data('pageY', event.pageY);
					
					bottom = top + map_position.height();
					right = left + map_position.width();
					
					coords = map_position_resize.parent().find('.btn-edit').attr('coords').split(',');
					coords[2] = right;
					coords[3] = bottom;
					map_position_resize.parent().find('.btn-edit').attr('coords',coords.join(','));
					
					return false;
				}).mouseup(function(event){
					map_position_resize.data('mousedown', false);
					return false;
				});
			});
			$('.position-conrainer .map-position .delete').unbind('click').click(function(){
				var ref = $(this).parent().attr('ref');
				var _position_conrainer = $(this).parents('.position-conrainer');
				var pointid = $(this).parent().find('.btn-edit').attr('pointid');
				if(pointid){
					$.getJSON(cururl+'&do=infocount&pointid='+pointid+'&rand='+Math.random(), function(result){
						if(parseInt(result.data)>0){
							bootbox.confirm("此定位点含有定位信息，也将一并删除，确定删除?", "取消", "确定", function(result) {
								if(result===true){
									$.get(cururl+'&do=del&pointid='+pointid+'&rand='+Math.random());
									_position_conrainer.find('.map-position[ref='+ref+']').remove();
								}	
							});
						}else{
							$.get(cururl+'&do=del&pointid='+pointid+'&rand='+Math.random());
							_position_conrainer.find('.map-position[ref='+ref+']').remove();
						}
					});
				}else{
					_position_conrainer.find('.map-position[ref='+ref+']').remove();
				}
				down_x = 0;
			});
		}
		
		bind_map_event();
		
		function define_css(){
			//样式定义
			$container.find('.map-position').css({
				position:'absolute',
				border:'1px solid #000',
				'font-weight':'bold'
			});
			$container.find('.map-position .map-position-bg').css({
				position:'absolute',
				background:'#0F0',
				opacity:0.3,
				top:0,
				left:0,
				right:0,
				bottom:0
			});
			$container.find('.map-position .btn-edit').css({
				display:'block',
				position:'absolute',
				weight: 'bold',
				background:'#000 none repeat scroll 0% 0%',
				color:'#fff',
				padding:'1px 3px',
				cursor:'pointer'
			});
			$container.find('.map-position .resize').css({
				display:'block',
				position:'absolute',
				right:0,
				bottom:0,
				width:5,
				height:5,
				cursor:'nw-resize',
				background:'#000'
			});
			$container.find('.map-position .delete').css({
				display:'block',
				position:'absolute',
				right:0,
				top:0,
				width:10,
				height:12,
				'line-height':'11px',
				'font-size':12,
				'font-weight':'bold',
				background:'#000',
				color:'#fff',
				'font-family':'Arial',
				'padding-left':'2px',
				cursor:'pointer',
				opactiey : 1
			});
		}
		define_css();
    };
})(jQuery); 