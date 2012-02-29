{include file="Visitor/_header.tpl" module="index"}

      <div class="title">
        <h1>Page title</h1>
        <h2>Subtitle</h2>
      </div>
      
      <h1>Timeline test</h1>
      
      <div class="timeline">
        <ul>
          <li class="active">
            <a href="{$language}/recordings/modifybasics/">step1</a>
          </li>
          <li>
            <a href="{$language}/recordings/modifyclassification/">step2</a>
          </li>
          <li>
            <a href="{$language}/recordings/modifydescription/">step3</a>
          </li>
          <li>
            <a href="{$language}/recordings/modifycontributors/">step4</a>
          </li>
          <li>
            <a href="{$language}/recordings/modifysharing/">step5</a>
          </li>
          <li class="last"></li>
        </ul>
      </div>
      <div class="clear">
<div class="widepager"><ul><li><a rel="prev" class="previous" id="pgr0" href="hu/featured?order=isfeatured%20DESC%2C%20id%20DESC&amp;direction=&amp;start=0&amp;perpage=10">előző oldal</a></li> <li><a  id="pgr0" href="hu/featured?order=isfeatured%20DESC%2C%20id%20DESC&amp;direction=&amp;start=0&amp;perpage=10">1</a></li>   <li class="currentpage">2</li>   <li><a  id="pgr20" href="hu/featured?order=isfeatured%20DESC%2C%20id%20DESC&amp;direction=&amp;start=20&amp;perpage=10">3</a></li>   <li><a  id="pgr30" href="hu/featured?order=isfeatured%20DESC%2C%20id%20DESC&amp;direction=&amp;start=30&amp;perpage=10">4</a></li>   <li><a  id="pgr40" href="hu/featured?order=isfeatured%20DESC%2C%20id%20DESC&amp;direction=&amp;start=40&amp;perpage=10">5</a></li>   <li><a  id="pgr50" href="hu/featured?order=isfeatured%20DESC%2C%20id%20DESC&amp;direction=&amp;start=50&amp;perpage=10">6</a></li>   <li><a  id="pgr60" href="hu/featured?order=isfeatured%20DESC%2C%20id%20DESC&amp;direction=&amp;start=60&amp;perpage=10">7</a></li>   <li><a  id="pgr80" href="hu/featured?order=isfeatured%20DESC%2C%20id%20DESC&amp;direction=&amp;start=80&amp;perpage=10">...</a></li> <li><form action="hu/featured?order=isfeatured%20DESC%2C%20id%20DESC&amp;direction=&amp;start=0&amp;perpage=10" method="post"><select name="perpage" onchange="this.form.submit();"><option  value="5">5</option><option selected="selected" value="10">10</option><option  value="20">20</option><option  value="50">50</option><option  value="100">100</option></select> tétel/oldal</form></li> <li><a rel="next" class="next" id="pgr20" href="hu/featured?order=isfeatured%20DESC%2C%20id%20DESC&amp;direction=&amp;start=20&amp;perpage=10">következő oldal</a></li></ul></div>

</div>
      <div class="leftdoublebox" style="background-color: #1f1f1f; height: 350px;">
        nagy kép
      </div>
      <div class="rightbox" style="background-color: #ccc; height: 350px;">
        right box
      </div>
      
      <div class="leftdoublebox">
        
        <h1>HTML Ipsum Presents</h1>
        <p><strong>Pellentesque habitant morbi tristique</strong> senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. <em>Aenean ultricies mi vitae est.</em> Mauris placerat eleifend leo. Quisque sit amet est et sapien ullamcorper pharetra. Vestibulum erat wisi, condimentum sed, <code>commodo vitae</code>, ornare sit amet, wisi. Aenean fermentum, elit eget tincidunt condimentum, eros ipsum rutrum orci, sagittis tempus lacus enim ac dui. <a href="#">Donec non enim</a> in turpis pulvinar facilisis. Ut felis.</p>

        <h2>Header Level 2</h2>
        
        <ol>
           <li>Lorem ipsum dolor sit amet, consectetuer adipiscing elit.</li>
           <li>Aliquam tincidunt mauris eu risus.</li>
        </ol>

        <blockquote><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus magna. Cras in mi at felis aliquet congue. Ut a est eget ligula molestie gravida. Curabitur massa. Donec eleifend, libero at sagittis mollis, tellus est malesuada tellus, at luctus turpis elit sit amet quam. Vivamus pretium ornare est.</p></blockquote>

        <h3>Header Level 3</h3>

        <ul>
           <li>Lorem ipsum dolor sit amet, consectetuer adipiscing elit.</li>
           <li>Aliquam tincidunt mauris eu risus.</li>
        </ul>

        <pre><code>
        #header h1 a 
          display: block; 
          width: 300px; 
          height: 80px; 
        
        </code></pre>
        
        <table>
            <tr>
              <th>heading1</th>
              <th>heading2</th>
              <th>heading3</th>
              <th>heading4</th>
            </tr>
            <tr>
              <td>cell1</td>
              <td>cell2</td>
              <td>cell3</td>
              <td>cell4</td>
            </tr>
            <tr>
              <td>cell1</td>
              <td>cell2</td>
              <td>cell3</td>
              <td>cell4</td>
            </tr>
            <tr>
              <td>cell1</td>
              <td>cell2</td>
              <td>cell3</td>
              <td>cell4</td>
            </tr>
        </table>
        <div class="clear"></div>
        <div class="form">
<form enctype="multipart/form-data" target="_self" name="signup" id="signup" action="/hu/users/signup"  onsubmit="return check_signup();"  method="post"><input type="hidden" id="action" name="action" value="submitsignup"  />
<input type="hidden" id="forward" name="forward" value=""  />
<fieldset  id="fs1">
<legend>Regisztráció</legend>
<span class="legendsubtitle">Az alábbi egyszerű form kitöltésével regisztrálhat nálunk:</span><div class="formrow"><span class="label"><label for="email">E-mail cím: <span class="required">*</span></label></span><div class="element"><input  type="text" name="email" id="email" value=""  /><span class="postfix"></span><div id="cf_erroremail" style="display: none; visibility: hidden; padding: 2px 5px 2px 5px; background-color: #d03030; color: white;clear: both;"></div></div></div><div class="formrow"><span class="label"><label for="password">Jelszó: <span class="required">*</span></label></span><div class="element"><input type="password" name="password" id="password" value=""  /><span class="postfix"></span><div id="cf_errorpassword" style="display: none; visibility: hidden; padding: 2px 5px 2px 5px; background-color: #d03030; color: white;clear: both;"></div></div></div><div class="formrow"><span class="label"><label for="confirmpassword">Jelszó ellenőrzése: <span class="required">*</span></label></span><div class="element"><input type="password" name="confirmpassword" id="confirmpassword" value=""  /><span class="postfix"></span><div id="cf_errorconfirmpassword" style="display: none; visibility: hidden; padding: 2px 5px 2px 5px; background-color: #d03030; color: white;clear: both;"></div></div></div><div class="formrow"><span class="label"><label for="nameprefix">Titulus</label></span><div class="element"><select  id="nameprefix" name="nameprefix">
<option value="">Nincs titulus</option>
<option value="Dr.">Dr.</option>
<option value="BSc.">BSc.</option>
<option value="MSc.">MSc.</option>
<option value="PhD.">PhD.</option>
<option value="Prof.">Prof.</option>
<option value="Prof. Emer">Prof. Emer</option>
<option value="Sir">Sir</option>
<option value="DLA">DLA</option>
</select><span class="postfix"></span><div id="cf_errornameprefix" style="display: none; visibility: hidden; padding: 2px 5px 2px 5px; background-color: #d03030; color: white;clear: both;"></div></div></div><div class="formrow"><span class="label"><label for="namefirst">Keresztnév: <span class="required">*</span></label></span><div class="element"><input  type="text" name="namefirst" id="namefirst" value=""  /><span class="postfix"></span><div id="cf_errornamefirst" style="display: none; visibility: hidden; padding: 2px 5px 2px 5px; background-color: #d03030; color: white;clear: both;"></div></div></div><div class="formrow"><span class="label"><label for="namelast">Vezetéknév: <span class="required">*</span></label></span><div class="element"><input  type="text" name="namelast" id="namelast" value=""  /><span class="postfix"></span><div id="cf_errornamelast" style="display: none; visibility: hidden; padding: 2px 5px 2px 5px; background-color: #d03030; color: white;clear: both;"></div></div></div><div class="formrow"><span class="label"><label for="nameformat">Név megjelenése</label></span><div class="element"><select  id="nameformat" name="nameformat">
<option selected="selected" value="straight">Keleti névsorrend: vezetéknév - keresztnév</option>
<option value="reverse">Nyugati névsorrend: keresztnév - vezetéknév</option>
</select><span class="postfix"></span><div id="cf_errornameformat" style="display: none; visibility: hidden; padding: 2px 5px 2px 5px; background-color: #d03030; color: white;clear: both;"></div></div></div><div class="formrow"><span class="label"><label for="nickname">Megjelenített név (becenév): <span class="required">*</span></label></span><div class="element"><input  type="text" name="nickname" id="nickname" value=""  /><span class="postfix"></span><div id="cf_errornickname" style="display: none; visibility: hidden; padding: 2px 5px 2px 5px; background-color: #d03030; color: white;clear: both;"></div></div></div><div class="formrow"><span class="label"><label for="newsletter">Igen, kérek hírlevelet</label></span><div class="element"><input type="checkbox" name="newsletter" id="newsletter" value="1" checked="checked"   />
<span class="postfix"></span><div id="cf_errornewsletter" style="display: none; visibility: hidden; padding: 2px 5px 2px 5px; background-color: #d03030; color: white;clear: both;"></div></div></div><div class="formrow"><span class="label"><label for="tos">Megismertem és elfogadom a felhasználási feltételeket.</label></span><div class="element"><input type="checkbox" name="tos" id="tos" value="1"  />
<span class="postfix"><a href="hu/contents/userstos" id="termsofservice" target="_blank">Felhasználási feltételek</a></span><div id="cf_errortos" style="display: none; visibility: hidden; padding: 2px 5px 2px 5px; background-color: #d03030; color: white;clear: both;"></div></div></div>
</fieldset>
<input type="submit" value="OK" class="submitbutton" />
</form>
</div>
      </div>
      <div class="rightbox">
        <h1>Üdvözlünk</h1>
        <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>
        <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo. Quisque sit amet est et sapien ullamcorper pharetra. Vestibulum erat wisi, condimentum sed, commodo vitae, ornare sit amet, wisi. Aenean fermentum, elit eget tincidunt condimentum, eros ipsum rutrum orci, sagittis tempus lacus enim ac dui. Donec non enim in turpis pulvinar facilisis. Ut felis. Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat. Nam dui mi, tincidunt quis, accumsan porttitor, facilisis luctus, metus</p>
      </div>
      

{include file="Visitor/_footer.tpl"}
