$.fn.liTranslit = function(options){
    // настройки по умолчанию
    var o = $.extend({
        elName: '#Title',        //Класс елемента с именем
        elAlias: '#StaticPath',        //Класс елемента с алиасом
        table: ''
    },options);
    return this.each(function(){
        var elName = $(this).find(o.elName),
            elAlias = $(this).find(o.elAlias),
            nameVal;
        function tr(el){
            nameVal = el.val();
            if(el.val()!=""){
            	validate(get_trans(nameVal));
            }
        };
        elName.focusout(function () {
        	//if(elAlias.val()==""){
        		tr($(this));
        	//}
        });    
        //tr(elName);
        function get_trans() {
            en_to_ru = {
                'а': 'a',
                'б': 'b',
                'в': 'v',
                'г': 'g',
                'д': 'd',
                'е': 'e',
                'ё': 'jo',
                'ж': 'zh',
                'з': 'z',
                'и': 'i',
                'й': 'j',
                'к': 'k',
                'л': 'l',
                'м': 'm',
                'н': 'n',
                'о': 'o',
                'п': 'p',
                'р': 'r',
                'с': 's',
                'т': 't',
                'у': 'u',
                'ф': 'f',
                'х': 'h',
                'ц': 'c',
                'ч': 'ch',
                'ш': 'sh',
                'щ': 'sch',
                'ъ': '',
                'ы': 'y',
                'ь': '',
                'э': 'je',
                'ю': 'ju',
                'я': 'ja',
                ' ': '-',
                'і': 'i',
                'ї': 'i',
                '!': '',
                '@': '',
                '#': '',
                '$': '',
                '%': '',
                '^': '',
                '&': '',
                '*': '',
                '(': '',
                ')': '',
                '"': '',
                '№': '',
                ';': '',
                '%': '',
                ':': '',
                '?': '',
                '\\': '',
                '/': '',
                '+': '',
                '-': '-',
                '_': '-',
                '`': '',
                '~': ''
                
            };
            nameVal = nameVal.toLowerCase();
            nameVal = trim(nameVal);
            nameVal = nameVal.split("");
            var trans = "";
            for (i = 0; i < nameVal.length; i++) {
                for (key in en_to_ru) {
                    val = en_to_ru[key];
                    if (key == nameVal[i]) {
                        trans += val;
                        break
                    } else if (key == "ї") {
                        trans += nameVal[i]
                    };
                };
            };
            return trans;
        }
        function inser_trans(result) {
        	elAlias.val(result);
        }
		function validate(staticpath){
			var newpath = "";
			$.ajax({
				url:ADMIN_PATH+"ajax.php",
		        type:"POST",
		        data:{
		      	  "Action":"ValidateStaticPath",
		      	  "StaticPath":staticpath,
		      	  "Table": o.table
		        },
		          dataType:"JSON",
		            success:function(data){
		            	if(typeof data.SessionExpired != 'undefined')
		          		{
		          			window.location.href = ADMIN_PATH+"index.php";
		          			return;
		          		}
		            	if(data && data.ValidStaticPath){
		          			inser_trans(data.ValidStaticPath);
		          		}else{
		          			alert('error');
		          		}
		            }
		    });
        }

        function trim(string) {
            string = string.replace(/'|"|<|>|\!|\||@|#|$|%|^|\^|\$|\\|\/|&|\*|\(|\)|=|-|\|\/|;|\+|№|,|\?|_|:|{|}|~|`|\[|\]/g, "");
            string = string.replace(/(^\s+)|(\s+$)/g, "");
            return string;
        };
    });
};