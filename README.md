Запуск:
1. Скачать Docker.

2. Клонируем репозиторий:
	1) ssh - git@github.com:1clogon1/grokhotov_studio_test.git; 
	2) https - https://github.com/1clogon1/grokhotov_studio_test.git; 
	3) Скачать архив и распаковать его у себя.

3. Переходим в папку проекта в терминале(если не в ней находитесь): 
	cd .\grokhotov_studio_test\

4. Запускаем сборку и запуск контейнеров:          
	docker-compose up -d или make up

5. Подключаемся к контейнеру:
   docker exec -it grokhotov_studio_tests-gs_test-1 /bin/bash

6. Запускаем миграции:
   php bin/console make:migration
   php bin/console doctrine:migrations:migrate

7. Запускаем команды дял создание пользователя админ и подгрузки книг:
   Книги: php bin/console app:import-books
   Админ: php bin/console app:create-admin (логин: admin, пароль: adminpassword)

