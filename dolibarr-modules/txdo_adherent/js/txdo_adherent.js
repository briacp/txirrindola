/*jshint bitwise:true, browser:true, jquery:true, camelcase:true, curly:true, devel:false, eqeqeq:false, forin:true, immed:true, indent:4, newcap:true, noarg:true, noempty:true, nonew:true, prototypejs:true, quotmark:true, regexp:false, strict:true, trailing:true, undef:true, unused:true */
$(document).ready(function () {
    'use strict';

    console.log('txdo_adherent.js loaded');

    // Fiche Adhérent - Affichage du rowid
    var tabAdh = $('a[href*="adherents/card.php?rowid="][class*="tab"]');
    if (tabAdh.length > 0) {
        var add = tabAdh.attr('href').match(/adherents\/card.*?\.php\?rowid=(\d+)/);
        var rowId = add[1];
        $('div.refid').prepend('<small class="adherentid" onclick="document.location.href=\'/adherents/card.php?rowid=' + rowId + '\'" style="cursor:pointer;border-radius: 25px;color:#FFF;background-color:#866;padding: 2px 13px;margin-right: 6px;text-align:center;">' + rowId + '</small> ');
    }

    // Formulaire de cotisation -- Calcul de 1 an après la date d'adhésion
    if ($('form[name="cotisation"]') && $('#re')) {
        calcExpiration();
    }

    function calcExpiration() {
        var updateEnd = function () {
            var startDate = $('#re').val();

            if (!startDate) {
                return;
            }

            var dmy = startDate.split('/');
            dmy[2] = parseInt(dmy[2]) + 1;

            var end = new Date(dmy[2], dmy[1] - 1, dmy[0]);
            end.setDate(end.getDate() - 1);

            dmy = [
            ('00' + end.getDate()).slice(-2), ('00' + (end.getMonth() + 1)).slice(-2),
            end.getFullYear()];

            $('#endday').val(dmy[0]);
            $('#endmonth').val(dmy[1]);
            $('#endyear').val(dmy[2]);
            $('#end').val(dmy.join('/'));
        };

        // Lien "Maintenant"
        $('#reButtonNow').on('click', updateEnd);

        // Hijack dpClickDay(2017,parseInt('12',10),26,'dd/MM/yyyy')
        if (window.dpClickDay) {
            var olddpClickDay = window.dpClickDay;
            dpClickDay = function (y, m, d, f) {
                olddpClickDay(y, m, d, f);
                updateEnd();
            };
        }

        $('#reButton').on('click', updateEnd);
        $('#re').on('blur', updateEnd);
        $('#re').on('change', updateEnd);

        updateEnd();
    }
});
