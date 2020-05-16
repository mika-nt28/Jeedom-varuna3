<?php

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
?>
<form class="form-horizontal">
	<fieldset>  
		<div class="col-sm-12 knx">
   			<legend>{{Général}}</legend>
			<div class="form-group">
				<label class="col-lg-3 control-label">{{Protocol de communication :}}
					<sup>
						<i class="fas fa-question-circle tooltips" title="{{Choisir comment communiquer avec votre central}}"></i>
					</sup>
				</label>
				<div class="col-lg-4">
					<select class="configKey" data-l1key="protocol">
						<option value="knx">{{KNX}}</option>
						<option value="snmp">{{SNMP}}</option>
				</div>
			</div>
		</div>
		<div class="col-sm-12 knx">
   			<legend>{{Interogation d'etat}}</legend>
			<div class="form-group">
				<label class="col-lg-3 control-label">{{Adresse de groupe principal et médian :}}
					<sup>
						<i class="fas fa-question-circle tooltips" title="{{Saisir les valeur de groupe}}"></i>
					</sup>
				</label>
				<div class="col-lg-4">
					<input type="number" class="form-control configKey" data-l1key="InterogationPrincipal" />
					<input type="number" class="form-control configKey" data-l1key="InterogationMedian" />
				</div>
			</div>
			<legend>{{Emission d'etat}}</legend>
			<div class="form-group">
				<label class="col-lg-3 control-label">{{Adresse de groupe principal et médian :}}
					<sup>
						<i class="fas fa-question-circle tooltips" title="{{Saisir les valeur de groupe}}"></i>
					</sup>
				</label>
				<div class="col-lg-4">
					<input type="number" class="form-control configKey" data-l1key="EmissionPrincipal" />
					<input type="number" class="form-control configKey" data-l1key="EmissionMedian" />
				</div>
			</div>
			<legend>{{Retour d'etat}}</legend>
			<div class="form-group">
				<label class="col-lg-3 control-label">{{Adresse de groupe principal et médian :}}
					<sup>
						<i class="fas fa-question-circle tooltips" title="{{Saisir les valeur de groupe}}"></i>
					</sup>
				</label>
				<div class="col-lg-4">
					<input type="number" class="form-control configKey" data-l1key="RetourPrincipal" />
					<input type="number" class="form-control configKey" data-l1key="RetourMedian" />
				</div>
			</div>
		</div>
	</fieldset>
</form>
<script>
	$('.configKey[data-l1key=protocol]').off().on('change',function(){
		switch($(this).val()){
			case 'knx':
				$('.knx').show();
			break;
			default:
				$('.knx').hide();
			break;
		}
	});
</script>
		
