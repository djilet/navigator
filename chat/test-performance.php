<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta charset="utf-8">
		<title>Тестируем чат</title>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
		<script type="text/javascript">

		//live server
		var chatURL = "wss://propostuplenie.ru:9502";
		var chatGroupID = 124;
		var chatUserID = 1852;

		//local server
		//var chatURL = "ws://localhost:9503";
		//var chatGroupID = 125;
		//var chatUserID = 1852;
		
		$(document).ready(function () {
			$('#start').click(function(){
				$('#messages').empty();
				for(var i=1; i<200; i++){
					testOneConnection(i);
				}
			});

			function testOneConnection(testID){
				if(chatGroupID && chatUserID){
					if('WebSocket' in window) {
						logMessage(testID, 'create');
						var startTime = (new Date()).getTime();
						var connection = new WebSocket(chatURL + '?GroupID=' + chatGroupID + '&UserID=' + chatUserID);
						connection.onopen = function() {
							logMessage(testID, 'onopen - ' + ((new Date()).getTime() - startTime));
						}
						connection.onclose = function() {
							logMessage(testID, 'onclose - ' + ((new Date()).getTime() - startTime));
						}
						connection.onmessage = function(e){
							logMessage(testID, 'onmessage - ' + ((new Date()).getTime() - startTime));
						}
					} else {
						logMessage(testID, "Ваш браузер не поддерживает работу чата");
					}
				}
			}
			
			function logMessage(testID, message){
				$('#messages').prepend('<div><b>' + testID + '</b>: ' + message + '</div>');
			}
		});
		</script>
	</head>
	<body>
		<input type="button" id="start" value="Начать" />
		<div style="border: 1px solid grey; width: 500px; height: 400px; overflow: auto;">
			<div id="messages" style="width:100%; min-height:100%;">
			</div>
		</div>
	</body>
</html>