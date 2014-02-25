var l = {$localization};
var allowedfiletypes = "{$allowedfiletypes}";

{literal}
/* Hungarian initialisation for the jQuery UI date picker plugin. */
/* Written by Istvan Karaszi (jquerycalendar@spam.raszi.hu). */
jQuery(function($){
  $.datepicker.regional['hu'] = {
    closeText: 'bezárás',
    prevText: '&laquo;&nbsp;vissza',
    nextText: 'előre&nbsp;&raquo;',
    currentText: 'ma',
    monthNames: ['Január', 'Február', 'Március', 'Április', 'Május', 'Június',
    'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December'],
    monthNamesShort: ['Jan', 'Feb', 'Már', 'Ápr', 'Máj', 'Jún',
    'Júl', 'Aug', 'Szep', 'Okt', 'Nov', 'Dec'],
    dayNames: ['Vasámap', 'Hétfö', 'Kedd', 'Szerda', 'Csütörtök', 'Péntek', 'Szombat'],
    dayNamesShort: ['Vas', 'Hét', 'Ked', 'Sze', 'Csü', 'Pén', 'Szo'],
    dayNamesMin: ['V', 'H', 'K', 'Sze', 'Cs', 'P', 'Szo'],
    dateFormat: 'yy-mm-dd', firstDay: 1,
    isRTL: false};
});
jQuery(function($){
  $.timepicker.regional['hu'] = {
    timeOnlyTitle: 'Időpont kiválasztása',
    timeText: 'Idő',
    hourText: 'Óra',
    minuteText: 'Perc',
    secondText: 'Mperc',
    millisecText: 'Miliszekundum',
    timezoneText: 'Időzóna',
    currentText: 'Most',
    closeText: 'Kész',
    timeFormat: 'HH:mm',
    amNames: ['AM', 'A'],
    pmNames: ['PM', 'P'],
    isRTL: false
  };
});
{/literal}

{if $language == 'hu'}
{literal}
var RecaptchaOptions = {
  custom_translations : {
    instructions_visual: "Írja be a fent látható szavakat:",
    instructions_audio:  "Hang alapú feladvány",
    play_again:          "Újrajátszás",
    cant_hear_this:      "Letöltés MP3 formátumban",
    visual_challenge:    "Képi feladvány",
    audio_challenge:     "Hang alapú feladvány",
    refresh_btn:         "Új szavakat kérek",
    help_btn:            "Súgó",
    incorrect_try_again: "Hiba. Próbálja újra!"
  },
  lang: 'hu'
};
{/literal}
{/if}
