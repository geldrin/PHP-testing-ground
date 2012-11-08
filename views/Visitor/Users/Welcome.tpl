{include file="Visitor/_header.tpl"}
<div id="contentsbody">
<h1>Üdvözöljük XYZ!</h1>

<h2>Saját oldalaim</h2>

  <ul>
    <li><a href="{$language}/users/modify">Adataim</a></li>
    <li><a href="{$language}/users/logout">Kilépés</a></li>
  </ul>

<h2>Tartalom</h2>
  <ul>
    <li><a href="{$language}/recordings/myrecordings">Felvételeim</a></li>
    <li><a href="{$language}/channels/mychannels">Csatornáim</a></li>
    <li><a href="{$language}/channels/create">Csatorna létrehozása</a></li>
    <li><a href="{$language}/recordings/featured">Ajánló</a></li>

  </ul>

<h2>Adminisztrátori funkciók</h2>
  <ul>
    <li><a href="{$language}/users/admin">Felhasználók kezelése</a></li>
    <li><a href="{$language}/genres/admin">Műfajok szerkesztése</a></li>
    <li><a href="{$language}/categories/admin">Kategóriák szerkesztése</a></li>
    <li><a href="{$language}/organizations/createnews">Hír hozzáadása</a></li>
    <li><a href="{$language}/organizations/listnews">Hírszerkesztő</a></li>
    <li><a href="{$language}/organizations/modifyintroduction">Szervezet bemutatása</a></li>
    <li><a href="{$language}/departments/admin">Szervezeti egységek szerkesztése</a></li>
    {*}<li><a href="{$language}/"></a></li>{/*}
  </ul>

</div>
{include file="Visitor/_footer.tpl"}