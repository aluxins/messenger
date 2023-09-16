"use strict"; //Strict Mode

(function () {
    // ======== private vars ========
	let socket;

	//Объект, хранящий сообщения сеанса.
	const messages = {};
	
	//Объект, хранящий контакты сеанса.
	const contacts = {};

    const parameters = {
        
        //Контейнер мессенджера
        'containerMessenger' :   document.getElementById('container-messenger'),
        
        //Контейнер контактов и шаблон контакта
	    'containerContacts' :   document.getElementById('container-contacts'),
		'caseContact'       :   document.getElementById('case-contact'),
		    
	    //Контейнер сообщений и шаблоны сообщений
	    'containerMessage'  :   document.getElementById("container-messages"),
	    'caseMessageFrom'   :   document.getElementById('case-message-from'),
	    'caseMessageI'      :   document.getElementById('case-message-i'),
	    'caseMessageDefault':   document.getElementById('case-message-default'),

	    //Поле сообщения
	    'msgInput'          :   document.getElementById("sock-msg"),
	    
	    //Кнопка отправки сообщения
	    'msgButton'         :   document.getElementById("sock-send-butt"),	    

	    //Поле названия чата
	    'titleMessage'      :   document.getElementById('container-messages-name'),
	    
	    //Контейнер с полем отправки сообщений
	    'containerSender'   :   document.getElementById('container-sender'),
	    
        'disconnectButton'  :   document.getElementById("sock-disc-butt"),

        'connectButton'     :   document.getElementById("sock-recon-butt"),

        'statusAlert'       :   document.getElementById("status-alert"),
	    
	    //url сокета
	    'urlSocket'         :   window.urlSocket,
	    
	    //ID пользователя
	    'uId'               :   0,
	    
	    //ID собеседника
	    'fId'               :   0
    };
    
    let resize = function () {
        parameters.containerMessenger.style.width = document.documentElement.clientWidth + 'px';
        parameters.containerMessenger.style.height = document.documentElement.clientHeight + 'px';
    };
    
    //Инициализация событий элементов интерфейса 
    let init = function () {
        
        newConnection();
        
        //Обработчик поля textarea по нажатию на клавиатуре enter
        parameters.msgInput.addEventListener("keypress", function (e) {
            if(e.key === 'Enter') {
                //Отменяем фактический перенос строки
                e.preventDefault();

                messageSend();
                return false;
            }
        }, false);
        
        //Обработчик кнопки send
        parameters.msgButton.addEventListener("click", messageSend, false);

        //Обработчики кнопок connect и disconnect
        parameters.disconnectButton.addEventListener("click", connectionClose, false);
        parameters.connectButton.addEventListener("click", newConnection, false);
        
        //Привязываем обработчик к контейнеру с контактами, позволяющий переключаться между контактами
        parameters.containerContacts.addEventListener("click", ({ target }) => { 
			if(target.closest('li')){
			    selectContact(target.closest('li').id);
			} 
		});
        
        parameters.containerMessage.innerHTML = parameters.caseMessageDefault.innerHTML;
    };

    //Переключение между контактами
    function selectContact(id){
        let el = document.getElementById(id),
            cl = el.dataset.active.split(' '),
            us = Number(el.dataset.user),
            old = document.getElementById('case-contact-'+parameters.fId);
            
        if(us !== parameters.fId){
            //Определяем ID собеседника
            parameters.fId = us;
            
            //Запрос архива сообщений
            if(!contacts[parameters.fId].archive){
                archiveGet(parameters.fId);
                contacts[parameters.fId].archive = true;
            }

            //Добавляем фон активному контакту
            cl.forEach((element) => el.classList.add(element));
           
            //Удаляем фон у предыдущего активного контакта
            cl.forEach((element) => { if(old) old.classList.remove(element) });
            
            //Делаем отчистку контейнера сообщений
            parameters.containerMessage.innerHTML = '';
            parameters.containerSender.classList.remove('d-none');
            
            //Заголовок контейнера сообщений
            parameters.titleMessage.innerText = parameters.titleMessage.dataset.text + contacts[parameters.fId].name;
    
            //И производим рендер сообщений из объекта messages.
    	    for(let key in messages[parameters.fId]){
                messageRender(parameters.fId, key, false, 'auto');
            }
        }
    }
    
    //Заполняем данные в слои
	function divSet(id, text){
	    if (document.getElementById(id)){
	        let el = document.getElementById(id);
	        switch (el.nodeName) {
                case 'IMG':
                    el.src = 'upload/'+text;
                    break;
                default:
                    el.innerText = text;
            }
	    }
	}
	
	//Удаление элемента
	function divRemove(id){
        let node = document.getElementById(id);
        if (node && node.parentNode)
            node.parentNode.removeChild(node);
	}

    //Запрос архива сообщений
	function archiveGet(id) {
        socket.send(jsonMessage(id, '0', 'archive'));
	}
		
    //Отправка сообщения
    function messageSend(){
        if(parameters.msgInput.value.length > 0){
            //Выполняем отправку сообщения
        	socket.send(jsonMessage(parameters.fId, null));
    		//Стираем поле ввода
            parameters.msgInput.value = '';
        }
    }
    
    //Добавляем новые сообщения, полученные от websocket, в объекты messages и contacts и производим их рендер
    //Обрабатываем системные сообщения
    function messageAdd(data){
        let data_obj = JSON.parse(data);
        //Устанавливаем ID пользователя
        if(parameters.uId === 0)
            parameters.uId = data_obj.uId;

        switch (data_obj.type) {
            
            //Новые сообщения
            case 'msg':
                let id = data_obj.uId === parameters.uId ? data_obj.fId : data_obj.uId;
                
                if (!(id in messages)) messages[id] = {};
        		messages[id][data_obj.time] = data_obj;
        		divSet('case-contact-lastmess-'+id, data_obj.msg);
        		divSet('case-contact-status-'+id, convertTimestamp(data_obj.time,'time'));

                //Если открыт диалог с отправителем сообщения, производим рендер сообщения.
        		if(id === parameters.fId)
                    messageRender(id, data_obj.time);

        		//Если закрыт диалог - показываем уведомление о новых сообщениях.
                else if(parameters.uId !== data_obj.uId){
                    let numb = document.getElementById('case-contact-unreadmess-'+id).innerText === '' ?
                        0 : Number(document.getElementById('case-contact-unreadmess-'+id).innerText);
                    divSet('case-contact-unreadmess-'+id, numb + 1);
                    
                    let bold = document.getElementById('case-contact-lastmess-'+id);
                    if(!bold.classList.contains('fw-bold'))
                        bold.classList.add('fw-bold');
                }
                break;
        
            //Список контактов
            case 'contacts':
                contactsRender(data_obj.msg['contacts']);
                break;
                
            //Удаление контакта
            case 'delete':
                if(data_obj.msg['delete'] in contacts)
                    delete contacts[data_obj.msg['delete']];
                if(data_obj.msg['delete'] === parameters.fId){
                    //Делаем обнуление диалога
                    parameters.fId = 0;
                    parameters.containerMessage.innerHTML = parameters.caseMessageDefault.innerHTML;
                    parameters.titleMessage.innerText = '';
                    parameters.containerSender.classList.add('d-none');
                }
                divRemove('case-contact-'+data_obj.msg['delete']);
                break;
                
            //Архив сообщений
            case 'archive':
                if (!(data_obj.fId in messages)) messages[data_obj.fId] = {};
        		
                for(let key in data_obj.msg.archive){
                    if(!messages[data_obj.fId][data_obj.msg.archive[key].time]){
                        messages[data_obj.fId][data_obj.msg.archive[key].time] = data_obj.msg.archive[key];
                        if(data_obj.fId === parameters.fId)
                            messageRender(data_obj.fId, data_obj.msg.archive[key].time, true, 'auto');
                    }
                }
                
                //Сортировка массива сообщений в хронологической последовательности
                messages[data_obj.fId] = sortObject(messages[data_obj.fId]);
                break;
        }
	}
	
	//Рендер сообщений в чат
	function messageRender(id, time, reverse = false, behavior = 'smooth'){
	    if(id > 0){
	        let case_message = messages[id][time].uId === parameters.uId ? parameters.caseMessageI : parameters.caseMessageFrom;
	        if(reverse)
	            parameters.containerMessage.innerHTML = case_message.innerHTML.replaceAll('%ID%', id+'-'+time) 
	                + parameters.containerMessage.innerHTML;
	        else
    		    parameters.containerMessage.innerHTML += case_message.innerHTML.replaceAll('%ID%', id+'-'+time);
    		    
    		for(let key in messages[id][time]) {
    		    let div_value = messages[id][time][key];
    		    if(key === 'time')
    		        div_value = convertTimestamp(div_value, '');
    	        divSet('case-message-'+key+'-'+id+'-'+time, div_value);   
    	    }

    	    divSet('case-message-name-'+id+'-'+time, contacts[messages[id][time].uId]['name']);
            divSet('case-message-avatar-'+id+'-'+time, contacts[messages[id][time].uId]['avatar']);
            divSet('case-contact-unreadmess-'+id, '');
            let bold = document.getElementById('case-contact-lastmess-'+id);
            if(bold && bold.classList.contains('fw-bold'))
                bold.classList.remove('fw-bold');
            
            //Скролл контейнера с сообщениями. При добавлении архивных сообщений behavior = auto, при новых сообщениях behavior = smooth
            parameters.containerMessage.scrollTo({top: parameters.containerMessage.scrollHeight, behavior: behavior});
	    }
	}
	
    //Рендер контактов
    function contactsRender(data_contacts){
		for(let key in data_contacts) {
    		if(!(key in contacts)){
                key = Number(key);
    		    contacts[key] = data_contacts[key];
    		    if(key !== parameters.uId){
        		    parameters.containerContacts.innerHTML += parameters.caseContact.innerHTML.replaceAll('%ID%', key.toString());
        		    data_contacts[key].archive = false;
        		    for(let key_c in data_contacts[key]){
        		        let div_value = data_contacts[key][key_c];
        		        if(key_c === 'status')
        		            div_value = convertTimestamp(div_value, '');
        		        divSet('case-contact-'+key_c+'-'+key, div_value);
        		    }
        		}
        		else{
        		    for(let key_c in data_contacts[key])
        		        divSet('user-'+key_c, data_contacts[key][key_c]);  
        		}
    		}
		}
    }
	
	//Формирование строки в представлении json для отправки на сервер
	function jsonMessage(fId, msg, type = 'msg') {
		const data = {
			msg: msg ? msg : parameters.msgInput.value,
			type: type,
			fId: fId
		};
		return JSON.stringify(data);
	}

    //Функция, выполняющая соединение с веб-сокетом
    function newConnection(){
        //Открываем веб-сокет и привязываем к событиям функции
        socket = new WebSocket('ws://' + parameters.urlSocket);
		socket.onopen = connectionOpen; 
		socket.onmessage = messageReceived; 
		socket.onerror = errorOccurred; 
		socket.onclose = connectionClose;
    }
    
    //Функция вызывается при открытии соединения с сервером
	function connectionOpen() {
        socket.send(jsonMessage(0, '0', 'start'));
        parameters.statusAlert.textContent = 'Соединение установлено: ' +
            convertTimestamp(Date.now() / 1000, '');
	}

    //Функция вызывается при поступлении данных с сервера
	function messageReceived(e) {
        console.log("Ответ сервера: " + e.data);
        messageAdd(e.data);
	}
	
    //Функция вызывается в случае ошибки
	function errorOccurred() {
        parameters.statusAlert.textContent = '<span class="text-danger">Ошибка веб-сокета!:</span> ' +
            convertTimestamp(Date.now() / 1000, '');
        console.log('Ошибка веб-сокета!');
	}

    //Функция вызывается при закрытии соединения
    function connectionClose() {
        socket.close();
        for (let member in contacts) delete contacts[member];
        for (let member in messages) delete messages[member];
        parameters.fId = 0;
        parameters.containerContacts.innerHTML = '';
        parameters.containerMessage.innerHTML = '';
        parameters.titleMessage.innerHTML = '';
        parameters.containerSender.classList.add('d-none');
        parameters.statusAlert.innerHTML = '<span class="text-danger">Соединение разорвано:</span> ' +
            convertTimestamp(Date.now() / 1000, '');
    }
    
    //Сортировка
    function sortObject(obj) {
        return Object.keys(obj).sort().reduce(function (result, key) {
            result[key] = obj[key];
            return result;
        }, {});
    }
    
    //Конвертация unixtime
    function convertTimestamp(timestamp, type) {
        let d = new Date(timestamp * 1000),
            yyyy = d.getFullYear(),
            mm = ('0' + (d.getMonth() + 1)).slice(-2),
            dd = ('0' + d.getDate()).slice(-2),
            h = ('0' + d.getHours()).slice(-2),
            min = ('0' + d.getMinutes()).slice(-2),
            time;
        
        switch (type) {
          case 'time':
            time = h + ':' + min;
            break;
          case 'date':
            time = dd + '.' + mm + '.' + yyyy;
            break;
          default:
            time = dd + '.' + mm + '.' + yyyy.toString().slice(2) + ' ' + h + ':' + min;
        }
    return time;
    }


    return {
        // ---- onload event ----
        load : function () {
            window.addEventListener('DOMContentLoaded', function () {
                init();
                resize();
            }, false);
            
            window.addEventListener('resize', function() {
                resize();
            }, true);
        }
    };
})().load();