## Створення віртуального сервера

1. Переходимо на [cloud.google.com](http://cloud.google.com/) (ви маєте бути залогіненими у обліковому запису Google). 
Обираємо вгорі "Get started for free"

![](http://atack.just-for-some.fun/images/readme/01.png)

2. Нас перекидує на наступну сторінку, яку заповнюємо ось так, (можливо ви не в гугл акаунті ще, тому вас попросять ще авторизуватись)

![](http://atack.just-for-some.fun/images/readme/02.png)

Можливо треба буде відкрити в новому вікні сині гіперссилки праворуч від галок, якщо це попросить гугл

3. Далі заповнюємо як показано на рисунку нижче, заповнюємо дані з карти (з вас спише та потім поверне 1 доллар, за поточним курсом десь 32грн).

![](http://atack.just-for-some.fun/images/readme/03.png)

На цьому кроці гугл може запросити додаткову перевірку. Наприклад може попросити документ де ваше імя співпадає зі вказаним імям на картці.

![](http://atack.just-for-some.fun/images/readme/04.png)

Жмемо “START MY FREE TRIAL” синя кнопка внизу.

4. Нас перекине на сторінку нижче. Далі обираємо зліва внизу пункт "Compute Engine" (виділено червоним)

![](http://atack.just-for-some.fun/images/readme/05.png)

Далі тицяєте а Enable

![](http://atack.just-for-some.fun/images/readme/06.png)

5. Обираємо пункт "Create Instance”

![](http://atack.just-for-some.fun/images/readme/07.png)

6. Створюємо виділений сервер, заповнюючи форму нижче як показано на рисунку

![](http://atack.just-for-some.fun/images/readme/08.png)

В Розділі **Boot Disk** нажимаємо кнопку "Change" та у вікні нижче обираємо операційну систему Ubuntu 20.04:

![](http://atack.just-for-some.fun/images/readme/09.png)

![](http://atack.just-for-some.fun/images/readme/10.png)

7. Після заповнення форми жмемо на синю кнопку "Create".

![](http://atack.just-for-some.fun/images/readme/11.png)

Далі Бачимо ось таку сторінку. Вітаю ви створили виділений сервер. Тепер потрібно зайти на нього та вже через цей сервер починати саме ддос атаку.  Далі обираємо стрілочку вниз біля SSH (обведено червоним), і обираємо 1ий пункт “Open in browser window”,

![](http://atack.just-for-some.fun/images/readme/12.png)

8. Браузер відкриє нове вікно, або дасть повідомлення що гугл хоче відкрити нове вікно - погоджуйтесь. Далі нове вікно буде виглядати ось так: Це командна строка вашого сервера, через це вікно будемо посилати команди на сервер:

![](http://atack.just-for-some.fun/images/readme/console-01.png)

На цьому етапі ви вже власник вашого першого бойового серверу але він пустий, наступним кроком ми його зарядім.

## Встановлення програмного забезпечення

**По черзі запускаємо команди в консолі, копіюйте команду, вставляйте в чорному вікні (Ctrl+V) і жмакайте Enter. Слідкуйте за вікном.**

`sudo apt-get update`

Ці команди встановлять оточення для того шо б можна було запустити прогу для ддос атаки. Слідкуйте за вікном, в процесі встановлення може попросити підтвердити Y/N, в цьому разі вікно буде чекати коли ви тицьнете Y і натиснете Enter

`sudo apt install php7.4-cli`

`sudo apt-get install php-curl`

`sudo apt install supervisor`

![](http://atack.just-for-some.fun/images/readme/console-02.png)

Встановлення ПЗ для атаки:

`git clone https://github.com/justforsome/atack`

`cd atack`

`chmod u+x supervisor/install.sh`

`sudo supervisor/install.sh`

![](http://atack.just-for-some.fun/images/readme/console-04.png)

На цьому встановлення завершене, **атака вже розпочалась**. Вікно консолі можна закривати.

Щоб переглянути статус, введіть наступну команду (переконайтесь, що ви знаходитесь у каталозі `atack`, при необхідності перейдіть у нього за допомогою `cd atack`):

`php adminer/adminer.php`

## **Крок 3. Клонуємо наш сервер**

Максимально кількість клонів буде обмежена лише вашим часом і бюджетом (після тріалу гугл запросить грошики)
Мінімально я б рекомендував налаштувати **не менше 10 клонів**

1. Клікаєте навпроти вашого першого сервера на три крапки і обираєте “Create new machine image”

![](http://atack.just-for-some.fun/images/readme/13.png)

Це створить шаблон вашого першого сервера з усіма налаштуваннями котрі ви на ньому зробили, відтак ви зможете в 1клік клонувати солдатиків і запускати атаки

Вводите будь-яку назву шаблону і тицяєте “Create”

![](http://atack.just-for-some.fun/images/readme/14.png)

Ви створили шаблон. Тепер по його образу і подобію зможете створювати армію клонів!

2. Тепер для того щоб створити клон із шаблона переходите в розділ “Machine images” і навпроти шаблона тицяєте три крапки і обираєте “Create instance”

![](http://atack.just-for-some.fun/images/readme/15.png)

![](http://atack.just-for-some.fun/images/readme/16.png)

Всьо, клон створений.

Тільки рекомендую кожен раз створювати клон в новій країні бо в гугла є ліміти на кількість серверів в одній країні (можливо це в тріалці так)

![](http://atack.just-for-some.fun/images/readme/17.png)

3. Запускаємо на новому клоні нову атаку

Знову вилізе чорне віконце і у ньому одразу запускаємо команду

`cd atack`

`supervisor/install.sh`

Якщо під час клонування отримали помилку
Operation type [insert] failed with message "Quota 'CPUS_ALL_REGIONS' exceeded. Limit: 12.0 globally.”

![](http://atack.just-for-some.fun/images/readme/19.png)

Створюйте новий проект і продовжуйте клонувати в нього

![](http://atack.just-for-some.fun/images/readme/20.png)
