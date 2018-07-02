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
// $sapi_type = php_sapi_name();
// $script_file = basename(__FILE__);
// $path=dirname(__FILE__).'/';
// 
// // Test if batch mode
// if (substr($sapi_type, 0, 3) == 'cgi') {
//     echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
// 	exit(-1);
// }

require('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php');
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");

// Vérification des droits d'accès (le process meurt s'il n'est pas autorisé)
$res = restrictedArea($user, 'import');

/* Creation en masse de Tiers à partir des Adhérents */
ob_end_flush();
echo "Nettoyage des adhérents\n";

$sql = "SELECT rowid FROM llx_adherent";

// loop over the rows, outputting them
$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    $i = 0;

    echo $num . " tiers a creer.\n";
    ob_flush();
    while ($i < $num)
    {
        $rowid = $db->fetch_object($resql);

        $object = new Adherent($db);
        $object->fetch($rowid->rowid);

        $adherent = cleanupMember($object);

        $sql = "UPDATE llx_adherent SET";
        $sql .= "  firstname = " . ($adherent->firstname ? "'" . $adherent->db->escape($adherent->firstname) . "'" : "null");
        $sql .= ", lastname=" . ($adherent->lastname ? "'" . $adherent->db->escape($adherent->lastname) . "'" : "null");
        $sql .= ", login=null";
        $sql .= ", pass=null";
        $sql .= ", zip=" . ($adherent->zip ? "'" . $adherent->db->escape($adherent->zip) . "'" : "null");
        $sql .= ", town=" . ($adherent->town ? "'" . $adherent->db->escape($adherent->town) . "'" : "null");
        $sql .= ", country=" . ($adherent->country_id > 0 ? "'" . $adherent->country_id . "'" : "null");
        $sql .= ", state_id=" . ($adherent->state_id > 0 ? "'" . $adherent->state_id . "'" : "null");
        $sql .= ", email='" . $adherent->email . "'";
        $sql .= ", phone=" . ($adherent->phone ? "'" . $adherent->db->escape($adherent->phone) . "'" : "null");
        $sql .= ", phone_perso=" . ($adherent->phone_perso ? "'" . $adherent->db->escape($adherent->phone_perso) . "'" : "null");
        $sql .= ", phone_mobile=" . ($adherent->phone_mobile ? "'" . $adherent->db->escape($adherent->phone_mobile) . "'" : "null");
        $sql .= " WHERE rowid = " . $adherent->id;

        $rsql = $db->query($sql);
        echo "* " . $i  . "\n";
        ob_flush();

        $i++;
    }
}

    /** Nettoyage d'une fiche Adhérent */
    function cleanupMember($adherent)
    {
        // Pas de login ni de pass
        $adherent->login = null;
        $adherent->pass  = null;

        // Mise en majuscule du nom de famille
        if (!empty($adherent->lastname)) {
            // Utilisation de mb_strtoupper au lieu de strtoupper pour préserver les accents.
            $adherent->lastname = mb_strtoupper($adherent->lastname, 'utf-8');
        }

        // Mise en majuscule du prénom (en faisant attention aux noms composés)
        if (!empty($adherent->firstname)) {
            $adherent->firstname = mb_convert_case($adherent->firstname, MB_CASE_TITLE, 'utf-8');
        }

        // Ville en majuscule
        if (!empty($adherent->town)) {
            $town = $adherent->town;

            $town = mb_strtoupper($town, 'utf-8');

            // Normalisation des ST en SAINT
            $town = preg_replace('/\bST\b/', 'SAINT', $town);
            $town = preg_replace('/\bSTE\b/', 'SAINTE', $town);

            $adherent->town = $town;
        }

        // Renseignement du département automatique pour le 64 et 40.

        $dept_cp = substr($adherent->zip, 0, 2);
        // update llx_adherent_TEST set state_id = 66 where SubString(zip,1,2) = 64 and country = 1
        if ($dept_cp == 64 && $adherent->country_id == 1) {
            $adherent->state_id = 66;
        }
        // update llx_adherent_TEST set state_id = 42 where SubString(zip,1,2) = 40
        else if ($dept_cp == 40 && $adherent->country_id == 1) {
            $adherent->state_id = 42;
        }

        // E-mail en minuscule
        if (!empty($adherent->email)) {
            $adherent->email = mb_strtolower($adherent->email, 'utf-8');
        }

        // Normalisation des numéros de téléphone
        if (!empty($adherent->phone)) {
            $adherent->phone = cleanupPhone($adherent->phone);
        }
        if (!empty($adherent->phone_perso)) {
            $adherent->phone_perso = cleanupPhone($adherent->phone_perso);
        }
        if (!empty($adherent->phone_mobile)) {
            $adherent->phone_mobile = cleanupPhone($adherent->phone_mobile);
        }

        return $adherent;
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


echo "Termine\n";

ob_start();


$db->close();
