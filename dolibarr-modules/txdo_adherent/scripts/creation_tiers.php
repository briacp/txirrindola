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
// require_once($path."../../htdocs/master.inc.php");

require('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php');
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");

// Vérification des droits d'accès (le process meurt s'il n'est pas autorisé)
$res = restrictedArea($user, 'import');

/* Creation en masse de Tiers à partir des Adhérents */

ob_end_flush();
echo "Creation Tiers Auto\n";

$sql = "SELECT rowid FROM llx_adherent a WHERE fk_soc IS NULL";

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
        $company = new Societe($db);
        $result  = $company->create_from_member($object,GETPOST('companyname'));

        echo "* " . $i  . " - " . $result . "\n";
        ob_flush();

        $i++;
    }
}


echo "Termine\n";

ob_start();


$db->close();
