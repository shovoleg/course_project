# Быстрый запуск на другом Synology

DSM 7, Container Manager уже установлен. База создаётся сама, данные лежат в томе Docker `db-data`, не на общей папке.

После распаковки в папке должны лежать рядом `docker-compose.yaml`, папка `app` и этот файл. Внутри `app` уже есть `vendor` и `assets/vendor`.

## 1. Положить файлы

1. File Station → `docker` → создайте папку `course_project`. Обычно это `/volume1/docker/course_project`.
2. Загрузите туда `course_project.zip`.
3. Правый клик по архиву → Извлечь → извлечь сюда.
4. Если появилась вложенная папка `course_project/course_project`, перенесите `docker-compose.yaml` и `app` на уровень выше. Рядом с ними не должно быть второй папки `app`.
5. Включите показ скрытых файлов. В `app` должен быть файл `.env`.

## 2. Запустить

1. Container Manager → Проект → Создать.
2. Имя проекта: `course_project`.
3. Путь: папка из шага 1.
4. Источник: существующий `docker-compose.yaml`.
5. Далее → Готово. Первый запуск скачивает образы `webdevops/php-nginx-dev:8.4` и `mysql:8.4.4`. Дождитесь, пока оба контейнера станут запущенными.

Порт сайта на NAS: `8010`. Порт базы `3306` тоже открыт на самом NAS. На роутере наружу не пробрасывайте `3306`, `8010`, `5000` и `5001`.

## 3. Создать таблицы

Container Manager → Контейнер → `course_project-app-1` → Действие → Открыть терминал:

```sh
mkdir -p /app/var/cache /app/var/log
php bin/console doctrine:migrations:migrate --no-interaction
chown -R application:application /app/var
```

`composer install` не нужен. Если сайт пишет, что не найден `vendor/autoload.php`, тогда в том же терминале выполните `composer install --no-interaction` и повторите две команды выше.

## 4. Открыть

`http://IP-ЭТОГО-NAS:8010`

Проверка: `http://IP-ЭТОГО-NAS:8010/health` отвечает `ok`.

Администратор: `admin@example.com` / `admin12345`.

Google, GitHub, Cloudinary и HTTPS в этот архив не входят. Ключи пустые, сайт без них открывается. Как их добавить, написано в `zapusk-na-synology.md`.
