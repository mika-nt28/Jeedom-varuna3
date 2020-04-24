<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
function varuna3_install(){
}
function varuna3_update(){
	log::add('varuna3','debug','Lancement du script de mise a jours'); 
	foreach(eqLogic::byType('varuna3') as $eqLogic){
		$eqLogic->save();
	}
	log::add('varuna3','debug','Fin du script de mise a jours');
}
function varuna3_remove(){
}
?>
