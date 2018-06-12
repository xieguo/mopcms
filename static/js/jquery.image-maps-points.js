(function($) {
    jQuery.fn.imageMaps = function(setting) {
        var aid = setting.aid;
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
					var _position_conrainer = _img_conrainer.find('.position-conrainer');
					container.find('map[name='+map_name+']').find('area[shape=rect]').each(function(){
						var coord = $(this).attr('coords');
						coords = coord.split(',');
						r = (coords[2]-coords[0]-60)/2;
						t = (coords[3]-coords[1]-20)/2;
						_position_conrainer.append('<div class="map-position" style="left:'+coords[0]+'px;top:'+coords[1]+'px;width:'+(coords[2]-coords[0])+'px;height:'+(coords[3]-coords[1])+'px;"><div class="map-position-bg"></div><span class="btn-list" pointid="'+$(this).attr('pointid')+'">列表</span><span style="position: absolute;right:'+r+'px; top: '+t+'px; background: #000;font-size:18px; color: #fff; padding: 1px;opacity:0.6">定位点ID:'+$(this).attr('pointid')+'</span><span class="btn-add" pointid="'+$(this).attr('pointid')+'">添加</span></div>');
					});
				}
			});
			$('.btn-list').click(function(){
				$('#infolist').modal();
				infolist($(this).attr('pointid'))
			})
			$('.btn-add').click(function(){
				pointid = $(this).attr('pointid');
				$('#infoadd').modal();
				$('#infoadd h4').html('加载中。。。');
				$('#pointinfo').html('<i class="fontello-icon-spin5"></i>加载中。。。');
				$('#pointid').val(pointid);
				$('#infoid').val('');
				$('#thumb').val('');
				$.getJSON('?mod=ajax&ac=location&do=point&id='+pointid+'&aid='+aid+'&rand='+Math.random(),function(result){
				  if(result.code==100){
				      $('#title').val(result.data.arc.title);
                      $('#color').val(result.data.arc.color);
                      $('#summary').val(result.data.arc.description);
                      $('#picname').val('');
                      $('#picview').attr('src',result.data.arc.thumb);
					  pointInfo(result.data);
				  }else{
					  alert(result.msg);
				  }
				})
			})
			
		});
		
		function pointInfo(data){
			$('#infoadd h4').html(data.title);
			pictxt = '';
			if(parseInt(data.imgwidth)>0){
				pictxt = '有缩略图,宽度：'+data.imgwidth+'象素，高度：'+data.imgheight+'象素，';
				$('#thumbli').show();
			}else{
				$('#thumbli').hide();
			}
			titletxt = '';
			if(parseInt(data.titlelen)>0){
				titletxt = '标题字数上限建议为'+data.titlelen+'个汉字。';
			}
			summarytxt = '';
			if(parseInt(data.summarylen)>0){
				summarytxt = '文章摘要字数上限建议为'+data.summarylen+'个汉字。';
				$('#summaryli').show();
			}else{
				$('#summaryli').val('').hide();
			}
			$('#pointinfo').html('显示上限：'+data.infonums+'条, '+pictxt+titletxt+summarytxt);
		}
		
		function infolist(pointid){
			$('#infolist tbody').html('<tr><td class="dataTables_empty" colspan="5"><i class="fontello-icon-spin5"></i>加载中。。。</td></tr>');
			$.getJSON('?mod=ajax&ac=location&do=infolist&pointid='+pointid+'&rand='+Math.random(),function(result){
			  if(result.code==100){
				  $('#infolist h4').html(result.data.point.title);
				  html = '';
				  $.each(result.data.list, function(Index, row) {
					  icon = '';
					  if(row.thumb){
						  icon = '<img src="static/images/image.gif" style="vertical-align:middle;"> ';
					  }
					  html += '<tr><td>'+row.id+'</td><td><a href="?mod=archives&ac=edit&id='+row.id+'&menuid=49" class="archives">'+row._title+'</a>'+icon+'</td><td class="hidden-tablet hidden-phone">'+row.displayorder+'</td><td class="hidden-tablet hidden-phone">'+row._createtime+'</td><td class=" text-right"><a class="btn btn-yellow btn-mini no-wrap btn-edit" infoid="'+row.id+'" pointid="'+row.pointid+'">编辑<i class="fontello-icon-edit"></i></a> <a class="btn btn-red btn-mini no-wrap btn-del" infoid="'+row.id+'" pointid="'+row.pointid+'">删除<i class="fontello-icon-cancel"></i></a> <a href="?mod=archives&ac=edit&id='+row.aid+'&menuid=49" class="btn btn-blue btn-mini no-wrap">原文档<i class="fontello-icon-publish"></i></a></td></tr>';
				  });
				  $('#infolist tbody').html(html);
			  }else{
				  alert(result.msg);
			  }
			  
			  $('.btn-edit').on("click", function(){
				  $('#infoid').val($(this).attr('infoid'));
				  $.getJSON('?mod=ajax&ac=location&do=infoedit&id='+$(this).attr('infoid')+'&pointid='+$(this).attr('pointid')+'&rand='+Math.random(),function(result){
					   if(result.code==100){
						   $('#title').val(result.data.info.title);
						   $('#color').val(result.data.info.color);
						   $('#size').val(result.data.info.size);
						   $('#isbold').val(result.data.info.isbold);
						   $('.btn-radio').removeClass('active');
						   var index = result.data.info.isbold==1?1:0;
						   $($('.btn-radio').get(index)).addClass('active');
						   $('#summary').val(result.data.info.summary);
						   $('#displayorder').val(result.data.info.displayorder);
						   $('#picname').val(result.data.info.thumb);
						   $('#picview').prop('src',result.data.info.thumb);
						   pointInfo(result.data.point);
						   $('#infoadd').modal();
					   }else{
						   alert(result.msg);
					   }
				   })
			  })
			  
			  $('.btn-del').on("click", function(){
				   if(confirm("确定要删除吗？")){
					   pointid = $(this).attr('pointid');
					   $.getJSON('?mod=ajax&ac=location&do=infodel&id='+$(this).attr('infoid')+'&rand='+Math.random(),function(result){
						   if(result.code==100){
							   infolist(pointid);
						   }else{
							   alert(result.msg);
						   }
					   })
					   
				   }
			  });
		  });
		}

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
			$container.find('.map-position .btn-list').css({
				display:'block',
				position:'absolute',
				weight: 'bold',
				background:'#000',
				color:'#fff',
				padding:'1px',
				cursor:'pointer'
			});
			$container.find('.map-position .btn-add').css({
				display:'block',
				position:'absolute',
				right:0,
				top:0,
				background:'#000',
				color:'#fff',
				padding:'1px',
				cursor:'pointer',
			});
		}
		define_css();
    };
})(jQuery); 