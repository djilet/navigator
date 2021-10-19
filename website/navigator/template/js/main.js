var redirect_url = '';
var phone_mask = '+7 (999) 999 - 99 - 99';
var delay = (function(){
	var timer = 0;
	return function(callback, ms){
		clearTimeout (timer);
		timer = setTimeout(callback, ms);
	};
})();

$(document).ready(function () {
	//setup datetimepicker
	if (typeof jQuery.datetimepicker !== "undefined"){
		jQuery.datetimepicker.setLocale('ru');
	}

	//common data actions
	$('[data-change]').change(function () {
		let action = $(this).data('change');
		if (action === 'href'){
			location.href = $(this).val();
		}
	});

	//apply phone mask
	if(document.location.pathname.indexOf('/minsk/') != -1){//TODO: move to city config
		phone_mask = '+375 99 999-99-99';
	}
	$(".form--phone").mask(phone_mask);
	$(".form--registration").mask('100099999');

	//header search
	$('.header-search__init').click(function() {
		var search = $(this).closest('.header-search');
		search.addClass('show');
		search.find('.header-search__input').focus();
	});

	$('.header-search__close').click(function() {
		var search = $(this).closest('.header-search');
		search.removeClass('show');
		search.find('.header-search__input').val('');
		var form = $('#'+search.attr('data-form'));
		form.find('.text').val('');
		updateAjaxFormContent(form);
	});


	//Search filter input
	function searchInputFn(event){
		if ($(this).val().length > 0) {
			if (searchInputFn.timeout){
				clearTimeout(searchInputFn.timeout);
			}

			searchInputFn.timeout = setTimeout(() => {
				let form = $( $(this).attr('data-form') );
				form.find('.text').val($(this).val());
				form.find('.page').val(1);
				updateAjaxFormContent(form);
			}, 1000);
		}
	};

	$(".search-input").keyup(searchInputFn);

	$(".search-input-on-single-article").keyup(function(e){
		e.preventDefault();
		if(e.keyCode == 13){
			location.href=$(this).data('href')+'?ArticleSearch='+$(this).val();
		}
	});


	//checkbox filter expand
	$('.checkbox-filter-more').click(function(){
		var filter = $(this).parent().find('.checkbox-filter');
		if($(this).hasClass('opened')){
			$(this).removeClass('opened');
			if (!$(this).hasClass('def-text')) {
				$(this).text('Показать все');
			}
			filter.css('overflow-y', 'hidden').scrollTop(0);
			filter.parent().children('.checkbox-filter-search').remove();
			filter.find('.checkbox').show();
			filter.parent().removeClass('active');
			updateFilterSort(filter.parent());
		}
		else {
			$(this).addClass('opened');
			if (!$(this).hasClass('def-text')) {
				$(this).text('Свернуть');
			}
			filter.css('overflow-y', 'scroll');
			var search = $('<input class="checkbox-filter-search" placeholder="Поиск" type="text" />');
			search.insertBefore(filter).focus().keyup(function(event) {
				if ($(this).val().length > 0) {
					var find = $(this).val().toLowerCase();
					filter.find('.checkbox').each(function(e){
						var title = $(this).find('a').text().toLowerCase();
						if (title.indexOf(find) >= 0){
							$(this).show();
						}
						else {
							$(this).hide();	
						}
						
					});
					filter.find('.input').each(function(e){
						var title = $(this).text().toLowerCase();
						if (title.indexOf(find) >= 0){
							$(this).show();
						}
						else {
							$(this).hide();	
						}
						
					});
				}
				else {
					filter.find('.checkbox').show();
					filter.find('.input').show();
				}
			});
			filter.parent().addClass('active');
		}
		return false;
	});

	//checkbox filter sort
	$('.checkbox-filter-block').each(function(){
		updateFilterSort($(this));
	});

	// ajax pagination
	$(".ajax-content").on("click", ".ajax-paging a", function(){
		var form = $('#'+$(this).closest('ul').attr('data-form'));
		form.find('.page').val($(this).attr('data-page'));
		updateAjaxFormContent(form);
		return false;
	});

	// ajax pagination with button "more"
	$(".ajax-content").on("click", ".ajax-paging-more a", function(){
		form = $('#'+$(this).closest('div').attr('data-form'));
		form.find('.page').val($(this).attr('data-page'));
		updateAjaxFormContent(form, null, null, true);
		return false;
	});

	// toggle event block
	$('.block-event .toggled').click(function () {
		let block = $(this).closest('.block-event');
		$(this).toggleClass('open');
		block.toggleClass('open').find('.block-event__toggle').slideToggle('200', 'linear');

		block.trigger(block.hasClass('open') ? 'open' : 'close');
	});


	//dod toggle event block
	$('.block-event.block-event--dod').on('open', function (event) {
		let loadMap = () => {
			let loadedClass = 'loaded';
			let mapBlock = $(this).find('.map');
			if (!mapBlock.length){
				return true;
			}
			let latitude = mapBlock.data('latitude');
			let longitude = mapBlock.data('longitude');

			if (!mapBlock.data(loadedClass)){
				let myMap = new ymaps.Map(mapBlock.get(0), {
					center: [latitude, longitude],
					zoom: 15
				});

				let myPlacemark = new ymaps.Placemark([latitude, longitude]);
				myMap.geoObjects.add(myPlacemark);
				mapBlock.data(loadedClass, true)
			}
		};

		if (typeof ymaps.Map === 'function'){
			loadMap();
		}
		else{
			ymaps.ready(() => {
				loadMap();
			})
		}
	});
	
	// toggle class "active" list-towns
	$('.list-towns li > a').click(function(e) {
		var sel = $(this).attr('href').replace('#', '.');
		$(sel).removeClass('hidden').siblings('div').addClass('hidden');
	});

	// nav tabs animate
	/*$('.nav-tabs a').click(function (e) {
		e.preventDefault();
		$(this).tab('show');
		$('html, body').animate({
			scrollTop: $(this).parent().parent().offset().top - 20
		}, 700);
	});*/

	//additional action on tab opened
	$('.nav-tabs a').on('show.bs.tab', function(event){
		if (event.currentTarget.id='questionSpecialityLink'){
			return
		} else {
			var elementClass = $(event.target).attr('data-hideshow');
			if(elementClass){
				var elementId = elementClass + '-' + $(event.target).attr('aria-controls');
				$('.' + elementClass).attr('style','display: none !important');
				$('#' + elementId).attr('style','display: block !important');
			}
		}
	});
	// nav tabs animate
	$('.nav-tabs a').not('[data-toggle="href"]').click(function (e) {
		e.preventDefault();
		var link = $(this).attr('data-link');
		if(link && e.currentTarget.id!='questionSpecialityLink'){
			document.location = link;
		}
		else {
			var duration = 500;
			if ($(this).hasClass('no-animate')) {
				duration = 0;
			}

			$(this).tab('show');
			$('html, body').animate({
				scrollTop: $(this).parent().parent().offset().top - 20
			}, duration);
		}
	});

	//auto open tab
	if(window.location.hash && window.location.hash.indexOf("=") < 0){
		$('a[aria-controls="' + window.location.hash.substring(1) + '"]').tab('show');
	}
	
	$(".aside__section").click(function () {
		var _this = $(this);
		var el = _this.find('.icon');
		if (el.hasClass('checked')){
			return false;
		}

		_this.closest('.aside__first').find('.aside__section .icon').removeClass('checked');

		el.addClass('checked');
		var cls = _this.attr('class').split(/\s+/);
		var type = cls[1].replace('aside__section', '');

		$('.block-event').addClass('hidden').fadeOut('fast', blockEventWrapUpdate);
		$('.block-event-' + type).removeClass('hidden').fadeIn('fast', blockEventWrapUpdate);
	});

	$('.all-events').click(function (e) {
		e.preventDefault();
		$('.nav-tabs li').removeClass('active');
		$('.nav-tabs li a[aria-controls="tab-3"]').parent().addClass('active');
		$(this).tab('show');
		$('html, body').animate({
			scrollTop: 300
		}, 500);
	})

	$('[data-toggle="modal"][data-redirect-default]').click(function () {
		if(!redirect_url){
			redirect_url = $(this).data('redirect');
		}
	});
	$('[data-toggle="modal"][data-redirect]').click(function () {
		redirect_url = $(this).data('redirect');
	});

	$('.modal form').submit(function (e) {
		e.preventDefault();
		e.stopPropagation();
		var _form = $(this);
		_form.find('.error').removeClass('error');
		$('.loading').show();
		$.ajax({
			url: _form.attr('action'),
			type: 'post',
			data: _form.serialize(),
			dataType: 'json',
			success: function (data) {
				$('.loading').hide();
				if (data && data.status) {
					if (data.status === 'success') {
						if (data.reload) {
							if (redirect_url) {
								location.href = redirect_url;
							} else {
								location.reload();
							}
						}
						else if(data.message) {
							_form.closest('.modal').modal('hide');
							var messageDialog = $('#modal-message');
							messageDialog.find('.message').html(data.message);
							messageDialog.modal('show');
						}
					} else {
						if (data.errorNames) {
							$.each(data.errorNames, function (i, e) {
								let input = _form.find('[name="' + e + '"]');
								if (input.hasClass('success')) {
									input.removeClass('success').parent().removeClass('success');
								}
								input.addClass('error').parent().addClass('error');
							});
						}
						if(data.errors) {
							var messageDialog = $('#modal-message');
							messageDialog.find('.message').html(data.errors);
							messageDialog.modal('show');
						}

						_form.trigger('hasError');
					}
				}
			},
			error: function(request) {
				$('body').append(request.responseText);
		       	console.log(request);
			}
		});
	});

	$('.inline form').submit(function (e) {
		e.preventDefault();
		e.stopPropagation();
		var _form = $(this);
		_form.find('.error').removeClass('error');
		$('.loading').show();
		$.ajax({
			url: _form.attr('action'),
			type: 'post',
			data: _form.serialize(),
			dataType: 'json',
			success: function (data) {
				$('.loading').hide();
				if (data && data.status) {
					if (data.status === 'success') {
						if(data.paymentForm){
							var paymentForm = $(data.paymentForm);
							$('body').append(paymentForm);
							paymentForm.submit();
						}
						else {
							if (data.reload) {
								sessionStorage.reloadAfterPageLoad = 1;
								if (redirect_url) {
									location.href = redirect_url;
								} else {
									location.reload();
								}
							} else {
								$('#' + _form.attr('data-success')).modal('show');
							}
						}
					} else {
						if (data.errorNames) {
							$.each(data.errorNames, function (i, e) {
								_form.find('[name="' + e + '"]').addClass('error').parent().addClass('error');
							});
							var errorDialog = $('#' + _form.attr('data-error'));
							errorDialog.find('.message').html(data.errors);
							errorDialog.modal('show');
						}
						
						_form.trigger('hasError');
					}
				}
			}
		});
	});

	if (sessionStorage.reloadAfterPageLoad == 1) {
		$('#' + $('.inline form').attr('data-success')).modal('show');
		sessionStorage.reloadAfterPageLoad = 0;
	}

	$('.modal-body .modal__social a').click(function (e) {
		e.preventDefault();
		var link = $(this).attr('href');
		if (redirect_url) {
			link += (link.indexOf('?') === -1 ? '?' : '&') + 'redirect_url=' + encodeURIComponent(redirect_url);
		}
		location.href = link;
	});

	$('a.ajax').click(function (e) {
		e.preventDefault();
		var _self = $(this);
		$('.loading').show();
		$.ajax({
			url: _self.attr('href'),
			type: 'post',
			data: {'Action': _self.data('action'), 'Value': _self.data('value')},
			dataType: 'json',
			success: function (data) {
				$('.loading').hide();
				location.reload(false);
			},
			error: function(data) {
				$('.loading').hide();
				location.reload(false);
			}
		});
	});


	/* ======================================== */
	$('.carousel').carousel({
		interval: 6000,
		pause: false
	});
	$('.univer-carousel').owlCarousel({
		interval: 0,
		items: 1,
		singleItem: true,
		nav: true,
		navText: ["", ""]
	});
	$('.list-carousel .item').each(function(){
		//console.log($(this).width());
	});
	var listFilter = $('.list-filter').owlCarousel({
		interval: 0,
		autoWidth:true,
		nav: true,
		navText: ["", ""],
	});
	listFilter.find('.owl-prev').hide();
	listFilter.on('changed.owl.carousel', function(e) {
		if(e.page.index == 0){
			$(this).find('.owl-prev').hide();
		}
		else {
			$(this).find('.owl-prev').show();
		}
	});

	//TODO .nav-tabs.list-filter edit
	$('.nav-tabs.list-filter a').click(function(event) {
		let list = $(this).closest('.nav-tabs.list-filter');
		list.find('.owl-item li').not($(this).closest('li')).removeClass('active');
	});

	$('input[type="number"]').on('change keyup paste', function () {
		var value = $(this).val().replace(/[^0-9]/g, '');
		var min = parseInt($(this).attr('min'));
		var max = parseInt($(this).attr('max'));
		if (min && value < min) {
			value = min;
		} else if (max && max < value) {
			value = max;
		}
		$(this).val(value);
	});

	$('body').on('change', '#formWho, #formWho2, #formWho3, .formWho', function () {
		updateUserWhoInForm($(this));
	});

	$('body').on('change', '#formContactType', function () {
		var selectedValue = $(this).find('option:selected').val();
		var nextBlock = $(this).parent().next('.form-group');
		
		if (selectedValue === "vk") {
			nextBlock.show();
			nextBlock.find('input').attr('disabled', false);
		}
		else {
			nextBlock.hide();
			nextBlock.find('input').attr('disabled', true);
		}
	});
	
	$('#modal-checkin').on('shown.bs.modal', function (e) {
		var type = $(e.relatedTarget).data('type');
		if(type && type == 'student'){
			var whoField = $(this).find('#formWho');
			whoField.val('student');
			updateUserWhoInForm(whoField);
		}
	});

	$('.block-filters__scoring .end ul li a').click(function(e) {
		if ($(this).attr('aria-disabled') === "true") {
			e.preventDefault();
			return false;
		}
		
		var id = $(this).data('id');
		var title = $(this).text();
		var list = $(this).closest('ul');
		$(this).attr('aria-disabled', true).parent().addClass("disabled");
		
		var li = $('<li class="item">' +
			'    <div class="dropdown bootstrap-select btn-group show-tick" style="width:auto; float: left;">' +
			'        <a class="dropdown-toggle" href="#" data-toggle="dropdown">' + title + '</a>' +
			'        <ul class="dropdown-menu" role="listbox">' + list.html() +
			'        </ul>' +
			'    </div>' +
			'    <span class="scoring-subject"><input type="text" value="" placeholder="0"' +
			' name="SpecialFilter[Subject][' + id + ']"></span>' +
			'</li>');
		li.find('[area-selected="true"]').attr('aria-selected', false);
		li.find('.selected, .disabled').removeClass('selected disabled');
		li.find('a[data-id='+id+']').attr('aria-selected', true).parent().addClass('selected');
		li.insertBefore('.block-filters__scoring .end');
		li.find('.scoring-subject input').focus();

		updateSubjectList();
	});

	$('.block-filters')
		.on('click', '.item .dropdown-menu li a', function (e) {
			if ($(this).parent().hasClass('disabled')) {
				e.preventDefault();
				return false;
			}
			var id = $(this).data('id');
			var title = $(this).text();
			if ($(this).parent().hasClass('selected')) {
				var form = $(this).closest('form');
				$(this).closest('.item').remove();
				form.find('input:first').trigger('change');
			} else {
				$(this).closest('.item').find('li.selected a').attr('aria-selected', false).parent().removeClass('selected');
				$(this).closest('.item').find('input').attr('name', 'SpecialFilter[Subject][' + id + ']');
				$(this).closest('.item').find('.dropdown-toggle').text(title);
				$(this).attr('aria-selected', true).parent().addClass('selected');
				$(this).closest('.item').find('input').trigger('change');
			}
			updateSubjectList();
		})
		.on('keydown', '.scoring-subject input', function(e) {
			return /[0-9]/.test(e.key) || (e.keyCode === 8 || (e.keyCode >= 37 && e.keyCode <= 40));
		})
		.on('keyup paste', '.scoring-subject input', function() {
			var self = $(this);
			var value = parseInt(self.val().replace(/[^\d\.\-]/g,''));
			if (isNaN(value)) {
				value = '';
			} else if (value > 100) {
				value = 100;
			} else if (value < 0) {
				value = 0;
			}
			$(this).val(value);
			delay(function(){
				self.trigger('change');
			}, 250);
		})
		.on('change', 'select, input[type=checkbox], input[type=text], input[type=hidden]', function() {
			var form = $(this).closest('form.block-filters');
			updateAjaxFormContent(form);
		});
	updateSubjectList();

	//checkbox filter multiple
	$('body').on('click', '.checkbox-filter.multiple .checkbox a', function(){
		var item = $(this).parent('.checkbox');
		var item_filter = item.parent('.checkbox-filter');

		if ( item.hasClass('active') ) {
			item.find('.sub-list').fadeOut(150);
			item.find('.checkbox').removeClass('active');
			item.find('input').attr('disabled', 'disabled');

			if ( (item_filter.children('.checkbox.active').length -1) < 1 ) {
				item_filter.parent('.checkbox').children('input').removeAttr('disabled');
			}
		}
		else{
			if (item_filter.parents('.checkbox').length) {
				item_filter.parents('.checkbox').children('input').attr('disabled', 'disabled');
			}
			item.children('.sub-list').fadeIn(150);
		}
	});
	
	//checkbox filter
	$('body').on('click', '.checkbox-filter .checkbox a', function(){
		var nextBlock = $(this).next();
		if ($(this).parent().hasClass('active')) {
			$(this).parent().removeClass('active');
			nextBlock.attr('disabled', true);
		}
		else {
			$(this).parent().addClass('active');
			nextBlock.attr('disabled', false);
		}
		$(this).trigger('change');
		var form = $(this).closest('form');
		var sort;
		if ($('.dropdown-type2__text').text()=='по среднему доходу') {
			sort = 'cost';
		} else if ($('.dropdown-type2__text').text()=='по алфавиту') {
			sort = 'title'
		}
		updateAjaxFormContent(form, 1, sort);
		return false;
	});
	
	//input filter
	$('.checkbox-filter .input input').on('keyup', function(e){
		let previousValue = $(e.target).data('previousValue');

		let regex = new RegExp("^([0-9]+|)$");

		if (!regex.test(this.value)) {
			$(e.target).val(previousValue);
		}
		else if (this.value !== previousValue){
			let form = $(this).closest('form');
			updateAjaxFormContent(form);
			return false;
		}

		/*if(e.which != 8 && (e.which < 48 || e.which > 57)){
			$(e.target).val(previousValue);
		}
		else if(parseInt($(e.target).val()) > 100){
			$(e.target).val(previousValue);
		}
		else {
			var form = $(this).closest('form');
			updateAjaxFormContent(form);
		}*/
		return false;
	}).on('keydown', function(e){
		$(e.target).data('previousValue', $(e.target).val());
	});

	$('#countdown').each(function() {
	   var date = $(this).text();
	   var target = moment.tz(date, "YYYY/MM/DD H:m:s", "Europe/Moscow");
		$(this).countdown(target.toDate(), function(event) {
			$(this).html(event.strftime(''
				+ '<span class="date">%D<span>Дней</span></span> <span class="divider">:</span> '
				+ '<span class="date">%H<span>Часов</span></span> <span class="divider">:</span> '
				+ '<span class="date">%M<span>Минут</span></span> <span class="divider">:</span> '
				+ '<span class="date">%S<span>Секунд</span></span>'));
		});
	});
	
	$('.schedule__list-type li').click(function() {
		var el = $(this).find('.icon').toggleClass('checked');

		var cls = $(this).attr('class').split(/\s+/);
		cls = cls[0];
		
		if (el.hasClass('checked')) {
			$('.block-event--' + cls).removeClass('hidden').fadeIn('fast', blockEventWrapUpdate);
		} else {
			$('.block-event--' + cls).addClass('hidden').fadeOut('fast', blockEventWrapUpdate);
		}
	});
	
	//Registration exhibition form
	//save form for autofill
	$('body').on('change', '.registration-exhibition-form .form-control', function(event) {
		var form = $(this).closest('.registration-exhibition-form');
		var data = form.serialize();
		data += '&Action=saveRegisterExhibitionForm';

		$.ajax({
				url: form.attr('ajax-action'),
				type: 'post',
				data: data,
				dataType: 'json',
				success: function (data) {
					//console.log(data);
				},
				error: function(request, error){
		        	//$('body').append(request.responseText);
		        	//console.log(request);
		        	//console.log('error: ' + error);
		        }
			});
	});

	$('.white-btn--registration').click(function(e) {
		e.preventDefault();
		var block = $('.wrap-block-registration .block-registration:first')
			.clone()
			.appendTo('.wrap-block-registration')
			.end();
		block.find(':input')
			.not(':button, :submit, :reset, :hidden')
			.val(null)
			.removeAttr('checked')
			.removeAttr('selected');
		
		block.find(".form--phone").mask(phone_mask);
		block.find(".modal__social").remove();
		block.find('select option:nth-child(1)')
			.attr('selected', true)
			.closest('select')
			.trigger('change');
		block.find('.form-group').removeAttr('style');
		block.children('.errors').html('');
		block.children('.message-list').html('');
		block.children('.header').removeClass('hidden');
	});

	$('body').on('click', '.block-registration .remove-item', function(e) {
		e.preventDefault();
		$(this).parents('.block-registration').remove();
	});

	// menu-open
	$(document).on('click', '.dropdown-type2 .menu-open', function (e) {
		e.stopPropagation();
	});

	$(document).on('click', '.dropdown-region .menu-open', function (e) {
		e.stopPropagation();
	});

	$('.dropdown-region > .menu-open > li > a').click(function(e) {
		e.preventDefault();
		var e = $(this).parent().toggleClass('active');
		if (! e.hasClass('active')) {
			e.find('ul li.active a').trigger('click');
		}
	});

	$('.dropdown-type1').find('.dropdown-type1__text').text($('.dropdown-type1').find('li.active').find('span').text());
	$('.dropdown-type1 .menu-open li > a').click(function(e) {
		var selectName = $(this).find('span').text();
		$(this).parents('.dropdown').find('li').removeClass('active');
		$(this).parent().addClass('active');
		$(this).parents('.dropdown-type1').find('.dropdown-type1__text').text(selectName);
		document.location = $(this).attr('href');
		e.preventDefault();
	});

	$('.dropdown-type2').find('.dropdown-type2__text').text($('.dropdown-type2').find('li.active').find('span').text());
	$('.dropdown-type2 .menu-open li > a').click(function(e) {
		var selectName = $(this).find('span').text();
		$(this).parents('.dropdown').find('li').removeClass('active');
		$(this).parent().addClass('active');
		$(this).parents('.dropdown-type2').find('.dropdown-type2__text').text(selectName);
		if ($(this).hasClass('open-link')) {
			document.location = $(this).attr('href');
		}
		e.preventDefault();
	});

	$('.menu-open__2 li > a, .dropdown-type2 .menu-open li > a').click(function(e) {
		e.preventDefault();
		var parent = $(this).parent().toggleClass('active');
		parent.find('input').attr('disabled', !parent.hasClass('active')).trigger('change');
		updateDropdownSelected($(this).closest('.dropdown'));
		if ($('ul.dropdown-menu.menu-open').css('display')=='block'){
			$('ul.dropdown-menu.menu-open').css('display', 'none');
		} else {
			$('ul.dropdown-menu.menu-open').css('display', 'block')
		}
		var form = $('form#professionFilterForm');
		var href = $(e.target).closest('a')[0].href;
		var url = new URL(href);
		var searchParams = new URLSearchParams(url.search);
		var sort = searchParams.get('SortOrder');
		updateAjaxFormContent(form, 1, sort);
	});
	$('.custom-select .dropdown').each(function() {
		updateDropdownSelected($(this));
	});
	
	$('.block-filters__clear').click(function(e) {
		e.preventDefault();
		var form = $(this).closest('form');
		form.find('.block-filters__scoring li.item').remove();
		updateSubjectList();
		
		form.find('input[type="text"]').val("");
		form.find('input[type="checkbox"]').removeAttr('checked')
		form.find('select option').removeAttr('selected');
		form.find('.custom-select .dropdown li.active')
			.removeClass('active')
			.find('input')
			.attr('disabled', true);
		form.find('.custom-select .dropdown').each(function() {
			updateDropdownSelected($(this));
		});
		form.find('select, input[type=checkbox], input[type=text], input[type=hidden]').trigger('change');
	});
	
	//time filling
	$('.timelink').click(function(){
		$('.form-control.time').val($(this).text());
		$('html, body').animate({
			scrollTop: $(".p-header").offset().top
		}, 1000);
		return false;
	});
	
	//userpic upload
	$('#userpiclick').click(function(){
		$('#userpic').trigger('click');
		return false;
	});
	
	$(".dropdown-main").on('show.bs.dropdown', function(e){
		$(".second-menu").addClass('possible');
	});
	$(".dropdown-main").on('hidden.bs.dropdown', function(e){
		$(".second-menu").removeClass('possible');
	});

	$(".dropdown-main").on('click','.dropdown-menu', function(e){
        e.stopPropagation();
	});
	
	//tootips
	$('[data-toggle="tooltip"]').tooltip();

	//slide toggle btn
	$('[data-side-toggle]').click(function(event) {
		let target = $(this).data('side-toggle');
		$(target).slideToggle();
	});
	
	//questions
	$('.questions').on('focus', '.question textarea', function(){
		if(!ANON_USER_CAN_QUESTION && !user_id){ //open login dialog if user not logged in
			$('#modal-signin').modal('show'); 
			return;
		}
		$(this).parent().find('.send').show();
	}).on('focusout', '.question textarea', function(){
		var input = $(this);
		delay(function(){
			if(!input.is(':focus')){
				input.parent().find('.send').hide();
			}
		}, 500);
	}).on('keydown', '.question textarea', function(){
		 $(this).css('height','auto');
		 $(this).height(this.scrollHeight);
	});
	$('.questions').on('click', '.question .send', function(){
		var input = $(this).parent().find('textarea');
		var text = input.val();
		var typesForMessage = ['university', 'speciality', 'college', 'collegeSpeciality'];

		if(text){
			$('.loading').show();
			var form = $(this).closest('.questions').find('form');
			var parentId = $(this).closest('.question').attr('data-parentid');
			const anonUserName = $(this).closest('.question').find('input[name="AnonUserName"]').val();

			const data = {
				Action: 'addQuestionMessage',
				Type: form.find('input[name=Type]').val(),
				AttachID: form.find('input[name=AttachID]').val(),
				AnonUserName: form.find('input[name=AnonUserName]').val(),
				Text: text,
				ParentID: parentId,
			};

			if(!user_id) {
				data['AnonUserName'] = anonUserName;
			}

			$.ajax({
				url: form.attr('action'),
				type: 'post',
				data,
				dataType: 'json',
				success: function (data) {
					if (data && data.status && data.status === 'success') {
						input.val("");
						updateAjaxFormContent(form);
						if ( $.inArray(form.children('input[name=Type]').val(), typesForMessage) !== -1 ) {
							input.parents('.form-group').html('Спасибо! Мы ответим на Ваш вопрос в течение 5 рабочих дней.');
						}
					}
					else if(data && data.status && data.status === 'error'){
						alert(data.error_list);
					}
				},
				error: function(request, error){
					if (DEV_MODE){
						$('body').append(request.responseText);
						console.log(request.responseText);
					}
		        	//console.log('error: ' + error);
		        }
			})
			.always(function(){
				$('.loading').hide();
			});
		}
		return false;
	});
	$('.questions').on("click", ".message .answer", function(){
		if(!ANON_USER_CAN_QUESTION && !user_id) { //open login dialog if user not logged in
			$('#modal-signin').modal('show'); 
			return false;
		}
		var message = $(this).closest(".message");
		$(this).closest(".ajax-content").find(".question.answer").remove();
		var answerBlock = $(this).closest(".questions").find(".question.answer").clone();
		answerBlock.insertAfter(message).show();
		answerBlock.attr("data-parentid", message.closest('.message-block').attr("data-messageid")).find("textarea").val(message.find(".user").text() + ", ").focus();
		return false;
	});
	
	//document form
	updateDocumentPrice($('.document-form'));
	updateDocumentUniversities($('.document-form'));
	$('.document-form').on("change", "select", function(){
		updateDocumentPrice($(this).closest('form'));
	}).on("click", ".document-control-add", function(){
		var countInput = $(this).closest('form').find('input[name=UniversityCount]');
		if(countInput.val() >= 5){
			alert("Нельзя подавать документы в более чем 5 вузов");
		}
		else {
			countInput.val(parseInt(countInput.val()) + 1);
			updateDocumentUniversities($(this).closest('form'));
			updateDocumentPrice($(this).closest('form'));
		}
		return false;
	}).on("click", ".document-control-del", function(){
		var countInput = $(this).closest('form').find('input[name=UniversityCount]');
		if(countInput.val() <= 1){
			alert("Должен остаться хотя бы 1 вуз");
		}
		else {
			countInput.val(parseInt(countInput.val()) - 1);
			updateDocumentUniversities($(this).closest('form'));
			updateDocumentPrice($(this).closest('form'));
		}
		return false;
	});
	
	//linkify
	linkify();
	
	//alert on open
	if(alertMessage){
		var messageDialog = $('#modal-message');
		messageDialog.find('.message').html(alertMessage);
		messageDialog.modal('show');
	}
	
	//back history support
	window.onpopstate = function(event) {
		if (window.location.hash.length < 1){
			location.reload();
		}
	};
	
	//short speciality list
	initSpecialityList();
	$('.university-item').parent().on("click", ".more", function(){
		updateSpecialityList($(this).closest('.university-item'), $(this).attr('data-count'));
		return false;
	});
	
	//custom check empty fields
	$('.form-group').on('keyup', '.checkempty', function () {
		if($(this).val() == ''){
			$(this).removeClass('notempty');
		}
		else {
			$(this).addClass('notempty');
		}
	});

	//show banner on first open
	npbanner();

	//Banners
	/*$('.rotate-banner').each(function (e) {
		let interval = $(this).data('rotate-interval');
		if (interval > 0){
			rotateBanners($(this), interval);
		}
	});*/

	//rotateBanners($('.rotate-banner'), 1);

	// слушаем клик по пагинации
	$(document).on('click', '.page-profession-html .ajax-content ul.pagination a', function (e) {
		e.preventDefault();
		var form = $('form#professionFilterForm');
		var url = new URL(e.target.href);
		var searchParams = new URLSearchParams(url.search);
		var page = searchParams.get('ProfessionPager');
		var sort;
		if ($('.dropdown-type2__text').text()=='по среднему доходу') {
			sort = 'cost';
		} else if ($('.dropdown-type2__text').text()=='по алфавиту') {
			sort = 'title'
		}
		updateAjaxFormContent(form, page, sort);
	});

	$('a[data-toggle="dropdown"]').click(function(){
		var droplist = $(this).next('ul.dropdown-menu.menu-open');
		if (droplist.css('display')=='block'){
			droplist.css('display', 'none');
		} else {
			droplist.css('display', 'block');
		}
	})

	//university closed alert
	$('.closed-alert').click(function(){
		$('#modal-closed-alert').modal();
		var link = $(this).attr('href');
		$('#modal-closed-alert').find('.link').unbind("click").click(function(){
			window.open(link, '_blank');
			$('#modal-closed-alert').modal('toggle');
			return false;
		});
		return false;
	});

	//article hover
	$('.other-articles .item a').hover(function() {
		$(this).closest('.item').find('.title').addClass('active');
	}, function() {
		$(this).closest('.item').find('.title').removeClass('active');
	});
	
	//hide btn
	$('body').on('click', '.hide-btn', function(event) {
		event.preventDefault();
		var selector = $(this).attr('data-hide');
		$(this).closest(selector).hide();
	});

	//proftest reset function
	$('.proftest-reset-btn').click(function(){
		$('#modal-proftest-reset').modal();
		return false;
	});
	$('.proftest-reset-dialog-close').click(function(){
		$('#modal-proftest-reset').modal('toggle');
		return false;
	});
	$('.proftest-reset-dialog-reset').click(function(){
		document.location = $(this).attr('href');
		return false;
	});


	//modal
	$('body').on('click', '.modal-close-btn', function(event) {
		event.preventDefault();
		$(this).closest('.modal').modal('hide');
	});


	if ($('#formWho, #formWho2, #formWho3, .formWho').length) {
		$('#formWho, #formWho2, #formWho3, .formWho').each(function(index, el) {
			updateUserWhoInForm($(this));
		});
	}

	//Article vk comments block
	if ($('#vk_comments').length) {
	 	VK.Widgets.Comments("vk_comments", {limit: 10, attach: "*"});
	}

	//Share block
	$('.share-block .item').click(function (event) {
	    let counter = $(this).find('.counter');
		let count = parseInt(counter.text());
		let block = $(this).parent();
		let data = {
			Action: 'NewShare',
			ItemType: block.data('item-type'),
			ItemID: block.data('item-id'),
			Value: 1,
			ShareItem: $(this).data('share-item'),
		};

		AjaxRequester.send(project_path + 'module/data/ajax_public.php', data);
		
		if (counter.length < 1){
            $(this).append('<div class="counter">1</div>');
        }
        else{
			counter.text(count + 1);
        }
	});

    $('.share-block.selected .item').click(function(event){
        event.preventDefault();
        let shareName = $(this).data('share-item');
        let href = $(this).attr('href');
        let str = window.getSelection().toString();
        if (str.length > 0){
            switch (shareName) {
                case 'Facebook':
                    href += `&quote=${encodeURI(str)}`;
                    break;
                default:
                    break;
            }
        }

        window.open(href, '_blank')
    });

	//Auto save form
	if ($('[auto-save-form]').length){
		$('[auto-save-form]').each(function () {
			new AutoSaveForm($(this)).init()
		})
	}

	//Like article
	$('.article-like-btn').bind("click", function (event) {
		let itemID = $(this).data('item-id');
		let data = {
			ItemID: itemID,
			Action: 'AddArticleLike',
		};
		AjaxRequester.send(project_path + 'module/data/ajax_public.php', data, (response) => {
			let counter = $(this).find('.count');
			counter.text(parseInt(counter.text()) > 0 ? parseInt(counter.text()) + 1: 1);
			$(this).unbind('click');
			$(this).addClass('active');
		});
	});

	//scroll top btn
	$('#scroll-top-btn').click(function(event) {
		event.preventDefault();
		$('html,body').animate({
			scrollTop: 0
		}, 400);
	});

    //sticky sidebar
    $('.sticky-sidebar').stick_in_parent({
		offset_top: 80,
	});

    //share when select text
	let shareableSelected = $('.shareable-selected');
	if (shareableSelected.length){
		let tip = new CustomToolTip();
		tip.init();
		tip.content = $('.share-block.selected');
		tip.block.click(function () {
			window.getSelection().removeAllRanges();
		});

		shareableSelected.mouseup(function (event){
			let srt = window.getSelection().toString();
			if (srt.length > 3){
				tip.showUnderCursor({
					coords: {x: event.pageX, y: event.pageY},
				});
			}
			else{
				tip.hide();
			}

			$(document).on('click.shareableSelected', function (event) {
				if (shareableSelected.has(event.target).length === 0){
					tip.hide();
					$(document).unbind('click.shareableSelected');
				}
			});
		});
	}

	//sticky header
	let header = document.querySelector('.navbar');
	let secondMenu = document.querySelector('.second-menu');
	window.addEventListener('scroll',function (event) {
		if (this.pageYOffset >= header.offsetHeight && header.offsetTop !== 0 && !secondMenu.classList.contains('possible')){
			header.classList.add('glued');
		}
		else{
			header.classList.remove('glued');
		}
	});
	
	//form multiselect
	$('.form-multiselect').multiselect({
		nonSelectedText: $(this).data('placeholder'),
		buttonWidth: '100%',
		buttonClass: 'form-control',
		nSelectedText: ' элементов выбрано',
		numberDisplayed: 2
    });
	
	//article images
	$('.article p img').click(function(){
		var dialog = $('#modal-article-image');
		dialog.find('.message').html('<img src="' + $(this).attr('src') + '" style="width:100%"/>');
		dialog.modal('show');
	});

	/*$('.social-widget#widget-vk').iframeTracker({
        blurCallback: function(event){
        	yaCounter47132955.reachGoal('vk_widget');
			ga('gtag_UA_111538395_1.send', 'event', 'social', 'vk_click');
			amplitude.getInstance().logEvent('vk_widget');
        }
    });
    $('.social-widget#widget-fb').iframeTracker({
        blurCallback: function(event){
        	yaCounter47132955.reachGoal('fb_widget');
        	ga('gtag_UA_111538395_1.send', 'event', 'social', 'fb_click');
        	amplitude.getInstance().logEvent('fb_widget');
        }
    });
    $('.social-widget#widget-ig').iframeTracker({
        blurCallback: function(event){
        	yaCounter47132955.reachGoal('ig_widget');
        	ga('gtag_UA_111538395_1.send', 'event', 'social', 'ig_click');
        	amplitude.getInstance().logEvent('ig_widget');
        }
    });*/

	initInfinityScroll();

	//carousel-widget
	let carouselWidget = $('[data-carousel-widget]');
	if (carouselWidget.length){
		carouselWidget.owlCarousel({
			nav: true,
			navText: false,
		});

		carouselWidget.find('.item').each(function () {
			let item = $(this);
			let image = item.find('img');
			if (image.length){
				item.attr('href', image.attr('src'))
			}
		});

		let lightbox = $('[data-carousel-widget] .item').simpleLightbox();
	}

	//slide list
	let slideList = $('[data-slide-list]');
	if (slideList.length){
		slideList.find('[data-nav-btn]').click(function (event) {
			let list = $(this).closest('[data-slide-list]');
			let nextBtn = list.find('[data-nav-btn="next"]');
			let reloadBtn = list.find('[data-nav-btn="reload"]');
			let active = list.find('.item.active');
			let firstSlide = list.find('.item:first');
			let newSlide = $(this).data('nav-btn') === 'next' ? active.next('.item') : firstSlide;

			active.hide().removeClass('active');
			newSlide.fadeIn().addClass('active');


			if (newSlide.next('.item').length < 1) {
				nextBtn.hide();
				reloadBtn.show();
			} else {
				nextBtn.show();
				reloadBtn.hide();
			}
		})
	}

	$('.date-link').on('shown.bs.tab', function (e) {
		$('.schedule .rooms:not(.active)').hide();
		$('.schedule .actions').removeClass('active').removeClass('in');
		$('.schedule .rooms.active').show();
		id = $('.schedule .rooms.active li.active a').attr('href');
		$(id).addClass('active').addClass('in');
	});
});


$(window).on('load', function () {
	//defer src
	$('[data-defer-src]').each(function () {
		$(this).attr('src', $(this).data('defer-src'));
	});

	//include after load

	/*if ($('[data-after-load]').length) {

	    let fn = function(item, timeout){
			setTimeout(function() {
				item.load(item.data('after-load'));
			}, timeout);
		};

		let items = $('[data-after-load]');
		items.each(function(index, el) {
		    let timeOut = $(this).data('time-out') * 1000;

		    if (typeof timeOut == 'undefined' || parseInt(timeOut) < 1){
		        timeOut = 1000;
            }

			fn($(this), timeOut);
		});
	}*/

	var maxHeight = 0;
	$(".section-event--type3 .block-event").each(function () {
		var height = parseInt($(this).height());
		if (height > maxHeight) {
			maxHeight = height;
		};
	});
	$(".section-event--type3 .block-event").height(maxHeight);
});


function sortByPosition(dragList) {
	count = dragList.children('.item').length;
	for (var i = 1; i <= count; i++) {
		var item = dragList.children('.item').children('.item-position[value=' + i + ']').parent('.item');
		item.detach().appendTo(dragList);
	}
}

function setOrderPosition(dragList) {
	//console.log(dragList);
	i = 1;
	dragList.children('.item').each(function(index, el) {
		var position = $(this).children('.item-position');
		position.val(i);
		i++;
	});
}

function blockEventWrapUpdate() {
	$('.block-event-wrap').each(function () {
		var bl = $(this).find('.block-event:not(.hidden)');
		if (bl.length === 0) {
			$(this).hide();
		} else {
			$(this).show();
		}
	});
}

function updateSubjectList() {
	$('.block-filters .end .dropdown-menu li.disabled, .block-filters .item .dropdown-menu li.disabled')
		.removeClass('disabled').find('a').attr('aria-disabled', false);
	$('.block-filters .item .dropdown-menu li.selected a').each(function() {
		var id = $(this).data('id');
		$(this).closest('.item').siblings('.item, .end').find('a[data-id='+id+']').attr('aria-disabled', true)
			.parent().addClass('disabled');
	});
}

function updateDropdownSelected(dropdown) {
	var list = dropdown.find('ul li ul li.active a').map(function() {
		return $.trim($(this).text());
	}).get();
	
	if (list.length > 0) {
		if (list.length < 3) {
			dropdown.find('.block-filters__selected-text').text(list.join(', '));
		} else {
			dropdown.find('.block-filters__selected-text').text(list.length + ' выбрано');
		}
	} else {
		dropdown.find('.block-filters__selected-text').text("");
	}
}

function updateAjaxFormContent(form, page, sort, isPagingMore = false) {
	if (history.pushState) {
		//modify GET parameters on AJAX form
		var params = [];
		var regex = /(.+?)\[(.+?)\](\[(.*?)\])*/;
		var filterNames = ['UniverFilter','ProfessionFilter','CollegeFilter', 'ArticleFilter'];
		var pagerNames = ['UniverPager','CollegePager'];
		$.each(form.serializeArray(), function (i, input) {
			var res = regex.exec(input.name);
			if(res !== null && $.inArray(res[1], filterNames) != -1 && input.value){
				if(res[3] == '[]'){
					params.push(res[2] + '[]=' + input.value);
				}
				else if(res[4]){
					params.push(res[2] + '[' + res[4] + ']=' + input.value);
				}	
				else {
					params.push(res[2] + '=' + input.value);
				}
			}
			else if($.inArray(input.name, pagerNames) != -1 && input.value > 1){
				params.push(input.name + '=' + input.value);
			}
			//console.log(input);
		});
		var url = window.location.href;
		if(url.indexOf('?') != -1){
			url = url.substring(0, url.indexOf('?'));
		}
		if(params.length > 0){
			url += '?' + params.join('&');
		}
		window.history.pushState(null, "", url);
	}
	var dataToServer = form.serialize()+(page?'&AjaxPager='+page:'')+(sort?'&SortOrder='+sort:'');
	delay(function(){
		var outputArea = form.next('.ajax-content'); //or
		if(outputArea.length == 0) outputArea = form.closest('.ajax-content'); //or
		if(outputArea.length == 0) outputArea = form.closest('.row').find('.ajax-content');

		if (isPagingMore) {
			$('.ajax-paging-more').remove();
		} else {
			outputArea.empty();
		}

		outputArea.append('<div class="loading-inner"><img src="' + path2main + 'img/loader.png"/></div>');
		$.ajax({
			url: form.attr('action'),
			type: 'post',
			data: dataToServer,
			dataType: 'json',
			success: function (data) {
				$('.loading').hide();
				//console.log(data);
				if (data && data.status && data.status === 'success' && data.html) {
					$('.loading-inner').remove();
					outputArea.append(data.html);
					//call custom js function after data load
					var onload = form.attr('data-onload');
					if(onload){
						var fn = window[onload];
						if (typeof fn === "function") fn();
					}
					
					linkify();
				}
			},
			error: function(request, error){
	        	//console.log(request.responseText);
	        }
		});
	}, 100);
}

function updateDocumentPrice(form) {
	if (typeof documentPriceList !== 'undefined') {
		var regionID = form.find('select[name=RegionID],input[name=RegionID]').val();
		var universityCount = form.find('select[name=UniversityCount],input[name=UniversityCount]').val();
		var price = documentPriceList[regionID]['Price' + universityCount];
		form.find('.document-price').text(price.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1 ") + " ₽");
	}
}

function updateDocumentUniversities(form) {
	if (typeof documentPriceList !== 'undefined') {
		var oldValues = form.find('#university-list input').map(function(){return $(this).val();}).get();
		form.find('#university-list').empty();
		var universityCount = form.find('input[name=UniversityCount]').val();
		for(var i=0; i<universityCount; i++){
			var value = "";
			if(oldValues.length > i) value = oldValues[i];
			var input = $('<div class="form-group"><input type="text" required  name="University[]" class="form-control" value="' + value + '"><label>Вуз ' + (i+1) + '</label></div>');
			form.find('#university-list').append(input);
		}
	}
}

function linkify() {
	$(".message p").linkify({
		target: "_blank",
		attributes: {"rel":"nofollow"}
	});
}

function updateFilterSort(block) {
	var list = block.children('.checkbox-filter');
	var items = [];
	var active = [];
	block.find('.checkbox, .input').each(function(){
		var i = parseInt($(this).attr('data-sort'));
		if(i > 0){
			items[i - 1] = $(this);
			active[i - 1] = ($(this).hasClass('checkbox') && $(this).hasClass('active')) || ($(this).hasClass('input') && $(this).children('input').val() > 0);
			$(this).detach();
		}
	});
	for(var i=0; i<items.length; i++) if(active[i]) list.append(items[i]);
	for(var i=0; i<items.length; i++) if(!active[i]) list.append(items[i]);
}

function initSpecialityList() {
	$('.university-item').each(function(){
		updateSpecialityList($(this), 3);
	});
}

function updateSpecialityList(block, count) {
	var i=0;
	block.find('tr.hidden').each(function(){
		if(i < count) $(this).removeClass('hidden');
		i++;
	});
	var more = block.find('tr.hidden').length;
	if(more > 0){
		if(more > 10) more = 10;
		block.find('.more').attr('data-count', more).text('Еще ' + more + ' направлений').show();
	}
	else {
		block.find('.more').hide();
	}
}

function npbanner(){
	var cookiesArray = document.cookie.split('; ').filter((e)=> (e.indexOf('npbanner')==0))
	if (!(cookiesArray.length) && document.location.href.indexOf("/exhibition") == -1 && document.location.href.indexOf("/navi_prof") == -1){
		setTimeout(function(){
			var date = new Date();
			date.setMonth(date.getMonth() + 1);
			let cookie = 'npbanner=1; expires='+date;
			if (typeof DOMAIN != 'undefined'){
				cookie += '; domain='+DOMAIN;
			}
			document.cookie = cookie;
			$('#npbanner').modal();

			//analytic
			let as = new AnalyticsSystem();
			as.Systems = ['Am'];
			let link = $('#npbanner').find('.banner a');
			let linkName = link.data('name');
			as.sendEvent(
				'show_banner',
				{
					name: linkName,
				}
			);
		}, 5000);
	}
}

/*function rotateBanners(element, seconds = 5){
	let id;
	let interval = seconds * 1000;
	let nextItemFn = function() {
		let active = element.children('.active');
		let next = active.removeClass('active').hide().next();
		if (next.length < 1) {
			next = element.children().first();
		}
		next.addClass('active').fadeIn();
	};

	if (element.children().length < 2){
		nextItemFn();
		return false;
	}

	id = setInterval(nextItemFn, interval);
	element.hover(function() {
		clearInterval(id);
	}, function() {
		id = setInterval(nextItemFn, interval);
	});
}*/

function updateUserWhoInForm(input){
	var selectedValue = input.find('option:selected').val();
	var classBlock = input.parent().next('.form-group');
	var classLabel = classBlock.find('label');
	var universityBlock = input.parent().parent().find('#formUniversity-group');
	var courseBlock = input.parent().parent().find('#formCourse-group');

	if (selectedValue === "child") {
		classBlock.show();
		classLabel.text((typeof LNG_WhatClassYou !== 'undefined' ? LNG_WhatClassYou : 'В каком вы классе?'));
		classBlock.find('input').attr('disabled', false);
		universityBlock.hide();
		universityBlock.find('input').attr('disabled', true);
		courseBlock.hide();
		courseBlock.find('input').attr('disabled', true);
	}
	else if (selectedValue === "parent") {
		classBlock.show();
		classLabel.text((typeof LNG_WhichClassChild !== 'undefined' ? LNG_WhichClassChild : 'В каком классе ребенок?'));
		classBlock.find('input').attr('disabled', false);
		universityBlock.hide();
		universityBlock.find('input').attr('disabled', true);
		courseBlock.hide();
		courseBlock.find('input').attr('disabled', true);
	}
	else if (selectedValue === "student") {
		classBlock.hide();
		classBlock.find('input').attr('disabled', true);
		universityBlock.show();
		universityBlock.find('input').attr('disabled', false);
		courseBlock.show();
		courseBlock.find('input').attr('disabled', false);
	}
	else if (selectedValue === "teacher") {
		classBlock.hide();
		classBlock.find('input').attr('disabled', true);
		universityBlock.show();
		universityBlock.find('input').attr('disabled', false);
		courseBlock.show();
		courseBlock.find('input').attr('disabled', false);
	}

	classBlock.children('input.form-control').trigger('change');
}

function getCurrentModuleUrl() {
	let path = window.location.pathname.split('/');
	let url = path[1];
	if (path[1] == 'test' || path[1] == 'navigator') {
		url = path[2];
	}
	if (url.length < 1) {
		url = 'main';
	}

	return url;
}

//special for articles // TODO use InfiniteScrollPagination after
function initInfinityScroll(){
	//console.log('ad');
	let infScroll = $('[infinite-scroll]');

	if (infScroll.length){
		infScroll.each(function () {
			new InfiniteScrollPagination($(this), '[infinite-scroll]').init();
		});
	}
}