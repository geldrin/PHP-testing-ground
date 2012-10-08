
<?php

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

set_time_limit(0);

$iscommit = FALSE;

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Roles
// 1 = Előadó
// 2 = Műsorvezető
// 3 = Moderátor

$authors = array(
	// Vezetéknév, keresztnév, nénsorrend, pozíció, cég, recordingid, role
/*	array("Vörös", "Csilla", "straight", "ügyvezető", "Nielsen", 21, 1),
	array("Hann", "Endre", "straight", "ügyvezető igazgató", "Medián", 22, 1),
	array("Závecz", "Tibor", "straight", "véleménykutatási igazgató", "Ipsos", 23, 1),
	array("Kozák", "Ákos", "straight", "ügyvezető igazgató", "GfK", 24, 1),
	array("Gajdos", "Tamás", "straight", "műsorvezető", null, 25, 2),
	array("dr. László", "Géza", "straight", "igazgató", "MagyarBrands", 25, 1),
	array("dr. Serényi", "János", "straight", null, null, 26, 1),
	array("Forgács", "Péter", "straight", null, null, 26, 1),
	array("Kollár", "Csaba", "straight", "ügyfélkapcsolati igazgató", "Popart Reklámügynökség Kft.", 27, 1),
	array("Szántó", "Balázs", "straight", "ügyvezető partner", "Noguchi", 28, 1), 
	array("Gajdos", "Tamás", "straight", "műsorvezető", null, 29, 2),
	array("Ludvig", "Klára", "straight", "ügyvezető", "Jókenyér Pékség", 29, 1),
	array("Mészáros", "Gábor", "straight", null, "chokoMe Csokoládémanufaktúra", 29, 1),
	array("Schweickhardt", "András", "straight", "ügyvezető", "Prima Maroni Kft.", 29, 1),
	array("Neumann", "Péter", "straight", "társtulajdonos", "Kristálytorony", 29, 1),
	array("Hinora", "Bálint", "straight", null, "Hinora Marketing Group", 30, 1),
	array("Gajdos", "Tamás", "straight", "műsorvezető", null, 31, 2),
	array("Végvári", "Imre", "straight", "Networks képzés témavezető", "Kürt Akadémia", 32, 1),
	array("Kaizer", "Gábor", "straight", "társalapító", "ReVISION", 33, 1),
	array("Dr. Kádas", "Péter", "straight", "társalapító", "BrandVocat", 34, 1),
	array("Galiba", "Péter", "straight", null, "EPAM Systems", 35, 1),
	array("Guy", "Loury", "reverse", "társalapító", "CreativeSelector", 36, 1),
	array("Turcsán", "Tamás", "straight", "alapító", "indulj.be", 37, 1),
	array("Szabó", "György", "straight", "vezérigazgató", "Sanoma Media", 38, 1),
	array("Kolosi", "Péter", "straight", "programigazgató", "RTL Klub", 39, 1),
	array("Rényi", "Ádám", "straight", null, null, 40, 3),
	array("Mautner", "Zsófia", "straight", null, "Chili és Vanília blog", 40, 1),
	array("Farkas", "Lívia", "straight", null, "Urban:Eve", 40, 1),
	array("Panyi", "Szabolcs", "straight", null, "Véleményvezér", 40, 1),
	array("Nyári", "Krisztián", "straight", null, null, 40, 1),
	array("Varga", "Attila", "straight", null, "Comment.com", 40, 1),
	array("Varga", "Attila", "straight", null, "Hogyvolt blog", 40, 1),
	array("Prukner", "Brigitta", "straight", "produkciós vezető", "Viasat3", 41, 1),
	array("Herman", "Péter", "straight", null, "RTL Klub", 41, 1),
	array("Gonda", "Péter", "straight", "producer", "Q-Art Produkciós Iroda", 41, 1),
	array("Bátorfy", "Attila", "straight", "újságíró", "Kreatív", 42, 1),
	array("Bódis", "András", "straight", "szerkesztő újságíró", "Heti Válasz", 42, 1),
	array("Gavra", "Gábor", "straight", "főszerkesztő", "HVG Online", 42, 1),
	array("Vári", "György", "straight", "újságíró", "Magyar Narancs", 42, 1),
	array("Bodoki", "Tamás", "straight", "főszerkesztő", "atlatszo.hu", 42, 1),
	array("Murányi", "Marcell", "straight", "főszerkesztő", "Blikk", 42, 1),
	array("Kumin", "Ferenc", "straight", "nemzetközi kommunikációs vezető", "A Magyar Köztársaság Kormánya", 43, 1),
	array("Bruck", "Gábor", "straight", null, "Sawyer Miller Group", 44, 1),
	array("Vidus", "Gabriella", "straight", null, "R-time", 45, 1),
	array("Turi", "Árpád", "straight", null, "Class FM", 45, 1),
	array("Vaszily", "Miklós", "straight", "vezérigazgató", "Origo Média és Kommunikációs Szolgáltató Zrt.", 45, 1),
	array("Varga", "Attila", "straight", null, null, 46, 3),
	array("Kolosi", "Péter", "straight", null, null, 46, 1),
	array("Szöllősy", "Gábor", "straight", "programigazgató", "Viasat3", 46, 1),
	array("Rényi", "Ádám", "straight", null, null, 47, 3),
	array("Kalamár", "Tamás", "straight", "producer", "Barátok Közt", 47, 1),
	array("Illés", "Csaba", "straight", "főszerkesztő", "HOT Magazin", 47, 1),
	array("Kordos", "Szabolcs", "straight", "vezető szerkesztő", "Vasárnapi Blikk", 47, 1),
	array("Rényi", "Ádám", "straight", null, null, 48, 3),
	array("Papp", "Gábor", "straight", "lapigazgató", "Blikk Csoport", 48, 1),
	array("Guttengéber", "Csaba", "straight", "kereskedelmi igazgató", "Metropol", 48, 1),
	array("Pintye", "László", "straight", "igazgató", "Sanoma Media Magazin Divízió", 48, 1),
	array("Lengyel", "András", "straight", "hirdetési igazgató", "Axel Springer", 48, 1),
	array("Réz", "András", "straight", null, null, 49, 1),
	array("Rubin", "Kristóf", "straight", null, null, 49, 1),
	array("Kis", "Ervin Egon", "straight", "elnök", "SzEK.org", 50, 1),
	array("Arany", "János", "straight", null, "Google Magyarország", 51, 1),
	array("Körmendi", "Zsolt", "straight", "social media menedzser", "Shop.Builder Testépítő Webáruház", 52, 1),
	array("Nagy", "Róbert", "straight", "online marketing manager", "Shop.Builder Testépítő Webáruház", 52, 1),
	array("Deli", "Norbert", "straight", "divízió vezető", "HD Marketing", 53, 1),
	array("Urbán", "Zsolt", "straight", "vezérigazgató", "Full Market Hungary Zrt.", 54, 1),
	array("Olasz", "Ilona", "straight", null, "Procter & Gamble", 54, 1),
	array("Dr. Kiss", "Ferenc", "straight", "tudományos rektorhelyettes", "Budapesti Kommunikációs és Üzleti Főiskola", 55, 1),
	array("Bognár", "Ákos", "straight", "ügyvezető", "JátékNet.hu webáruház", 56, 1),
	array("Valner", "Szabolcs", "straight", "ügyvezető", "Digital Factory", 56, 1),
	array("Csányi", "Zoltán", "straight", "ügyvezető", "Harisnyadiszkont", 56, 1),
	array("Starcz", "Ákos", "straight", "vezérigazgató", "Shopline Webáruház NyRt.", 57, 1),
	array("Antal", "Ádám", "straight", "online marketing szakértő", "Book & Walk Kft.", 58, 1),
	array("Molnár", "Kinga", "straight", "szerkesztő", "Kreatív", 59, 3),
	array("Csurgó", "Balázs", "straight", null, "EuroRSCG", 59, 1),
	array("Klaba", "Márk", "straight", "végzett hallgató", "Budapesti Corvinus Egyetem", 59, 1),
	array("Gyémánt", "Balázs", "straight", "végzett hallgató", "Budapesti Kommunikációs és Üzleti Főiskola", 59, 1),
	array("Bacsa", "Gábor", "straight", "végzett hallgató", "BGF Kereskedelmi, Vendéglátóipari és Idegenforgalmi Főiskolai Kar", 59, 1),
	array("Palicz", "Péter", "straight", "végzett hallgató", "International Business School", 59, 1),
	array("Hajdu", "Zoltán", "straight", "fejvadász", "SmartStaff Kft.", 60, 1),
	array("Hajdu", "Zoltán", "straight", "fejvadász", "SmartStaff Kft.", 61, 1),
	array("Hajdu", "Zoltán", "straight", "fejvadász", "SmartStaff Kft.", 62, 3),
	array("Grátz", "Melinda", "straight", "HR manager", "LG Electronics", 62, 1),
	array("Bierbaum", "Gyöngyi", "straight", "HR manager", "Adidas", 62, 1),
	array("Kerékgyártó", "Laura", "straight", "HR manager", "Trilak PPG Architectural Coatings EMEA", 62, 1),
	array("Trefán", "Krisztina", "straight", "HR manager", "Arkon Zrt.", 62, 1),
	array("Hajdu", "Zoltán", "straight", "fejvadász", "SmartStaff Kft.", 63, 3),
	array("Ábrahám", "Gergely", "straight", "CEO", "Grayling Hungary", 63, 1),
	array("Kőszegi", "András", "straight", "ügyvezető", "BrandTrend Kommunikáció Kft.", 64, 2),
	array("Aczél", "László", "straight", "elnök", "Magyarországi Kommunikációs Ügynökségek Szövetsége", 65, 1),
	array("Ruppert", "Slade", "reverse", "regionális vezető", "PHD International", 66, 1),
	array("Johann", "Wachs", "reverse", "regional planning director", "Grey MCEA", 67, 1),
	array("Nagy", "László", "straight", "társ kreatív igazgató", "AGC Reklámügynökség", 68, 1),
	array("Tihanyi", "Péter", "straight", "kreatív igazgató", "AGC Reklámügynökség", 68, 1),
	array("Halas", "Attila", "straight", "stratégiai igazgató", "JWT Budapest Reklámügynökség", 69, 1),
	array("Faragó", "Tamás", "straight", "kreatív igazgató", "JWT Budapest Reklámügynökség", 69, 1),
	array("Havasi", "Zoltán", "straight", "stratégiai igazgató", "Kirowski Isobar", 70, 1),
	array("Jedlicska", "Márton", "straight", "társ kreatív igazgató", "Kirowski Isobar", 70, 1),
	array("Gaál", "Sarolta", "straight", "startégiai igazgató", "Y&R", 71, 1),
	array("Falvay", "László", "straight", "kreatív igazgató", "Y&R", 71, 1),
	array("Bram", "Westenbrink", "reverse", "marketing igazgató", "Heineken", 72, 1),
	array("Kovács", "Ildikó", "straight", "kommunikációs igazgató", "T-Csoport", 73, 1),
	array("Szelei", "Szabolcs", "straight", "marketing igazgató", "Google Magyarország", 74, 1),
	array("Vízkeleti", "Ildikó", "straight", "regionális marketing kommunikációs vezető", "Nissan CEE", 75, 1),
	array("Macher", "Szabolcs", "straight", null, null, 76, 3),
	array("Szemes", "Éva", "straight", null, null, 76, 1),
	array("Pénzes", "Anna", "straight", null, null, 76, 1),
	array("Pető-Dickinson", "Andrea", "straight", "search konzultáns", null, 76, 1),
	array("dr. Megyer", "Örs", "straight", null, null, 76, 1),
	array("Bátorfy", "Attila", "straight", "újságíró", "Kreatív", 77, 1),
	array("Csuday", "Gábor", "straight", null, "Kreatív", 77, 1),
	array("Werle", "Zoltán", "straight", "elnökségi tag", "Beszerzési Vezetők Klubja", 78, 1),
	array("Werle", "Zoltán", "straight", "beszerzési igazgató", "Magyar Állam Vasutak", 78, 1),
	array("Gábor", "Zsolt", "straight", "elnökségi tag", "Beszerzési Vezetők Klubja", 78, 1),
	array("Gábor", "Zsolt", "straight", "beszerzési igazgató", "Ericcson Magyarország", 78, 1),
	array("Giltán", "Tivadar", "straight", "elnökségi tag", "Beszerzési Vezetők Klubja", 78, 1),
	array("Giltán", "Tivadar", "straight", "beszerzési igazgató", "Allianz", 78, 1),
	array("Aczél", "László", "straight", "beszerzési igazgató", "Allianz", 79, 1),
	array("Kemendy", "Nándor", "straight", "elnök", "Beszerzési Vezetők Klubja", 79, 1),
	array("Gábor", "Zsolt", "straight", "elnökségi tag", "Beszerzési Vezetők Klubja", 79, 1),
	array("Száday", "Balázs", "straight", "stratégiai tervezés vezető", "Ogilvy", 80, 1),
	array("Sipos", "Dávid", "straight", null, "HPS", 81, 1),
	array("Bujtás", "Attila", "straight", "kreatív igazgató", "Rebel Rouse", 82, 1),
	array("Németh", "Béla", "straight", null, "Initiative Budapest Médiaügynökség", 83, 1),
	array("Gábor", "Iván", "straight", null, "Café Communications", 84, 1),
	array("Bujtás", "Attila", "straight", null, null, 85, 1),
	array("Száday", "Balázs", "straight", "stratégiai tervezés vezető", "Ogilvy", 85, 1),
	array("Sipos", "Dávid", "straight", null, "HPS", 85, 1),
	array("Németh", "Béla", "straight", null, "Initiative Budapest Médiaügynökség", 85, 1),
	array("Gábor", "Iván", "straight", null, "Café Communications", 85, 1),
	array("Werle", "Zoltán", "straight", "elnökségi tag", "Beszerzési Vezetők Klubja", 85, 1),
	array("Gábor", "Zsolt", "straight", "elnökségi tag", "Beszerzési Vezetők Klubja", 85, 1),
	array("Szabó", "Ákos", "straight", null, "Beszerzési Vezetők Klubja", 85, 1),
	array("Blaskó", "Nikolett", "straight", null, "ACG Reklámügynökség", 85, 3),
	array("Gideon", "Amichay", "reverse", "a zsűri elnöke", null, 86, 1),
	array("Szabó", "Béla", "straight", "márka és kommunikációs igazgató", "Vodafone", 87, 1),
	array("Kövesházi", "Dániel", "straight", "art director", null, 87, 1),
	array("Marosi", "Gergely", "straight", "copywriter", "Mito", 87, 1),
	array("Dr. Szeles", "Péter", "straight", "elnök", "Magyar Public Relations Szövetség", 88, 1),
	array("Dr. Szeles", "Péter", "straight", "docens", "Budapesti Gazdasági Főiskola", 88, 1),
	array("Varga", "Zsolt", "straight", "kutatási igazgató", "E-benchmark", 89, 1),
	array("Mayer", "Zsolt", "straight", null, "Mars Magyarország", 90, 1),
	array("Bakos", "Miklós", "straight", null, "Bakosa Kft.", 91, 1),
	array("Petrányi-Széll", "András", "straight", null, "PS:PRovocative", 92, 1),
	array("Dr. Rácz", "Gábor", "straight", "főosztályvezető", "Nemzeti Külgazdasági Hivatal Kommunikációs és Rendezvényszervezési Főosztály", 93, 1),
	array("Dr. Virányiné dr. Reichenbach", "Mónika", "straight", null, null, 94, 1),
	array("Dr. Szeles", "Péter", "straight", "docens", "Budapesti Gazdasági Főiskola", 95, 1),
	array("Rajnai-Adamecz", "Ildikó", "straight", "zsűritag", null, 95, 1),
	array("Mayer", "Zsolt", "straight", null, "Mars Magyarország", 95, 1),
	array("Nagy", "József", "straight", null, "Nemzeti Adó- és Vámhivatal", 95, 1),
	array("Gulyás", "Csaba", "straight", "párlatszakértő", null, 96, 1),
	array("Beke", "Zsuzsa", "straight",  "alelnök", "Magyar Reklámszövetség", 97, 1),
	array("Morva", "Gábor", "straight",  "alelnök", "Magyar Public Relations Szövetség", 97, 1),
	array("Kiss", "Katalin", "straight",  "kutatási igazgató", "Szinapszis Piackutató", 98, 1),
	array("Dr. Fazekas", "Ildikó", "straight", "elnök", "Európai Önszabályozó Reklám Szervezet", 99, 1),
	array("Dr. Lantos", "Zoltán", "straight", "igazgató", "GFK Global", 100, 1),
	array("Mészáros", "Zsolt", "straight", "igazgató", "ÉrtékTrend Consulting", 101, 1),
	array("Mihalszki", "Zsuzsa", "straight", "kommunikációs igazgató", "Kantar Media Hungary", 102, 1),
	array("Gyarmati", "Gábor", "straight", "igazgató", "Farmapromo", 103, 1),
	array("Varga-Tarr", "Sándor", "straight", "kommunikációs szakértő", "Egészségügyi Média Szövetség", 104, 1),
	array("Lukács", "Katalin", "straight", "ügyvezető", "H2Online", 105, 1),
	array("Zsédely", "Péter", "straight", null, "Sportsmarketing Hungary", 107, 1),
	array("Müller", "Tamás", "straight", "CEO", "GF Social Media", 108, 1),
	array("Müller", "Tamás", "straight", null, "Grundfoci.hu", 108, 1),
	array("Juhász", "Ádám", "straight", null, "Ustream", 108, 1),
	array("Radisics", "Milán", "straight", "ügyvezető", "Socialtimes.hu", 109, 1),
	array("Kálnoki Kis", "Attila", "straight", "sportszakmai szakértő", null, 110, 1),
	array("Túróczy", "Gábor", "straight", "vezető", "Webpont", 110, 1),
	array("Csanda", "Gergely", "straight", null, "Csapat.hu", 110, 1),
	array("Sztancsik", "Tamás", "straight", "kommunikációs és marketing referens", "Magyar Labdarúgó Szövetség", 111, 1),
	array("Sz. Nagy", "Tamás", "straight", "digitális tartalomfejlesztési vezető, felelős szerkesztő", "Nemzeti Sport Online", 112, 1),
	array("Bányász", "Árpád", "straight", "senior mobiltartalom manager", "Origo Média és Kommunikációs Szolgáltató Zrt.", 112, 1),
	array("Máté", "Pál", "straight", "főszerkesztő", "Sport TV", 112, 1),
	array("dr. Faragó", "András", "straight", "főszerkesztő", "Digisport", 112, 1),
	array("Sági", "Ferenc", "straight", "marketing kutató, tanácsadó", "NRC Kft.", 114, 1),
	array("Romsics", "Csilla", "straight", null, "Maxxon Kft.", 116, 1),
	array("Vaszary", "Ádám", "straight", null, "Adamsky Advertising Kft.", 117, 1),
	array("Fialka", "Krisztina", "straight", "média üzletág igazgató", "Hírek Média", 118, 1),
	array("Németh", "Kinga", "straight", "értekesítési igazgató", "Mood media Kft.", 119, 1),
	array("Romsics", "Csilla", "straight", null, "Maxxon Kft.", 120, 1),
	array("Vaszary", "Ádám", "straight", null, "Adamsky Advertising Kft.", 120, 1),
	array("Fialka", "Krisztina", "straight", "média üzletág igazgató", "Hírek Média", 120, 1),
	array("Németh", "Kinga", "straight", "értekesítési igazgató", "Mood media Kft.", 120, 1),
	array("Árpa", "Attila", "straight", null, null, 120, 1),
	array("Markovics", "Réka", "straight", "főtitkár", "Magyar Reklámszövetség", 121, 1),
	array("Hargitai", "Lilla", "straight", "stratégiai és kreatív igazgató", "Brand Avenue", 122, 1),
	array("Dr. Tamás", "Pál", "straight", "kutatóprofesszor", "Budapesti Corvinus Egyetem", 123, 1),
	array("Kaszás", "György", "straight", "kreatív direktor, tréner", "Upgrade Communications", 124, 1),
	array("Sas", "István", "straight", "címzetes főiskolai tanár, vezető", "Kommunikációs Akadémia", 125, 1),
	array("Geszti", "István", "straight", null, "Okego", 126, 1),
	array("Halázs", "Gyula", "straight", null, "HD Group", 126, 1),
	array("Papp-Váry", "Árpád", "straight", null, "Budapesti Kommunikációs és Üzleti Főiskola", 126, 1),
	array("Szabó", "Erik", "straight", null, "Carnation", 126, 1),
	array("Tímár", "János", "straight", null, "Eötvös Lóránd Tudományegyetem", 126, 1),
	array("Bátorfy", "Attila", "straight", null, null, 126, 3),
	array("Csermely", "Ákos", "straight", null, "Media Hungary", 127, 1),
	array("Ferling", "József", "straight", null, "Ferling PR", 127, 1),
	array("Hivatal", "Péter", "straight", null, "Metropol", 127, 1),
	array("Horváth", "Dávid", "straight", "Netminiszterelnök", null, 127, 1),
	array("Jakab", "György", "straight", null, "Oktatáskutató és Fejlesztő Intézet", 127, 1),
	array("Szabó", "György", "straight", null, null, 127, 1),
	array("R. Nagy", "András", "straight", null, "Próbakő", 127, 3),
	array("Salamon", "Gergő", "straight", null, "eastaste", 128, 1),
	array("Bíró", "Attila", "straight", null, "Mediator Group", 129, 1),
	array("Machán", "Frigyes", "straight", null, "Neston", 129, 1),
	array("Bihari", "Balázs", "straight", null, "Majdnem Híres Rocksuli", 130, 1),
	array("Kiss", "Kriszta", "straight", null, "Bravó", 130, 1),
	array("Kiss", "Kriszta", "straight", null, "Neon.hu", 130, 1),
	array("Inkei", "Bence", "straight", null, "Quart", 130, 1),
	array("Dankó", "Gábor", "straight", null, "Lángoló Gitárok", 130, 1),
	array("Mender", "Szilvia", "straight", null, "Soundkitchen", 130, 1),
	array("Zságer", "Balázs", "straight", "producer, zeneszerző", null, 131, 1),
	array("Dr. Tóth", "Péter Benjámin", "straight", "startégiai és kommunikációs igazgató", "Artisjus", 131, 1),
	array("Horváth", "Renátó", "straight", "producer, zeneszerző", "eastaste", 131, 1),
	array("Kozlov", "Sándor", "straight", "sajtóreferens", "A38", 132, 1),
	array("Vasák", "Benedek", "straight", "kommunikációs munkatárs", "A38", 132, 1),
	array("Kovács", "Ágnes", "straight", "Burn brandmanager", "Coca-Cola", 133, 1),
	array("Károly", "Zsuzsanna", "straight", "kommunikációs vezető", "E.ON", 133, 1),
	array("Korentsy", "Endre", "straight", "client service director", "HD Group", 133, 1),
	array("Hodik", "Tibor", "straight", "senior account manager", "Progressive", 133, 1),
	array("Lobenwein", "Norbert", "straight", "fesztiváligazgató", "VOLT és Heineken Balaton Sound", 133, 1),
	array("Dr. Németh", "Zoltán", "straight", null, "Prezimagyarul.hu", 134, 1),
	array("Farkas", "Vilmos", "straight", "kreatív igazgató", "Leo Burnett", 135, 1),
	array("Balatoni", "Emese", "straight", "ügyfélkapcsolati igazgató", "Adverticum", 136, 1),
	array("Torday", "Gábor", "straight", "kreatív igazgató", "TBWA", 137, 1),
	array("Baráth", "Károly", "straight", "ambient tagozat", "Magyar Reklámszövetség", 138, 1),
	array("Palotai", "Zoltán", "straight", "ügyvezető", "trnd", 139, 1),
	array("Novák", "Péter", "straight", "head of account department", "Lowe GGK", 140, 1),
	array("Kaizer", "Gábor", "straight", null, null, 141, 1),
	array("Vankó", "Csilla", "straight", "ügyfélkapcsolati igazgató", "Well", 142, 1),
	array("Varga", "Ákos", "straight", null, null, 150, 1),
	array("Jakopánecz", "Eszter", "straight", null, null, 143, 1),
	array("Sas", "István", "straight", null, null, 144, 1),
	array("Csizmadia", "Attila", "straight", null, "Magyar Reklámszövetség Digital Signage Tagozat", 145, 1),
	array("Szekeres", "Zoltán", "straight", "kereskedelmi igazgató", "Senso Media", 146, 1),
	array("Bauer", "Tamás", "straight", "ügyvezető igazgató", "Portland Közterületi Reklámügynökség Kft.", 147, 1),
	array("Strublik", "Sándor", "straight", "kereskedelmi igazgató", "Samsung Electronics", 148, 1),
	array("Szirtes", "János", "straight", "képzőművész, tanszékvezető egyetemi tanár", "Moholy-Nagy Művészeti Egyetem Média Design Tanszék", 149, 1),
	array("Dankó", "Zoltán", "straight", "üzletfejlesztési és értékesítési igazgató", "Factory Creative Studio", 151, 1),
	array("Góré", "Dániel", "straight", "digital producer", "Factory Creative Studio", 151, 1),
	array("Istvanovszky", "Zsanett", "straight", "ügyvezető", "Markcon", 152, 1),
	array("Kéri", "Gábor", "straight", null, "ThinkDigital", 153, 1),
	array("Nyakas", "Kitti", "straight", "HR", "Carnation Group", 154, 1),
	array("Molnár Szabó", "Zsuzsa", "straight", "fejvadász", "Randsrad Hungary", 154, 1),
	array("Ihász", "Ingrid", "straight", null, "CEMP Sales House", 155, 1),
	array("Balatoni", "Emese", "straight", null, null, 156, 1),
	array("Szabó", "Ákos", "straight", null, "IAB Hungary", 157, 1),
	array("Hubert", "Kornél", "straight", null, "Origo Média és Kommunikációs Szolgáltató Zrt.", 158, 1),
	array("Szépvölgyi", "Tamás", "straight", null, "Sanoma Media", 158, 1),
	array("Sziebig", "Péter", "straight", null, null, 159, 3),
	array("Barta", "Attila", "straight", null, "Infectious media Ltd.", 159, 1),
	array("Debre", "Zoltán", "straight", null, "Skillpages.com", 159, 1),
	array("Csendes", "Mátyás", "straight", null, "TUI", 159, 1),
	array("Szabó", "Erik", "straight", null, "Carnation Group", 160, 1),
	array("Dr. Tóth", "Péter Benjamin", "straight", null, null, 161, 1),
	array("Molnár", "Alexandra", "straight", "jogász", "Artisjus", 162, 1),
	array("Klicher", "Zoltán", "straight", "ügyvezető igazgató", "Stúdió 68 Reklámajándék Kft.", 163, 1),
	array("Dr. Fazekas", "Ildikó", "straight", "elnök", "Európai Önszabályozó Reklám Szervezet", 164, 1),
	array("Lakatos", "Zsófia", "straight", "ügyvezető", "Hill & Knowlton", 165, 1),
	array("Szabó", "Béla", "straight", "márka és kommunikációs igazgató", "Vodafone", 165, 1),
	array("Sipos", "Dávid", "straight", null, "HPS", 165, 1),
	array("Gosztonyi", "Csaba", "straight", "ügyvezető", "Carbon Group", 165, 1),
	array("Hajnal", "Tamás", "straight", "ügyvezető igazgató", "Human Telex", 166, 1),
	array("Mile", "Gabriella", "straight", "üzletfejlesztési vezető", "Nielsen", 167, 1),
	array("Dörnyei", "Otília", "straight", "ügyfélkapcsolati igazgató", "GfK", 168, 1),
	array("Ács", "Dóra", "straight", "főszerkesztő", "Élelmiszer", 169, 3),
	array("Kanyó", "Roland", "straight", "PR manager", "Drogerie Markt", 169, 1),
	array("Orcifalvi", "Tamás", "straight", "kommunikációs menedzser", "Tesco", 169, 1),
	array("Babocsay", "Ádám", "straight", "igazgató", "Millward Brown Firefly Kutatási Szolgáltatás", 169, 1),
	array("Tót-Csanádi", "Zita", "straight", "értékesítési- és marketing vezérigazgató helyettes", "Univer Product", 169, 1),

*/

	array("Hermann", "Irén", "straight", null, "Figyelő", 170, 3),
	array("Horváth", "János", "straight", null, null, 170, 1),
	array("Mark", "M. West", "reverse", "European operations director", "The Communicorp Group", 170, 1),
	array("Simó", "György", "straight", null, null, 170, 1),
	array("Hermann", "Irén", "straight", null, "Figyelő", 171, 3),
	array("Megyeri", "András", "straight", null, "TV2", 171, 1),
	array("Vándor", "Ágnes", "straight", null, "Proffesional Publishing", 171, 1),
	array("Steff", "József", "straight", null, "Sanoma Media", 171, 1),

	array("Kalmár", "Tibor", "straight", null, "RTL Klub", 172, 3),
	array("Pécsi", "Ferenc", "straight", null, "Médiablog", 172, 1),
	array("Szabó", "Zoltán", "straight", null, "Index", 172, 1),
	array("Dobó", "Mátyás (Doransky)", "straight", null, "Peer", 172, 1),

	array("Kalmár", "Tibor", "straight", null, "RTL Klub", 173, 3),
	array("Margaret Ann", "Dowling", "reverse", null, "Marquard Media", 173, 1),
	array("Kovalcsik", "Ildikó Lilu", "straight", null, "RTL Klub", 173, 1),
	array("Sabján", "Johanna", "straight", null, "Cosmopolitan", 173, 1),
	array("Rubin", "Kata", "straight", null, "Red Lemon", 173, 1),

	array("Szilágyi", "Miklós", "straight", null, "Vezetői Coaching", 174, 1),
	array("Kiss", "Ottó", "straight", null, "Braining", 174, 1),
	array("Braskó", "Csaba", "straight", null, "Szemléletfejlesztés.hu", 174, 1),
	array("Dobay", "Róbert", "straight", null, "Menedzsmentor", 174, 1),

	array("Rényi", "Ádám", "straight", null, null, 176, 3),
	array("Lakatos", "Zsófia", "straight", null, null, 176, 1),
	array("Rubin", "Kata", "straight", null, "Red Lemon", 176, 1),
	array("Ábrahám", "Gergely", "straight", null, null, 176, 1),
	array("Hampuk", "Richárd", "straight", null, "Person", 176, 1),

	array("Rényi", "Ádám", "straight", null, null, 177, 3),
	array("Szabó", "Zoltán", "straight", null, "Index", 177, 1),
	array("Csuday", "Gábor", "straight", null, "Kreatív", 177, 1),
	array("Simon", "Krisztián", "straight", null, "Marketing és Média", 177, 1),

);

// Establish database connection
try {
	$db = $app->bootstrap->getAdoDB();
} catch (exception $err) {
	// Send mail alert, sleep for 15 minutes
	echo "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err . "\n";
	exit -1;
}

foreach($authors as $key => $value) {

	echo "Processing record:\n";
	var_dump($authors[$key]);

	$c_name1 = $authors[$key][0];
	$c_name2 = $authors[$key][1];
	$c_format = $authors[$key][2];
	$c_job = $authors[$key][3];
	$c_org = $authors[$key][4];
	$rec_id = $authors[$key][5];
	$c_role = $authors[$key][6];

	$id_org = null;

	if ( !empty($c_org) ) $id_org = insert_org($c_org);
	echo "OrgID: " . $id_org . "\n";

	$id_cont = insert_cont($c_name1, $c_name2, $c_format, $id_org, $c_job, $c_role, $rec_id);

}

exit;

function insert_string_dbl($string_hu, $string_en) {
global $db, $iscommit; 

	// Insert HU string
	$query = "
		INSERT INTO
			strings (language, value, translationof)
		VALUES('hu', '" . $string_hu . "', NULL)";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
		exit -1;
	}

	$id_hu = $db->Insert_ID();
	echo "string insert id: " . $id_hu . "\n";

	// Update translationof field to itself
	$query = "
		UPDATE
			strings
		SET
			translationof = " . $id_hu . "
		WHERE
			id  = " . $id_hu;

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
		exit -1;
	}

	// Insert EN string
	$query = "
		INSERT INTO
			strings (language, value, translationof)
		VALUES('en', '" . $string_en . "', " . $id_hu . ")";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
		exit -1;
	}

	$id_en = $db->Insert_ID();
//	echo "string en insert id: " . $id_en . "\n";

	return $id_hu;
}

function insert_cont($c_name1, $c_name2, $nameformat, $id_org, $c_job, $c_role, $rec_id) {
global $db, $iscommit;

	if ( $nameformat == "straight" ) {
		$c_first = $c_name2;
		$c_last = $c_name1;
	} else {
		$c_first = $c_name1;
		$c_last = $c_name2;
	}

	// check if cont exists
	$query = "
		SELECT
			a.id,
			a.namefirst,
			a.namelast,
			b.id as jobid,
			b.contributorid,
			b.organizationid,
			b.jobgroupid,
			b.joboriginal,
			c.id as orgid,
			c.name as orgname
		FROM
			contributors as a
		LEFT OUTER JOIN
			contributors_jobs as b
		ON
			a.id = b.contributorid
		LEFT OUTER JOIN
			organizations as c
		ON
			b.organizationid = c.id
		WHERE
			a.namefirst LIKE '%" . $c_first . "%' AND
			a.namelast LIKE '%" . $c_last . "%'
		ORDER BY
			a.id,
			b.id
	";

//echo $query . "\n";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
		exit -1;
	}

	// Contributor exists?
	if ( $rs->RecordCount() >= 1 ) {
		echo "ERROR: már létezik a szerző? Válassz szerzőt, job és organization kombinációt az adatbázisból!\n";

		//List contribs and job groups
		$q = 1;
		$keys = array();
		$vals = array();
		$vals['cont'] = array();
		$vals['jobgrp'] = array();
		$vals['org'] = array();
		while ( !$rs->EOF ) {
			$cont = $rs->fields;
			array_push($keys, $q);
			array_push($vals['cont'], $cont['id']);
			array_push($vals['jobgrp'], $cont['jobgroupid']);
			array_push($vals['org'], $cont['organizationid']);
			echo "(" . $q . ") " . $cont['id'] . ", " . $cont['namelast'] . " " . $cont['namefirst'] . ", jobgrpid = " . $cont['jobgroupid'] . ", job = " . $cont['joboriginal'] . ", orgid = " . $cont['organizationid'] . ", org: " . $cont['orgname'] . "\n";
			$q++;
			$rs->MoveNext();
		}

		array_push($keys, "i");
		array_push($keys, "j");
		echo "...or (i)nsert new contrib or insert new (j)ob group?\n";
// Melyik szerzohoz???

		$key = read_key($keys);
		echo "\n";

var_dump($vals);

		// if not new contributor or job group, then use the selected job group
		if ( is_numeric($key) ) {

			$values = "";
			if ( empty($vals['org'][$key-1]) ) {
				$values .= "null, ";
			} else {
				$values .= $vals['org'][$key-1] . ", ";
			}

			$values .= $vals['cont'][$key-1] . ", " . $rec_id . ", ";

			if ( empty($vals['jobgrp'][$key-1]) ) {
				$values .= "null, ";
			} else {
				$values .= $vals['jobgrp'][$key-1] . ", ";
			}

			$values .= $c_role;

			$query = "
				INSERT INTO
					contributors_roles (organizationid, contributorid, recordingid, jobgroupid, roleid)
				VALUES(" . $values . ")
			";

echo $query . "\n";

			try {
				$rs = $db->Execute($query);
			} catch (exception $err) {
				echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
				exit -1;
			}

			return TRUE;
		}

		if ( $key == "j" ) {
			echo "ERROR: !!!!!!!!! SELECT AUTHOR !!!!!!!!!!!!!!!\n";
			exit -1;
		}

	}

	// Cannot find any record or (i) is chosen
	$date = date("Y-m-d H:i:s");

	// insert contributor
	$query = "
		INSERT INTO
			contributors (timestamp, namefirst, namelast, nameformat, organizationid, createdby)
		VALUES('" . $date . "', '" . $c_first . "', '" . $c_last . "', '" . $nameformat . "', 2, 14)";

//echo $query . "\n";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
		exit -1;
	}

	$id_cont = $db->Insert_ID();

	if ( empty($id_org) ) $id_org = "null";
	if ( empty($c_job) ) {
		$c_job = "null";
	} else {
		$c_job = "'" . $c_job . "'";
	}

	if ( ( $id_org != "null" ) or ( $c_job != "null" ) ) {

		// add job group
		$query = "
			INSERT INTO
				contributors_jobs (contributorid, organizationid, userid, jobgroupid, joboriginal)
			VALUES(" . $id_cont . ", " . $id_org . ", 14, 1, " . $c_job . ")
		";

		if ( !$iscommit ) echo $query . "\n";

		try {
			$rs = $db->Execute($query);
		} catch (exception $err) {
			echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
			exit -1;
		}

//		$id_jobid = $db->Insert_ID();
	}

	// add job group to recording
	$query = "
		INSERT INTO
			contributors_roles (organizationid, contributorid, recordingid, jobgroupid, roleid)
		VALUES(" . $id_org . ", " . $id_cont . ", " . $rec_id . ", 1, " . $c_role . ")
	";

	if ( !$iscommit ) echo $query . "\n";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
		exit -1;
	}

	return TRUE;
}

function insert_org($name) {
global $db, $iscommit;

	$recording = array();

	// check if org exists
	$query = "
		SELECT
			id,
			name
		FROM
			organizations
		WHERE
			name LIKE '%" . $name . "%'
	";

	if ( !$iscommit ) echo $query . "\n";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
		exit -1;
	}

	if ( $rs->RecordCount() >= 1 ) {
		echo "ERROR: már létezik az org?\n";

		$q = 1;
		$keys = array();
		$vals = array();
		while ( !$rs->EOF ) {
			$org = $rs->fields;
			echo "(" . $q . ") org = " . $org['id'] . ", " . $org['name'] . "\n";
			array_push($keys, $q);
			array_push($vals, $org['id']);
			$q++;
			$rs->MoveNext();
		}

		array_push($keys, "i");
		echo "...or (i)nsert record?\n";

		$key = read_key($keys);
		echo "\n";

		// if not insert then
		if ( $key != "i" ) return $vals[$key-1];
	}

	$id_org_name = insert_string_dbl($name, null);
	$id_org_short = insert_string_dbl(null, null);
	$id_org_intro = insert_string_dbl(null, null); 

	// Insert EN string
	$query = "
		INSERT INTO
			organizations (name, name_stringid, nameshort_stringid, introduction_stringid, languages)
		VALUES('" . $name . "', " . $id_org_name . ", " . $id_org_short . ", " . $id_org_intro . " , 'hu,en')";

	if ( !$iscommit ) echo $query . "\n";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
		exit -1;
	}

	$id_org = $db->Insert_ID();
//echo "idorg = " . $id_org . "\n";

	return $id_org;
}

function read_key($vals) {

	system("stty -icanon");
	echo "input# ";
	$inKey = "";
	while (!in_array($inKey, $vals)) {
		$inKey = fread(STDIN, 1);
	}

    return $inKey;
}

?>