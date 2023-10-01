# messenger

This is a web messenger developed on the [Webman](https://www.workerman.net/webman) PHP-framework using WebSocket for messaging.

![Screenshot of messenger.](https://vi1.ru/imgDemo/messenger.png)

## Online Demo

*user: demo/demo*
  - [https://vi1.ru/messenger/](https://vi1.ru/messenger/)

## Install

Choose one of the two options.

1. Install with Docker Compose
	```
	git clone https://github.com/aluxins/messenger
	cd messenger
	docker-compose up -d
	docker exec -it messenger-php composer update
	docker restart messenger-php
	```
    Open in your browser [http://<your_domain>/messenger/](http://<your_domain>/messenger/)


2. Install from source

	```
	git clone https://github.com/aluxins/messenger
	cd messenger/src
	composer install
	php start.php start -d
	```
    Open in your browser [http://localhost:2345/](http://<your_domain>/messenger/)

If you chose the second option, take care of importing the database *.docker/mysql/dump.sql* to your MySQL/MariaDB server.
And edit the database connection in the file *src/config/database.php*.

## docker-compose.yml
Environment Variables:
- SERVER_WS - WebSocket server, *ex. wss://<your_domain>/wss*. 
- SERVER_BASE - The base URL for *< base >* HTML element.

## SSL

If you want to use the WSS protocol for WebSocket, choose one of two options

1. Change the configuration config/app.php according to the documentation:
    - [https://www.workerman.net/doc/workerman/faq/ssl-support.html](https://www.workerman.net/doc/workerman/faq/ssl-support.html)

2. Using NGINX as a WebSocket Proxy. Change your nginx.conf according to the documentation:
    - [https://www.workerman.net/doc/webman/others/nginx-proxy.html](https://www.workerman.net/doc/webman/others/nginx-proxy.html)
    - [https://www.nginx.com/blog/websocket-nginx/](https://www.nginx.com/blog/websocket-nginx/)
