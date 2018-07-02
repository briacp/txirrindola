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
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

// Load traductions files requiredby by page
//$langs->load("companies");
//$langs->load("other");

llxHeader('',"Adhérent Txirrind'Ola",'');
print load_fiche_titre("Adhérent Txirrind'Ola");

dol_fiche_head();

print <<<_HTML_
<p>Page adhérent contenant les informations synthétiques sur un Adhérent en tant 
qu'Adhérent, Tiers et Utilisateur (cotisations, factures, etc.)</p>
<p>Le temps de tester, on peut choisir les ids à prendre en compte en les passant dans l'URL, en vrai les 
ids découleront de l'id de l'utilisateur connecté.
<b><a 
href="page.php?userid=6&socid=1&adhid=1552">LIEN DEBUG</a></b></p>
_HTML_;

dol_fiche_end();

$form=new Form($db);

// XXX Le temps de tester, on peut choisir les ids à prendre en compte, en vrai 
// les ids découleront de l'id de l'utilisateur connecté
// ex: https://test.dolibarr.txirrindola.org/txirrindola/adherent/page.php?userid=6&socid=10&adhid=1552
$socid  = GETPOST('socid','int');
$adhid  = GETPOST('adhid','int');
$userid = GETPOST('userid','int');

//$socid  = $user->socid;
//$adhid  = $user->fk_member;
//$userid = $user->id;


/*****************************************************************************/
/*                                                                           */
/* Synthèse Adhérent                                                         */
/*                                                                           */
/*****************************************************************************/
print "<h2>Adhérent</h2>";
print "<b>#" . $adhid ."</b>";

$adh = new Adherent($db);
$result = $adh->fetch($adhid);

print "<p>" . $adh->firstname . " " . $adh->lastname . "</p>";
print "<p>" . $adh->address . " " . $adh->zip . " "  . $adh->town . "</p>";

/*****************************************************************************/
/*                                                                           */
/* Synthèse Utilisateur                                                      */
/*                                                                           */
/*****************************************************************************/
print "<h2>Utilisateur</h2>";
print "<b>#" . $userid . " (" .$user->login.")</b>";
print "<hr/>";

/*****************************************************************************/
/*                                                                           */
/* Synthèse Tiers                                                            */
/*                                                                           */
/*****************************************************************************/
print "<h2>Tiers</h2>";
print "<b>Tiers #" . $socid."</b><br/>";
print "Factures :<br/>";

// code suivant adapté de htdocs/comm/card.php --------------------------------
$facturestatic = new Facture($db);
$client = new Client($db);
$result = $client->fetch($socid);
$prefix = MAIN_DB_PREFIX;
$rowid = $object->id;
$conf_entity = $conf->entity;

$sql = <<<"_SQL_"
SELECT 
  f.rowid as facid, f.facnumber, f.type, f.amount,
  f.total as total_ht, f.tva as total_tva, f.total_ttc, 
  f.datef as df, f.datec as dc, f.paye as paye, 
  f.fk_statut as statut, s.nom, s.rowid as socid, SUM(pf.amount) as am 
FROM
  {$prefix}societe as s,
  {$prefix}facture as f
LEFT JOIN {$prefix}paiement_facture as pf ON f.rowid=pf.fk_facture 
WHERE 
  f.fk_soc = s.rowid AND 
  s.rowid  = $socid 
GROUP BY
  f.rowid, f.facnumber, f.type, f.amount, f.total, f.tva, f.total_ttc, 
  f.datef, f.datec, f.paye, f.fk_statut, s.nom, s.rowid
ORDER BY f.datef DESC, f.datec DESC
_SQL_;
$resql = $db->query($sql);

if ($resql) {
    $var = true;
    $num = $db->num_rows($resql);
    $i = 0;
    if ($num > 0) {
        $MAXLIST = 10;
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print '<td colspan="5"><table width="100%" class="nobordernopadding"><tr><td>Dernières factures</td><td align="right"><a href="' . DOL_URL_ROOT . '/compta/facture/list.php?socid=' . $object->id . '">' . $langs->trans("AllBills") . ' <span class="badge">' . $num . '</span></a></td>';
        print '<td width="20px" align="right"><a href="' . DOL_URL_ROOT . '/compta/facture/stats/index.php?socid=' . $object->id . '">' . img_picto($langs->trans("Statistics") , 'stats') . '</a></td>';
        print '</tr></table></td>';
        print '</tr>';
    }

    while ($i < $num && $i < $MAXLIST) {
        $objp = $db->fetch_object($resql);
        print "<tr>";
        print "<td class='nowrap'>";
        $facturestatic->id = $objp->facid;
        $facturestatic->ref = $objp->facnumber;
        $facturestatic->type = $objp->type;
        $facturestatic->total_ht = $objp->total_ht;
        $facturestatic->total_tva = $objp->total_tva;
        $facturestatic->total_ttc = $objp->total_ttc;
        print $facturestatic->getNomUrl(1);
        print '</td>';
        if ($objp->df > 0) {
            print '<td align="right" width="80px">' . dol_print_date($db->jdate($objp->df) , 'day') . '</td>';
        }
        else {
            print '<td align="right"><b>!!!</b></td>';
        }

        print '<td align="right" style="min-width: 60px">';
        print price($objp->total_ttc);
        print '</td>';
        if (!empty($conf->global->MAIN_SHOW_PRICE_WITH_TAX_IN_SUMMARIES)) {
            print '<td align="right" style="min-width: 60px">';
            print price($objp->total_ttc);
            print '</td>';
        }

        print '<td align="right" class="nowrap" style="min-width: 60px">' . ($facturestatic->LibStatut($objp->paye, $objp->statut, 5, $objp->am)) . '</td>';
        print "</tr>\n";
        $i++;
    }

    $db->free($resql);
    if ($num > 0) print "</table>";
}
// ----------------------------------------------------------------------------------------

print "<hr/>";

print "<hr/>";

// End of page
llxFooter();
$db->close();
