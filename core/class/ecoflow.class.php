<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class ecoflow extends eqLogic {
  /*     * *************************Attributs****************************** */

  /*
  * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
  * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
  public static $_widgetPossibility = array();
  */

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration du plugin
  * Exemple : "param1" & "param2" seront cryptés mais pas "param3"
  public static $_encryptConfigKey = array('param1', 'param2');
  */

  /*     * ***********************Methode static*************************** */

  
  // Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function cron() 
  {
/*    foreach (self::byType('ecoflow', true) as $ecoflow) 
      { //parcours tous les équipements actifs du plugin ecoflow
        $cmd = $ecoflow->getCmd(null, 'refresh'); //retourne la commande "refresh" si elle existe
        if (!is_object($cmd)) 
        { //Si la commande n'existe pas
          continue; //continue la boucle
        }
        $cmd->execCmd(); //la commande existe on la lance
      }
*/
  }
  
  

  // Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
  public static function cron5() 
  {
/*    foreach (self::byType('ecoflow', true) as $ecoflow) 
      { //parcours tous les équipements actifs du plugin ecoflow
        $cmd = $ecoflow->getCmd(null, 'refresh'); //retourne la commande "refresh" si elle existe
        if (!is_object($cmd)) 
        { //Si la commande n'existe pas
          continue; //continue la boucle
        }
        $cmd->execCmd(); //la commande existe on la lance
      }
*/
  }
  

  /*
  * Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
  public static function cron10() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
  public static function cron15() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
  public static function cron30() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les heures par Jeedom
  public static function cronHourly() {}
  */

  /*
  * Fonction exécutée automatiquement tous les jours par Jeedom
  public static function cronDaily() {}
  */

	public static function update() {
		foreach (self::byType('ecoflow') as $ecoflows) {
			$autorefresh = $ecoflows->getConfiguration('autorefresh');
			if ($ecoflows->getIsEnable() == 1 && $autorefresh != '') {
				try {
					$c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
					if ($c->isDue()) {

                $cmd = $ecoflows->getCmd(null, 'refresh'); //retourne la commande "refresh" si elle existe
                if (!is_object($cmd)) 
                { //Si la commande n'existe pas
                  continue; //continue la boucle
                }
                $cmd->execCmd(); //la commande existe on la lance
              
					}
				} catch (Exception $exc) {
					log::add('ecoflow', 'error', __('Expression cron non valide pour ', __FILE__) . $ecoflows->getHumanName() . ' : ' . $autorefresh);
				}
			}
		}
	}
 
  /*     * *********************Méthodes d'instance************************* */


  // Fonction de récupération des métriques de l'équipement EcoFlow
  public function ecoflowInfo() {
  
  log::add("ecoflow", 'info', '==> ' . $this->getHumanName(), 'ecoflowInfo');
  
    $Serial = $this->getConfiguration("serial", " ");
    $ApiKey = $this->getConfiguration("apikey", " ");
    $SecretKey = $this->getConfiguration("secret", " ");
    
    //Par défaut : "https://api.ecoflow.com/iot-service/open/api/device/queryDeviceQuota?sn="
    $url = config::byKey('EcoApiUrl', 'ecoflow') . $Serial;  

    $headers = 
    [
      "Content-Type: application/json",
      "appKey: " . $ApiKey,
      "secretKey: " . $SecretKey
    ];
    
    $curl_command = curl_init();
    curl_setopt($curl_command, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl_command, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl_command, CURLOPT_URL, $url);
    curl_setopt($curl_command, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl_command, CURLINFO_HEADER_OUT, true);
    
    $curl_return = curl_exec($curl_command);
    
    log::add("ecoflow", 'info', '==> curl command URL : ' . curl_getinfo($curl_command, CURLINFO_EFFECTIVE_URL) , 'ecoflowInfo');
    log::add("ecoflow", 'info', '==> curl command HEADER OUT: ' . curl_getinfo($curl_command, CURLINFO_HEADER_OUT) , 'ecoflowInfo');
    log::add("ecoflow", 'info', '==> curl return : ' . $curl_return , 'ecoflowInfo');
    
    curl_close($curl_command);
    
    //décodage du JSON $curl_return pour récupérer les valeurs
    $obj = json_decode($curl_return);

    $sortie[0] = $obj->code;
    $sortie[1] = $obj->message;

    log::add("ecoflow", 'info', '==> Sortie 0 (code) : ' .  $sortie[0] , 'ecoflowInfo');
  
    //si le retour est correct, on récupère les autres infos
    if ($sortie[0] == 0) {          
      $wattsOutSum = $obj->data->wattsOutSum;
      $wattsInSum = $obj->data->wattsInSum;
      $remainTime = sprintf('%02d',intdiv($obj->data->remainTime, 60)) .'h '. ( sprintf('%02d',$obj->data->remainTime % 60) . 'm');      
      $soc = $obj->data->soc;
      
      log::add("ecoflow", 'info', '==> CODE OK' , 'ecoflowInfo');
    } 
    else
    {
      $wattsOutSum = ' ';
      $wattsInSum = ' ';
      $remainTime = ' ';
      $soc = ' ';
      
      log::add("ecoflow", 'info', '==> CODE KO' , 'ecoflowInfo');
    }
    
    $sortie[2] = $soc;
    $sortie[3] = $remainTime;
    $sortie[4] = $wattsOutSum;
    $sortie[5] = $wattsInSum;        
          
    return $sortie;

  }
  
  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert() {
  }

  // Fonction exécutée automatiquement après la création de l'équipement
  public function postInsert() {
  }

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate() {
  }

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate() {
    $cmd = $this->getCmd(null, 'refresh'); //On recherche la commande refresh de l’équipement
    if (is_object($cmd)) 
    { //elle existe et on lance la commande
      $cmd->execCmd();
    }
  }

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {
		if ($this->getConfiguration('autorefresh') == '') {
			$this->setConfiguration('autorefresh', '* * * * *');
		}
  }

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {
    $info = $this->getCmd(null, 'code');
    if (!is_object($info)) {
      $info = new ecoflowCmd();
      $info->setName(__('Code retour', __FILE__));
    }
    $info->setLogicalId('code');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('numeric');
    $info->save();
    
    //-----------------------------------------------------------
    $info = $this->getCmd(null, 'message');
    if (!is_object($info)) {
      $info = new ecoflowCmd();
      $info->setName(__('Message retour', __FILE__));
    }
    $info->setLogicalId('message');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    //-----------------------------------------------------------
    $info = $this->getCmd(null, 'soc');
    if (!is_object($info)) {
      $info = new ecoflowCmd();
      $info->setName(__('Pourcentage restant', __FILE__));
    }
    $info->setLogicalId('soc');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('numeric');
    $info->save();
    
    //-----------------------------------------------------------
    $info = $this->getCmd(null, 'remain_time');
    if (!is_object($info)) {
      $info = new ecoflowCmd();
      $info->setName(__('Durée restante', __FILE__));
    }
    $info->setLogicalId('remain_time');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();
    
    //-----------------------------------------------------------
    $info = $this->getCmd(null, 'puissance_out');
    if (!is_object($info)) {
      $info = new ecoflowCmd();
      $info->setName(__('Puissance en sortie', __FILE__));
    }
    $info->setLogicalId('puissance_out');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('numeric');
    $info->save();

    //-----------------------------------------------------------
    $info = $this->getCmd(null, 'puissance_in');
    if (!is_object($info)) {
      $info = new ecoflowCmd();
      $info->setName(__('Puissance en entrée', __FILE__));
    }
    $info->setLogicalId('puissance_in');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('numeric');
    $info->save();
    
    //-----------------------------------------------------------
    $refresh = $this->getCmd(null, 'refresh');
    if (!is_object($refresh)) {
      $refresh = new ecoflowCmd();
      $refresh->setName(__('Rafraichir', __FILE__));
    }
    $refresh->setEqLogic_id($this->getId());
    $refresh->setLogicalId('refresh');
    $refresh->setType('action');
    $refresh->setSubType('other');
    $refresh->save();
    
    log::add("ecoflow", 'info', 'Nouvel objet cree', 'ecoflowInfo');
  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
  }

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration des équipements
  * Exemple avec le champ "Mot de passe" (password)
  public function decrypt() {
    $this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
  }
  public function encrypt() {
    $this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
  }
  */

  /*
  * Permet de modifier l'affichage du widget (également utilisable par les commandes)
  public function toHtml($_version = 'dashboard') {}
  */

  /*
  * Permet de déclencher une action avant modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function preConfig_param3( $value ) {
    // do some checks or modify on $value
    return $value;
  }
  */

  /*
  * Permet de déclencher une action après modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function postConfig_param3($value) {
    // no return value
  }
  */

  /*     * **********************Getteur Setteur*************************** */

}

class ecoflowCmd extends cmd {
  /*     * *************************Attributs****************************** */

  /*
  public static $_widgetPossibility = array();
  */

  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */

  /*
  * Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
  public function dontRemoveCmd() {
    return true;
  }
  */

  // Exécution d'une commande
  public function execute($_options = array()) {

    $eqlogic = $this->getEqLogic(); //récupère l'éqlogic de la commande $this
    
    switch ($this->getLogicalId()) 
    { 
      //vérifie le logicalid de la commande
      case 'refresh': 
        // LogicalId de la commande rafraîchir que l’on a créé dans la méthode Postsave de la classe vdm .
        $info = $eqlogic->ecoflowInfo(); //On lance la fonction ecoflowInfo() pour récupérer les métriques et on stocke dans la variable $info   
 
        //on met à jour les commandes avec les LogicalId "soc", "message", ......"puissance_out"  de l'eqlogic
        $eqlogic->checkAndUpdateCmd('code', $info[0]);
        $eqlogic->checkAndUpdateCmd('message', $info[1]);
        $eqlogic->checkAndUpdateCmd('soc', (double)$info[2]);
        $eqlogic->checkAndUpdateCmd('remain_time', $info[3]); 
        $eqlogic->checkAndUpdateCmd('puissance_out', (double)$info[4]); 
        $eqlogic->checkAndUpdateCmd('puissance_in', (double)$info[5]);

        log::add("ecoflow", 'info', 'code : ' . $info[0], 'ecoflowInfo');
        log::add("ecoflow", 'info', 'message : ' . $info[1], 'ecoflowInfo');
        log::add("ecoflow", 'info', 'soc : ' . $info[2], 'ecoflowInfo');
        log::add("ecoflow", 'info', 'remainTime : ' . $info[3], 'ecoflowInfo');
        log::add("ecoflow", 'info', 'puissance_in : ' . $info[4], 'ecoflowInfo');
        log::add("ecoflow", 'info', 'puissance_out : ' . $info[5], 'ecoflowInfo');                       
      break;
    } 
  
  }

  /*     * **********************Getteur Setteur*************************** */

}
