<?php
/* Copyright (C) 2017 Briac Pilpré <briacp@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
$res = 0;

if (!$res && file_exists("../../main.inc.php")) $res = @include '../../main.inc.php';

if (!$res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

header('Content-Type: application/json');

// see cashdesk/validation_verif.php ~156

$data = ['ajax' => 1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $now = dol_now();
    $adhid           = GETPOST('adhid');
    $cliid           = GETPOST('cliid');
    $f_uid           = GETPOST('uid');
    $items           = GETPOST('items');
    $total_panier    = GETPOST('total');
    $type_paiement   = GETPOST('type');

    $recu_liq       = GETPOST('LIQ'); // Montant perçu en liquide (euro)
    $recu_chq       = GETPOST('CHQ'); // Montant perçu en chèque
    $recu_eusko     = GETPOST('EUSKO'); // Montant perçu en Eusko
    $recu_eusko_num = GETPOST('EUSKON'); // Montant perçu en Eusko numerique
    $recu_total     = $recu_liq + $recu_chq + $recu_eusko + $recu_eusko_num;
    
    dol_syslog("Txdo::facture: LIQ=" . $recu_liq . ", CHQ=" . $recu_chq . ", EUSKO=" . $recu_eusko . ", EUSKO_NUM=" . $recu_eusko_num . ", recu_total=" . $recu_total . ", total_panier=" . $total_panier);

    // On devrait avoir rendu de la monnaie si on a trop payé
    if ($recu_total > $total_panier) {
      $rendu =  $recu_total - $total_panier;
    
      dol_syslog("Txdo::facture::rendu: " . $rendu);

      // TODO - Décrémenter le LIQ ou EUSKO pour que le total arrive à 0 (sinon on verra toujours que l'adhérent à payer trop)
      if ($recu_liq > $rendu) {
          $recu_liq = $recu_liq - $rendu;
          dol_syslog("Txdo::facture::rendu LIQ: " . $recu_liq);
      }
      elseif ($recu_eusko > $rendu) {
          $recu_eusko = $recu_eusko - $rendu;
          dol_syslog("Txdo::facture::rendu EUSKO: " . $recu_liq);
      }

      $recu_total = $recu_liq + $recu_chq + $recu_eusko;
      if ($recu_total != $total_panier) {
          dol_syslog("Txdo::facture::recu/total:error :" . $recu_total . "/" . $total_panier);
      }
//      $recu_total = $total_panier;
    }

    $adh = new Adherent($db);
    $result = $adh->fetch($adhid);

    $client = new Client($db);
    $result = $client->fetch($adh->fk_soc);

    $db->begin();

    dol_syslog("Txdo::facture::post: " . $f_uid);

    //=========================================================================
    // Création de la facture
    //=========================================================================
    $invoice = new Facture($db);

    // Loop on each line into cart

    foreach($items as $item) {
        $item_total_price = $item['pxu'] * $item['qte'];
        $item_total_price = $item_total_price - ( $item['remise'] / 100 * $item_total_price);

        // $tmp = getTaxesFromId($tab_liste[$i]['fk_tva']);
        // $vat_rate = $tmp['rate'];
        // $vat_npr = $tmp['npr'];

        $invoiceline = new FactureLigne($db);
        $invoiceline->fk_product = $item['id'];
        $invoiceline->desc = $item['libelle'];
        $invoiceline->qty = $item['qte'];

        $invoiceline->remise_percent=$item['remise'];

        $invoiceline->price = $item['pxu'];
        $invoiceline->subprice = $item['pxu'];
        $invoiceline->tva_tx = 0;
        $invoiceline->info_bits = 0;
        $invoiceline->total_ht = $item_total_price;
        $invoiceline->total_ttc = $item_total_price;
        $invoiceline->total_tva = 0;
        $invoiceline->total_localtax1 = 0;
        $invoiceline->total_localtax2 = 0;
        $invoice->lines[] = $invoiceline;
        dol_syslog("Txdo::facture::invoice: " . $item['id'] . ">" . $item_total_price);
    }

    $invoice->socid = $cliid;
    $invoice->date_creation = $now;
    $invoice->date = $now;
    $invoice->date_lim_reglement = 0;
    $invoice->total_ht = $total_panier;
    $invoice->total_tva = 0;
    $invoice->total_ttc = $total_panier;

    //if ($remise_accordee) {
    //  $invoice->note_private= "Remise accordée:" . $remise_accordee . "%";
    //}

    $invoice->cond_reglement_id = 0;
    $invoice->mode_reglement_id = dol_getIdFromCode($db, $type_paiement, 'c_paiement');
    $resultcreate = $invoice->create($user, 0, 0);
    $invoice_id = $invoice->id;
    dol_syslog("Txdo::facture::invoice_id: " . $invoice_id);

    $invoice->type = Facture::TYPE_STANDARD;
    $numInvoice = $invoice->getNextNumRef($company);

    //=========================================================================
    // Création des paiements
    //=========================================================================
    dol_syslog("Txdo::facture::preLIQ=" . $recu_liq);
    if ($recu_liq)   { createPayment($db, $user, $invoice, $numInvoice, 'LIQ',   $recu_liq);   }
    dol_syslog("Txdo::facture::preCHQ=" . $recu_chq);
    if ($recu_chq)   { createPayment($db, $user, $invoice, $numInvoice, 'CHQ',   $recu_chq);   }
    dol_syslog("Txdo::facture::preEUSKO=" . $recu_eusko);
    if ($recu_eusko) { createPayment($db, $user, $invoice, $numInvoice, 'EUSKO', $recu_eusko); }
    dol_syslog("Txdo::facture::preEUSKON=" . $recu_eusko_num);
    if ($recu_eusko_num) { createPayment($db, $user, $invoice, $numInvoice, 'EUSKON', $recu_eusko_num); }

    dol_syslog("Txdo::facture::postPaiement");
    // Facture marquee comme payee si tout a ete regle
    if ($recu_total >= $total_panier) {
      $result = $invoice->set_paid($user);
      $data += ['paid' => true];
    }
    else {
      $data += ['paid' => false];
    }

    $db->commit();
    $data+= ['grand_total' => $total_panier, 'status' => 'created', 'invoice_id' => $invoice_id, 'invoice_num' => $numInvoice];
}
else {
    $data+= ['status' => 'error'];
}

echo json_encode($data);
$db->close();


function createPayment($db, $user, $invoice, $numInvoice, $type_paiement, $montant_paiement) {
    $bankaccountid = 1;

    dol_syslog("Txdo::facture::createPayment: " . $type_paiement . "=" . $montant_paiement);
    $payment = new Paiement($db);
    //$paiement->multicurrency_amounts = $multicurrency_amounts;   // Array with all payments dispatching
    $payment->datepaye = $now;
    $payment->bank_account = $bankaccountid;
    $payment->amounts[$invoice->id] = $montant_paiement;
    $payment->paiementid = dol_getIdFromCode($db, $type_paiement, 'c_paiement');
    $payment->num_paiement = '';
    $payment->note='Paiement facture '.$numInvoice;
    $paiement_id = $payment->create($user);
    dol_syslog("Txdo::facture::createPayment:id" . $paiement_id);
    if ($paiement_id > 0) {
        if (!$error) {
            $result = $payment->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $bankaccountid, '', '');
            if (!$result > 0) {
                $errmsg = $paiement->error;
                $error++;
            }
            else {
                $resultvalid = $invoice->validate($user, $numInvoice, 0);

            }
        }
        $db->commit();
    }

    return $numInvoice;
}
