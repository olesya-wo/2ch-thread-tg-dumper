@echo off
php php-cs-fixer-v3.phar fix ./ --config=cs_fixer_rule.php_cs --allow-risky=yes
