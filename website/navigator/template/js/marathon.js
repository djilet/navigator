$(document).ready(function() {
	var MODULE_AJAX_PATH = project_path + 'module/marathon/ajax_public.php';

/*Breadcrumb*/
/*$('.marathon.breadcrumb').owlCarousel({
    loop:false,
    margin:0,
    nav: false,
})*/

/*Graduate Map*/
	var items = $('.graduate-map .horizontal-big li');
	
    $('.graduate-map-block').css('opacity', '1');
    generateGraduateMap();

	function generateGraduateMap(){
		var map = $('.graduate-map');
		if ( $(window).width() <= 600) {
			in_row_count = 2;
		}
		else if ($(window).width() <= 991) {
			in_row_count = 3;
		}
		else{
			in_row_count = 4;
		}

		$('.horizontal-big').each(function(e){
			if ($(this).text() == '') {
				$(this).remove();
			}
		});

		width_item  = map.width() / in_row_count;
		var row_num = 1;
		var i = 0;
		items.each(function(e){
			//Create row
			if (i >= in_row_count) {
				i = 0;
				row_num++;
				if ( !$('#row-num-' + row_num).length ) {
					map.append('<ul id="row-num-' + row_num + '" class="stages-list horizontal-big"></ul>');
				}
			}
			//append item
			map.children('#row-num-' + row_num).append($(this).css('width', width_item));
			i++;
		});
		map.removeClass('not-decor');
	}

	$(window).resize(function(event) {
		generateGraduateMap();
		//resizeItem( $('.stages-list.horizontal'), 'li');
	});

	//Download map
	/*$('body').on('click', '#download-map-btn', function(event) {
		event.preventDefault();
		$('.loading').show();
		$.ajax({
	        url: MODULE_AJAX_PATH,
	        type: 'POST',
	        data: {Action: "downloadMap"},
	        dataType: 'json',
	        success: function (data) {
	        	$('.graduate-map-block').append('<div class="hidden-content">' + data.content + '</div>');
	        	saveMap2PDF();
	        	$('.loading').hide();
	        },
	        error: function(request, error){
	        	$('body').append(request.responseText);
	        	//console.log(request);
	        	//console.log('error: ' + error);
	        }
	    });
	});*/
		//Service
		function saveMap2PDF(){
        	$('.vertical-big li.disabled').prev('li').addClass('line-disabled');
			$('.vertical-big li.active').prev('li').addClass('line-gradient');
			$('.vertical-big li.completed').prev('li').addClass('line-completed');

			//var map = $('#graduate-map2print');

			//init save
			var element = document.getElementById('graduate-map2print');
			var opt = {
			  margin:       1,
			  filename:     'map.pdf',
			  image:        { type: 'jpeg', quality: 1 },
			  html2canvas:  { scale: window.devicePixelRatio },
			  jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
			};
			
			html2pdf().from(element).set(opt).save();
			$('.map2print-wrap').remove();
		}

/*Stages list*/
	var stages_list = $('.stages-list.scroll');
	var active_item = stages_list.find('li.active');

	stages_list.fadeIn();
	stages_list.owlCarousel({
	    loop:false,
	    items:10,
	    margin:0,
    	startPosition: active_item.attr('data-hash') - 2,
	    nav: false,
  		navText: ['<div class="prev-btn"></div>','<div class="next-btn"></div>'],
	    responsiveClass:true,
	    responsive:{
			 0 : {
		        items : 4
		    },
		    500 : {
		        items : 5,
		    },
		    768 : {
		    	items : 5,
		        nav:true
		    },
		    992 : {
		        items : 10,
		    }
	    }
	})

/*Step Answer Form*/
	$('body').on('click', '#save-answer-btn', function(event) {
		event.preventDefault();
		var form = $('.answer-form');
		form.submit();
	});
	$('body').on('click', '#skip-answer-btn', function(event) {
		event.preventDefault();
		var form = $('.answer-form');
		form.append('<input type="hidden" name="skip" value="1">')
		form.submit();
	});
	$('body').on('click', '.close-step-btn', function(event) {
		event.preventDefault();
		$(this).parents('#modal-data').modal('hide');
	});

/*Modal ajax*/
	//Check user info
	if ($('.user-info-modal').length) {
		loadContent(MODULE_AJAX_PATH, 'POST', {Action: "getInfo"});
	}
	$('body').on('click', '.change-user-info-btn', function(event) {
		event.preventDefault();
		$('.loading').show();
		loadContent(MODULE_AJAX_PATH, 'POST', {Action: "getInfo", Item: $(this).attr('data-info-item')});
		$('.loading').hide();
	});

	//Social Show
	if ($('.social-show').length) {
		loadContent(MODULE_AJAX_PATH, 'POST', {Action: "getSocialLink"});
		$('body').on('click', '.social-show .btn.social', function(event) {
			window.location.href = $(this).attr('data-redirect');
		});

		$(document).mouseup(function (e) {
			var modal_container = $('.social-show');
			if (modal_container.has(e.target).length === 0){
				window.location.href = $('.social-show .btn.social').attr('data-redirect');
		    }
		});
	}
	//Complete marathon
	else if( $('.complete-marathon-modal').length ){
		loadContent(MODULE_AJAX_PATH, 'POST', {Action: "getCompleteMarathonPage"});
	}

	//load step
	$('body').on('click', '.stages-list.horizontal-big a', function(event) {
		event.preventDefault();
		LoadMapByDataID($(this).attr('data-step-id'));
	});
	$('body').on('click', '#map-link-btn', function(event) {
		event.preventDefault();
		LoadMapByDataID($(this).attr('data-step-id'), $(this).attr('data-redirect'));
	});

	function LoadMapByDataID(step_id, redirect = null){
		var action = MODULE_AJAX_PATH;
		var data = {Action: "loadStep", Map: step_id};
		if (redirect !== null) {
			data.Redirect = redirect;
		}

		if (step_id == 6) {
			loadContent(action, 'GET', data, initUniversity);
		}
		else{
			loadContent(action, 'GET', data);
		}
	}
	/*function LoadMapByHref(href){
		var action = MODULE_AJAX_PATH;
		var step_id = href.split('Map=').pop();
		var data = {Action: "loadStep", Map: step_id};
		loadContent(action, 'GET', data);
	}*/

	function loadContent(action, method, data, call_back_func = null){
		$.ajax({
	        url: action,
	        type: method,
	        data: data,
	        dataType: 'json',
	        success: function (data) {
	        	$('#modal-data').find('.modal-body .content').html(data['content']);
	        	$('#modal-data').modal('show');
	        	if (call_back_func !== null) {
	        		call_back_func();
	        	}
	        	//console.log(data);
	        },
	        error: function(request, error){
	        	$('body').append(request.responseText);
	        	//console.log(request);
	        	//console.log('error: ' + error);
	        }
	    });
	}


/*Answer list*/
	//drop list checkbox
	$('body').on('click', '.drop-list.checkbox:not(.no-save) .item', function(event) {
		var drop_list = $(this).parent('.drop-list');
		var input = $(this).children('input');
		if ( drop_list.attr('data-max-count') ) {
			var max_count = drop_list.attr('data-max-count');
		}

		if ( input.attr('checked') ) {
			$(this).removeClass('selected');
			input.attr('checked', false);
		}
		else if( $('.drop-list .item.selected').length < max_count || typeof max_count =="undefined" ){
			$(this).addClass('selected');
			input.attr('checked', true);
		}
		else{
			alert('Максимальное число элементов "' + max_count + '"');
		}

		updateSelectedList($(this));
		//$('#save-answer-btn').addClass('disabled');
		//$('#save-answer-btn').addAttr('disabled');
		//$('#save-answer-btn').removeClass('disabled');
		//$('#save-answer-btn').removeAttr('disabled');
	});

	//drop list select
	$('body').on('click', '.drop-list.select .item', function(event) {
		var container = $(this).parents('.marathon-droplist.select');
		var search_input = container.find('.droplist-search');
		var input = $(this).children('input');
		var selected_item = container.find('.drop-list.select .item.selected');

		selected_item.children('input').removeAttr('checked');
		selected_item.removeClass('selected');

		$(this).addClass('selected');
		input.attr('checked', true);

		search_input.val( $(this).children('.title').html() );
		container.removeClass('active');

		if (container.parent('.awsner-block').hasClass('university')) {
			getSpecialityList($(this));
		}

		//Other
		$('.drop-list.select .item.hide input[value=' + selected_item.children('input').val() + ']').parents('.item').each(function(index, el) {
			$(this).removeClass('hide');
		});
		$('.drop-list.select .item input[value=' + input.val() + ']').parents('.item:not(.selected)').each(function(index, el) {
			$(this).addClass('hide');
		});
	});

	//radio button
	$('body').on('click', '.answer-list.radiobutton .item', function(event) {
		event.preventDefault();
		var list = $(this).parents('.answer-list.radiobutton');
		var input = $(this).children('input');

		if ( !input.attr('checked') ) {
			var selected_item = list.children('.item.selected');
			selected_item.children('input').attr('checked', false);
			selected_item.removeClass('selected');

			$(this).addClass('selected');
			input.attr('checked', true);
		}
	});

/*Drop list*/

	//reset active
	$(document).mouseup(function (e) {
	    var container = $(".marathon-droplist.active");
	    if (container.has(e.target).length === 0){
	    	if (container.hasClass('select')) {
	    		container.find('.droplist-search').val(container.find('.drop-list .item.selected .title').html());
	    	}
	    	container.removeClass('active');
	    }
	});

	//remove from selected
	$('body').on('click', '.awsner-block .selected-list .remove', function(event) {
		event.preventDefault();
		var input = $(this).parents('.awsner-block').find('.drop-list .item input[value=' + $(this).attr('data-remove') + ']');

		if (input) {
			input.removeAttr('checked');
			input.parent('.item').removeClass('selected');
		}

		$(this).parent('li').remove();
	});

	//search
	$('body').on('click', '.marathon-droplist .search-wrap', function(event) {
		var drop_list = $(this).parent('.marathon-droplist');
		var filter = drop_list.find('.drop-list .item:not(.hide)');
		var search_input = drop_list.find('.search-wrap .droplist-search');
		//select list
		if (drop_list.hasClass('select')) {
			search_input.val('');
			filter.show();
		}

		//all
		drop_list.addClass('active');
		search_input.keyup(function(event) {
			if ($(this).val().length > 0){
				var word = $(this).val().toLowerCase();

				filter.each(function(index, el) {
					var title = $(this).children('span').html().toLowerCase();

					if (title.indexOf(word) >= 0){
						$(this).show();
					}
					else {
						$(this).hide();	
					}
				});
			}
			else{
				filter.show();
			}
		});
	});

	/*Service*/
	function updateSelectedList(element){
		var block = element.parents('.awsner-block');
		var list = block.find('.selected-list');
		if (list) {
			list.html('');
			block.find('.drop-list .item input[checked]').each(function(index, el) {
				list.append('<li class="item"><span class="name">' + $(this).prev('.title').text() + '</span><a class="remove" data-remove="' + $(this).val() + '"></a></li>')	
			});
		}
	};

		/*University list functions*/
		function initUniversity(){
			var select_wrap = $('.marathon-droplist.select');
			select_wrap.each(function(index, el) {
				if ( $(this).attr('data-id') > 0) {
					var id = $(this).attr('data-id');
					var input = $(this).find('.drop-list .item').children('input[value=' + id + ']');

					input.parents('.drop-list .item').addClass('selected');
					input.attr('checked', true);
					$(this).find('.droplist-search').val(input.prev('.title').html());

					getSpecialityList(input.parents('.drop-list .item'));
				
				}
			});

			$('.drop-list.select .item.selected').each(function(index, el) {
				$('.drop-list.select .item input[value=' + $(this).children('input').val() + ']').parents('.item').addClass('hide');
			});
		}

		function getSpecialityList(element){
			var container = element.parents('.marathon-droplist.select');
			var speciality_list = element.children('.speciality');
			var seleted_list = container.find('.selected-list');

			seleted_list.html(speciality_list.html());
			seleted_list.children('.item').show();
		}

/*Decision*/
	$('#attach-file-btn').click(function(event) {
		var input = $('input#'+$(this).data('inputid'));
		event.preventDefault();
		input.click();

		input.change(function(event) {
			var files = $(this)[0].files;
			var list = input.parent().find('.selected-list');
			list.html('');

			$.each(files, function(index, val) {
				list.append('<li class="item"><span class="name">' + val.name + '</span><a class="remove" data-remove="' + index + '"></a></li>')
			});

		});
	});

	//Selected list
	/*$('.selected-list').on('click', '.remove', function(event) {
		$(this).parent('li').remove();
	});*/

//show login popup if necessary
	if($('.show-modal-signin').length){
		$('#modal-signin').modal('show');
	}
	
//webinar
	$('.marathon-webinar-area, .marathon-video-area').each(function(){
		var form = $(this).find('form');
		var iframeID = $(this).find('iframe').attr('id');
		var clickIframe = window.setInterval(function(){
			if(document.activeElement == document.getElementById(iframeID)) {
				$.ajax({
		            url: form.attr('action'),
		            type: 'post',
		            data: form.serialize(),
		            dataType: 'json',
		            success: function (data) {
		            	//nothing to do
		            }
		        });
			  	window.clearInterval(clickIframe);
			}
		}, 1000);
	});

/*All steps*/
	if($('.all-stages-block .top-title.current').length){
		$('html,body').animate({scrollTop: $('.all-stages-block .top-title.current').offset().top});
	}

/*Proftest*/
$('#print-btn').click(function(event) {
	event.preventDefault();
	window.print();
});


$('body').on('click', '.answer-list.proftest .item', function(event) {
	$(this).closest('form').submit();
});


});
