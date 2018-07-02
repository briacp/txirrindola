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

/** \file       htdocs/core/triggers/interface_99_modAdherent_ValidationTiers.class.php */
require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';

/**
 * La classe ValidationTiers permet de  créer un Tiers Dolibarr une fois l'adhérent validé
 */
class InterfaceValidationTiers extends DolibarrTriggers
{
    public $family = 'txirindola';
    public $picto = 'technic';
    public $description = "Création automatique d'un tiers lors de la validation de l'adhérent";
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
            dol_syslog("TxDo\t?MEMBER_VALIDATE\t" . $action);
        if ($action == 'MEMBER_VALIDATE') {
            dol_syslog("TxDo\tMEMBER_VALIDATE\t" . $action);

            /* from dolibarr\htdocs\adherents\card.php line 208 */
	    $company = new Societe($this->db);
	    $result=$company->create_from_member($object,GETPOST('companyname'));

            if ($resql > 0) {
                return 0;
            } else {
                return 1;
            }
        }
        return 0;
    }

}

