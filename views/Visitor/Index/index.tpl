{include file="Visitor/_header.tpl" module="index"}

{if !empty( $recordings )}
  <div id="indexcontainer">
    <div class="leftdoublebox">
      {assign var=recording value=$recordings[0]}
      <a class="imageinfo wlarge" href="{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}">
        <img src="{$recording|@indexphoto:player}"/>
        <div class="playpic"></div>
        <div class="imageinfowrap">
          <div class="avatar"><img src="{$STATIC_URI}images/avatar_placeholder.png"/></div>
          <div class="content">
            <h1>{$recording.title|mb_truncate:60|escape:html}</h1>
            <h2>{$recording.nickname|mb_truncate:90|escape:html}</h2>
          </div>
        </div>
      </a>
      
    </div>
    
    <div class="rightbox">
      {if isset( $recordings[1] )}
        {assign var=recording value=$recordings[1]}
        <a class="imageinfo wwide first" href="{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}">
          <img src="{$recording|@indexphoto:wide}"/>
          <div class="imageinfowrap">
            <div class="avatar"><img src="{$STATIC_URI}images/avatar_placeholder.png"/></div>
            <div class="content">
              <h1>{$recording.title|mb_truncate:23|escape:html}</h1>
              <h2>{$recording.nickname|mb_truncate:90|escape:html}</h2>
            </div>
          </div>
        </a>
      {/if}
      {if isset( $recordings[2] )}
        {assign var=recording value=$recordings[2]}
        <a class="imageinfo wwide" href="{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}">
          <img src="{$recording|@indexphoto:wide}"/>
          <div class="imageinfowrap">
            <div class="avatar"><img src="{$STATIC_URI}images/avatar_placeholder.png"/></div>
            <div class="content">
              <h1>{$recording.title|mb_truncate:23|escape:html}</h1>
              <h2>{$recording.nickname|mb_truncate:90|escape:html}</h2>
            </div>
          </div>
        </a>
      {/if}
    </div>
  </div>
{/if}

<div class="leftdoublebox">
  <div class="title">
    <h1>HTML Ipsum Presents</h1>
  </div>
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
  
</div>
<div class="rightbox">
  <div class="title"><h1>Üdvözlünk</h1></div>
  <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>
  <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo. Quisque sit amet est et sapien ullamcorper pharetra. Vestibulum erat wisi, condimentum sed, commodo vitae, ornare sit amet, wisi. Aenean fermentum, elit eget tincidunt condimentum, eros ipsum rutrum orci, sagittis tempus lacus enim ac dui. Donec non enim in turpis pulvinar facilisis. Ut felis. Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat. Nam dui mi, tincidunt quis, accumsan porttitor, facilisis luctus, metus</p>
</div>


{include file="Visitor/_footer.tpl"}
