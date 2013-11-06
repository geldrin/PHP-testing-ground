      <br/>
      <img src="{$organization|@uri:static}images/email_footer.png" width="560" height="1" hspace="0" vspace="0" border="0" style="border: 0; margin: 0; display: block; width: 560px; height: 1px;"/>
    </td>
  </tr>
  <tr>
    <td width="600" height="50" align="center" valign="center" style="width: 600px; text-align:center; color: #333333;">
      {newsletter}
      <span style="font-size: 13px;"><center>&copy; Videosquare, <a href="{$organization|@uri:base}">{$organization|@uri:base}</a></center></span>
      {/newsletter}
    </td>
  </tr>
  <tr>
    <td width="600" height="30" style="width: 600px; height: 30px; padding: 0; margin: 0;background-color: #fff; color: #333333; font-family: 'Arial', 'sans-serif'; font-size: 13px;" bgcolor="#fff" align="center">
      <a href="{$organization|@uri:base}"><img hspace="0" vspace="0" border="0" src="{$organization|@uri:static}images/email_footer.png" style="border: 0; margin: 0; display: block;" alt="{#newsletter_enablepictures#}" /></a>
    </td>
  </tr>
</table>

</body>
</html>
