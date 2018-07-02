<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006 Briac Pilpré <briacp@gmail.com>
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
 *   	\file       dev/skeletons/skeleton_page.php
 *		\ingroup    mymodule othermodule1 othermodule2
 *		\brief      This file is an example of a php page
 *					Put here some comments
 */

//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test
//if (! defined('NOSTYLECHECK'))   define('NOSTYLECHECK','1');			// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined("NOLOGIN"))        define("NOLOGIN",'1');				// If this page is public (can be called outside logged session)

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';			        // to work if your module directory is into dolibarr root htdocs directory
//if (! $res && file_exists("/../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
//if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
//if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");

// Change this following line to use the correct relative path from htdocs
//include_once(DOL_DOCUMENT_ROOT.'/core/class/formcompany.class.php');
//dol_include_once('/module/class/skeleton_class.class.php');
include_once(DOL_DOCUMENT_ROOT.'/txdo_adherent/core/triggers/interface_99_modAdherent_Nettoyage.class.php');

// Load traductions files requiredby by page
//$langs->load("companies");
//$langs->load("other");

llxHeader('','Exports Mailjet','');

$form=new Form($db);


// Put here content of your page

// Example : Adding jquery code
print <<<'_JS_'
<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {

      var changeCal = function(field, day, month, year) {
            day = ("0" + day).slice(-2); 
            month = ("0" + month).slice(-2); 
            $('#' + field + 'day').val(day);
            $('#' + field + 'month').val(month);
            $('#' + field + 'year').val(year);
            $('#' + field + '').val([day, month, year].join('/'));
      }

      $("#export_type").change(function() {
            // "tous"
            // "a_jour"
            // "non_a_jour"
            // "nouveaux"
            var type = $(this).val();

            var d = new Date();
            if (type == 'tous') {
                  // Création de TxDo - 01/01/2010
                  changeCal('date_validite', 1, 1, 2010);
            }
            else if (type == 'a_jour') {
                  changeCal('date_validite', d.getDate(), d.getMonth()+1, d.getFullYear());
            }
            else if (type == 'non_a_jour') {
                  changeCal('date_validite', 1, d.getMonth()+1, d.getFullYear());
            }
            else if (type == 'nouveaux') {
                  changeCal('date_validite', 1, d.getMonth()+1, d.getFullYear());
            }
      });

});
</script>
_JS_;

dol_fiche_head();

print <<<'_HTML_'
  <table>
  <tr>
  <td class="nobordernopadding" valign="middle">
        <div class="titre">Export Adhérents Txirrind'Ola</div></td>
_HTML_;

print '<form method="POST" action="/txdo_stats/mailjet.php">';

print <<<"_HTML_"

<table class="noborder nohover centpercent">
  <tbody>
    <tr class="liste_titre">
      <td colspan="3">Export vers Mailjet</td>
    </tr>
    <tr class="impair">
      <td class="nowrap"><label for="export_type">Type d'export</label>:</td>
      <td>
        <select name="export_type" id="export_type" class="flat inputsearch">
          <option value="tous">Tous les adhérents</option>
          <option value="a_jour">Adhérents à jour de leur cotisation</option>
          <option value="non_a_jour">Adhérents non à jour de cotisation sur les 3 derniers mois</option>
          <option value="nouveaux">Nouveaux adhérents du mois précédent</option>
        </select>
      </td>
    </tr>
    <tr>
      <td class="nowrap"><label for="date_validite">Date de validité</label>:</td>
      <td>
_HTML_;
//'

$first_day = new DateTime('first day of this month');

$form->select_date($first_day->Format('Y-m-d'),'date_validite',0,0,1,"myform");

print <<<"_HTML_"
      </td>
    </tr>
_HTML_;

print <<<"_HTML_"
    <tr>
      <td rowspan="1"><input type="submit" name="export_adherents" value=" Exporter " class="button"></td>
    </tr>
    <tr class="liste_titre">
      <td colspan="3">Listes des volontaires</td>
    </tr>
    <tr class="impair">
      <td class="nowrap"><label for="type_volontaire">Type de volontaires</label>:</td>
      <td>
        <select name="type_volontaire" id="type_volontaire" class="flat inputsearch">
          <option value="tous">Tous</option>
          <option value="orga">Organisation</option>
          <option value="admin">Administratif</option>
          <option value="atelier">Atelier</option>
        </select>
      </td>
    </tr>
    <tr>
      <td class="nowrap"><label for="debut_volontaire_">Début de l'adhésion</label>:</td>
      <td>
_HTML_;
//'


$now_day   = new DateTime('first day of this month');
$last_year = $now_day->modify('-1 year')->format('Y-m-d');

$now_day   = new DateTime('last day of this month');
$next_year = $now_day->modify('+1 year')->format('Y-m-d');


$form->select_date($last_year,'debut_volontaire_',0,0,0,"myform");

print <<<"_HTML_"
      </td>
    </tr>
    <tr>
      <td class="nowrap"><label for="fin_volontaire_">Fin de l'adhésion</label>:</td>
      <td>
_HTML_;
//'

$form->select_date($next_year,'fin_volontaire_',0,0,0,"myform");

print <<<"_HTML_"
      </td>
    </tr>
_HTML_;

print <<<"_HTML_"
    <tr>
      <td rowspan="2"><input type="submit" name="export_volontaires" value=" Exporter " class="button"></td>
    </tr>
  </tbody>
</table>
</form>

</td></tr></table>
_HTML_;


//$mydate = dol_mktime(12, 0 , 0, $_POST['start_export_month'], $_POST['start_export_day'], $_POST['start_export_year']);
//print strftime('%A %d %B %Y', $mydate);

$cleaner=new InterfaceNettoyage($db);


dol_fiche_end();


// End of page
llxFooter();
$db->close();
