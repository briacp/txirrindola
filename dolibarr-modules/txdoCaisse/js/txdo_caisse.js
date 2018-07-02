/*jshint bitwise:true, browser:true, jquery:true, camelcase:true, curly:true, devel:false, eqeqeq:false, forin:true, immed:true, indent:4, newcap:true, noarg:true, noempty:true, nonew:true, prototypejs:true, quotmark:true, regexp:false, strict:true, trailing:true, undef:true, unused:true */
$(document).ready(function () {
    'use strict';

    console.log('txdo_caisse.js loaded');

    // Fiche Facture - Masquage de lignes inutiles dans le formulaire de création
    if ($('form[name="add"][action="/compta/facture.php"]').length > 0) {
        var t = $('table.border')[0];

        // Ref. facture    : 0 
        // Type de facture : 2
        // Remises         : 3
        // Compte bancaire : 7
        // Modèle          : 8
        var hideRows = [0, 2, 3, 7, 8];
        for (var i = 0; i < hideRows.length; i++) {
            $(t.rows[hideRows[i]]).hide();
        }

        if ($('#re') && $('#re').val().match(/^\s*$/)) {
            var date = new Date();
            var d = ('00' + date.getDate()).slice(-2);
            var m = ('00' + (1 + date.getMonth())).slice(-2);
            var y = date.getFullYear();
            $('#re').val(d + '/' + m + '/' + y);
            $('#reday').val(d);
            $('#remonth').val(m);
            $('#reyear').val(y);
        }
    }

    // Fiche Adhérent - Affichage du rowid
    var tabAdh = $('a[href*="adherents/card.php?rowid="][class*="tab"]');
    if (tabAdh.length > 0) {
        var add = tabAdh.attr('href').match(/adherents\/card.*?\.php\?rowid=(\d+)/);
        var rowId = add[1];

        // Fiche Adhérent - Bouton Création Facture (si Tiers)
        var lienTiers = $('a[href*="socid"]');
        if (lienTiers.length > 0) {
            var socId = lienTiers[0].href.match(/socid=(\d+)/)[1];
            if (socId) {
                var html = '<div class="inline-block divButAction"><a class="butAction" href="/txdo_caisse/facture/page.php?adhid=' + rowId + '">Nouvelle facture</a></div>';
                $(html).insertAfter($('.divButAction')[1]);
            }
        }
    }

});
