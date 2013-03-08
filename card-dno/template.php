<div class="cn-entry" style="-moz-border-radius:4px; background-color:#FFFFFF; border:1px solid #E3E3E3; color: #000000; margin:8px 0px; padding:6px; position: relative;">
	<div style="width:72%; float:left">
		<div style="float: left;">
			<?php $entry->getImage(); ?>
		</div>
		
		<div style="float: left; margin: 0 0 10px 15px;">
			<div style="margin-bottom: 15px; font-size: x-large; font-variant: small-caps"><strong><?php echo $entry->getNameBlock(); ?></strong></div>
			<?php $entry->getTitleBlock(); ?>
			<?php //$entry->getOrgUnitBlock(); ?>
			<?php //$entry->getContactNameBlock(); ?>
			<?php echo $entry->getBioBlock(); ?>			
		</div>			
	</div>
		
	<div style="float: right;" align="right">
		<?php 
		$attr['format'] = '%line1% %line2% %line3% %city% %country%';
		$entry->getAddressBlock( $attr ); 
		?>
		<?php 
		$attr['format'] = '%label%%separator% %number%';
		$entry->getPhoneNumberBlock( $attr ); 
		?>
		<?php 
		$attr['format'] = "%address%";
		$entry->getEmailAddressBlock( $attr ); 
		?>
		<?php 
		$attr['format'] = '%title%';
		$entry->getLinkBlock( $attr ); 
		?>
		<?php $entry->getSocialMediaBlock(); ?>
	</div>
	
	<div style="clear:both"></div>
	
	<div class="cn-meta" align="left" style="margin-top: 6px">
		<span><?php echo $vCard->download(); ?></span>
		<span style="<?php echo $entry->getLastUpdatedStyle() ?>; font-size:x-small; font-variant: small-caps; position: absolute; right: 26px; bottom: 8px;">Updated <?php echo $entry->getHumanTimeDiff() ?> ago</span><span style="position: absolute; right: 3px; bottom: 5px;"><?php echo $entry->returnToTopAnchor() ?></span><br />
	</div>
	
</div>