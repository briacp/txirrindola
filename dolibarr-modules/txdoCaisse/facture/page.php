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

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

// Load traductions files requiredby by page
//$langs->load("companies");
//$langs->load("other");

llxHeader('',"Facture Txirrind'Ola",'','','',0,array('txdoCaisse/js/pos.js'), array('txdoCaisse/css/facture.css') );
print load_fiche_titre("Facture Txirrind'Ola");

/*
dol_fiche_head();
print <<<_HTML_
<p>Création de facture simplifiée (à la manière du Point de Vente Dolibarr)<b><a 
href="page.php?adhid=1552">LIEN DEBUG</a></b></p>
_HTML_;
dol_fiche_end();
*/

$form=new Form($db);

$adhid  = GETPOST('adhid','int');

$adh = new Adherent($db);
$result = $adh->fetch($adhid);
$facturestatic = new Facture($db);
$client = new Client($db);
$result = $client->fetch($adh->fk_soc);


print "<div class='refid'>".
      "<small class='adherentid'>" . 
       $adhid .  "</small> " . $adh->firstname . ' ' . $adh->lastname . '</div>';
print "<div class='refidno'>" . $adh->address . " " . $adh->zip . " "  . $adh->town . "</div>";
print '<input type="hidden" id="adhid" value="' . $adhid . '"/>';
print '<input type="hidden" id="cliid" value="' . $client->id . '"/>';

print <<<_HTML_
<fieldset id="articles">
    <legend class="titre1">Articles</legend>
_HTML_;

$resql = $db->query("SELECT * FROM llx_product ORDER BY ref");
if ($resql) {
  print '<label for="product">Article :</label> <select id="product" name="product"><option data-ref="--">-- Choisissez un article --</option>';
  $num = $db->num_rows($resql);
  $i = 0;
  while ($i < $num) {
      $objp = $db->fetch_object($resql);
      print "<option id='". $objp->rowid ."' data-price='" . $objp->price . "' data-ref='" . $objp->ref . "' data-libelle='" . $objp->label . "'>";
      print $objp->ref . " - ";
      print $objp->label . " (";
      print price($objp->price) . ")";
      print "</option>";
      $i++;
  }
  print '</select>';
}

print <<<_HTML_
    <label for="qte">Qté :</label> <input type="text"   id="qte"  name="qte" size="4" value="1"/>
    <input type="button" id="add"  name="add" value=" Ajouter article " class="butAction"/>
</fieldset>
<fieldset id="panier">
    <legend class="titre1">Panier</legend>
    <table class="noborder centpercent list">
      <thead>
        <tr class="entete">
          <th class="liste_titre"></th>
          <th class="liste_titre" style="text-align:left;">Ref</th>
          <th class="liste_titre" style="text-align:left;">Libellé</th>
          <th class="liste_titre">PU</th>
          <th class="liste_titre">Qté</th>
          <th class="liste_titre">%&nbsp;Remise</th>
          <th class="liste_titre">Total</th>
      </thead>
      <tbody id="items">
      </tbody>
    </table>
</fieldset>

<table class="mode_paiement">
<tr>
  <th>Liquide</th>
  <th>Eusko</th>
  <th>Eusko Num.</th>
  <th>Chèque</th>
</tr>
<tr>
<td><input type="text" class="inputPaiement" size="4" id="LIQ" name="LIQ" value="" /></td>
<td><input type="text" class="inputPaiement" size="4" id="EUSKO" name="EUSKO" value="" /></td>
<td><input type="text" class="inputPaiement" size="4" id="EUSKON" name="EUSKON" value="" /></td>
<td><input type="text" class="inputPaiement" size="4" id="CHQ" name="CHQ" value="" /></td>
</tr>
</table>

<div id="grand_total">
  Total ticket&nbsp;: <span id="total"></span><br/>
  <div id="recurendu">
    <label for="recu">Reçu</label>&nbsp;:   <input type="text" disabled readonly value="" id="recu" size="5"/><br/>
    <label for="rendu">Rendu</label>&nbsp;: <input type="text" disabled readonly value="" id="rendu" size="5"/>
  </div>

</div>

<div class="tabsAction">
  <input type="button" id="resetFacture"  name="resetFacture" value=" Annuler la facture " class="butAction butActionDelete"/>
  <input type="button" id="validFacture"  name="validFacture" value=" Valider la facture " class="butAction"/>
</div>
_HTML_;

// End of page
llxFooter();
$db->close();
