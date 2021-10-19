<?php


use Phinx\Seed\AbstractSeed;

class DataCitySeeder extends AbstractSeed
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
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (4, 'Барнаул', 'barnaul'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (4, 'Бийск', 'biysk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (58, 'Благовещенск', 'blagoveshhensk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (79, 'Архангельск', 'arhangelsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (23, 'Астрахань', 'astrahan'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (64, 'Белгород', 'belgorod'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (24, 'Брянск', 'bryansk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (24, 'Новозыбков', 'novozybkov'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (65, 'Владимир', 'vladimir'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (31, 'Волгоград', 'volgograd'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (31, 'Волжский', 'volzhskij'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (70, 'Вологда', 'vologda'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (70, 'Череповец', 'cherepovec'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (1, 'Воронеж', 'voronezh'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (73, 'Биробиджан', 'birobidzhan'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (10, 'Чита', 'chita'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (2, 'Иваново', 'ivanovo'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (2, 'Шуя', 'shuya'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (20, 'Братск', 'bratsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (20, 'Иркутск', 'irkutsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (12, 'Нальчик', 'nalchik'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (50, 'Калининград', 'kaliningrad'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (59, 'Калуга', 'kaluga'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (42, 'Петропавловск-Камчатский', 'petropavlovsk-kamchatskij'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (14, 'Карачаевск', 'karachaevsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (14, 'Черкесск', 'cherkesk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (5, 'Кемерово', 'kemerovo'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (5, 'Новокузнецк', 'novokuzneck'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (66, 'Киров', 'kirov'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (81, 'Кострома', 'kostroma'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (19, 'Армавир', 'armavir'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (19, 'Краснодар', 'krasnodar'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (19, 'Славянска-на-Кубани', 'slavyansk-na-kubani'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (19, 'Сочи', 'sochi'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (19, 'Туапсе', 'tuapse'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (36, 'Красноярск', 'krasnoyarsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (36, 'Норильск', 'norilsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (75, 'Курган', 'kurgan'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (75, 'Шадринск', 'shadrinsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (47, 'Курск', 'kursk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (38, 'Волхов', 'volhov'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (38, 'Выборг', 'vyborg'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (38, 'Сосновый Бор', 'sosnoviy-bor'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (52, 'Елец', 'elec'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (52, 'Липецк', 'lipeck'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (77, 'Магадан', 'magadan'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (15, 'Москва', 'msk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (48, 'Коломна', 'kolomna'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (48, 'Королев', 'korolyov'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (48, 'Красногорск', 'krasnogorsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (48, 'Пущино', 'pushchin'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (54, 'Мурманск', 'murmansk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (61, 'Арзамас', 'arzamas'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (61, 'Нижний Новгород', 'nn'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (78, 'Великий Новгород', 'velikiy-novgorod'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (40, 'Куйбышев', 'kujbyshev'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (40, 'Новосибирск', 'nsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (9, 'Омск', 'omsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (9, 'Тара', 'tara'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (56, 'Оренбург', 'orenburg'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (56, 'Орск', 'orsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (63, 'Орёл', 'orel'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (28, 'Пенза', 'penza'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (43, 'Пермь', 'perm'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (25, 'Владивосток', 'vladivostok'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (25, 'Уссурийск', 'ussurijsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (30, 'Псков', 'pskov'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (17, 'Майкопа', 'majkop'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (32, 'Горно-Алтайск', 'gorno-altaysk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (57, 'Бирск', 'birsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (57, 'Стерлитамак', 'sterlitamak'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (57, 'Уфа', 'ufa'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (51, 'Улан-Удэ', 'ulan-ude'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (6, 'Махачкала', 'mahachkala'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (26, 'Элиста', 'elista'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (53, 'Петрозаводск', 'petrozavodsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (60, 'Сыктывкар', 'syktyvkar'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (82, 'Симферополь', 'simferopol'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (82, 'Ялта', 'yalta'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (74, 'Йошкар-Ола', 'joshkar-ola'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (22, 'Саранск', 'saransk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (33, 'Нерюнгри', 'neryungri'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (33, 'Якутск', 'yakutsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (11, 'Владикавказ', 'vladikavkaz'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (45, 'Бугульма', 'bugulma'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (45, 'Елабуга', 'elabuga'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (45, 'Казань', 'kazan'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (45, 'Набережные Челны', 'naberezhnye-chelny'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (45, 'Нижнекамска', 'nizhnekamsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (49, 'Кызыл', 'kyzyl'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (62, 'Абакан', 'abakan'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (3, 'Новочеркасск', 'novocherkassk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (3, 'Ростов', 'rostov'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (3, 'Таганрог', 'taganrog'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (67, 'Рязань', 'ryazan'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (71, 'Новокуйбышевск', 'novokuybyshevsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (71, 'Самара', 'samara'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (71, 'Тольятти', 'tolyatti'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (13, 'Санкт-Петербург', 'spb'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (21, 'Балашов', 'balashov'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (21, 'Саратов', 'saratov'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (72, 'Южно-Сахалинск', 'yuzhno-sahalinsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (27, 'Екатеринбург', 'ekaterinburg'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (27, 'Нижний Тагил', 'nizhniy-tagil'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (133, 'Севастополя', 'sevastopol'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (18, 'Смоленск', 'smolensk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (29, 'Ессентуки', 'essentuki'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (29, 'Кисловодск', 'kislovodsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (29, 'Пятигорск', 'pyatigorsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (44, 'Ставрополь', 'stavropol'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (44, 'Тамбов', 'tambov'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (41, 'Тверь', 'tver'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (37, 'Томск', 'tomsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (69, 'Тула', 'tula'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (76, 'Ишим', 'ishim'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (76, 'Тобольск', 'tobolsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (68, 'Тюмень', 'tyumen'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (68, 'Ижевск', 'izhevsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (55, 'Ульяновск', 'ulyanovsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (16, 'Комсомольск-на-Амуре', 'komsomolsk-na-amure'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (16, 'Хабаровск', 'habarovsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (34, 'Нижневартовск', 'nizhnevartovsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (34, 'Сургут', 'surgut'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (34, 'Ханты-Мансийск', 'hanty-mansiysk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (46, 'Магнитогорск', 'magnitogorsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (46, 'Миасс', 'miass'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (46, 'Троицк', 'troick'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (46, 'Челябинск', 'chelyabinsk'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (7, 'Грозный', 'groznij-checnya'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (39, 'Чебоксары', 'cheboksary'); 
        INSERT INTO `data_city` (`RegionID`, `Title`, `StaticPath`) VALUES (35, 'Ярославль', 'yaroslavl'); 
";

        $this->execute($sql);
    }
}
