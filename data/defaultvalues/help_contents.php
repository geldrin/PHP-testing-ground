<?php
return array (
  0 => 
  array (
    'title' => 'Feltöltés súgó',
    'titleen' => 'Upload help',
    'shortname' => 'recordings_upload',
    'title_stringid' => 0,
    'body' => '<p>Elsőként válassza ki a felvétel eredeti nyelvét!</p><p>Az <strong>"Állományok kiválasztása"</strong> gombra kattintva válassza ki a feltölteni kívánt média állomány(oka)t!</p><p>Kérjük, figyelmesen olvassa el a <strong>"Felhasználási feltételek"</strong> oldalt, majd amennyiben elfogadja tartalmát, hagyja jóvá a dokumentumot!</p><p>A feltölteni kívánt média fájlokat megjelölheti <strong>intro/outro</strong>ként. Az ilyen videókat a későbbiekben felvételekhez rendelheti, az intro a felvétel előtt, az outro pedig a felvétel után kerül majd automatikus lejátszásra.</p><p>A feltöltést a <strong>"Feltöltés kezdése"</strong> gomb megnyomásával indíthatja el.</p><p>Amennyiben rendelkezik <strong>HTML5</strong> böngészővel, használja a "fogd és vidd" (drag and drop) módszert! A fájlkezelő alkalmazásban válassza ki a feltölteni kívánt állományokat, majd dobja őket erre a lapra! Modern, HTML5 képes böngésző esetén egyszerre több állományt is feltölthet, valamint megszakadt feltöltéseit 1 héten át megőrizzük Önnek, így bármikor folytathatja azokat.</p>',
    'bodyen' => '<p>To start upload a media file, select original language of the recording.</p><p>By clicking <strong>"Select files"</strong> select the media files to be uploaded.</p><p>Before starting the upload process, please read <strong>"Terms of Service"</strong> document and in case you agree with its content, approve it.</p><p>The media files to be uploaded can be marked as <strong>intro/outro</strong>. These videos can be associated with recordings later, intro is automatically played before, outro after the recording.</p><p>To start upload, press <strong>"Start upload"</strong> button.</p><p>In case you have a <strong>HTML5</strong> enabled browser, use drag and drop technique to add files. In your file browser select media files and drop them onto this webpage. With a HTML5 enabled browser you can upload more files at a time. In addition, you can continue uploading your uncompleted recordings within a week from first starting the process.</p>',
    'body_stringid' => 0,
  ),
  1 => 
  array (
    'title' => 'Felvétel alapadatok',
    'titleen' => 'Recording basics',
    'shortname' => 'recordings_modifybasics',
    'title_stringid' => 0,
    'body' => '<p>A<strong>lapadatok</strong></p><p>Ezen a panelen a felvételre vonatkozó elemi információkat szerkesztheti.</p><p>A <strong>"Felvétel eredeti nyelve"</strong> listából válassza ki a felvétel nyelvét!</p><p>A <strong>"Cím"</strong> mezőben adja meg a felvétel címét (kötelező) és alcímét (opcionális)!</p><p>Amennyiben prezentációt is feltöltött a felvétel mellé, a <strong>"Prezentáció elhelyezkedése"</strong> opciónál (jobb/bal) tudja megadni a prezentáció alapértelmezett elhelyezését a lejátszóban.</p><p><strong>"Intro/Outro felvétel"</strong>: Lehetőséget biztosítunk a felvételek előtt intro (bevezető, főcím), illetve a felvételek után outro (végefőcím) automatikus lejátszására. Ehhez elsőként egy intro/outroként megjelölt videót kell feltöltenie (ld. <a href="../../hu/recordings/upload">"Feltöltés"</a> oldal). Ezt követően a legördülő listából válassza ki az aktuális felvételhez társítandó intro és/vagy outro részletet.</p><p>Végül válassza ki az alapértelmezett indexképet! A listákban, a csatornák tartalmában, keresési találatokban ez a kép reprezentálja majd a felvételt.</p><p>Az <strong>OK</strong> gombra kattintva elmentheti adatokat, majd a böngésző a következő panelre ugrik.</p>',
    'bodyen' => '<p>Through this panel basic recording information can be edited.</p><p>From the list <strong>Language of recording</strong> please select language of uploaded media file.</p><p>Please provide title in <strong>Title</strong> field (mandatory) and subtitle (optional).</p><p>If presentation has also been added, please select which side the presentation is shown by selecting left/right under <strong>Presentation placement</strong> option. This will be the default placement of presentation when playback is started.</p><p><strong>Intro/Outro recording</strong>: It is possible to automatically play intro (e.g. introduction, main title) before, and outro (e.g. end credits) after recording playback. For this to work, first an intro/outro video has to be uploaded (see page on <a href="../../en/recordings/upload">"Uploading"</a>). After this intro and outro videos should be visible in this drop-down list.</p><p>Finally, please select a video thumbnail for this recording. This picture is to represent the recording in lists, channels and search results.</p><p>By clicking <strong>OK,</strong> changes are applied to recording and browser will be transferred to the next panel.</p>',
    'body_stringid' => 0,
  ),
  2 => 
  array (
    'title' => 'Felhasználók meghívása',
    'titleen' => 'Inviting new users',
    'shortname' => 'users_invite',
    'title_stringid' => 0,
    'body' => '<p><strong>Felhasználók:</strong> egyéni vagy csoportos meghívók. Egyéni meghívó esetén adja meg a meghívni kívánt felhasználó e-mail címét! Csoportos meghívó esetén CSV állományt tölthet fel "keresztnév;vezetéknév;email cím" formátumban. Válassza ki a szöveges fájl kódolását, valamint a mezőket elválasztó karaktert! Amennyiben külső levélküldő szolgáltatón keresztül (pl. Mailchimp) szeretné kézbesíteni a leveleket, erre is van lehetőség a "Külső szolgáltatón keresztül (CSV)" opció választásával. Ekkor az egyedi meghívó URL-eket CSV formában fogjuk visszaadni, amelyet importálni kell az Ön által használt levélküldő rendszerbe.</p><p><strong>Hozzáférés beállításai:</strong> A "Regisztráció korlátozott" funkció választásával a meghívandó felhasználó csak az itt megadott dátumig lesz képes a portálra bejelentkezni, ezt követően a fiók érvényességét veszti. A "Meghívó érvényes eddig" opcióval a meghívó érvényességét is megadhatja, a beállított dátum után a rendszer nem fogja érvényesnek tekinteni a meghívót. A "Belépés/regisztráció után átirányítás ide" alatt megadhat egy webcímet, ahová a sikeres regisztrációt követően a felhasználót automatikusan átirányítjuk. Ennek elsősorban beágyazott tartalmak esetén van jelentősége.</p><p><strong>Jogosultságok:</strong> a jelölőnégyzetek segítségével  különböző jogokkal ruházhatja fel a meghívandó felhasználót (több jogosultság is választható):</p><ul><li><strong>Szerkesztő:</strong> Képes szerkeszteni/törölni a portálhoz tartozó összes felhasználó felvételeit, csatornákat hozhat létre, hírt vehet fel.</li><li><strong>Kliens adminisztátor:</strong> Portál adminisztrátor. Felhasználókat hívhat meg, jogosultságaikat kezelheti. Felhasználói csoportokat, illetve szervezeti egységeket hozhat létre. Szerkesztheti a kategória- és műfaj-rendszert.</li><li><strong>Feltöltő:</strong> Felvételeket tölthet fel a portálra, illetve szerkesztheti saját felvételeit.</li><li><strong>Moderált feltöltő:</strong> Tölthet fel felvételeket, de nem publikálhatja azokat. A publikálandó felvételeket a szerkesztő számára tudja továbbküldeni.</li><li><strong>Live adminisztrátor:</strong> Élő közvetítéseket hozhat létre és menedzselhet, valamint moderálhatja a felhasználók hozzászólásait (chat).</li></ul><p><strong>Tartalom meghívás:</strong> a felhasználókat adott tartalomra/tartalmakra is meghívhatja. Válassza ki a tartalom típusát, kezdje el gépelni a felvétel, a csatorna vagy élő adás címét, majd válassza ki a megfelelőt! Amennyiben létező felhasználót hív meg, akkor a felhasználó jogosultságai automatikusan kiegészülnek a meghívóban választottaknak megfelelően.</p><p><strong>Felhasználói csoportok:</strong> a meghívandó felhasználót hozzárendelheti az előre létrehozott felhasználói csoportokhoz, egyszerre több elem is választható.</p><p><strong>Sablonok:</strong> Igény esetén egészítse ki a meghívót egyedi szöveges tartalommal. Lehetősége van a korábbi meghívók elmentett sablonjainak a visszatöltésére is.</p>',
    'bodyen' => '<p><strong>User invitation:</strong> personal or group invitation. In case of a personal invitation please provide the e-mail address of the user to be invited. For a group invitation you can upload a CSV file using the format "lastname;surname;e-mail address". Please properly define character encoding of your text file and select field separator character. If using an external e-mail delivery service (e.g. Mailchimp) choose "By a third party provider (CSV)". In this case the user specific URLs are returned in CSV form for further import to your mail delivery provider.</p><p><strong>Access:</strong> by choosing "Registration is restricted" the invited user\'s account will be valid until the given date and time. "Invitation valid until" allows you to control the validity of the invitation message. When having an embedded content use "Redirect user here after login/registration" to redirect successfully registered user to your own page with the embedded Videosquare player.</p><p><strong>Permissions: </strong>By clicking the right check box the invited user can be assigned different permissions (assignment of multiple permissions is possible):</p><ul><li><strong>Editor: </strong>Able to edit/delete any recording, can create channels and add news.</li><li><strong>Client administrator:</strong> Portal administrator, owns the highest level of privilege. A client administrator able to invite users and handle their permissions, can create user groups and departments, edit categories and genres.</li><li><strong>Uploader:</strong> Allowed to upload and publish recordings and edit his/her own uploads.</li><li><strong>Moderated uploader:</strong> Can upload recordings but cannot publish them. For publishing, the user can forward a recording to an editor for confirmation. </li><li><strong>Live administrator:</strong> Can create and manage live webcast events and moderate user live chat messages.</li></ul><p><strong>Content invitation:</strong> users can be invited to specific video contents. Choose type of content, start typing the title of recording, channel or live webcast then select the right one. In case of inviting an existing user, the user\'s rights will be upgraded with the ones selected in the invitation.</p><p><strong>User groups:</strong> invited users can be assigned to predefined user groups. You can select more items.</p><p><strong>Templates:</strong> add your own message to the invitation e-mail or load previously saved invitation templates.</p>',
    'body_stringid' => 0,
  ),
  3 => 
  array (
    'title' => 'Felvétel besorolása',
    'titleen' => 'Classification',
    'shortname' => 'recordings_modifyclassification',
    'title_stringid' => 0,
    'body' => '<p>Ebben a lépésben a videó műfaji, illetve tematikus kategorizálását végezhetjük el, a felvétel tartalmát kulcsszavak segítségével feltárhatjuk (keresés), valamint csatornához rendelhetjük.</p><p><strong>Kategóriák:</strong> A jelölőmezők segítségével az előre <a href="../../hu/categories/admin">létrehozott</a> kategóriákba sorolhatja a felvételt, így a tematikus <a href="../../hu/categories">kategória-rendszer</a> böngészésével a felhasználók könnyedén megtalálhatják. Egyszerre akár több kategória is választható, minimum egy kijelölése kötelező.</p><p><strong>Műfaj:</strong> Hasonlóan a kategóriákhoz, a felvételt az előre <a href="../../hu/genres/admin">definiált</a> műfajokhoz sorolhatja. Egyszerre több műfaj is választható, minimum egy kijelölése kötelező.</p><p><strong>Címkék:</strong> A címkék szabadon választott kulcsszavak, amelyek röviden leírják (feltárják) a felvétel tartalmát. A megfelelő kulcsszavakra keresve így megtalálhatjuk a felvételt. A szövegmezőben tetszőleges számú kifejezés megadható, amelyeket vesszővel (,) válasszon el egymástól!</p><p><strong>Felvétel hozzáadása csatornához:</strong> Amennyiben a felvételek tartalmilag kapcsolódnak, érdemes őket egy csatornához rendelni (pl. xyz konferencia), így az összetartozó anyagok egy helyen jelennek meg. A felvétel csatornához rendelése a csatorna mellett látható "+" jelre kattintással történik. Pipával jelöljük azokat a csatornákat, amelyekbe a felvételt már besorolta. A "-" jelre kattintva eltávolíthatja a felvételt a csatornából.</p>',
    'bodyen' => '<p>In this step the recording can be categorized by subject, genre and assigned to channels. Search can be further improved by adding a few keywords describing content of the recording.</p><p><strong>Categories:</strong> By using the right check boxes recording can be classified in <a href="../../en/categories/admin">predefined categories</a>, so by browsing the <a href="../../en/categories">thematic category system</a> users can easily find them. Multiple categories can be chosen at a time, selecting at least one category is mandatory.</p><p><strong>Genre:</strong> Same way as categories, recordings can be classified to <a href="../../en/genres/admin">predefined genres</a>. Multiple genres can be chosen at a time, selecting at least one genre is mandatory.</p><p><strong>Keywords: </strong>Keywords can added freely to describe (unfold) the content of recording. This will greatly improve hit list when search is used. You can add an unlimited number of expression to the text box, each must be separated by a coma (,).</p><p><strong>Add recording to channel: </strong>If the recording belongs to a set of recordings, then you can add them to the same channel, so all related video material can be organized into a collection. Click "+" sign next to each channel to add this particular recording to it. If the recording is already member of a channel, "-" sign will appear. By clicking "-" recording can be removed from the channel.</p>',
    'body_stringid' => 0,
  ),
  4 => 
  array (
    'title' => 'Felvétel leírása',
    'titleen' => 'Recording description',
    'shortname' => 'recordings_modifydescription',
    'title_stringid' => 0,
    'body' => '<p>Ezen az oldalon a felvétel néhány alapvető leíróját módosíthatja.</p><p><strong>Felvétel ideje:</strong> A felvétel elkészítésének ideje. A mezőre kattintva megjelenő panelen, vagy az értékeket közvetlen módosításával adható meg a megfelelő dátum és idő.</p><p><strong>Leírás:</strong> A felvétel tartalmának rövid leírása, amely a lejátszó alatt jelenik meg. Tartalma megjelenik a keresőben is, ezért érdemes kitölteni.</p><p><strong>Szerzői joggal kapcsolatos megjegyzés:</strong> A felvételre vonatkozó szerzői jogi nyilatkozatot itt lehet megadni, szintén a lejátszó alatt olvasható.</p><p><strong>Technikai megjegyzés:</strong> Saját jegyzet, csak a szerkesztő, illetve a feltöltő számára érhető el.</p>',
    'bodyen' => '<p>On this panel description of the recording can be modified.</p><p><strong>Time of recording:</strong> Date and time, when the recording was created. Use calendar panel and sliders to set exact date/time value.</p><p><strong>Description: </strong>Short description of the content of the recording. It will be visible under the player and will help finding the video.</p><p><strong>Copyright note:</strong> Copyright notice can be added here. It will be visible under the player area.</p><p><strong>Technical note:</strong> A private note can be added here, it is only readable for users with editor and uploader permission.</p>',
    'body_stringid' => 0,
  ),
  5 => 
  array (
    'title' => 'Felvétel közreműködők',
    'titleen' => 'Recording contributors',
    'shortname' => 'recordings_modifycontributors',
    'title_stringid' => 0,
    'body' => '<p>A Videosquare lehetőséget ad arra, hogy a felvételekhez közreműködőket (előadó, író, szerkesztő, stb.) rendeljen. A közreműködő személyt csak egyszer kell felvenni, ezt követően könnyedén hozzárendelheti más felvételekhez is.</p><p><strong>Közreműködő hozzáadása:</strong> Kezdje el gépelni a közreműködő nevét a szövegmezőben! A rendszer egy folyamatosan frissülő listában jeleníti meg a lehetséges személyeket.</p><p>Amennyiben az adatbázisban már létezik a megfelelő személy, válassza ki a listából és kattintson a <strong>"Hozzáadás"</strong> gombra. Ha nem találja a személyt, akkor az <strong>"Új közreműködő"</strong> segítségével adhat új szerzőt az adatbázishoz. Ehhez értelem szerűen töltse ki a megfelelő mezőket (keresztnév, vezetéknév, névsorrend, szerep), a jövőbeli azonosításhoz pedig rendeljen egy kulcsképet a szerzőhöz. Ebben a <strong>"Kép kiválasztása"</strong> funkció lesz segítségére, válasszon ki az aktuális felvételből egy, a személy azonosításra alkalmas képkockát!</p><p>A hozzáadott személyek listájában, az egyes nevek mellett néhány ikont láthat. A le/fel nyilakkal beállíthatja a közreműködők sorrendjét (a legfelső jelenik meg elsőként). A szemeteskosár ikonnal törölheti a személyt a felvétel közreműködői közül. A fogaskerék választásával szerkesztheti a személy adatait, a létrehozáskor megismert ablakkal fog találkozni.</p>',
    'bodyen' => '<p>Videosquare makes it possible to associate contributors (presenter, writer, editor, etc.) to each recording. Contributor has to be added only once and hereafter can be reused for additional recordings.</p><p><strong>Add contributor: </strong>Start writing the name of contributor in the text box. The hit list will be continuously refreshed.</p><p>If the appropriate person already exists in the database just click <strong>Add</strong>. If the person was not found, new contributor can be added to the database by clicking <strong>New contributor</strong>. To make this happen fill out the appropriate boxes with the necessary information (first name, family name, name order, role) and for future identification purposes, please associate a video index picture to the presenter. This picture will help identifying the person when future videos will be added.</p><p>Next to the list of added contributors some icons are shown. By <strong>downside/upside arrows</strong> the order of contributors can be changed (the one on top is going to be the first). By clicking <strong>trash</strong> icon a person can be removed from the list. By clicking <strong>gear</strong> icon the particular person\'s data can be edited.</p>',
    'body_stringid' => 0,
  ),
  6 => 
  array (
    'title' => 'Megosztás',
    'titleen' => 'Share',
    'shortname' => 'recordings_modifysharing',
    'title_stringid' => 0,
    'body' => '<p>Ebben a lépésben publikálhatja a felvételt, illetve beállíthatja a hozzáférési jogosultságokat. Négy különböző hozzáférési szint közül választhat:</p><p><strong>Publikus:</strong> A felvételt bárki megtekintheti.</p><p><strong>Regisztráció szükséges:</strong> Csak regisztrált felhasználók tekinthetik meg.</p><p><strong>Szervezeti egységek egy csoportja számára:</strong> Csak szervezeti egységek tagjai férnek hozzá.</p><p><strong>Saját csoportom számára:</strong> A megjelölt csoport tagjai férhetnek hozzá.</p><p>A továbbiakban néhány alapvető hozzáférési paramétert állíthat be:</p><p><strong>Korlátozza a hozzáférést dátum szerint?</strong> A felvétel csak a megadott időablakban érhető el. Adja meg a kezdő- és végdátumot!</p><p><strong>Eredeti felvétel letölthető?</strong> Letölthető legyen-e a felvétel a nézők számára? (nem javasolt)</p><p><strong>Audió sáv letölthető?</strong> Letölthető legyen-e a felvétel hangsávja? Hasznos lehet offline hallgatáshoz.</p><p><strong>Beágyazható?</strong> Amennyiben a felvételben olyan információkat találunk, amely adott kontextusban negatívan jelenhet meg, tilthatja a lejátszó külső weboldalakba történő beágyazását. A beágyazáshoz szükséges kódot a lejátszó alatt találja (ld. <strong><em>"&lt;&gt;"</em></strong> ikon).</p><p><strong>Felvétel állapota: </strong>A felvétel publikálásához az <em>"elérhető"</em> opciót kell választania. Alapértelmezett esetként a feltöltött videó <em>"vázlat"</em> állapotban van, azaz nem kerül automatikusan publikálásra. A vázlatokat kizárólag az adminisztrátorok és szerkesztők tekinthetik meg.</p><p><strong>Biztonsági szint:</strong> Lehallgatásra érzékeny tartalom esetén szükséges lehet a kliens és a Videosquare közötti médiafolyam titkosítása. Éles használatot megelőzően a tesztelés erősen ajánlott. <strong>VIGYÁZAT! Vegye figyelembe, hogy a mobil platformok egyelőre nem támogatják a média titkosítását!</strong></p><p><strong>Kreditpontos felvétel?</strong> A Videosquare rendszere lehetőséget ad a felvétel nézettségének nyomon követésére. Amennyiben ezt az opciót választja, a felvétel lejátszásakor csak a már lejátszott részben lehet pozícionálni. A nézettségről statisztika áll rendelkezésre.</p>',
    'bodyen' => '<p>At this stage recording access rights can be set and recording can be published. Different access levels are available:</p><p><strong>Public:</strong> Anyone can playback the recording.</p><p><strong>Requires registration:</strong> Only registered users can watch the recording.</p><p><strong>Group of departments:</strong> Only members of appointed departments can access the recording.</p><p><strong>For only own user group:</strong> Only members of selected groups can access the recording.</p><p>Some basic parameter in connection to access can be set below:</p><p><strong>Time restriction?</strong> Recording is accessible only in the given time window. Please provide start/end date!</p><p><strong>Original recording downloadable?</strong> Should the recording be available for download for viewers? (Not recommended.)</p><p><strong>Audio track downloadable?</strong> Should the audio track of the recording be downloadable? (It can be useful in case of offline listening.)</p><p><strong>Embed possible?</strong> If recording can possibly appear negatively in certain contexts, embedding it into other web pages can be disabled. Code for embedding can be found under player (see <em>"&lt;&gt;" </em>icon).</p><p><strong>Secure streaming</strong>: Secured media stream between client and our servers might be required in case of sensitive business information is transferred. ATTENTION! Bear in mind that mobile platforms do not support secure media stream at the moment.</p><p><strong>Accredited training?</strong> Videosquare makes it possible to track the progress of playback of a recording for a specific user. Only watched part of the recording can be seeked by choosing this option. Detailed statistics are available about the viewers.</p>',
    'body_stringid' => 0,
  ),
  7 => 
  array (
    'title' => 'Felirat feltöltése',
    'titleen' => 'Upload subtitles',
    'shortname' => 'recordings_uploadsubtitle',
    'title_stringid' => 0,
    'body' => '<p>Felvételeihez hagyományos <strong>.srt</strong> formátumú feliratfájlokat is feltölthet.</p><p>A felirat nyelvét a lenyíló menüből választhatja ki, az <strong>"Alapértelmezett felirat"</strong> engedélyezésével pedig az adott felirat automatikusan megjelenik a lejátszás elindításakor.</p><p>A felvételekhez egyszerre több feliratot is feltölthet, ezek a videó alatti listában fognak megjelenni és a <strong>"törlés"</strong> funkcióra kattintva törölhetőek</p>',
    'bodyen' => '<p>You can upload <strong>.srt</strong> format subtitles to the recording.</p><p>Choose subtitle language from the dropdown list. By enabling <strong>"default subtitle"</strong> option this particular subtitle will be the default one and will be shown when playback is started.</p><p>More than one subtitle can be uploaded to each recording that will be listed under uploaded media items. Remove them any time by clicking "delete" option.</p>',
    'body_stringid' => 0,
  ),
  8 => 
  array (
    'title' => 'Csatolmány feltöltése',
    'titleen' => 'Uploading attachments',
    'shortname' => 'recordings_uploadattachment',
    'title_stringid' => 0,
    'body' => '<p>A felvétele mellé bármilyen típusú- és számú dokumentumot feltölthet. Kattintson a "tallózás" gombra, adja meg a fájl helyét, adjon egy címet a csatolmánynak, és kattintson az "OK" gombra feltöltés megkezdéséhez. A csatolmány letöltő linkje csak akkor jelenik meg a felhasználók számára, ha a "Dokumentum elérhető" alatt ezt választja.</p>',
    'bodyen' => '<p>Any type and number of document can be attached to your recording. Click "Browse" and locate the file, add the title of the attachment and click "OK" to start uploading. Link for downloading the attachment is going to be visible for users only if selected under "Document dowloadable".</p>',
    'body_stringid' => 0,
  ),
  9 => 
  array (
    'title' => 'Prezentáció feltöltése',
    'titleen' => 'Upload content video',
    'shortname' => 'recordings_uploadcontent',
    'title_stringid' => 0,
    'body' => '<p>Az "Állomány kiválasztása" gombra kattintva válassza ki a feltölteni kívánt tartalom videót! Kérjük, figyelmesen olvassa el a "Felhasználási feltételek" oldalt, majd amennyiben elfogadja a tartalmát, hagyja jóvá a dokumentumot! A feltöltést a "Feltöltés kezdése" gomb megnyomásával indíthatja el.</p><p>Amennyiben rendelkezik HTML5 böngészővel, használja a "fogd és vidd" (drag and drop) módszert! A fájlkezelő alkalmazásban  válassza ki a feltölteni kívánt állományokat, majd dobja őket erre a  lapra! Modern, HTML5 képes böngésző esetén egyszerre több állományt is  feltölthet, valamint megszakadt feltöltéseit egy héten át megőrizzük  Önnek, így bármikor folytathatja azokat.</p>',
    'bodyen' => '<p>By clicking "Select file" the video file for upload can be selected. Please read the "Terms of service" carefully and in case you agree with its content approve the document. Upload can be started by clicking "Start upload".</p><p>In case you have a HTML5 enabled browser, use drag and  drop technique to add files. In your file browser select media files for upload and  drop them onto this webpage. With a modern HTML5 enabled browser you can  also upload more files at a time. In addition, you can continue uploading  your uncompleted recordings within a week from first starting the  process.</p>',
    'body_stringid' => 0,
  ),
  10 => 
  array (
    'title' => 'Csatornák létrehozása',
    'titleen' => 'Creating channels',
    'shortname' => 'channels_create',
    'title_stringid' => 0,
    'body' => '<p>A felvételeket csatornákban, azaz felvétel-gyűjtemények segítségével rendszerezheti. A csatorna létrehozásához adja meg a csatorna "Megnevezés", "Alcím" és "Leírás" mezőit, majd a lenyíló menüből válassza ki a csatorna típusát!</p>',
    'bodyen' => '<p>Create channels in order to organize your recordings. For creating a channel provide its "Name", "Subtitle", and "Description". Finally, choose "Channel type" from the lower drop down list.</p>',
    'body_stringid' => 0,
  ),
  11 => 
  array (
    'title' => 'Élő adás létrehozása',
    'titleen' => 'Create live event',
    'shortname' => 'live_create',
    'title_stringid' => 0,
    'body' => '<p>Élő adás létrehozásához adja meg az közvetítésre vonatkozó alapinformációkat, úgy mint az esemény címét, alcímét, illetve leírását. A cím kitöltése kötelező. Válassza ki a lenyíló listából az "Esemény típusát"! A kezdési és befejezési időpont beállításával meghatározhatja azt az időintervallumot, amelyen belül az élő esemény látható marad a portálon. Az alsó rádiógombok segítségével a felhasználók hozzáférését szabályozhatja.</p>',
    'bodyen' => '<p>For creating a live event it is necessary to fill out basic information related to the webcast such as event title, event subtitle or event description. Choose event type from drop-down list. Set event start and end time so it will be visible in the live webcast list until its end time. By radio buttons user authorization is defined.</p>',
    'body_stringid' => 0,
  ),
  12 => 
  array (
    'title' => 'Helyszín létrehozása',
    'titleen' => 'Create location',
    'shortname' => 'live_createfeed',
    'title_stringid' => 0,
    // REWRITE!!!
    'body' => '<p>Az élő eseményhez több helyszín tartozhat (pl. A, B, és C terem), amelyeket ezen a felületen hozhat létre.</p><p><strong>Típus:</strong> Klasszikus streaminghez (a H.264 streaming encoder eszköz az Ön birtokában van) válassza az "Élő adás" opciót. H.323/SIP videokonferencia felvételéhez a "Videokonferencia felvétel" beállítást kell alkalmazni (nem áll rendelkezésre minden előfizetőnek!).</p><p><strong>Biztonsági szint:</strong> A szerver és a lejátszók között titkosítottan továbbított adáshoz a "Titkosított streaming" funkció szükséges (nem áll rendelkezésre minden előfizetőnek!).</p><p><strong>Tartalom elhelyezése:</strong> A prezentáció elhelyezése bal, illetve jobb oldalon.</p><p><strong>Hozzáférés:</strong> Válassza ki az adáshoz hozzáférő felhasználói csoportokat!</p><p><strong>Hozzászólások moderálása:</strong> Amennyiben az élő adáshoz chat lehetőséget szeretne, az "Utólagos moderálás" vagy "Megjelenés előtt moderált hozzászólások" beállítást kell alkalmaznia. Előbbi esetén minden chat üzenet megjelenik, de utólagos moderálásra van lehetőség, míg utóbbi esetben minden hozzászólást megjelenés előtt explicit módon jóvá kell hagynia.</p>',
    // REWRITE!!!
    'bodyen' => '<p>A live event might feature several locations (e.g. rooms A, B and C) those you can add here.</p>',
    'body_stringid' => 0,
  ),
  13 => 
  array (
    'title' => 'Stream hozzáadása',
    'titleen' => 'Create stream',
    'shortname' => 'live_createstream',
    'title_stringid' => 0,
    'body' => '<p>Adja a helyszínhez a megfelelő streameket, és válassza ki azokat a platformokat, amelyekkel az Ön H.264 streaming encodere által küldött adás(ok) kompatibilis(ek)! Kérjük, a megfelelő beállítások elvégzéséhez forduljon az adott encoder eszköz dokumentációjához, vagy igényelje segítségünket a <a href="mailto:support@videosqr.com">support@videosqr.com</a> e-mail címen. Amennyiben egynél több minőségi változatot küldene (pl. desktop kompatibilis, mobil kompatibilis, stb.), akkor további streameket kell hozzáadnia.</p>',
    'bodyen' => '<p>Add streams to event location and choose player platforms that are compatible with this specific H.264 stream your encoder is sending. Please consult encoder user manual for more information or ask our assistance at <a href="mailto:support@videosqr.com">support@videosqr.com</a>. In case you are sending several quality versions then more streams should be added.</p>',
    'body_stringid' => 0,
  ),
  14 => 
  array (
    'title' => 'Helyszín módosítása',
    'titleen' => 'Modify location',
    'shortname' => 'live_modifyfeed',
    'title_stringid' => 0,
    'body' => '<p>Az élő eseményhez több helyszín tartozhat (pl. A, B, és C terem), amelyeket ezen a felületen módosíthat.</p><p><strong>Típus:</strong> Klasszikus streaminghez (a H.264 streaming encoder eszköz az Ön birtokában van) válassza az "Élő adás" opciót. H.323/SIP videokonferencia felvételéhez a "Videokonferencia felvétel" beállítást kell alkalmazni (nem áll rendelkezésre minden előfizetőnek!).</p><p><strong>Biztonsági szint:</strong> A szerver és a lejátszók között titkosítottan továbbított adáshoz a "Titkosított streaming" funkció szükséges (nem áll rendelkezésre minden előfizetőnek!).</p><p><strong>Tartalom elhelyezése:</strong> A prezentáció elhelyezése bal, illetve jobb oldalon.</p><p><strong>Hozzáférés:</strong> Válassza ki az adáshoz hozzáférő felhasználói csoportokat!</p><p><strong>Hozzászólások moderálása:</strong> Amennyiben az élő adáshoz chat lehetőséget szeretne, az "Utólagos moderálás" vagy "Megjelenés előtt moderált hozzászólások" beállítást kell alkalmaznia. Előbbi esetén minden chat üzenet megjelenik, de utólagos moderálásra van lehetőség, míg utóbbi esetben minden hozzászólást megjelenés előtt explicit módon jóvá kell hagynia.</p>',
    'bodyen' => '<p>A live event might feature several locations (e.g. rooms A, B and C) those you can modify here.</p>',
    'body_stringid' => 0,
  ),
  15 => 
  array (
    'title' => 'Csatorna módosítása',
    'titleen' => 'Modify channel',
    'shortname' => 'channels_modify',
    'title_stringid' => 0,
    'body' => '<p>A felvételeket csatornákban, azaz felvétel-gyűjtemények segítségével rendszerezheti. A csatorna módosításához szerkessze a "Megnevezés", "Alcím" vagy "Leírás" mezőket. A típus módosításához a "Csatorna típus" menüt használhatja. Amennyiben nem szeretné, hogy a csatorna nyilvánosan hozzáférhető legyen, az alsó rádiógombok segítségével beállíthatja a kívánt hozzáférési csoportokat. A csatorna nézőkép változtatásához az aktuális csatornába sorolt felvételek nézőképei közül választhat.</p>',
    'bodyen' => '<p>Recordings can be organized in channels. To modify channel edit "Title", "Subtitle" and "Description" fields. Change "Channel type" from drop down list. In case channel is public select the appropriate radio button. You can also select a different channel thumbnail to represent channel recordings.</p>',
    'body_stringid' => 0,
  ),
  16 => 
  array (
    'title' => 'Műfaj módosítása',
    'titleen' => 'Modify genre',
    'shortname' => 'genres_modify',
    'title_stringid' => 0,
    'body' => '<p>Ezen a képernyőn módosíthatja a műfajlista kiválasztott elemét. Amennyiben ez az elem nem fő műfaj, válassza ki a szülőt!</p>',
    'bodyen' => '<p>On this panel, you can modify genre list items. If the element is a child item, please assign a parent to it.</p>',
    'body_stringid' => 0,
  ),
  17 => 
  array (
    'title' => 'Műfaj létrehozás',
    'titleen' => 'Create genre',
    'shortname' => 'genres_create',
    'title_stringid' => 0,
    'body' => '<p>Adja meg a műfaj nevét. Amennyiben nem fő műfajt hoz létre, válassza ki a szülő elemet!</p>',
    'bodyen' => '<p>Provide name of the genre to be added. If this is a child item, please assign a parent to it!</p>',
    'body_stringid' => 0,
  ),
  18 => 
  array (
    'title' => 'Kategória létrehozása',
    'titleen' => 'Create category',
    'shortname' => 'categories_create',
    'title_stringid' => 0,
    'body' => '<p>Hozza létre a kívánt kategóriát! Adja meg a kategória nevét, majd jelölje ki a tárgyát reprezentáló ikont. Amennyiben nem fő elemről van szó, válasszon szülő kategóriát!</p>',
    'bodyen' => '<p>Add a new category item. Please provide its name, then an icon representing subject of category. In case this is a child item, assign a parent to it.</p>',
    'body_stringid' => 0,
  ),
  19 => 
  array (
    'title' => 'Kategória szerkesztése',
    'titleen' => 'Modify category',
    'shortname' => 'categories_modify',
    'title_stringid' => 0,
    'body' => '<p>Módosítsa a kategória nevét, válasszon szülőt hozzá, illetve a tárgyát reprezentáló ikont!</p>',
    'bodyen' => '<p>Modify name of category, assign a parent to it or change its icon.</p>',
    'body_stringid' => 0,
  ),
  20 => 
  array (
    'title' => 'Csoport létrehozása',
    'titleen' => 'Create group',
    'shortname' => 'groups_create',
    'title_stringid' => 0,
    'body' => '<p>Felhasználói csoport létrehozása. Adja meg a csoport nevét! A "lokális" csoport a Videosquare rendszeren belül jön létre lokálisan. Külső csoport esetén - amennyiben konfigurált - csoportokat és azok tagjait importálhatja a vállalati Active Directory vagy LDAP címtár rendszerből. Ehhez adja meg a csoport DN-jét!</p>',
    'bodyen' => '<p>Create a user group. Add the name of the group first. A "local" group is created locally in Videosquare. An "external" group - if configured - will import a group and its members from the corporate Active Directory or LDAP. For an external group please provide a full lenght DN.</p>',
    'body_stringid' => 0,
  ),
  21 => 
  array (
    'title' => 'Csoport módosítás',
    'titleen' => 'Modify group',
    'shortname' => 'groups_modify',
    'title_stringid' => 0,
    'body' => '<p>Módosítsa a csoport beállításait.</p>',
    'bodyen' => '<p>Modify group properties.</p>',
    'body_stringid' => 0,
  ),
  22 => 
  array (
    'title' => 'Felhasználói profil módosítás',
    'titleen' => 'Modify user profile',
    'shortname' => 'users_modify',
    'title_stringid' => 0,
    'body' => '<p>Ezen az oldalon saját felhasználójának adatait módosíthatja, avatar képet tölthet fel, illetve ellenőrizheti meglévő jogosultságait.</p>',
    'bodyen' => '<p>On this page you can modify your own user profile, upload an avatar image and verify your access rights.</p>',
    'body_stringid' => 0,
  ),
  23 => 
  array (
    'title' => 'Felhasználó módosítása',
    'titleen' => 'Modify user',
    'shortname' => 'users_edit',
    'title_stringid' => 0,
    'body' => '<p>Ezen az oldalon a kiválasztott felhasználó beállításait módosíthatja, csoport jogosultságot adhat hozzá, korlátozhatja a regisztráció érvényességét.</p>',
    'bodyen' => '<p>On this page you can modify the properties of the selected user, edit its group membership and set account time restriction.</p>',
    'body_stringid' => 0,
  ),
  24 => 
  array (
    'title' => 'Meghívó szerkesztése',
    'titleen' => 'Edit invitation',
    'shortname' => 'users_editinvite',
    'title_stringid' => 0,
    'body' => '<p>Ezen az oldalon a már kiküldött meghívót tudja módosítani. A meghívóra regisztráló felhasználó az itt beállított (módosított) jogosultságokkal fog rendelkezni a sikeres regisztrációt követően.</p><p><strong>Felhasználók:</strong> egyéni vagy csoportos meghívók. Egyéni meghívó esetén adja meg a meghívni kívánt felhasználó e-mail címét! Csoportos meghívó esetén CSV állományt tölthet fel "keresztnév;vezetéknév;email cím" formátumban. Válassza ki a szöveges fájl kódolását, valamint a mezőket elválasztó karaktert! Amennyiben külső levélküldő szolgáltatón keresztül (pl. Mailchimp) szeretné kézbesíteni a leveleket, erre is van lehetőség a "Külső szolgáltatón keresztül (CSV)" opció választásával. Ekkor az egyedi meghívó URL-eket CSV formában fogjuk visszaadni, amelyet importálni kell az Ön által használt levélküldő rendszerbe.</p><p><strong>Hozzáférés beállításai:</strong> A "Regisztráció korlátozott" funkció választásával a meghívandó felhasználó csak az itt megadott dátumig lesz képes a portálra bejelentkezni, ezt követően a fiók érvényességét veszti. A "Meghívó érvényes eddig" opcióval a meghívó érvényességét is megadhatja, a beállított dátum után a rendszer nem fogja érvényesnek tekinteni a meghívót. A "Belépés/regisztráció után átirányítás ide" alatt megadhat egy webcímet, ahová a sikeres regisztrációt követően a felhasználót automatikusan átirányítjuk. Ennek elsősorban beágyazott tartalmak esetén van jelentősége.</p><p><strong>Jogosultságok:</strong> a jelölőnégyzetek segítségével  különböző jogokkal ruházhatja fel a meghívandó felhasználót (több jogosultság is választható):</p><ul><li><strong>Szerkesztő:</strong> Képes szerkeszteni/törölni a portálhoz tartozó összes felhasználó felvételeit, csatornákat hozhat létre, hírt vehet fel.</li><li><strong>Kliens adminisztátor:</strong> Portál adminisztrátor. Felhasználókat hívhat meg, jogosultságaikat kezelheti. Felhasználói csoportokat, illetve szervezeti egységeket hozhat létre. Szerkesztheti a kategória- és műfaj-rendszert.</li><li><strong>Feltöltő:</strong> Felvételeket tölthet fel a portálra, illetve szerkesztheti saját felvételeit.</li><li><strong>Moderált feltöltő:</strong> Tölthet fel felvételeket, de nem publikálhatja azokat. A publikálandó felvételeket a szerkesztő számára tudja továbbküldeni.</li><li><strong>Live adminisztrátor:</strong> Élő közvetítéseket hozhat létre és menedzselhet, valamint moderálhatja a felhasználók hozzászólásait (chat).</li></ul><p><strong>Tartalom meghívás:</strong> a felhasználókat adott tartalomra/tartalmakra is meghívhatja. Válassza ki a tartalom típusát, kezdje el gépelni a felvétel, a csatorna vagy élő adás címét, majd válassza ki a megfelelőt! Amennyiben létező felhasználót hív meg, akkor a felhasználó jogosultságai automatikusan kiegészülnek a meghívóban választottaknak megfelelően.</p><p><strong>Felhasználói csoportok:</strong> a meghívandó felhasználót hozzárendelheti az előre létrehozott felhasználói csoportokhoz, egyszerre több elem is választható.</p><p><strong>Sablonok:</strong> Igény esetén egészítse ki a meghívót egyedi szöveges tartalommal. Lehetősége van a korábbi meghívók elmentett sablonjainak a visszatöltésére is.</p>',
    'bodyen' => '<p>On this page an already delivered invitation can be modified. A user registering to this invitation will be modified accordingly after a successful registration.</p><p><strong>User invitation:</strong> personal or group invitation. In case of a personal invitation please provide the e-mail address of the user to be invited. For a group invitation you can upload a CSV file using the format "lastname;surname;e-mail address". Please properly define character encoding of your text file and select field separator character. If using an external e-mail delivery service (e.g. Mailchimp) choose "By a third party provider (CSV)". In this case the user specific URLs are returned in CSV form for further import to your mail delivery provider.</p><p><strong>Access:</strong> by choosing "Registration is restricted" the invited user\'s account will be valid until the given date and time. "Invitation valid until" allows you to control the validity of the invitation message. When having an embedded content use "Redirect user here after login/registration" to redirect successfully registered user to your own page with the embedded Videosquare player.</p><p><strong>Permissions: </strong>By clicking the right check box the invited user can be assigned different permissions (assignment of multiple permissions is possible):</p><ul><li><strong>Editor: </strong>Able to edit/delete any recording, can create channels and add news.</li><li><strong>Client administrator:</strong> Portal administrator, owns the highest level of privilege. A client administrator able to invite users and handle their permissions, can create user groups and departments, edit categories and genres.</li><li><strong>Uploader:</strong> Allowed to upload and publish recordings and edit his/her own uploads.</li><li><strong>Moderated uploader:</strong> Can upload recordings but cannot publish them. For publishing, the user can forward a recording to an editor for confirmation. </li><li><strong>Live administrator:</strong> Can create and manage live webcast events and moderate user live chat messages.</li></ul><p><strong>Content invitation:</strong> users can be invited to specific video contents. Choose type of content, start typing the title of recording, channel or live webcast then select the right one. In case of inviting an existing user, the user\'s rights will be upgraded with the ones selected in the invitation.</p><p><strong>User groups:</strong> invited users can be assigned to predefined user groups. You can select more items.</p><p><strong>Templates:</strong> add your own message to the invitation e-mail or load previously saved invitation templates.</p>',
    'body_stringid' => 0,
  ),
  25 => 
  array (
    'title' => 'Akkreditált felvételek előrehaladás lekérdezés',
    'titleen' => 'Accredited recordings progress query',
    'shortname' => 'analytics_accreditedrecordings',
    'title_stringid' => 0,
    'body' => '<p>Ezen az oldalon egy adott felhasználó kreditpontos felvételekkel kapcsolatos előrehaladását kérdezheti meg. Adja meg a kívánt kezdő és befejezési dátumot, valamint a felhasználó e-mail címét.</p>',
    'bodyen' => '<p>Query a user\'s progress regarding accredited recordings. Provide start and end date and username.</p>',
    'body_stringid' => 0,
  ),
  26 => 
  array (
    'title' => 'Analitika lekérdezés',
    'titleen' => 'Analytics query',
    'shortname' => 'analytics_statistics',
    'title_stringid' => 0,
    'body' => '<p>Ezen az oldalon a felvételek, illetve az élő adások nézettségi statisztikáit kérdezheti le. Elsőként válassza ki, hogy felvétel vagy élő adás a lekérdezés tárgya. Jelölje meg a statisztikai adatok szűréséhez a kezdési és befejezési dátumokat. A "Felvételek", illetve a "Közvetítések" mezőben kezdje el gépelni a felvétel vagy élő adás címét, majd a megjelenő listából válassza ki a megfelelőt, szükség esetén adjon hozzá további tartalmi elemeket. Hasonlóan jelölje ki a felhasználói csoportokat, illetve a konkrét felhasználókat, amelyekre szűkíteni kívánja a statisztika adatok szűrését. Amennyiben további technikai adatokra is kíváncsi, jelölje meg a "Technikai adatok letöltése" opciót.</p><p>A lekérdezés eredmény egy részletes CSV állomány, amely tartalmazza a felhasználók (lejátszó) lejátszási munkameneteit. Származtatott kimutatásokat, grafikonokat CSV állományok feldolgozására alkalmas programokkal készíthet (pl. Excel).</p>',
    'bodyen' => '<p>Recording and live webcast statistical data can be exported from this page. First, select whether recording or live data should be retrived. For basic filtering of statistical data set start and end date. Under "Recordings" or "Live webcast" start typing title of video content then select from search list, repeat for more content items. Similarly, select user groups or individual users for more specific filtration. In case you want to retrieve detailed technical data select "Download technical data" option.</p><p>The result of the query will be a CSV file containing user playback (player) sessions. Further derived reports can be made with applications capable of CSV processing (e.g. Excel).</p>',
    'body_stringid' => 0,
  ),
  
);
