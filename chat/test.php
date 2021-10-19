<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta charset="utf-8">
		<title>Тестируем чат</title>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
		<script type="text/javascript">
		var chatGroupID = 3;
		var chatUserID = 9;
		
		$(document).ready(function () {
			if(chatGroupID && chatUserID){
				if('WebSocket' in window) {
					var connection = new WebSocket('ws://localhost:9502?GroupID=' + chatGroupID + '&UserID=' + chatUserID);
					connection.onopen = function() {
						//console.log('Connection open!');
						$('#messages').empty();
					}
					connection.onclose = function() {
						//console.log('Connection closed');
					}
					connection.onmessage = function(e){
						var messages = JSON.parse(e.data);
						for(var i=0; i<messages.length; i++){
							$('#messages').prepend('<div><div class="title">' + messages[i].user + ' (' + messages[i].created + ')</div><div class="text">' + messages[i].text + '</div></div>');
						}
					}

					$('#send').click(function(){
						var msg = $('#message').val();
						connection.send(msg);
					});
				} else {
					alert("Ваш браузер не поддерживает работу чата");
				}
			}
		});
		</script>
	</head>
	<body>
		<input type="text" id="message" /><input type="button" id="send" value="Отправить" />
		<div style="border: 1px solid grey; width: 500px; height: 400px; overflow: auto;">
			<div id="messages" style="width:100%; min-height:100%;">
			</div>
		</div>
	</body>
</html>