var phone_mask = '+7 (999) 999 - 99 - 99';
var delay = (function(){
    var timer = 0;
    return function(callback, ms){
        clearTimeout (timer);
        timer = setTimeout(callback, ms);
    };
})();
$(document).ready(function () {
	//apply phone mask
	if(document.location.pathname.indexOf('/minsk/') != -1){//TODO: move to city config
		phone_mask = '+375 99 999-99-99';
	}
	$(".form--phone").mask(phone_mask);

	// backgroundForNav
	backgroundForNavInScroll();
	function backgroundForNavInScroll(){
		if ($('nav')[0]){
			if ($(window).width()>768){
				if ($('nav').offset().top > 20){
					$('nav').css('backgroundColor', 'var(--darkpurple)')
				}
				else {
					$('nav').css('backgroundColor', 'transparent')
				}
			}
		}
	}
	window.onscroll = backgroundForNavInScroll;

	// rectanglebottomWidth
	function rectanglebottomWidth(){
		if ($(window).width()>768){
			var windWidth = $(window).width(); //ширина документа
			var contWidth = $('.rectanglebottom').outerWidth(); //контейнер с падингами
			var offset = (windWidth - contWidth)/2; // отступ с бока
			var minwidth = contWidth+offset
			$('.rectanglebottom').css('min-width', minwidth+'px');
		}
	};
	rectanglebottomWidth();
	$(window).resize(function() {
		rectanglebottomWidth();
	});

	// menu scroll animation
	$("#menu .top-menu").on("click","a", function (event) {
		event.preventDefault();
		var id  = $(this).attr('href');
		if ($(id).offset()){
			var top = $(id).offset().top - 130;
			$('body,html').animate({scrollTop: top}, 300);
		}
	});

	// select like material design init
	$(".mad-select").each(function() {
		initMadSelector($(this));
	});
	$(document).on("mouseup", function(){
		if(!madSelectHover) $(".mad-select-drop").removeClass("show");
	});
	
	// custom multiselect
	jQuery(function($){
		var customSelectHover = 0;
		$(".custom-multiselect").each(function() {
			var $input = $(this).find("input[type=hidden]"),
				$ul = $(this).find("ul"),
				$search = $(this).find("input.search"),
				$result = $(this).find(".result");
			$ul.addClass("custom-multiselect-drop");
			$(this).on({
				hover : function() { customSelectHover ^= 1; },
				click : function() { $ul.addClass("show"); }
			});
			$ul.children('li').each(function(){
				$(this).attr('data-lower', $(this).text().toLowerCase());
			});
			$search.on("keyup", function(){
				var value = $(this).val();
				var selected = [];
				$result.find(".result-item").each(function(){
					selected.push($(this).data('value'));
				});
				if(value.length > 0){
					var lowerValue = value.toLowerCase();
					$ul.children('li').each(function(){
						if($(this).data('lower').slice(0, lowerValue.length) != lowerValue || selected.indexOf($(this).data('value')) != -1) {
							$(this).hide();
						}
						else {
							$(this).show();
						}
					});	
				}
				else {
					$ul.children('li').show();
				}
			});
			$ul.on("click", "li", function(evt) {
				evt.stopPropagation();
				$result.append('<span class="result-item" data-value="' + $(this).data('value') + '">' + $(this).text() + '<a href="" class="result-remove"></a></span>');
				$ul.children('li[data-value=' + $(this).data('value') + ']').hide();//hide selected
				var val = []; $result.find(".result-item").each(function(){val.push($(this).data('value'))}); $input.val(val.join(';'));//update hidden input
			});
			$result.on("click", ".result-remove", function(){
				$(this).closest(".result-item").remove();
				var val = []; $result.find(".result-item").each(function(){val.push($(this).data('value'))}); $input.val(val.join(';'));//update hidden input
				return false;
			});
		});
		$(document).on("mouseup", function(){
			if(!customSelectHover) $(".custom-multiselect-drop").removeClass("show");
		});
	})

	// radio button and lables
	updateRadioButtons();
	
	// program filters
	$('.program-filter-tab').click(function(){
		if($(this).hasClass('active')){
			$(this).removeClass('active');
			$('.program-filter-block#block-' + $(this).attr('data-block')).removeClass('visible');
		}
		else {
			$(this).addClass('active');
			$('.program-filter-block#block-' + $(this).attr('data-block')).addClass('visible');
		}
		return false;
	});
	$('#programs').on('click', '.program-filter-button', function(){
		if($(this).hasClass('active')){
			$(this).removeClass('active');
		}
		else {
			$(this).addClass('active');
		}
		var form = $(this).closest('form');
		$.each(['type','time','room'], function(index, value) {
			var values = [];
			form.find('.program-filter-' + value + '.active').each(function(){
				if($(this).attr('data-value')){
					values.push($(this).attr('data-value'));
				}
			});
			form.find('input[name=Filter-' + value + ']').val(values.join(';'));
		});
		form.find('input[name=LineLimit]').val(4);
		updateAjaxFormData(form);
		return false;
	});
	$('#programs').on('click', '.program-more', function(){
		var form = $(this).closest('form');
		var limitInput = form.find('input[name=LineLimit]');
		limitInput.val(parseInt(limitInput.val()) + 4);
		updateAjaxFormData(form);
		return false;
	});

	// univer slider
	$('.carousel-slick').slick({
		dots: true,
		infinite: false,
		slidesPerRow: 6,
		rows: 4,
		responsive: [{
              breakpoint: 1700,
              settings: {
            	  slidesPerRow: 4
              }
        },{
            breakpoint: 910,
            settings: {
          	  slidesPerRow: 3
            }
        }]
	});

	// клики по гамбургеру и ссылке
	$('span.hamburger').click(()=>$('.mobilemenu').toggleClass('d-flex'))
	$('.mobilemenu a').click(()=>$('.mobilemenu').toggleClass('d-flex'))

	//footer revers
	if ($(window).width()<768){
		$('div.footer p.blue').clone().addClass('col-12 myfooterclass').appendTo('div.footer .row');
		$($('div.footer p.blue')[0]).hide();
	}

	// city select
	if(!selectedCity){
		var citySelectDialog = $('#city-modal');
		if(detectedCity){
			var cityID = null;
			citySelectDialog.find('.mad-select li').each(function(){
				if($(this).text().toLowerCase() == detectedCity.toLowerCase()){
					cityID = $(this).attr('data-value');
				}
			});
			if(cityID != null){
				citySelectDialog.find('.mad-select input').val(cityID);
			}
		}
		citySelectDialog.modal('show');
	}
	$('#citySelectSubmit').click(function(){
		var link = $(this).closest('.modal-content').find('.mad-select li.selected').attr('data-link');
		document.location = link;
	});

	// countdown
	$('#countdown-landing').each(function() {
        var date = $(this).text();
        var target = moment.tz(date, "YYYY/MM/DD H:m:s", "Europe/Moscow");
         $(this).countdown(target.toDate(), function(event) {
             $(this).html(event.strftime(''
                 + '<div class="d-flex flex-column"><div>%D</div><div class="timegonename">Дней</div></div> '
                 + '<div class="d-flex flex-column blue">:</div> '
                 + '<div class="d-flex flex-column"><div>%H</div><div class="timegonename">Часов</div></div> '
                 + '<div class="d-flex flex-column blue">:</div> '
                 + '<div class="d-flex flex-column"><div>%M</div><div class="timegonename">Минут</div></div> '
                 + '<div class="d-flex flex-column blue">:</div> '
                 + '<div class="d-flex flex-column"><div>%S</div><div class="timegonename">Секунд</div></div>'));
         });
    });

	//custom check empty fields
	$(document).on('keyup', '.checkempty', function () {
    	if($(this).val() == ''){
    		$(this).removeClass('notempty');
    	}
    	else {
    		$(this).addClass('notempty');
    	}
    });
	
	// add registration user
	$('.registration-item-add').click(function(e) {
        e.preventDefault();
        var parent = $(this).parent().children('.registration-list');
        var i = parent.children('.registration-item').length;
        var block = parent.children('.registration-item:first').clone();
            
        block.find(':input[type=text]').val(null).each(function(){
        	$(this).removeClass('notempty');
        });
        block.find(':input[type=radio]').removeAttr('checked');
        block.find(':input').each(function(){
        	$(this).attr('name', $(this).attr('name').replace('[0]', '[' + i + ']'));
        });
        block.appendTo(parent);
        
        block.find('.form-errors').hide();
        block.find(".form--phone").mask(phone_mask);
        updateRadioButtons();
        var timeSelector = block.find(".mad-select");
        timeSelector.find('.material-icons').remove();
        initMadSelector(timeSelector);
    });

	// registration
	var waitingForRegistrationResponse = false;
	$('.exhibition-register').click(function(){
		if(waitingForRegistrationResponse) return false;
		waitingForRegistrationResponse = true;
		$('.loading').show();
		var form = $(this).closest('form');
		form.find('.form-errors').hide();
		$.ajax({
            url: form.attr('action'),
            type: 'post',
            data: form.serialize(),
            dataType: 'json',
            success: function (data) {
            	waitingForRegistrationResponse = false;
            	$('.loading').hide();
            	if (data && data.status) {
            		if(data.status === 'success'){
            			var url = document.location.href.split('?')[0];
            			document.location = url + "/" + data.nextPage + "/?ID=" + data.registrationID;
            		}
            		else {
            			$.each(data.formList, function(index, value) {
            				var errors = [];
                			$.each(value.ErrorList, function(index, value) {
                				errors.push(value.Message);
                			});
                			form.find('.registration-item').eq(index).find('.form-errors').html(errors.join('<br/>')).show();
            			});
            		}
                }
            }
        });
		return false;
	});

	thanksForReg();
	function thanksForReg(){
		if ($('.thanks-for-reg')[0]){
			var windWidth = $(window).width();
			var contWidth = $('.thanks-for-reg').outerWidth();
			var offset2 = (contWidth - $('.thanks-for-reg h1').outerWidth())/2;
			var offset = (windWidth - contWidth)/2;
			var newwidth = $('.thanks-for-reg h1').outerWidth() + offset2 + offset + 60;
			$('.blueunderline').outerWidth(newwidth);
			$('.blueunderline').css('left', offset);
		}
	}
	
	//registration additional
	$('#regfrom').on('click', '.program-filter-button', function(){
		if($(this).hasClass('active')){
			$(this).removeClass('active');
		}
		else {
			$(this).addClass('active');
		}
		var group = $(this).closest('.group');
		
		var values = [];
		group.find('.program-filter-type.active').each(function(){
			if($(this).attr('data-value')){
				values.push($(this).attr('data-value'));
			}
		});
		group.find('input.filter-type-value').val(values.join(';'));
		
		return false;
	});
})

function updateAjaxFormData(form) {
	delay(function(){
		$.ajax({
            url: form.attr('action'),
            type: 'post',
            data: form.serialize(),
            dataType: 'json',
            success: function (data) {
            	if (data && data.status && data.status === 'success' && data.html) {
                    form.find('.ajax-content').html(data.html);
                }
            }
        });
    }, 100);
}

function updateRadioButtons() {
	$('input[type=radio]').each(function(index){
		$(this).attr('id', 'radio-' + index);
		$(this).next('label').attr('for', 'radio-' + index);
	});
}

var madSelectHover = 0;
function initMadSelector(div) {
	var $input = div.find("input"),
	$ul = div.find("> ul"),
	$ulDrop =  $ul.clone().addClass("mad-select-drop");
	div.append('<i class="material-icons">arrow_drop_down</i>', $ulDrop)
		.on({
			hover : function() { madSelectHover ^= 1; },
			click : function() { $ulDrop.toggleClass("show"); }
	});
	$ul.add($ulDrop).find("li[data-value='"+ $input.val() +"']").addClass("selected");
	$ulDrop.on("click", "li", function(evt) {
		evt.stopPropagation();
		$input.val($(this).data("value")); // Update hidden input value
		$ul.find("li").eq($(this).index()).add(this).addClass("selected")
		.siblings("li").removeClass("selected");
	});
	$ul.on("click", function() {
		var liTop = $ulDrop.find("li.selected").position().top;
		$ulDrop.scrollTop(liTop + $ulDrop[0].scrollTop);
	});
}
