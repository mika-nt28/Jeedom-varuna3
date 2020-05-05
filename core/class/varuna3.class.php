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
		switch($Event->getConfiguration('decodage')){
			case 'bitToState':
				for($bit = 0;$bit<8;$bit++){
					$Commande = cmd::byLogicalId($Event->getId().'_'.$bit);
					if(is_object($Commande)){
						$value = $Event->DecodeState($_options['value'],$bit);
						log::add('varuna3','debug', $Commande->getHumanName().' est mise a jour: '.$value);
						$Commande->event($value);
					}
				}
			break;
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
				//config::byKey('EmissionPrincipal','varuna3');
				//config::byKey('EmissionMedian','varuna3');
				//config::byKey('RetourPrincipal','varuna3');
				//config::byKey('RetourMedian','varuna3');
				$_logicalId=config::byKey('InterogationPrincipal','varuna3').'/'.config::byKey('InterogationMedian','varuna3')."/".$secondaire;
				if($secondaire < 1){
					$Groupe= "Etat groupes de surveillance";
					$KnxCmd = $KnxEqLogic->AddCommande($Groupe,$_logicalId,"info", '5.xxx');
					$listener->addEvent($KnxCmd->getId());
					$Eqlogic = self::AddEquipement($Groupe,'groupe');
					for($Bit = 0; $Bit < 8; $Bit++){
						$Etat = $Bit+1;
						$Name = $Groupe . " " . $Etat;
						$LogicalId = $KnxCmd->getId().'_'.$Bit;
						$Eqlogic->AddCommande($Name,$LogicalId,"info",'binary','bitToState');
					}
				}elseif($secondaire < 7){
					$Groupe= "Etat des sorties universelles";
					$Debut = $secondaire * 8 - 7;
					$Fin = $secondaire * 8;
					$KnxCmd = $KnxEqLogic->AddCommande($Groupe. " [" . $Debut . " - " .$Fin. "]",$_logicalId,"info", '5.xxx');
					$listener->addEvent($KnxCmd->getId());
					$Eqlogic = self::AddEquipement($Groupe,'universelles');
					for($Bit = 0; $Bit < 8; $Bit++){
						$Etat =  $Debut + $Bit;
						$Name = $Groupe . " " . $Etat;
						$LogicalId = $KnxCmd->getId() . '_' . $Bit;
						$Eqlogic->AddCommande($Name,$LogicalId,"info",'binary','bitToState');
					}
				}
				
/*

007 : état des sorties chauffages des zones 1 à 8
008 : état des sorties climatisations des zones 1 à 8
009 : état des sorties cumulus 1 à 4 et du mode d’énergie (hiver (bit 5), été (bit 6), hors-gel (bit 7))
010 : état des sorties gâche des groupes 1 à 8
011 : état des entrées d’automatisme / surveillance technique 1 à 8
012 : état des entrées d’automatisme / surveillance technique 9 à 16
013 : état des entrées d’automatisme / surveillance technique 17 à 24
014 : état des entrées d’automatisme / surveillance technique 25 à 32
015 : état des boucles de surveillance (détecteur) 1 à 8
016 : état des boucles de surveillance (détecteur) 9 à 16
017 : état des boucles de surveillance (détecteur) 17 à 24
018 : état des boucles de surveillance (détecteur) 25 à 32
019 : état des boucles d’auto protection 1 à 8
020 : état des boucles d’auto protection 9 à 16
021 : état des boucles d’auto protection 17 à 24
022 : état des boucles d’auto protection 25 à 32
023 : état de la boucle d’auto protection de la centrale (sur bit 0)
024 : état des boucles d’auto protection des Unités Déportées 1 à 8
025 : état des 2 seuils de la cellule crépusculaire (seuil 1 sur bit 1, seuil 2 sur bit 0)
026 : al. secteur (bit 7), al. SOS (bit 6), al. seuils températures (bit 5), al. technique 25 à 32 (bit 3), al. tech. 17 à 24 (bit 2), al. tech. 9 à 16 (bit 1), al. tech. 1 à 8 (bit 0)
027 : présence alarme groupe 1 à 8
028 : Etat mode d’énergie (01 : mode hiver, 10 : mode été, 11 : mode hors-gel)
029 : présence tarif EDF (bits 7 et 6), au moins une al. (bit 3), présence du secteur (bit 0)
030 : état anti-gaspi des 8 zones de chauffage / climatisation (bits à 1 : en anti-gaspi)
031 : état délesté des 8 zones de chauffage / climatisation (bits à 1 : délestée)

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
	public function AddCommande($Name,$_logicalId,$Type="info", $SubType='binary',$Decodage='') {
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
		$Commande->setconfiguration('decodage',$Decodage);
		$Commande->save();
		return $Commande;
	}
	private function DecodeState($value,$bit) {
		return $value >> $bit & 0x01;
	}
}
class varuna3Cmd extends cmd {
    public function execute($_options = null) {	
	}
}
?>
