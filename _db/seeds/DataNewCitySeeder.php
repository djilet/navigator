<?php


use Phinx\Seed\AbstractSeed;

class DataNewCitySeeder extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     */
    public function run()
    {
        $sql = "
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 35, 'Рыбинск', 'rybinsk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 80, 'Ноябрьск', 'noyabrsk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 80, 'Салехард', 'salekhard');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 83, 'Анадырь', 'anadyr');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 46, 'Златоуст', 'zlatoust');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 55, 'Димитровград', 'dimitrovgrad');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 141, 'Ташкент', 'tashkent');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 68, 'Воткинск', 'votkinsk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 68, 'Сарапул', 'sarapul');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 69, 'Новомосковск', 'novomoskovsk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 37, 'Северск', 'seversk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 136, 'Душанбе', 'dushanbe');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 29, 'Невинномысск', 'nevinnomyssk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 27, 'Каменск-Уральский', 'kamensk-uralskij');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 21, 'Балаково', 'balakovo');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 21, 'Энгельс', 'ehngels');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 13, 'Пушкин', 'pushkin');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 71, 'Сызрань', 'syzran');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 3, 'Шахты', 'shahty');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 3, 'Ростов-на-Дону', 'rostov-na-donu');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 3, 'Новошахтинск', 'novoshahtinsk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 45, 'Альметьевск', 'almetevsk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 82, 'Керчь', 'kerch');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 82, 'Евпатория', 'evpatoriya');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 8, 'Назрань', 'nazran');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 8, 'Магас', 'magas');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 6, 'Дербент', 'derbent');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 6, 'Хасавюрт', 'hasavyurt');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 57, 'Нефтекамск', 'neftekamsk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 57, 'Октябрьский', 'oktyabrskij');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 30, 'Великие Луки', 'velikie-luki');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 25, 'Артём', 'artyom');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 25, 'Находка', 'nahodka');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 43, 'Чайковский', 'chajkovskij');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 135, 'Дубай', 'dubaj');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 61, 'Дзержинск', 'dzerzhinsk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 43, 'Березники', 'berezniki');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 48, 'Люберцы', 'lyubercy');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 48, 'Мытищи', 'mytishchi');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 48, 'Зеленоград', 'zelenograd');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 48, 'Жуковский', 'zhukovskij');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 48, 'Ступино', 'stupino');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 48, 'Электросталь', 'ehlektrostal');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 48, 'Пушкино', 'pushkino');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 48, 'Наро-Фоминск', 'naro-fominsk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 144, 'Улан-Батор', 'ulan-bator');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 38, 'Бокситогорск', 'boksitogorsk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 36, 'Ачинск', 'achinsk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 19, 'Новороссийск', 'novorossijsk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 19, 'Анапа', 'anapa');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 5, 'Прокопьевск', 'prokopevsk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 5, 'Междуреченск', 'mezhdurechensk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 5, 'Ленинск-Кузнецкий', 'leninsk-kuzneckij');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 59, 'Обнинск', 'obninsk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 142, 'Нур-Султан (Астана)', 'nur-sultan');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 142, 'Алматы', 'almaty');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 20, 'Ангарск', 'angarsk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 15, 'Сергиев Посад', 'sergiev-posad');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 15, 'Одинцово', 'odincovo');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 15, 'Химки', 'himki');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 15, 'Подольск', 'podolsk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 15, 'Домодедово', 'domodedovo');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 31, 'Камышин', 'kamyshin');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 65, 'Муром', 'murom');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 65, 'Ковров', 'kovrov');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 64, 'Старый Оскол', 'staryj-oskol');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 143, 'Минск', 'minsk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 79, 'Северодвинск', 'severodvinsk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 4, 'Рубцовск', 'rubcovsk');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 39, 'Алатырь', 'alatyr');
INSERT INTO `data_city` (`ID`, `RegionID`, `Title`, `StaticPath`) VALUES (NULL, 82, 'Крым', 'krym');
";

        $this->execute($sql);
    }
}
