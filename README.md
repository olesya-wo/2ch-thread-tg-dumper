# 2ch-thread-tg-dumper
Дамп любого бесконечного треда из 2ch.hk в telegram-канал.

### Установка и настройка:
- Создать tg-канал
- Создать tg-бота
- Добавить бота в администраторы канала
- `git clone https://github.com/olesya-wo/2ch-thread-tg-dumper.git`
- `cd 2ch-thread-tg-dumper`
- `cp config.example.php config.php`
- В `config.php` задать имя доски, номер треда, токен бота и имя канала.
- `mkdir data`
- Создать файл `data/last_post_from_<THREAD_NUMBER>` и записать в него номер ОП-поста из треда или единицу
- Скопировать все полученные файлы на хостинг
- Настроить на хостинге cron-задачу на запуск `main.php` раз в несколько минут

### Перед коммитом очередных правок

```sh
php tests/tests.php run_tests compact
php php-cs-fixer-v3.phar fix ./ --config=cs_fixer_rule.php_cs --allow-risky=yes
```
