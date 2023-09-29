# messenger

This is a web messenger developed on the [Webman](https://www.workerman.net/webman) PHP-framework using WebSocket for messaging.

![Screenshot of messenger.](https://vi1.ru/imgDemo/messenger.png)

## Online Demo

user: demo/demo
  - [https://vi1.ru/messenger/](https://vi1.ru/messenger/)

## Install

Choose one of the two options.

1. Install from source

	```
	git clone https://github.com/aluxins/messenger
	composer install -d webman
	```
2. Install with Docker Compose
	```
	git clone https://github.com/aluxins/messenger
	docker-compose up -d
	docker exec -it messenger-php composer update
	docker restart messenger-php
	```

## SSL

If you want to use the WSS protocol for WebSocket, choose one of two options

1. Change the configuration config/app.php according to the documentation:
    - [https://www.workerman.net/doc/workerman/faq/ssl-support.html](https://www.workerman.net/doc/workerman/faq/ssl-support.html)


2. Using NGINX as a WebSocket Proxy. Change your nginx.conf according to the documentation:
    - [https://www.workerman.net/doc/webman/others/nginx-proxy.html](https://www.workerman.net/doc/webman/others/nginx-proxy.html)
    - [https://www.nginx.com/blog/websocket-nginx/](https://www.nginx.com/blog/websocket-nginx/)
