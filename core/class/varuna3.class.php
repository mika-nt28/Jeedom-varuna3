<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
class varuna3 extends eqLogic {
	public static function deamon_info() {
		$return = array();
		$return['log'] = 'eibd';	
		$return['launchable'] = 'ok';
		$return['state'] = 'nok';		
		$listener = listener::byClassAndFunction('varuna3', 'pull');
		if(is_object($listener))
			$return['state'] = 'ok';	
		return $return;
	}
	public static function deamon_start($_debug = false) {
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') 
			return;
		//log::remove('varuna3');
		self::deamon_stop();
		self::CreateListener();
	}
	public static function deamon_stop() {
		$listener = listener::byClassAndFunction('varuna3', 'pull');
		if(is_object($listener))
			$listener->remove();			
	}
	public static function pull($_options) {
		$Event = cmd::byId($_options['event_id']);
		if(!is_object($Event)){
			log::add('varuna3','error','Impossible de touvée l\'objet '.$_options['event_id']);
			return;
		}
		log::add('varuna3','info',$Event->getHumanName().' est mise a jour: '.$_options['value']);
		$Gad = explode('/',$Event->getLogicalId());
		if($Gad[2] < 9){
			for($Bit = 0;$Bit<8;$Bit++){
				$LogicalId = $Event->getId().'_' . (7 - $Bit);
				$Commandes = cmd::byLogicalId($LogicalId);
				foreach($Commandes as $Commande){
					if(is_object($Commande)){
						$_value = $Commande->DecodeState($_options['value'],$Bit);
						log::add('varuna3','debug', $Commande->getHumanName().' est mise a jour: '.$_value);					
						$oldValue = $Commande->execCmd();
						if ($oldValue !== $Commande->formatValue($_value) || $oldValue === '') {
							$Commande->event($_value);
							continue;
						}
						if ($Commande->getConfiguration('repeatEventManagement', 'auto') == 'always') {
							$Commande->event($_value);
							continue;
						}
					}
				}
			}
		}
	}
	private function CreateBitCmd($KnxCmdId,$Groupe,$Debut,$Fin=8){
		for($Bit = 0; $Bit < $Fin; $Bit++){
			$Name = $Groupe . " " . ($Debut + $Bit);
			$LogicalId = $KnxCmdId . '_' . $Bit;
			$this->AddCommande($Name,$LogicalId,"info",'binary');
		}
	}
	private static function CreateListener(){
		$listener = listener::byClassAndFunction('varuna3', 'pull');
		if (!is_object($listener)){
			$listener = new listener();
			$listener->setClass('varuna3');
			$listener->setFunction('pull');
			$listener->emptyEvent();
			$cache = cache::byKey('varuna3::KnxId');
			if(is_object($cache) && $cache->getValue(null) != null){
				$KnxEqLogic = eqLogic::byId($cache->getValue(null));
			}
			if(!is_object($KnxEqLogic) || $cache->getValue(null) == null){
				$KnxEqLogic = eibd::AddEquipement("Varuna 3","");
				cache::set('varuna3::KnxId', $KnxEqLogic->getId(), 0);
			}
			for($secondaire = 0;$secondaire<76;$secondaire++){
				$GadInterogation=config::byKey('InterogationPrincipal','varuna3').'/'.config::byKey('InterogationMedian','varuna3')."/".$secondaire;
				$GadEmission=config::byKey('EmissionPrincipal','varuna3').'/'.config::byKey('EmissionMedian','varuna3')."/".$secondaire;
				$GadRetour=config::byKey('RetourPrincipal','varuna3').'/'.config::byKey('RetourMedian','varuna3')."/".$secondaire;
				if($secondaire < 1){
					$Groupe= "Etat groupes de surveillance";
					$KnxCmd = $KnxEqLogic->AddCommande($Groupe,$GadInterogation,"info", '5.xxx',array("FlagInit"=>"1","FlagRead"=>"0","FlagTransmit"=>"0","FlagUpdate"=>"1","FlagWrite"=>"1"));
					$listener->addEvent($KnxCmd->getId());
					$Eqlogic = self::AddEquipement($Groupe,'groupe');
					$Eqlogic->CreateBitCmd($KnxCmd->getId(),$Groupe,1);
				}elseif($secondaire < 7){
					$Groupe= "Etat des sorties universelles";
					$Debut = $secondaire * 8 - 7;
					$Fin = $secondaire * 8;
					$KnxCmd = $KnxEqLogic->AddCommande($Groupe. " [" . $Debut . " - " .$Fin. "]",$GadInterogation,"info", '5.xxx',array("FlagInit"=>"1","FlagRead"=>"0","FlagTransmit"=>"0","FlagUpdate"=>"1","FlagWrite"=>"1"));
					$listener->addEvent($KnxCmd->getId());
					$Eqlogic = self::AddEquipement($Groupe,'universelles');
					$Eqlogic->CreateBitCmd($KnxCmd->getId(),$Groupe,$Debut);
				}elseif($secondaire < 8){
					$Groupe= "Etat des sorties chauffages";
					$KnxCmd = $KnxEqLogic->AddCommande($Groupe,$GadInterogation,"info", '5.xxx',array("FlagInit"=>"1","FlagRead"=>"0","FlagTransmit"=>"0","FlagUpdate"=>"1","FlagWrite"=>"1"));
					$listener->addEvent($KnxCmd->getId());
					$Eqlogic = self::AddEquipement($Groupe,'chauffages');					
					$Eqlogic->CreateBitCmd($KnxCmd->getId(),$Groupe,1);
				}elseif($secondaire < 9){
					$Groupe= "Etat des sorties climatisations";
					$KnxCmd = $KnxEqLogic->AddCommande($Groupe,$GadInterogation,"info", '5.xxx',array("FlagInit"=>"1","FlagRead"=>"0","FlagTransmit"=>"0","FlagUpdate"=>"1","FlagWrite"=>"1"));
					$listener->addEvent($KnxCmd->getId());
					$Eqlogic = self::AddEquipement($Groupe,'climatisations');					
					$Eqlogic->CreateBitCmd($KnxCmd->getId(),$Groupe,1);
				}elseif($secondaire < 10){
					$Groupe= "Etat des sorties cumulus";
					$KnxCmd = $KnxEqLogic->AddCommande($Groupe,$GadInterogation,"info", '5.xxx',array("FlagInit"=>"1","FlagRead"=>"0","FlagTransmit"=>"0","FlagUpdate"=>"1","FlagWrite"=>"1"));
					$listener->addEvent($KnxCmd->getId());
					$Eqlogic = self::AddEquipement($Groupe,'cumulus');					
					$Eqlogic->CreateBitCmd($KnxCmd->getId(),$Groupe,1,4);
					$Eqlogic->AddCommande('Hivers',$KnxCmd->getId() . '_5',"info",'binary');
					$Eqlogic->AddCommande('Eté',$KnxCmd->getId() . '_6',"info",'binary');
					$Eqlogic->AddCommande('Hors-gel',$KnxCmd->getId() . '_7',"info",'binary');
				}elseif($secondaire < 11){
					$Groupe= "Etat des sorties gâche des groupes";
					$KnxCmd = $KnxEqLogic->AddCommande($Groupe,$GadInterogation,"info", '5.xxx',array("FlagInit"=>"1","FlagRead"=>"0","FlagTransmit"=>"0","FlagUpdate"=>"1","FlagWrite"=>"1"));
					$listener->addEvent($KnxCmd->getId());
					$Eqlogic = self::AddEquipement($Groupe,'gache');					
					$Eqlogic->CreateBitCmd($KnxCmd->getId(),$Groupe,1);
				}elseif($secondaire < 15){
					$Groupe= "Etat des entrées d’automatisme / surveillance technique";
					$Debut = $secondaire * 8 - 15 * 7;
					$Fin = $Debut + 8;
					$KnxCmd = $KnxEqLogic->AddCommande($Groupe. " [" . $Debut . " - " .$Fin. "]",$GadInterogation,"info", '5.xxx',array("FlagInit"=>"1","FlagRead"=>"0","FlagTransmit"=>"0","FlagUpdate"=>"1","FlagWrite"=>"1"));
					$listener->addEvent($KnxCmd->getId());
					$Eqlogic = self::AddEquipement($Groupe,'automatisme');					
					$Eqlogic->CreateBitCmd($KnxCmd->getId(),$Groupe,1);
				}elseif($secondaire < 19){
					$Groupe= "Etat boucles de surveillance";
					$Debut = $secondaire * 8 - 14 * 7;
					$Fin = $Debut + 8;
					$KnxCmd = $KnxEqLogic->AddCommande($Groupe. " [" . $Debut . " - " .$Fin. "]",$GadInterogation,"info", '5.xxx',array("FlagInit"=>"1","FlagRead"=>"0","FlagTransmit"=>"0","FlagUpdate"=>"1","FlagWrite"=>"1"));
					$listener->addEvent($KnxCmd->getId());
					$Eqlogic = self::AddEquipement($Groupe,'surveillance');					
					$Eqlogic->CreateBitCmd($KnxCmd->getId(),$Groupe,1);
				}elseif($secondaire < 23){
					$Groupe= "Etat d’auto protection";
					$Debut = $secondaire * 8 - 22 * 7;
					$Fin = $Debut + 8;
					$KnxCmd = $KnxEqLogic->AddCommande($Groupe. " [" . $Debut . " - " .$Fin. "]",$GadInterogation,"info", '5.xxx',array("FlagInit"=>"1","FlagRead"=>"0","FlagTransmit"=>"0","FlagUpdate"=>"1","FlagWrite"=>"1"));
					$listener->addEvent($KnxCmd->getId());
					$Eqlogic = self::AddEquipement($Groupe,'auto-protection');					
					$Eqlogic->CreateBitCmd($KnxCmd->getId(),$Groupe,1);
				}elseif($secondaire < 24){
					$Groupe= "Etat d’auto protection de la centrale";
					$KnxCmd = $KnxEqLogic->AddCommande($Groupe,$GadInterogation,"info", '5.xxx',array("FlagInit"=>"1","FlagRead"=>"0","FlagTransmit"=>"0","FlagUpdate"=>"1","FlagWrite"=>"1"));
					$listener->addEvent($KnxCmd->getId());
					$Eqlogic = self::AddEquipement($Groupe,'auto-protection_centrale');					
					$Eqlogic->CreateBitCmd($KnxCmd->getId(),$Groupe,1,1);
				}elseif($secondaire < 25){
					$Groupe= "Etat d’auto protection des Unités Déportées";
					$KnxCmd = $KnxEqLogic->AddCommande($Groupe,$GadInterogation,"info", '5.xxx',array("FlagInit"=>"1","FlagRead"=>"0","FlagTransmit"=>"0","FlagUpdate"=>"1","FlagWrite"=>"1"));
					$listener->addEvent($KnxCmd->getId());
					$Eqlogic = self::AddEquipement($Groupe,'auto-protection_UD');					
					$Eqlogic->CreateBitCmd($KnxCmd->getId(),$Groupe,1);
				}elseif($secondaire < 26){
					$Groupe= "Etat cellule crépusculaire";
					$KnxCmd = $KnxEqLogic->AddCommande($Groupe,$GadInterogation,"info", '5.xxx',array("FlagInit"=>"1","FlagRead"=>"0","FlagTransmit"=>"0","FlagUpdate"=>"1","FlagWrite"=>"1"));
					$listener->addEvent($KnxCmd->getId());
					$Eqlogic = self::AddEquipement($Groupe,'crépusculaire');
					$Eqlogic->AddCommande('Seuil 1',$KnxCmd->getId() . '_1',"info",'binary');
					$Eqlogic->AddCommande('Seuil 2',$KnxCmd->getId() . '_0',"info",'binary');;
				}elseif($secondaire < 27){
					$Groupe= "Alarme";
					$KnxCmd = $KnxEqLogic->AddCommande($Groupe,$GadInterogation,"info", '5.xxx',array("FlagInit"=>"1","FlagRead"=>"0","FlagTransmit"=>"0","FlagUpdate"=>"1","FlagWrite"=>"1"));
					$listener->addEvent($KnxCmd->getId());
					$Eqlogic = self::AddEquipement($Groupe,'alarme');
					$Eqlogic->AddCommande('Technique 1 à 8',$KnxCmd->getId() . '_0',"info",'binary');
					$Eqlogic->AddCommande('Technique 9 à 16',$KnxCmd->getId() . '_1',"info",'binary');
					$Eqlogic->AddCommande('Technique 17 à 24',$KnxCmd->getId() . '_2',"info",'binary');
					$Eqlogic->AddCommande('Technique 25 à 32',$KnxCmd->getId() . '_3',"info",'binary');
					$Eqlogic->AddCommande('Seuil température',$KnxCmd->getId() . '_5',"info",'binary');
					$Eqlogic->AddCommande('SOS',$KnxCmd->getId() . '_6',"info",'binary');
					$Eqlogic->AddCommande('Secteur',$KnxCmd->getId() . '_7',"info",'binary');
				}elseif($secondaire < 28){
					$Groupe= "Presence alarme groupe";
					$KnxCmd = $KnxEqLogic->AddCommande($Groupe,$GadInterogation,"info", '5.xxx',array("FlagInit"=>"1","FlagRead"=>"0","FlagTransmit"=>"0","FlagUpdate"=>"1","FlagWrite"=>"1"));
					$listener->addEvent($KnxCmd->getId());
					$Eqlogic = self::AddEquipement($Groupe,'presence_groupe');					
					$Eqlogic->CreateBitCmd($KnxCmd->getId(),$Groupe,1);
				}elseif($secondaire < 29){
					$Groupe= "Energie";
					$KnxCmd = $KnxEqLogic->AddCommande("Mode ".$Groupe,$GadInterogation,"info", '5.xxx',array("FlagInit"=>"1","FlagRead"=>"0","FlagTransmit"=>"0","FlagUpdate"=>"1","FlagWrite"=>"1"));
					$listener->addEvent($KnxCmd->getId());
					$Eqlogic = self::AddEquipement($Groupe,'presence_groupe');
					//$Eqlogic->AddCommande('Mode',$KnxCmd->getId(),"info",'binary');
					//028 : Etat mode d’énergie (01 : mode hiver, 10 : mode été, 11 : mode hors-gel)
				}elseif($secondaire < 30){
					//029 : présence tarif EDF (bits 7 et 6), au moins une al. (bit 3), présence du secteur (bit 0)
				}elseif($secondaire < 31){
					$Groupe= "Energie";
					$KnxCmd = $KnxEqLogic->AddCommande("Mode ".$Groupe,$GadInterogation,"info", '5.xxx',array("FlagInit"=>"1","FlagRead"=>"0","FlagTransmit"=>"0","FlagUpdate"=>"1","FlagWrite"=>"1"));
					$listener->addEvent($KnxCmd->getId());
					$Eqlogic = self::AddEquipement($Groupe,'presence_groupe');
					//$Eqlogic->AddCommande('Mode',$KnxCmd->getId(),"info",'binary');
					//028 : Etat mode d’énergie (01 : mode hiver, 10 : mode été, 11 : mode hors-gel)
				}elseif($secondaire < 32){
					$Groupe= "Etat anti-gaspi";
					$KnxCmd = $KnxEqLogic->AddCommande($Groupe,$GadInterogation,"info", '5.xxx',array("FlagInit"=>"1","FlagRead"=>"0","FlagTransmit"=>"0","FlagUpdate"=>"1","FlagWrite"=>"1"));
					$listener->addEvent($KnxCmd->getId());
					$Eqlogic = self::AddEquipement($Groupe,'anti-gaspi');					
					$Eqlogic->CreateBitCmd($KnxCmd->getId(),$Groupe,1);
				}elseif($secondaire < 33){
					$Groupe= "Delestage";
					$KnxCmd = $KnxEqLogic->AddCommande($Groupe,$GadInterogation,"info", '5.xxx',array("FlagInit"=>"1","FlagRead"=>"0","FlagTransmit"=>"0","FlagUpdate"=>"1","FlagWrite"=>"1"));
					$listener->addEvent($KnxCmd->getId());
					$Eqlogic = self::AddEquipement($Groupe,'Delestage');					
					$Eqlogic->CreateBitCmd($KnxCmd->getId(),$Groupe,1);
				}
				
/*

LISTE DES ADRESSES SECONDAIRES (implicites) dont la data utile est sur 3 octets :
032 : index du compteur N°1 (PF sur 1er octet utile de data, pf sur 3ème octet utile de data)
033 : index du compteur N°2 (PF sur 1er octet utile de data, pf sur 3ème octet utile de data)
034 : index du compteur N°3 (PF sur 1er octet utile de data, pf sur 3ème octet utile de data)
035 : index du compteur N°4 (PF sur 1er octet utile de data, pf sur 3ème octet utile de data)
036 : index du compteur N°5 (PF sur 1er octet utile de data, pf sur 3ème octet utile de data)
037 : index du compteur N°6 (PF sur 1er octet utile de data, pf sur 3ème octet utile de data)
038 : index du compteur N°7 (PF sur 1er octet utile de data, pf sur 3ème octet utile de data)
039 : index du compteur N°8 (PF sur 1er octet utile de data, pf sur 3ème octet utile de data)

Nota : Pour la lecture à partir du Bus EIB des variables domotiques au format ‹ 1 bit › voir l’encadré ci-contre nommé ‹ Emissions états domo. ›
Cela concerne la lecture de l’état des 32 boucles de surveillance, de l’état des 8 groupes de surveillance et de l’état de régulation des 8 zones de chauffage / climatisation.
Emission d état
Il est possible de faire émettre automatiquement sur le Bus EIB certaines « variables domotiques » ainsi que l’état de certaines
entrées de la centrale Varuna3 à chaque changement d’états de ces dernières vers des adresses ‹ groupe › prédéfinies.
Le groupe principal (1er chiffre de 0 à 15 de l’adresse groupe) et le groupe médian (2ème chiffre de 0 à 7 de l’adresse groupe)
sont renseignés dans cet encadré.
Les groupes secondaires (3ème chiffre de 0 à 255 de l’adresse groupe) définissent implicitement les variables domotiques concernées.

LISTE DES ADRESSES SECONDAIRES (implicites) :
000 : état de la boucle de surveillance 1 (envoi d’un 0 pour boucle fermée et d’un 1 pour boucle ouverte)
001 : état de la boucle de surveillance 2
|
031 : état de la boucle de surveillance 32

032 : état du délai de sortie du groupe de surveillance 1 (envoi d’un 1 au début du délai de sortie et d’un 0 à la fin)
033 : état du délai de sortie du groupe de surveillance 2
|
039 : état du délai de sortie du groupe de surveillance 8

040 : état de surveillance du groupe 1 (envoi d’un 1 à la mise En surveillance et d’un 0 à la mise Hors surveillance)
041 : état de surveillance du groupe 2
|
047 : état de surveillance du groupe 8

048 : état de régulation ‹ Absence › de la zone de chauffage/climatisation 1 (envoi d’un 1 en ‹ Absence › et d’un 0 si pas en ‹ Absence ›)
049 : état de régulation ‹ Absence › de la zone de chauffage/climatisation 2
|
055 : état de régulation ‹ Absence › de la zone de chauffage/climatisation 8

056 : état de régulation ‹ Présence › de la zone de chauffage/climatisation 1 (envoi d’un 1 en ‹ Présence › et d’un 0 si pas en ‹ Présence ›)
057 : état de régulation ‹ Présence › de la zone de chauffage/climatisation 2
|
063 : état de régulation ‹ Présence › de la zone de chauffage/climatisation 8

064 : état de régulation ‹ Confort › de la zone de chauffage/climatisation 1 (envoi d’un 1 en ‹ Confort › et d’un 0 si pas en ‹ Confort ›)
065 : état de régulation ‹ Confort › de la zone de chauffage/climatisation 2
|
071 : état de régulation ‹ Confort › de la zone de chauffage/climatisation 8*/
			}
			$listener->save();
		}
	}
	public static function AddEquipement($Name,$_logicalId) {
		$Equipement = eqLogic::byLogicalId($_logicalId,'varuna3');
		if(is_object($Equipement))
			return $Equipement;
		$Equipement = new varuna3();
		$Equipement->setName($Name);
		$Equipement->setLogicalId($_logicalId);
		$Equipement->setObject_id(null);
		$Equipement->setEqType_name('varuna3');
		$Equipement->setIsEnable(1);
		$Equipement->setIsVisible(1);
		$Equipement->save();
		return $Equipement;
	}
	public function AddCommande($Name,$_logicalId,$Type="info", $SubType='binary') {
		$Commande = $this->getCmd(null,$_logicalId);
		if(is_object($Commande))
			return $Commande;
		$Commande = new varuna3Cmd();
		$Commande->setId(null);
		$Commande->setName($Name);
		$Commande->setIsVisible(1);
		$Commande->setLogicalId($_logicalId);
		$Commande->setEqLogic_id($this->getId());
		$Commande->setType($Type);
		$Commande->setSubType($SubType);
		$Commande->save();
		return $Commande;
	}
}
class varuna3Cmd extends cmd {
	public function DecodeState($value,$bit) {
		return ($value >> $bit) & 0x01;
	}
	public function execute($_options = null) {	
	}
}
?>
