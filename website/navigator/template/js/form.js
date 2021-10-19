$(document).ready(function() {
	//change input
	$('.form.sign-in form input.form-control').not('[type="number"]').on('change keyup', function(event) {
		if ($(this).val().length > 0) {
			changeFormControlStatus($(this), 'success');
		}
		else{
			changeFormControlStatus($(this), 'error');
		}

		updateInteractiveForm($(this).closest('form'));
	});

	//after ajax validate
	$('.form.sign-in form').bind('hasError', function() {
		updateInteractiveForm($(this));
	});

	//Read later form
	$('.time-to-read form').submit(function (event) {
		event.preventDefault();
		let errorList = $(this).find('.error-list');
		AjaxRequester.send(
			$(this).attr('action'),
			$(this).serialize(),
			({status, errors}) => {
				$('.loading').hide();
				if (status === 'success'){
					$(this).html(`
					<h3>
						Статья успешно отправлена !
					</h3>
					<p>Если письмо не пришло, проверьте спам.</p>`);
				}
				else if (errors) {
					errorList.html('').fadeIn();
					for (let field in errors) {
						errorList.append(`${errors[field]} <br>`);
					}
				}
			},
			(response) => {
				$(this).html('<h3>Извините, произошла ошибка</h3>');
				$('.loading').hide();
			}
		);

		$('.loading').show();
	});

	if ($('.datetimepicker').length){
		var allowDatesTimes = $('.datetimepicker').attr('available-date-time').split(';');
		var dates = [];
		var times = [];

		allowDatesTimes.forEach(function(item, i, arr) {
			let dateTime = item.split(' ');
			dates.push(dateTime[0]);
			times.push(dateTime[1]);
		});

		var defaultDate = allowDatesTimes[0].split(' ')[0];
		var defaultTimes = allowDatesTimes[0].split(' ')[1].split(',');

		$('.datetimepicker').datetimepicker({
			dayOfWeekStart: 1,
			format: 'd/m/Y H:i',
			startDate: defaultDate,
			allowTimes: defaultTimes,
			allowDates: dates,
			timepicker: false,
			onSelectDate:function(currentDateTime){
				let date = moment(currentDateTime).format('YYYY/MM/DD');
				let i = dates.indexOf(date);
				this.setOptions({
					allowTimes: times[i].split(','),
					datepicker: false,
					timepicker: true,
				});
			},
			onSelectTime:function(){
				this.setOptions({
					datepicker: true,
					timepicker: false,
				});
			},
		});
	}
});

//submit form
$('.sing-in-form .submit-btn').on('click', function() {
	$(this).closest('form').submit();
});

//open modal
$('.sing-in-form').on('show.bs.modal', function() {
	updateInteractiveForm($(this).find('form'));
});

function updateInteractiveForm(form) {
	let full = 1;
	let half = 0;
	let modal = form.closest('.sing-in-form');
	let logo = form.closest('.sing-in-form').find('.interactive-logo');
	let submitBtn = form.find('.submit-btn');

	form.find('input.form-control').each(function(index, el) {
		if ($(this).prop('type') == 'checkbox') {
			if (!$(this).is(':checked')) {
				full = 0;
			}
		}

		if ($(this).is(':disabled') || $(this).val().length > 0 && !$(this).hasClass('error')) {

		}
		else{
			full = 0;
		}
	});

	if (modal.prop('id') == 'modal-signin' || modal.prop('id') == 'modal-restore') {
		half = 1;
	}

	if (full > 0) {
		logo.addClass('active');
		submitBtn.addClass('active');
	}
	else if(half > 0){
		logo.removeClass('active');
		submitBtn.removeClass('active');
		logo.addClass('half-active');
	}
	else{
		logo.removeClass('active');
		submitBtn.removeClass('active');
	}
}

function changeFormControlStatus(item, status) {
	if (status == 'success') {
		if (item.hasClass('error')) {
			item.removeClass('error');
			item.parent('.form-group').removeClass('error');
		}
	}
	else if(status == 'error'){
		if (item.hasClass('success')) {
			item.removeClass('success');
			item.parent('.form-group').removeClass('success');
		}
	}

	item.addClass(status);
	item.parent('.form-group').addClass(status);
}