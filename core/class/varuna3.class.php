<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
class varuna3 extends eqLogic {
	public function AddCommande($Name,$_logicalId,$Type="info", $SubType='binary',$visible,$Template='') {
		$Commande = $this->getCmd(null,$_logicalId);
		if (!is_object($Commande)){
			$Commande = new varuna3Cmd();
			$Commande->setId(null);
			$Commande->setName($Name);
			$Commande->setIsVisible($visible);
			$Commande->setLogicalId($_logicalId);
			$Commande->setEqLogic_id($this->getId());
			$Commande->setType($Type);
			$Commande->setSubType($SubType);
     		$Commande->setTemplate('dashboard',$Template );
			$Commande->setTemplate('mobile', $Template);
			$Commande->save();
		}
		return $Commande;
	}
	public function AddKnxCommande($Equipement,$Name,$_logicalId,$Type="info", $SubType='binary',$visible,$Template='') {
		$Commande = $Equipement->getCmd(null,$_logicalId);
		if (!is_object($Commande)){
			$Commande = new eibdCmd();
			$Commande->setId(null);
			$Commande->setName($Name);
			$Commande->setIsVisible($visible);
			$Commande->setLogicalId($_logicalId);
			$Commande->setEqLogic_id($Equipement->getId());
			$Commande->setType($Type);
			$Commande->setSubType($SubType);
     		$Commande->setTemplate('dashboard',$Template );
			$Commande->setTemplate('mobile', $Template);
			$Commande->save();
		}
		return $Commande;
	}
}
class varuna3Cmd extends cmd {
    public function execute($_options = null) {	
	}
}
?>
