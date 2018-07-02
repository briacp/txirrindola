/*jshint bitwise:true, browser:true, jquery:true, camelcase:true, curly:true, devel:false, eqeqeq:false, forin:true, immed:true, indent:4, newcap:true, noarg:true, noempty:true, nonew:true, prototypejs:true, quotmark:true, regexp:false, strict:true, trailing:true, undef:true, unused:true */

/* Point Of Sale */
(function () {
    'use strict';
    $(document).ready(function () {

        var price = function (p) {
            return p.toLocaleString('fr-FR', {
                style: 'currency',
                currency: 'EUR'
            });
        };

        var getUUID = function () {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g,

            function (c) {
                var r = Math.random() * 16 | 0,
                    v = c == 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });

        };

        var getPanier = function () {
            return $('#panier').data('panier');
        };

        var setPanier = function (panier) {

            // Recalcul du total
            var total = 0;
            for (var i = 0; i < panier.items.length; i++) {
                var item = panier.items[i];
                var totalItem = item.pxu * item.qte;
                totalItem -= (item.remise / 100) * totalItem;
                total += totalItem;
            }

            panier.EUSKO = $('#EUSKO').val();
            panier.EUSKON = $('#EUSKON').val();
            panier.LIQ = $('#LIQ').val();
            panier.CHQ = $('#CHQ').val();

            panier.total = total;

            $('#panier').data('panier', panier);
            $('#total').html(price(panier.total));
        };

        var initFacture = function () {
            setPanier({
                uid: getUUID(), // Avoid double invoices
                recu: 0,
                total: 0,
                EUSKO: 0,
                EUSKON: 0,
                LIQ: 0,
                CHQ: 0,
                items: []
            });
            $('#items').html('<tr id="empty_basket"><td colspan="6">Panier vide.</td></tr>');
            $('#grand_total').hide();
            $('#recu').val('');
            $('#rendu').val('');
            $('#remise').val('');
            $('#LIQ').val('');
            $('#EUSKO').val('');
            $('#EUSKON').val('');
            $('#CHQ').val('');
        };

        initFacture();

        $('.inputPaiement').keyup(function () {
            var panier = getPanier();
            var recuLiq = $('#LIQ').val();
            var recuChq = $('#CHQ').val();
            var recuEusko = $('#EUSKO').val();
            var recuEuskoNum = $('#EUSKON').val();

            panier.recu = (recuLiq * 1) + (recuChq * 1) + (recuEusko * 1) + (recuEuskoNum * 1);
            $('#recu').val(price(panier.recu));
            setPanier(panier);

            var rendu = panier.recu - panier.total;
            if (rendu >= 0) {
                $('#rendu').val(price(rendu));
            } else {
                $('#rendu').val('--');
            }
        });

        $('#resetFacture').click(function () {
            if (confirm('Etes-vous sûr de vouloir abandonner cette facture ?')) {
                initFacture();
            }
        });

        $('#validFacture').click(function () {
            console.log('Validation Facture');
            var panier = getPanier();
            panier.adhid = $('#adhid').val();
            panier.cliid = $('#cliid').val();
            console.log(panier);
            $.post('facture.php', panier, function (data) {
                alert('Facture ' + data.invoice_num + ' (#' + data.invoice_id + ') créée avec succès.');
                /* */
                initFacture();
                // Redirect vers /compta/facture.php?facid={data.invoice_id}
                window.location.href = '/compta/facture.php?facid=' + data.invoice_id;
                /* */
            });
        });

        $('#product').change(function () {
            $('#qte').val(1);
        });

        $('#add').click(function () {
            $('#empty_basket').hide();
            $('#grand_total').show();

            var sel = $('#product option:selected');

            var ref = sel.data('ref');

            if (ref == '--') {
                return;
            }

            var pxUnitaire = (1 * sel.data('price'));
            var qte = $('#qte').val() * 1;
            if (qte === 0) {
                qte = 1;
            }
            var totalItem = pxUnitaire * qte;

            var remise = 0;
            var uid = getUUID();
            var item = {
                id: sel.attr('id'),
                uid: uid,
                ref: ref,
                qte: qte,
                remise: remise,
                pxu: pxUnitaire,
                total: totalItem
            };

            var panier = getPanier();
            panier.total = panier.total + totalItem;

            panier.items.push(item);

            var rowHtml = [
                '<tr class="itemRow" data-uid="' + uid + '">',
                '    <td><input type="button" data-uid="' + uid + '" value=" - "  class="removeRow butAction butActionDelete"/></td>',
                '    <td>' + ref + '</td>',
                '    <td style="width:80%">' + sel.data('libelle') + '</td>',
                '    <td><input size="5" type="text" data-row-uid="' + uid + '" class="item" name="pxu"    value="' + pxUnitaire + '"/></td>',
                '    <td><input size="3" type="text" data-row-uid="' + uid + '" class="item" name="qte"    value="' + qte + '"/></td>',
                '    <td><input size="3" type="text" data-row-uid="' + uid + '" class="item" name="remise" value="' + remise + '"/></td>',
                '    <td><input size="5" type="text" data-row-uid="' + uid + '"              name="total"  value="' + price(totalItem) + '" readonly disabled /></td>',
                '</tr>'];

            var row = $(rowHtml.join(''));
            row.data('item', item);
            row.appendTo('#items');

            $('.item').keyup(function () {
                var panier = getPanier();
                var uid = $(this).data('row-uid');
                console.log('Changement ligne "' + uid + '"', panier);
                for (var i = 0; i < panier.items.length; i++) {
                    var item = panier.items[i];
                    if (item.uid == uid) {
                        var newPxu = $('input[data-row-uid="' + uid + '"][name="pxu"]').val();
                        var newQte = $('input[data-row-uid="' + uid + '"][name="qte"]').val();
                        var newRemise = $('input[data-row-uid="' + uid + '"][name="remise"]').val();
                        var newTot = $('input[data-row-uid="' + uid + '"][name="total"]');

                        item.pxu = newPxu * 1;
                        item.qte = newQte * 1;
                        item.remise = newRemise * 1;

                        item.total = item.pxu * item.qte;
                        item.total -= (item.remise / 100) * item.total;

                        newTot.val(price(item.total));
                        setPanier(panier);
                        break;
                    }
                }
            });

            $('#items tr:odd').addClass('odd');
            $('#items tr:not(.odd)').addClass('even');

            $('.removeRow').click(function () {
                var uid = $(this).data('uid');
                console.log('Remove row ' + uid);
                var panier = getPanier();
                var i = panier.items.length;
                while (i--) {
                    if (panier.items[i].uid == uid) {
                        panier.total -= panier.items[i].total;
                        panier.items.splice(i, 1);
                    }
                }
                setPanier(panier);
                $('tr[data-uid="' + uid + '"]').remove();
            });

            setPanier(panier);
        });

        $('.btnTypePaiement').click(function () {
            var panier = getPanier();
            panier.type = $(this).data('type');
            setPanier(panier);
        });
    });
})();
