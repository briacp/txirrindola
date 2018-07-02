<?php
/*
 * Copyright (C) 2016 Briac Pilpré  <briacp@gmail.com>
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

/**
 *      \file       htdocs/txirrindola/mailjet.php
 *
 *      Menu à ajouter :
 *        Accueil / Configuration / Menus
 *        onglet Edition menu
 *
 *        Type: Gauche
 *        Identifiant du menu parent : fk_mainmenu=members&fk_leftmenu=setup
 *        Titre: Export MailJet Adhérents
 *        Lien: /txirrindolla/mailjet_page.php
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

// Vérification des droits d'accès (le process meurt s'il n'est pas autorisé)
$res = restrictedArea($user, 'export');

if ($_POST['export_volontaires']) {
  liste_volontaires($db);
}
else {
  liste_adherents($db);
}

function liste_adherents($db) {
    $order_by = "";
    $from_extra = "";
    $date_export      = cleanupDate($_POST['date_validite']);
    
    // a_jour                              - tous les adhérents à jour de cotisation le 1 er du mois,
    if ($_POST['export_type'] == "a_jour") {
      $export_type = "adherents_a_jour";

      // La liste des adhérents à jour est la liste des adhérents à jour de 
      // cotisation le jour de la date de validité.
      // Par exemple si date de validité = 01/02/2017 la liste contient les 
      // membres dont la date de fin d'adhésion est supérieur ou égal au 
      // 01/02/2017 et dont la date d'adhésion est supérieur ou égal au 
      // 02/02/2016 (double contrôle)

      $where_clause = <<<"_SQL_"
  c.datef >= '$date_export' AND 
  c.dateadh <  '$date_export' AND
  c.dateadh >= DATE_ADD(DATE_SUB('$date_export', INTERVAL 1 YEAR), INTERVAL 1 DAY)
_SQL_;
      $order_by = "ORDER BY c.datef ASC";
    }
    // non_a_jour                          - tous les adhérents non à jour depuis les 3 derniers mois,
    elseif ($_POST['export_type'] == 'non_a_jour') {
      $export_type = "adherents_non_a_jour";
      //$where_clause = "c.datef >= DATE_SUB('$date_export', INTERVAL 3 MONTH) AND c.datef < '$date_export'";
      //$where_clause = "a.datefin <= DATE_ADD('$date_export', INTERVAL 3 MONTH) AND a.datefin > '$date_export'";
      $where_clause = "a.datefin >= DATE_SUB('$date_export', INTERVAL 3 MONTH) AND a.datefin < '$date_export'";
      $order_by = "ORDER BY c.dateadh ASC";
    }
    // nouveaux                            - tous les nouveaux adhérents du mois dernier.
    elseif ($_POST['export_type'] == 'nouveaux') {
      $export_type = "adherents_nouveaux";

      // La date de validité doit être le 1er jour du mois en cours par défaut 
      // (exemple : pour le 01/02/2017, nous aurons tous les adhérents de 
      // janvier, du 01/01/2017(inclus) au 02/02/2017 (non inclus). Si je mets 
      // le 05/03/2017, j'aurai tous les nouveaux du 05/02/2017 au 05/03/2017 
      // (non inclus).

      $where_clause = "c.dateadh >= DATE_SUB('$date_export', INTERVAL 1 MONTH) AND c.dateadh < '$date_export'";
      $order_by = "ORDER BY c.dateadh ASC";
    }
    else {  // Tous
      $export_type = "tous_adherents";
      $where_clause = "a.datevalid is not null";
      $where_clause = "1=1";
      $order_by = "ORDER BY a.rowid ASC";
    }

    do_sql($db, $export_type, $where_clause, $date_export, $order_by, $from_extra);
}


function liste_volontaires($db) {
    $order_by = "";
   // Cleanup dates from form
   $debut_volontaire = cleanupDate($_POST['debut_volontaire_']);
   $fin_volontaire   = cleanupDate($_POST['fin_volontaire_']);

   $interval_volontaire = "(c.datef between '$debut_volontaire' AND '$fin_volontaire')\nAND ex.fk_object=a.rowid\n";
   // LEFT JOIN  llx_cotisation c ON c.fk_adherent = a.rowid
   $from_extra = ", llx_adherent_extrafields ex LEFT JOIN llx_adherent ja ON ex.fk_object = ja.rowid";

   if ($_POST['type_volontaire'] == 'atelier') {  // Volontaires Atelier
     $export_type = "volontaires_orga";
     $where_clause = "$interval_volontaire AND ex.aide_atelier = 1";
   }
   elseif ($_POST['type_volontaire'] == 'orga') {     // Volontaires Orga
     $export_type = "volontaires_orga";
     $where_clause = "$interval_volontaire AND ex.aide_orga = 1";
   }
   elseif ($_POST['type_volontaire'] == 'admin') { // Volontaires Admin
     $export_type = "volontaires_admin";
     $where_clause = "$interval_volontaire AND ex.aide_admin = 1";
   }
   else {                                         // Tous
     $export_type = "volontaires";
     $where_clause = "$interval_volontaire AND (ex.aide_admin = 1 OR ex.aide_atelier = 1 OR ex.aide_orga = 1)";
   }


   do_sql($db, $export_type, $where_clause, $debut_volontaire, $order_by, $from_extra);
}

function do_sql($db, $export_type, $where_clause, $timestamp, $order_by, $from_extra) {
        $column_headers = array( 
                'ID','NOM','PRENOM',
                'ADRESSE','CODE_POSTAL','VILLE',
                'TEL_PRO','TEL_PERSO','TEL_PORTABLE','EMAIL',
                'DATE_ADHESION','DATE_FIN_ADHESION');

        $db_prefix = MAIN_DB_PREFIX;

        // fetch the data
        // id,nom,prenom,adresse,code_postal,ville,tel_pro,tel_perso,tel_portable,email,date_adhesion,date_fin_adhesion
        // co.label => Pays
        $sql = <<<"_SQL_"
        SELECT
          a.rowid, a.lastname, a.firstname,
          a.address, a.zip, a.town, 
          a.phone, a.phone_perso, a.phone_mobile, a.email,
          DATE_FORMAT(c.dateadh,'%d/%m/%Y') as date_adhesion,
          DATE_FORMAT(a.datefin,'%d/%m/%Y') as date_fin

        FROM llx_adherent a
          LEFT JOIN  (
            SELECT fk_adherent, dateadh, datef FROM llx_subscription ORDER BY dateadh DESC
          ) c ON c.fk_adherent = a.rowid
        $from_extra

        WHERE
        {$where_clause}

        GROUP BY a.rowid

        $order_by
_SQL_;

        dol_syslog("mailjet::export_type: " . $export_type);
        dol_syslog("mailjet::SQL [" . $sql . "]");

        // output headers so that the file is downloaded rather than displayed
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename={$timestamp}_export_${export_type}.csv");


        // create a file pointer connected to the output stream
        $output = fopen('php://output', 'w');

        // output the column headings
        fputcsv($output, $column_headers);

        // loop over the rows, outputting them
        $resql = $db->query($sql);
        if ($resql) {
            $num = $db->num_rows($resql);
            $i = 0;


            while ($i < $num)
            {
                $objp = $db->fetch_object($result);

                $address = $objp->address;
                // Replace newlines by space
                $address = trim(preg_replace('/\s+/', ' ', $address));

                $db_columns = array(
                    $objp->rowid, $objp->lastname, $objp->firstname,
                    $address, $objp->zip, $objp->town,
                    cleanupPhone($objp->phone), cleanupPhone($objp->phone_perso), cleanupPhone($objp->phone_mobile), 
                    $objp->email,
                    $objp->date_adhesion, $objp->date_fin
                );

                fputcsv($output, $db_columns);
                $i++;
            }
        }

        dol_syslog("MailJet::rows " . $i);


        $db->close();
}


/** Nettoyage du numéro de téléphone. */
function cleanupPhone($phone)
{
    // On retire d'abord tout ce qui n'est pas un chiffre
    $phone = preg_replace('/\D+/', '', $phone);

    // Vérification du nombre de chiffre dans le téléphone en prenant en
    // compte les cas +33601234578
    if (strlen($phone) == 10) {
        $phone = preg_replace('/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', '$1.$2.$3.$4.$5', $phone);
    } elseif (strlen($phone) == 11) {
        $phone = preg_replace('/(\d{2})(\d)(\d{2})(\d{2})(\d{2})(\d{2})/', '+$1 $2.$3.$4.$5.$6', $phone);
    }

    return $phone;
}

function cleanupDate($formDate) {
  $matches = array();
  if (preg_match("/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/", $formDate, $matches) == 1) {
    return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
  }
  else {
    dol_syslog("TxDo\tBad date format: \"" . $$formDate . '"'); 
    return null;
  }
}


