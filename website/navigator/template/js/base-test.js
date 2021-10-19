$(document).ready(function() {

//drag list
	if ($('.drag-list').length) {
      	$('.drag-list').sortable({
			axis: "y",
			containment: "parent",
			cursor: "move",
			/*handle: ".icon",*/
			revert: true,
			items: '.item',
			tolerance: "pointer",
			distance: 0,
			activate: function(event, ui) {
				$('.drag-list .item').removeClass('active');
				ui.item.addClass('active');
				itemInfoFunc(ui.item);
			},
			stop: function(event, ui){
				//updateDescriptionTopButton();
				itemInfoFunc(ui.item);
				/*var list = $('.description-list');
				list.removeClass('active');
				list.children('*').hide();
				list.children('.default').fadeIn();*/
			},
			sort: function(event, ui){
				var id = ui.item.children('.item-id').val();
				var item = $('.description-list.pointer').children('.item[data-id=' + id + ']');
				var list = item.parent('.description-list');

				if (!list.is(":hidden")) {
					list.css('top', ui.position.top);

					/*heigth = ($('#question').height() - item.closest('.description-wrap').height()) + list.height();
					item.closest('.description-wrap').css('margin-bottom', heigth);
					console.log($('#question').height());
					console.log(item.closest('.description-wrap').height());
					console.log(list.height());*/
				}
			}
		});
 		$('.drag-list').disableSelection();
 		sortByPosition($('.drag-list'));

 		//submit
		$('#drag-list-submit').click(function(event) {
			if ($(this).attr('disabled')) {
				return false;
			}
			var form = $(this).closest('form#question');
			var dragList = form.find('.drag-list');
			setOrderPosition(dragList);
			form.submit();
		});

		//hint
		if($('.base-test .hint').length){
			$('#drag-list-submit').attr('disabled', true);
			$('.drag-list').sortable('disable');
		}

		$('.base-test #close-hint-btn').click(function(event) {
			event.preventDefault();
			$(this).closest('.hint').fadeOut();
			$('#drag-list-submit').removeAttr('disabled');
			$('.drag-list').sortable('enable');
		});

		var itemInfoFunc = function(dragItem){
			var list = $('.description-list');
			var id = dragItem.children('.item-id').val();
			var item = list.children('.item[data-id=' + id + ']');

			list.addClass('active');

			if (!item.hasClass('active')) {
				list.children('.item').hide().removeClass('active');
				item.fadeIn().addClass('active');
			}

			$('.drag-list-wrap .numeric').children('.item').removeClass('active');
			$('.drag-list-wrap .numeric').children('.item[data-index=' + (dragItem.index() + 1) + ']').addClass('active');
		}
	}

//Description-list
	$('.description-list.top .navigate-btn').click(function(event) {
		event.preventDefault();
		let list = $('.drag-list');

		if ($(this).hasClass('next')) {
			item = list.children('.item.active').next('.item');
		}
		else if ($(this).hasClass('prev')){
			item = list.children('.item.active').prev('.item');
		}

		if (item.length < 1) {
			if ($(this).hasClass('next')) {
				item = list.children('.item').first();
			}
			else{
				item = list.children('.item').last();
			}
		}

		list.children('.item.active').removeClass('active');
		item.addClass('active');
		itemInfoFunc(item);
//
		updateDescriptionTopButton();
	});

	var updateDescriptionTopButton = function(){
		let list = $('.drag-list');
		let active = list.children('.item.active');

		if (active.length) {
			let count = $('.drag-list .item').length;
			let index = active.index('.drag-list .item') + 1;

			if (index === 1) {
				$('.description-list.top .navigate-btn.prev').hide();
			}
			else if(index === count){
				$('.description-list.top .navigate-btn.next').hide();
			}
			else{
				$('.description-list.top .navigate-btn:hidden').show();
			}
		}
	}

//category list
	$('body').on('click', '.information-list > .item:not(.active)', function(event) {
		let item = $(this);
		let btn = $(this).find('.more-info-btn');
		let feedbackBlock = $('#feedback-block');
		/*let films = item.find('.description.films');

		if (films.children('.content').length < 1) {
			$.ajax({
				url: dataAjaxPath,
				dataType: 'json',
				data: {
					Action: 'GetProfessionInfo',
					Fields: 'Films',
					ProfessionID: item.data('id'),
				},
			})
			.done(function(result) {
				films.append('<div class="content">' + result.Films + '</div>');
			})
			.fail(function(result) {
				//$('body').append(result.responseText);
			})
			.always(function() {
				//console.log("complete");
			});
		}*/

		if (feedbackBlock.data('viewed') == false) {
			feedbackBlock.insertAfter(item);
			feedbackBlock.data('viewed', true);
		}
		btn.toggleClass('active');
		item.toggleClass('active');
		item.find('.more-info').slideToggle();
	});

	$('body').on('click', '.information-list > .item.active .more-info-btn', function(event) {
		let btn = $(this);
		let item = $(this).closest('.item');

		btn.toggleClass('active');
		item.toggleClass('active');
		item.find('.more-info').slideToggle();
	});
	$('#load-more-profession').click(function(event) {
		event.preventDefault();
		var list = $(this).parents('.professions').find('.information-list');

		list.children('.item.hidden').slice(0, 25).removeClass('hidden');
		updateProfessionButton();
	});

//Profession filter
	$('#profession-filter-btn').click(function(event) {
		event.preventDefault();
		var form = $(this).parents('.filter').children('#professionFilterForm');
		form.slideToggle();
	});

	$('.base-test .checkbox-filter .checkbox a').click(function(event) {
		setTimeout(function() {
			updateFilterCounter();
		}, 10);
	});

	//TODO callback
	$("body").on('DOMSubtreeModified', ".professions .ajax-content", function() {
			if ($(this).children('.information-list').length) {
				updateProfessionButton();
			}
	});

//reset test
	$('#reset-test-btn').click(function(event){
		event.preventDefault();
		$('#modal-base-test').modal();
		return false;
	});

//show modal
	if($('.show-modal-dialog').length && $('#modal-supplement').length == 0){
		let selector = $('.show-modal-dialog').attr('data-modal');
		$(selector).modal('show');
	}

//Feedback form
	$('body').on('click', '.rating-list .item', function(event){
		var list = $(this).closest('.rating-list');
		list.children('.item').removeClass('active');
		list.children('.item').children('input').prop('checked', false);

		$(this).addClass('active');
		$(this).children('input').prop('checked', true);
		
		$(this).prevAll().addClass('active');

		updateOtherFeedbackForm($(this).parents('.feedback-from'));
	});

	//hover
	$('body').on('mouseenter', '.rating-list .item', function() {
		$(this).addClass('active');
		$(this).prevAll().addClass('active');
	});

	$('body').on('mouseleave', '.rating-list .item', function() {
		var checked = $('.rating-list .item').children('input:checked');

		if (checked.length) {
			$('.rating-list .item').removeClass('active');
			checked.parent('.item').addClass('active');
			checked.parent('.item').prevAll().addClass('active');
		}else{
			$(this).removeClass('active');
			$(this).prevAll().removeClass('active');
		}
	});

	//submit
	$('body').on('click', '#feedback-from-submit', function(event) {
		event.preventDefault();
		var form = $(this).closest('form');

		if (form.find('input[name=Rating]:checked').length) {
			var cbf = function(){
				//form.find('.content').children('.form-text').hide();
				//form.find('.content').children('.success-text').fadeIn();
				form.children('.feedback-from').addClass('success');
				updateOtherFeedbackForm(form.children('.feedback-from'));
			}
			sendAjaxForm(form, cbf);
		}
		else{
			alert('Поставьте оценку');
		}
	});

	//hide
	$('body').on('click', '.feedback-from-hide-btn', function(event) {
		event.preventDefault();
		form = $(this).closest('.feedback-from');
		form.hide();
		updateOtherFeedbackForm(form);
	});

	$('body').on('change', '.feedback-from textarea', function(event) {
		form = $(this).closest('.feedback-from');
		updateOtherFeedbackForm(form);
	});


//Load
	updateFilterCounter();
	updateProfessionButton();
});

function sendAjaxForm(form, callback){
	$.ajax({
		url: form.attr('action'),
		type: form.attr('method'),
		dataType: 'json',
		data: form.serialize(),
	})
	.done(function(result) {
		if (typeof callback === "function") {
			callback(result);
		}
		//console.log(result);
	})
	.fail(function(result) {
		$('body').append(result.responseText);
	})
	.always(function() {
		//console.log("complete");
	});
}

function updateProfessionButton(){
	button = $('#load-more-profession');
	var list = button.parents('.professions').find('.information-list');
	var count = list.children('.item.hidden').length;

	if (count > 0) {
		if (count > 25) {
			count = 25;
		}
		button.removeClass('hidden');
		button.html('Показать еше ' + count);
	}
	else{
		button.addClass('hidden');
	}
}

function updateFilterCounter(){
	var count = $('.base-test .checkbox-filter .checkbox.active').length;
	var counter = $('#profession-filter-btn').find('.count');

	if (count > 0) {
		counter.css('display', 'flex');
		counter.html(count);
	}
	else{
		counter.hide();
	}
}

function updateOtherFeedbackForm(form){
	contentForm = form.clone();
	$('.feedback-from').not(form).replaceWith(contentForm);
}