<?php
/* Copyright (C) 2016 Briac Pilpré  <briacp@gmail.com>
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

/** \file       htdocs/core/triggers/interface_99_modAdherent_Nettoyage.class.php */
require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';

/**
 * La classe InterfaceNettoyage permet de :
 *   - Passer le nom de famille de l'adhérent en majuscule, en respectant l'accentuation.
 *   - Formatter les numéros de téléphone (normal, mobile, perso) afin qu'ils soient au format ##.##.##.##.##
 * On s'attend à recevoir 10 ou 11 chiffres, pour prendre en compte les numéros du type +33123456789
 *
 * Une fois l'Adhérent modifié les données sont repassées à Dolibarr pour les insérer dans la base de données.
 *
 * Nécessite l'ajout d'un trigger MEMBER_PRE_UPDATE dans le fichier
 * adherents/class/adherent.class.php ligne 401 (function update())
 *
 *       $result=$this->call_trigger('MEMBER_PRE_UPDATE',$user);
 *       if ($result < 0) { $error++; }
 *
 */
class InterfaceNettoyage extends DolibarrTriggers
{
    public $family = 'txirindola';
    public $picto = 'technic';
    public $description = "Nettoyage des infos adhérents (nom en majuscule, n° de tel formatés, etc.)";
    public $version = 0.1;

    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
     *
     * @param string            $action     Event action code
     * @param Object            $object     Object concerned. Some context information may also be provided into array property object->context.
     * @param User              $user       Object user
     * @param Translate         $langs      Object langs
     * @param conf              $conf       Object conf
     * @return int              <0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        if ($action == 'MEMBER_MODIFY' || $action == 'MEMBER_CREATE') {
            dol_syslog("TxDo\tTrigger\t" . $action);

            $adherent = $this->cleanupMember($object);

            $sql = "UPDATE " . MAIN_DB_PREFIX . "adherent SET";
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

            dol_syslog("TxDo\tSQL\t" . $sql);

            // $this->db->begin();
            $resql = $this->db->query($sql);

            if ($resql > 0) {
                return 0;
            } else {
                return 1;
            }
        }
        return 0;
    }

    /** Nettoyage d'une fiche Adhérent */
    public function cleanupMember($adherent)
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
            $adherent->phone = $this->cleanupPhone($adherent->phone);
        }
        if (!empty($adherent->phone_perso)) {
            $adherent->phone_perso = $this->cleanupPhone($adherent->phone_perso);
        }
        if (!empty($adherent->phone_mobile)) {
            $adherent->phone_mobile = $this->cleanupPhone($adherent->phone_mobile);
        }

        return $adherent;
    }

    /** Nettoyage du numéro de téléphone. */
    private function cleanupPhone($phone)
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

}

