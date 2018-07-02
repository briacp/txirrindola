<?php
/* Copyright (C) 2018 Briac Pilpré <briacp@gmail.com>
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
 *  \defgroup   txdoCaisse     Module Txirrindola Caisse
 *  \brief      Module caisse enregistreuse
 *  \file       htdocs/txdoCaisse/core/modules/modTxdoCaisse.class.php
 *  \ingroup    txirrindola
 *  \brief      Description and activation file for module Txirrindola
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module Txirrindola
 */
class modTxdoCaisse extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs,$conf;

        $this->db = $db;

        // Id for module (must be unique).
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 500321;     // TODO Go on page http://wiki.dolibarr.org/index.php/List_of_modules_id to reserve id number for your module
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'txirrindola';

        // Family can be 'crm','financial','hr','projects','products','ecm','technic','interface','other'
        // It is used to group modules by family in module setup page
        $this->family = "other";
        // Module position in the family
        $this->module_position = 500;
        // Gives the possibility to the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
        //$this->familyinfo = array('myownfamily' => array('position' => '001', 'label' => $langs->trans("MyOwnFamily")));

        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i','',get_class($this));
        // Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
        $this->description = "Module Txirrindola Caisse";

        $this->descriptionlong = "Txirrindola - Caisse enregistreuse";
        $this->editor_name = "Txirrind'Ola";
        $this->editor_url = 'http://www.txirrindola.org/';

        // Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
        $this->version = '1.0.0';
        // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        $this->picto='txdo@txdoCaisse';

        // Defined all module parts (triggers, login, substitutions, menus, css, etc...)
        // for default path (eg: /txirrindola/core/xxxxx) (0=disable, 1=enable)
        // for specific path of parts (eg: /txirrindola/core/modules/barcode)
        // for specific css file (eg: /txirrindola/css/txirrindola.css.php)
        //$this->module_parts = array(
        //                          'triggers' => 0,                                    // Set this to 1 if module has its own trigger directory (core/triggers)
        //                          'login' => 0,                                       // Set this to 1 if module has its own login method directory (core/login)
        //                          'substitutions' => 0,                               // Set this to 1 if module has its own substitution function file (core/substitutions)
        //                          'menus' => 0,                                       // Set this to 1 if module has its own menus handler directory (core/menus)
        //                          'theme' => 0,                                       // Set this to 1 if module has its own theme directory (theme)
        //                          'tpl' => 0,                                         // Set this to 1 if module overwrite template dir (core/tpl)
        //                          'barcode' => 0,                                     // Set this to 1 if module has its own barcode directory (core/modules/barcode)
        //                          'models' => 0,                                      // Set this to 1 if module has its own models directory (core/modules/xxx)
        //                          'css' => array('/txirrindola/css/txirrindola.css.php'), // Set this to relative path of css file if module has its own css file
        //                          'js' => array('/txirrindola/js/txirrindola.js'),          // Set this to relative path of js file if module must load a js on all pages
        //                          'hooks' => array('hookcontext1','hookcontext2',...) // Set here all hooks context managed by module. You can also set hook context 'all'
        //                          'dir' => array('output' => 'othermodulename'),      // To force the default directories names
        //                          'workflow' => array('WORKFLOW_MODULE1_YOURACTIONTYPE_MODULE2'=>array('enabled'=>'! empty($conf->module1->enabled) && ! empty($conf->module2->enabled)', 'picto'=>'yourpicto@txirrindola')) // Set here all workflow context managed by module
        //                        );
        $this->module_parts = array(
           'triggers' => 1,
           'js' => array('/txdoCaisse/js/txdo_caisse.js'),          // Set this to relative path of js file if module must load a js on all pages
        );

        // Data directories to create when module is enabled.
        // Example: this->dirs = array("/txirrindola/temp");
        $this->dirs = array();

        // Config pages. Put here list of php page, stored into txirrindola/admin directory, to use to setup module.
        //$this->config_page_url = array("mysetuppage.php@txirrindola");

        // Dependencies
        $this->hidden = false;          // A condition to hide module
        $this->depends = array();       // List of modules id that must be enabled if this module is enabled
        $this->requiredby = array();    // List of modules id to disable if this one is disabled
        $this->conflictwith = array();  // List of modules id this module is in conflict with
        $this->phpmin = array(5,0);                 // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(3,0);  // Minimum version of Dolibarr required by module
        $this->langfiles = array("mylangfile@txdoCaisse");

        // Constants
        // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
        // Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
        //                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
        // );
        $this->const = array();

        // Array to add new pages in new tabs
        // Example: $this->tabs = array('objecttype:+tabname1:Title1:mylangfile@txirrindola:$user->rights->txirrindola->read:/txirrindola/mynewtab1.php?id=__ID__',                     // To add a new tab identified by code tabname1
        //                              'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@txirrindola:$user->rights->othermodule->read:/txirrindola/mynewtab2.php?id=__ID__',    // To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
        //                              'objecttype:-tabname:NU:conditiontoremove');                                                                                            // To remove an existing tab identified by code tabname
        // where objecttype can be
        // 'categories_x'     to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
        // 'contact'          to add a tab in contact view
        // 'contract'         to add a tab in contract view
        // 'group'            to add a tab in group view
        // 'intervention'     to add a tab in intervention view
        // 'invoice'          to add a tab in customer invoice view
        // 'invoice_supplier' to add a tab in supplier invoice view
        // 'member'           to add a tab in fundati:szon member view
        // 'opensurveypoll'   to add a tab in opensurvey poll view
        // 'order'            to add a tab in customer order view
        // 'order_supplier'   to add a tab in supplier order view
        // 'payment'          to add a tab in payment view
        // 'payment_supplier' to add a tab in supplier payment view
        // 'product'          to add a tab in product view
        // 'propal'           to add a tab in propal view
        // 'project'          to add a tab in project view
        // 'stock'            to add a tab in stock view
        // 'thirdparty'       to add a tab in third party view
        // 'user'             to add a tab in user view
        $this->tabs = array();

        if (! isset($conf->txirrindola) || ! isset($conf->txirrindola->enabled))
        {
            $conf->txirrindola=new stdClass();
            $conf->txirrindola->enabled=0;
        }
        
        // Dictionaries
        $this->dictionaries=array();
        /* Example:
        $this->dictionaries=array(
            'langs'=>'mylangfile@txirrindola',
            'tabname'=>array(MAIN_DB_PREFIX."table1",MAIN_DB_PREFIX."table2",MAIN_DB_PREFIX."table3"),      // List of tables we want to see into dictonnary editor
            'tablib'=>array("Table1","Table2","Table3"),                                                    // Label of tables
            'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),   // Request to select fields
            'tabsqlsort'=>array("label ASC","label ASC","label ASC"),                                                                                   // Sort order
            'tabfield'=>array("code,label","code,label","code,label"),                                                                                  // List of fields (result of select to show dictionary)
            'tabfieldvalue'=>array("code,label","code,label","code,label"),                                                                             // List of fields (list of fields to edit a record)
            'tabfieldinsert'=>array("code,label","code,label","code,label"),                                                                            // List of fields (list of fields for insert)
            'tabrowid'=>array("rowid","rowid","rowid"),                                                                                                 // Name of columns with primary key (try to always name it 'rowid')
            'tabcond'=>array($conf->txirrindola->enabled,$conf->txirrindola->enabled,$conf->txirrindola->enabled)                                               // Condition to show each dictionary
        );
        */

        // Boxes
        // Add here list of php file(s) stored in core/boxes that contains class to show a box.
        $this->boxes = array();         // List of boxes
        // Example:
        //$this->boxes=array(
        //    0=>array('file'=>'myboxa.php@txirrindola','note'=>'','enabledbydefaulton'=>'Home'),
        //    1=>array('file'=>'myboxb.php@txirrindola','note'=>''),
        //    2=>array('file'=>'myboxc.php@txirrindola','note'=>'')
        //);

        // Cronjobs
        $this->cronjobs = array();          // List of cron jobs entries to add
        // Example: $this->cronjobs=array(0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'test'=>true),
        //                                1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'test'=>true)
        // );

        // Permissions
        $this->rights = array();        // Permission array used by this module
        $r=0;

        // Add here list of permission defined by an id, a label, a boolean and two constant strings.
        // Example:
        // $this->rights[$r][0] = $this->numero + $r;   // Permission id (must not be already used)
        // $this->rights[$r][1] = 'Permision label';    // Permission label
        // $this->rights[$r][3] = 1;                    // Permission by default for new user (0/1)
        // $this->rights[$r][4] = 'level1';             // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
        // $this->rights[$r][5] = 'level2';             // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
        // $r++;

        // Main menu entries
        $this->menu = array();          // List of menus to add
        $r=0;


    }

    /**
     *      Function called when module is enabled.
     *      The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *      It also creates data directories
     *
     *      @param      string  $options    Options when enabling module ('', 'noboxes')
     *      @return     int                 1 if OK, 0 if KO
     */
    public function init($options='')
    {
        $sql = array();

        //$this->_load_tables('/txirrindola/sql/');

        return $this->_init($sql, $options);
    }

    /**
     * Function called when module is disabled.
     * Remove from database constants, boxes and permissions from Dolibarr database.
     * Data directories are not deleted
     *
     * @param      string   $options    Options when enabling module ('', 'noboxes')
     * @return     int              1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();

        return $this->_remove($sql, $options);
    }

}

