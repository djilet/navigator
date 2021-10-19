var chatConnection = null;
var chatConnectionLive = false;
$(document).ready(function () {
	if(typeof chatURL !== 'undefined' && chatGroupID){
		if('WebSocket' in window) {
			chatConnect();
            let timerId = 0;
			if(chatConnectionLive && chatStatus == 'moderator'){
                chatConnection.send(JSON.stringify({
                    action:'getLiveCount',
                }));
				timerId = setInterval(() =>
					chatConnection.send(JSON.stringify({
						action:'getLiveCount',
					})), 2000);
			}
			setInterval(function(){
				if(!chatConnectionLive){
					chatConnect();
				} if(timerId === 0 && chatStatus == 'moderator') {
                    timerId = setInterval(() =>
                        chatConnection.send(JSON.stringify({
                            action:'getLiveCount',
                        })), 2000);
                }
			}, 10000);
			
			var chatPane = $('.chat-pane');
			chatPane.show();
			if(chatPane.css('position') == 'absolute'){
				chatPane.parent().find('iframe').css('width', '67%');
			}
			chatPane.find('.chat-input-message').focus();
			chatPane.find('.chat-input-send').click(function(){
				if(chatConnectionLive){
					var msg = chatPane.find('.chat-input-message').val();
					chatConnection.send(JSON.stringify({
						action:'add',
						text: msg
					}));
					chatPane.find('.chat-input-message').val('');
				}
				return false;
			});
			chatPane.find('.chat-input-message').keypress(function(e){
				if (chatConnectionLive && e.keyCode == 13) {
					var msg = chatPane.find('.chat-input-message').val();
					chatConnection.send(JSON.stringify({
						action:'add',
						text: msg
					}));
					chatPane.find('.chat-input-message').val('');
					return false;
				}
			});
			chatPane.find('.chat-name-send').click(() => {
				if (chatUserID){
					renameHandler(chatPane);
				} else {
					enterHandler(chatPane);
				}
			});
			chatPane.find('.chat-input-name').keypress((e) => {
				if (chatConnectionLive && e.keyCode == 13) {
					if (chatUserID){
						renameHandler(chatPane);
					} else {
						enterHandler(chatPane);
					}
				}
			});
			$('.chat-input-rename').click(function (e) {
				e.preventDefault();
				showLoginInput();
			});
		} else {
			alert("Ваш браузер не поддерживает работу чата");
		}
	}
});

function enterHandler(chatPane){
	let name = chatPane.find('.chat-input-name').val();
	chatConnection.send(JSON.stringify({
		action: 'enterRequest',
		sessionId: device_id,
		name: name,
	}))

	showMessageInput();
	return false;
}

function renameHandler(chatPane){
	let name = chatPane.find('.chat-input-name').val();
	chatConnection.send(JSON.stringify({
		action: 'renameUserRequest',
		name: name,
	}))

	showMessageInput();
	return false;
}

function showMessageInput() {
	$('.chat-control.login').addClass('hidden');
	$('.chat-control').not('.login').removeClass('hidden');
}

function showLoginInput() {
	$('.chat-control.login').removeClass('hidden');
	$('.chat-control').not('.login').addClass('hidden');
}

function chatConnect(){
	chatConnection = new WebSocket(chatURL + '?GroupID=' + chatGroupID + '&UserID=' + chatUserID);
	chatConnection.onopen = function() {
		chatConnectionLive = true;
		chatOpen();
	}
	chatConnection.onclose = function() {
		chatConnectionLive = false;
	}
	chatConnection.onmessage = function(e){
		chatMessage(e.data);
	}
}

function chatOpen(){
	var chatPane = $('.chat-pane');
	var messagesPane = chatPane.find('.chat-messages');
	messagesPane.empty();
}

function chatMessage(data){
	var chatPane = $('.chat-pane');
	var messagesPane = chatPane.find('.chat-messages');
	var messages = JSON.parse(data);
	for(var i=0; i<messages.length; i++){
		if(messages[i].action == 'enterSuccess'){
			chatConnection.close();
			chatUserID = messages[i].chatUserId;
			chatConnect();
		}
		else if(messages[i].action == 'renameUser'){
			const messageIds = messages[i].messageIds;
			const newName = messages[i].name;
			for (let i = 0; i < messageIds.length; i++){
				chatPane.find(`.chat-message#message-${messageIds[i]} .user`).text(newName);
			}
		}
		else if(messages[i].action == 'remove'){
			chatPane.find('.chat-message#message-' + messages[i].id).remove();
		}
		else if(messages[i].action == 'info') {
			alert(messages[i].text);
		}
		else if(messages[i].action == 'liveCountInfo') {
			$('#chat-live-count').html(messages[i].liveCount);
		}
		else {
			var controlsDiv = $('<div class="actions"></div>');
			var messageDiv = $('<div class="chat-message" id="message-' + messages[i].id + '"><div class="content"><span class="user">' + messages[i].user + '</span> ' + messages[i].created.substring(11) + '</div>' + messages[i].text + '</div>');
			if(chatStatus == 'moderator'){
				var controlsDiv = $('<div class="actions"></div>');
				var remove = $('<a href="#" title="удалить сообщение">R</a>');
				remove.click(function(){
					var messageID = $(this).closest('.chat-message').attr('id').substring(8);
					chatConnection.send(JSON.stringify({
						action:'remove',
						messageID: messageID
					}));
					return false;
				});
				controlsDiv.append(remove);
				var ban1 = $('<a href="#" title="бан на 1 день">1D</a>');
				ban1.click(function(){
					var messageID = $(this).closest('.chat-message').attr('id').substring(8);
					chatConnection.send(JSON.stringify({
						action:'ban',
						time:'day',
						messageID: messageID
					}));
					return false;
				});
				controlsDiv.append(ban1);
				var ban2 = $('<a href="#" title="бан на неделю">7D</a>');
				ban2.click(function(){
					var messageID = $(this).closest('.chat-message').attr('id').substring(8);
					chatConnection.send(JSON.stringify({
						action:'ban',
						time:'week',
						messageID: messageID
					}));
					return false;
				});
				controlsDiv.append(ban2);
				var ban3 = $('<a href="#" title="бан навсегда">F</a>');
				ban3.click(function(){
					var messageID = $(this).closest('.chat-message').attr('id').substring(8);
					chatConnection.send(JSON.stringify({
						action:'ban',
						time:'forever',
						messageID: messageID
					}));
					return false;
				});
				controlsDiv.append(ban3);
			}

			messageDiv.append(controlsDiv);
			messagesPane.append(messageDiv);
			messagesPane.scrollTop(messagesPane.prop("scrollHeight"));
		}
	}
}