<div class="title">
  <h1>{#channels__mychannels_title#}</h1>
  {if $member.isuploader or $member.ismoderateduploader}
    <a href="{$language}/channels/create?forward={$FULL_URI|escape:url}">{#channels__create#}</a>
    <br/>
  {/if}
</div>
