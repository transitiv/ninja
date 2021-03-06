<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>
<?php $t = $this->translate; ?>
<div class="state_services">
<?php
	if (!isset($service_filter_status) || $service_filter_status == false) {
			$service_filter_status = array(
				'ok' => 1,
				'warning' => 1,
				'unknown' => 1,
				'critical' => 1,
				'pending' => 1
			);
	}

	if ($service_filter_status_show !== false) {
		echo $t->_('Showing services in state: ');
			$j = 0; foreach($service_filter_status as $key => $value) {
			if ($value == 1) {
				echo ($j > 0) ? ', ' : '';
				echo '<strong>'.$key.'</strong>';
				$j++;

			}
		}
	}
	?>
	<?php echo ($create_pdf) ? '<p>&nbsp;</p>' : '';?>
<?php
	$sg_no = 0;
	$prev_host = false;
	$prev_group = false;
	$prev_hostname = false;
	$j = 0; foreach ($multiple_states as $data) {
	for ($i=0;$i<$data['nr_of_items'];$i++) { if (isset($data['ok'][$i])) {
	$condition = (!empty($data['groupname'])) ? $data['groupname']!= $prev_group : $data['HOST_NAME'][$i]!= $prev_host;

	if ($condition) {
		$j++;
		$prev_host = $data['HOST_NAME'][$i];
		$prev_group = $data['groupname'];

		if ($j != 1) { ?>
	<!--</table>

</div>
<div class="state_services">-->
	<?php } ?>
		<table summary="<?php echo $t->_('State breakdown for host services') ?>" class="multiple_services" style="margin-top: 15px" border="1" <?php echo ($create_pdf) ? 'cellpadding="5"' : ''; ?>>
			<tr>
				<th <?php echo ($create_pdf) ? 'style="font-weight:bold; width: 219px;background-color: #e2e2e2; font-size: 0.9em"' : 'class="headerNone left" style="width: 90%"';?>>
				<?php
				echo ((!$create_pdf) ? help::render('servicegroup_breakdown') : '').' ';
				if(!empty($data['groupname'])) {
					echo $data['groupname'];
				} else {
					echo '<strong>'.$t->_('Services on host') .'</strong>: ';
					if (!$create_pdf)
						echo '<a href="'.str_replace('&','&amp;',$data['host_link'][$i]).'">';
					if (!$use_alias) {
						echo $data['HOST_NAME'][$i];
					 } else {
						echo $this->_get_host_alias($data['HOST_NAME'][$i]).' '.$data['HOST_NAME'][$i].')';
						}
					}
					if (!$create_pdf)
						echo '</a>';
				?>
				</th>
				<th <?php echo ($create_pdf) ? 'style="font-weight:bold; width: 90px;text-align: right; background-color: #e2e2e2; font-size: 0.9em"' : 'class="headerNone"';?>><?php echo $t->_('OK') ?></th>
				<th <?php echo ($create_pdf) ? 'style="font-weight:bold; width: 90px;text-align: right; background-color: #e2e2e2; font-size: 0.9em"' : 'class="headerNone"';?>><?php echo $t->_('Warning') ?></th>
				<th <?php echo ($create_pdf) ? 'style="font-weight:bold; width: 90px;text-align: right; background-color: #e2e2e2; font-size: 0.9em"' : 'class="headerNone"';?>><?php echo $t->_('Unknown') ?></th>
				<th <?php echo ($create_pdf) ? 'style="font-weight:bold; width: 90px;text-align: right; background-color: #e2e2e2; font-size: 0.9em"' : 'class="headerNone"';?>><?php echo $t->_('Critical') ?></th>
				<th <?php echo ($create_pdf) ? 'style="font-weight:bold; width: 105px;text-align: right; background-color: #e2e2e2; font-size: 0.9em"' : 'class="headerNone"';?>><?php echo $t->_('Undetermined') ?></th>
				</tr>
		<?php } ?>
			<?php if (!$hide_host && !empty($data['groupname']) && ($data['HOST_NAME'][$i]!= $prev_hostname || $data['groupname']!= $prev_groupname)) { ?>
			<tr class="even">
			<?php if (!$use_alias && $sg_no == 0) { ?>
				<td colspan="6" class="multiple label"><strong><?php echo $t->_('Services on host') ?></strong>: <?php echo $create_pdf != false ? $data['HOST_NAME'][$i] :'<a href="'.str_replace('&','&amp;',$data['host_link'][$i]).'">' . $data['HOST_NAME'][$i] . '</a>'; ?></td>
			<?php } elseif ($sg_no == 0) { ?>
				<td colspan="6" class="multiple label"><strong><?php echo $t->_('Services on host') ?></strong>: <?php echo $this->_get_host_alias($data['HOST_NAME'][$i]) ?> (<?php echo $create_pdf != false ? $data['HOST_NAME'][$i] : '<a href="'.str_replace('&','&amp;',$data['host_link'][$i]).'">' . $data['HOST_NAME'][$i] . '</a>'; ?>)</td>
			<?php } else { ?>
				<td colspan="6">&nbsp;</td>
			<?php } ?>
			</tr>
			<?php $prev_hostname = $data['HOST_NAME'][$i]; $prev_groupname = $data['groupname']; } ?>
			<?php $no = 0; $bg_color = ($i%2 == 0) ? '#ffffff' : '#f2f2f2'; ?>
			<?php if (($data['ok'][$i] != 0 && $service_filter_status['ok'] == true) ||
						 ($data['warning'][$i] != 0 && $service_filter_status['warning'] == true) ||
						 ($data['unknown'][$i] != 0 && $service_filter_status['unknown'] == true) ||
						 ($data['critical'][$i] != 0 && $service_filter_status['critical'] == true) ||
						 ($data['undetermined'][$i] != 0 && $service_filter_status['pending'] == true)) { $no++;?>
			<tr class="<?php echo ($i%2==0 ? 'even' : 'odd') ?>">
				<td <?php echo ($create_pdf) ? 'style="width: 219px; font-size: 0.9em; background-color: '.$bg_color.'"' : 'class="label"'; ?>>
					<?php if ($create_pdf) { ?>
						<?php echo $data['SERVICE_DESCRIPTION'][$i] ?>
					<?php } else { ?>
					<a href="<?php echo str_replace('&','&amp;',$data['service_link'][$i]); ?>"><?php echo $data['SERVICE_DESCRIPTION'][$i]; ?></a>
					<?php } ?>
				</td>
				<td <?php echo ($create_pdf) ? 'style="width: 90px; font-size: 0.9em; text-align: right; background-color: '.$bg_color.'"' : 'class="data"'; ?>><?php echo reports::format_report_value($data['ok'][$i]) ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.(reports::format_report_value($data['ok'][$i]) > 0 ? '' : 'not-').'ok.png'),
							array( 'alt' => $t->_('OK'), 'title' => $t->_('OK'),'style' => 'height: 12px; width: 11px')) ?></td>
				<td <?php echo ($create_pdf) ? 'style="width: 90px;font-size: 0.9em; text-align: right; background-color: '.$bg_color.'"' : 'class="data"'; ?>><?php echo reports::format_report_value($data['warning'][$i]) ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.(reports::format_report_value($data['warning'][$i]) > 0 ? '' : 'not-').'warning.png'),
							array( 'alt' => $t->_('Warning'), 'title' => $t->_('Warning'),'style' => 'height: 12px; width: 11px')) ?></td>
				<td <?php echo ($create_pdf) ? 'style="width: 90px;font-size: 0.9em; text-align: right; background-color: '.$bg_color.'"' : 'class="data"'; ?>><?php echo reports::format_report_value($data['unknown'][$i]) ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.(reports::format_report_value($data['unknown'][$i]) > 0 ? '' : 'not-').'unknown.png'),
							array( 'alt' => $t->_('Unknown'), 'title' => $t->_('Unknown'),'style' => 'height: 12px; width: 11px')) ?></td>
				<td <?php echo ($create_pdf) ? 'style="width: 90px;font-size: 0.9em; text-align: right; background-color: '.$bg_color.'"' : 'class="data"'; ?>><?php echo reports::format_report_value($data['critical'][$i]) ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.(reports::format_report_value($data['critical'][$i]) > 0 ? '' : 'not-').'critical.png'),
							array( 'alt' => $t->_('Critical'), 'title' => $t->_('Critical'),'style' => 'height: 12px; width: 11px')) ?></td>
				<td <?php echo ($create_pdf) ? 'style="width: 105px; font-size: 0.9em; text-align: right; background-color: '.$bg_color.'"' : 'class="data"'; ?>><?php echo reports::format_report_value($data['undetermined'][$i]) ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.(reports::format_report_value($data['undetermined'][$i]) > 0 ? '' : 'not-').'pending.png'),
							array( 'alt' => $t->_('Undetermined'), 'title' => $t->_('Undetermined'),'style' => 'height: 12px; width: 11px')) ?></td>
			</tr>
			<?php	} } }  $sg_no = $sg_no + $no; ?>

			<?php if (!empty($data['groupname'])) {
					if ($use_average==0 && $sg_no == 0) { ?>
			<tr class="<?php echo ($i%2==0 ? 'even' : 'odd') ?>">
				<td <?php echo ($create_pdf) ? 'style="width: 222px; font-size: 0.9em; background-color: '.$bg_color.'"' : ''; ?>><?php echo $t->_('Average') ?></td>
				<td <?php echo ($create_pdf) ? 'style="width: 90px; font-size: 0.9em; text-align: right; background-color: '.$bg_color.'"' : 'class="data"'; ?>><?php echo $data['average_ok'] ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.($data['average_ok'] > 0 ? '' : 'not-').'ok.png'),
							array( 'alt' => $t->_('Ok'), 'title' => $t->_('Ok'),'style' => 'height: 12px; width: 11px')) ?></td>
				<td <?php echo ($create_pdf) ? 'style="width: 90px; font-size: 0.9em; text-align: right; background-color: '.$bg_color.'"' : 'class="data"'; ?>><?php echo $data['average_warning'] ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.($data['average_warning'] > 0 ? '' : 'not-').'warning.png'),
							array( 'alt' => $t->_('Warning'), 'title' => $t->_('Warning'),'style' => 'height: 12px; width: 11px')) ?></td>
				<td <?php echo ($create_pdf) ? 'style="width: 90px; font-size: 0.9em; text-align: right; background-color: '.$bg_color.'"' : 'class="data"'; ?>><?php echo $data['average_unknown'] ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.($data['average_unknown'] > 0 ? '' : 'not-').'unknown.png'),
							array( 'alt' => $t->_('Unknown'), 'title' => $t->_('Unknown'),'style' => 'height: 12px; width: 11px')) ?></td>
				<td <?php echo ($create_pdf) ? 'style="width: 90px; font-size: 0.9em; text-align: right; background-color: '.$bg_color.'"' : 'class="data"'; ?>><?php echo $data['average_critical'] ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.($data['average_critical'] > 0 ? '' : 'not-').'critical.png'),
							array( 'alt' => $t->_('Critical'), 'title' => $t->_('Critical'),'style' => 'height: 12px; width: 11px')) ?></td>
				<td <?php echo ($create_pdf) ? 'style="width: 105px; font-size: 0.9em; text-align: right; background-color: '.$bg_color.'"' : 'class="data"'; ?>><?php echo $data['average_undetermined'] ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.($data['average_undetermined'] > 0 ? '' : 'not-').'pending.png'),
							array( 'alt' => $t->_('Undetermined'), 'title' => $t->_('Undetermined'),'style' => 'height: 12px; width: 11px')) ?></td>
			</tr>
			<?php } ?>
			<?php $i++; $bg_color = ($i%2 == 0) ? '#ffffff' : '#f2f2f2'; ?>
			<?php if ($sg_no == 0) { ?>
			<tr class="<?php echo ($i%2==0 ? 'even' : 'odd') ?>">
				<td <?php echo ($create_pdf) ? 'style="width: 222px; font-size: 0.9em; background-color: '.$bg_color.'"' : ''; ?>><?php if ($use_average==0) { ?><?php echo $t->_('Group availability (SLA)') ?> <?php } else { ?><?php echo $t->_('Average') ?><?php } ?></td>
				<td <?php echo ($create_pdf) ? 'style="width: 90px; font-size: 0.9em; text-align: right; background-color: '.$bg_color.'"' : 'class="data"'; ?>><?php echo $data['group_average_ok'] ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.($data['group_average_ok'] > 0 ? '' : 'not-').'ok.png'),
							array( 'alt' => $t->_('Ok'), 'title' => $t->_('Ok'),'style' => 'height: 12px; width: 11px')) ?></td>
				<td <?php echo ($create_pdf) ? 'style="width: 90px; font-size: 0.9em; text-align: right; background-color: '.$bg_color.'"' : 'class="data"'; ?>><?php echo $data['group_average_warning'] ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.($data['group_average_warning'] > 0 ? '' : 'not-').'warning.png'),
							array( 'alt' => $t->_('Warning'), 'title' => $t->_('Warning'),'style' => 'height: 12px; width: 11px')) ?></td>
				<td <?php echo ($create_pdf) ? 'style="width: 90px; font-size: 0.9em; text-align: right; background-color: '.$bg_color.'"' : 'class="data"'; ?>><?php echo $data['group_average_unknown'] ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.($data['group_average_unknown'] > 0 ? '' : 'not-').'unknown.png'),
							array( 'alt' => $t->_('Unknown'), 'title' => $t->_('Unknown'),'style' => 'height: 12px; width: 11px')) ?></td>
				<td <?php echo ($create_pdf) ? 'style="width: 90px; font-size: 0.9em; text-align: right; background-color: '.$bg_color.'"' : 'class="data"'; ?>><?php echo $data['group_average_critical'] ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.($data['group_average_critical'] > 0 ? '' : 'not-').'critical.png'),
							array( 'alt' => $t->_('Critical'), 'title' => $t->_('Critical'),'style' => 'height: 12px; width: 11px')) ?></td>
				<td <?php echo ($create_pdf) ? 'style="width: 105px; font-size: 0.9em; text-align: right; background-color: '.$bg_color.'"' : 'class="data"'; ?>><?php echo $data['group_average_undetermined'] ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.($data['group_average_undetermined'] > 0 ? '' : 'not-').'pending.png'),
							array( 'alt' => $t->_('Undetermined'), 'title' => $t->_('Undetermined'),'style' => 'height: 12px; width: 11px')) ?></td>
			</tr>
			<?php } } ?>
			<?php if ($sg_no > 0 && $no == 0) { ?>
			<tr class="even">
				<td colspan="6">
					<?php echo $t->_('No service in this group in state: ');
						$j = 0; foreach($service_filter_status as $key => $value) {
						if ($value == 1) {
							echo ($j > 0) ? $t->_(', ') : '';
							echo '<strong>'.$key.'</strong>';
							$j++;

						}
					}
					?>
				</td>
			</tr>
			<?php } ?>
			<?php if (!$create_pdf) { ?>
			<tr id="pdf-hide">
				<td colspan="6"><?php echo $this->_build_testcase_form($data[';testcase;']); ?></td>
			</tr>
			<?php } ?>
		</table>
</div>

<br />
<div class="state_services">
<?php }  ?>
	<table summary="<?php echo $t->_('State breakdown for host services') ?>" <?php echo ($create_pdf) ? 'style="border: 1px solid #cdcdcd" cellpadding="5"' : 'class="multiple_services" style="margin-bottom: 15px"';?>>
		<tr>
			<th <?php echo ($create_pdf) ? 'style="width: 222px; font-weight: bold; background-color: #e2e2e2; font-size: 0.9em"' : 'class="headerNone left" style="width: 90%"';?>><?php echo ((!$create_pdf) ? help::render('average_and_sla') : '').' '.$t->_('Average and Group availability for all selected services') ?></th>
			<th <?php echo ($create_pdf) ? 'style="width: 90px; font-weight: bold; text-align: right; background-color: #e2e2e2; font-size: 0.9em"' : 'class="headerNone"';?>><?php echo $t->_('OK') ?></th>
			<th <?php echo ($create_pdf) ? 'style="width: 90px; font-weight: bold; text-align: right; background-color: #e2e2e2; font-size: 0.9em"' : 'class="headerNone"';?>><?php echo $t->_('Warning') ?></th>
			<th <?php echo ($create_pdf) ? 'style="width: 90px; font-weight: bold; text-align: right; background-color: #e2e2e2; font-size: 0.9em"' : 'class="headerNone"';?>><?php echo $t->_('Unknown') ?></th>
			<th <?php echo ($create_pdf) ? 'style="width: 90px; font-weight: bold; text-align: right; background-color: #e2e2e2; font-size: 0.9em"' : 'class="headerNone"';?>><?php echo $t->_('Critical') ?></th>
			<th <?php echo ($create_pdf) ? 'style="width: 105px; font-weight: bold; text-align: right; background-color: #e2e2e2; font-size: 0.9em"' : 'class="headerNone"';?>><?php echo $t->_('Undetermined') ?></th>
		</tr>
		<?php if ($use_average==0) {  ?>
		<tr class="even">
			<td <?php echo ($create_pdf) ? 'style="width: 222px; font-size: 0.9em; background-color: #ffffff"' : ''; ?>><?php echo $t->_('Average');?></td>
			<td <?php echo ($create_pdf) ? 'style="width: 90px; font-size: 0.9em; text-align: right; "' : 'class="data"'; ?>><?php echo $data['average_ok'] ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.($data['average_ok'] > 0 ? '' : 'not-').'ok.png'),
						array( 'alt' => $t->_('OK'), 'title' => $t->_('OK'),'style' => 'height: 12px; width: 11px')) ?></td>
			<td <?php echo ($create_pdf) ? 'style="width: 90px; font-size: 0.9em; text-align: right;"' : 'class="data"'; ?>><?php echo $data['average_warning'] ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.($data['average_warning'] > 0 ? '' : 'not-').'warning.png'),
						array( 'alt' => $t->_('Warning'), 'title' => $t->_('Warning'),'style' => 'height: 12px; width: 11px')) ?></td>
			<td <?php echo ($create_pdf) ? 'style="width: 90px; font-size: 0.9em; text-align: right;"' : 'class="data"'; ?>><?php echo $data['average_unknown'] ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.($data['average_unknown'] > 0 ? '' : 'not-').'unknown.png'),
						array( 'alt' => $t->_('Unknown'), 'title' => $t->_('Unknown'),'style' => 'height: 12px; width: 11px')) ?></td>
			<td <?php echo ($create_pdf) ? 'style="width: 90px; font-size: 0.9em; text-align: right; "' : 'class="data"'; ?>><?php echo $data['average_critical'] ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.($data['average_critical'] > 0 ? '' : 'not-').'critical.png'),
						array( 'alt' => $t->_('Critical'), 'title' => $t->_('Critical'),'style' => 'height: 12px; width: 11px')) ?></td>
			<td <?php echo ($create_pdf) ? 'style="width: 105px; font-size: 0.9em; text-align: right;"' : 'class="data"'; ?>><?php echo $data['average_undetermined'] ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.($data['average_undetermined'] > 0 ? '' : 'not-').'pending.png'),
						array( 'alt' => $t->_('Undetermined'), 'title' => $t->_('Undetermined'),'style' => 'height: 12px; width: 11px')) ?></td>
		</tr>
		<?php } ?>
		<?php $i++; $bg_color = '#f2f2f2'; ?>
		<tr class="odd">
			<td <?php echo ($create_pdf) ? 'style="width: 222px; font-size: 0.9em; background-color: '.$bg_color.'"' : ''; ?>><?php if ($use_average==0) { ?><?php echo $t->_('Group availability (SLA)') ?> <?php } else { ?><?php echo $t->_('Average') ?><?php } ?></td>
			<td <?php echo ($create_pdf) ? 'style="width: 90px; font-size: 0.9em; text-align: right; background-color: '.$bg_color.'"' : 'class="data"'; ?>><?php echo $data['group_average_ok'] ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.($data['group_average_ok'] > 0 ? '' : 'not-').'ok.png'),
						array( 'alt' => $t->_('Ok'), 'title' => $t->_('Ok'),'style' => 'height: 12px; width: 11px')) ?></td>
			<td <?php echo ($create_pdf) ? 'style="width: 90px; font-size: 0.9em; text-align: right; background-color: '.$bg_color.'"' : 'class="data"'; ?>><?php echo $data['group_average_warning'] ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.($data['group_average_warning'] > 0 ? '' : 'not-').'warning.png'),
						array( 'alt' => $t->_('Warning'), 'title' => $t->_('Warning'),'style' => 'height: 12px; width: 11px')) ?></td>
			<td <?php echo ($create_pdf) ? 'style="width: 90px; font-size: 0.9em; text-align: right; background-color: '.$bg_color.'"' : 'class="data"'; ?>><?php echo $data['group_average_unknown'] ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.($data['group_average_unknown'] > 0 ? '' : 'not-').'unknown.png'),
						array( 'alt' => $t->_('Unknown'), 'title' => $t->_('Unknown'),'style' => 'height: 12px; width: 11px')) ?></td>
			<td <?php echo ($create_pdf) ? 'style="width: 90px; font-size: 0.9em; text-align: right; background-color: '.$bg_color.'"' : 'class="data"'; ?>><?php echo $data['group_average_critical'] ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.($data['group_average_critical'] > 0 ? '' : 'not-').'critical.png'),
						array( 'alt' => $t->_('Critical'), 'title' => $t->_('Critical'),'style' => 'height: 12px; width: 11px')) ?></td>
			<td <?php echo ($create_pdf) ? 'style="width: 105px; font-size: 0.9em; text-align: right; background-color: '.$bg_color.'"' : 'class="data"'; ?>><?php echo $data['group_average_undetermined'] ?> % <?php echo html::image($this->add_path('icons/12x12/shield-'.($data['group_average_undetermined'] > 0 ? '' : 'not-').'pending.png'),
						array( 'alt' => $t->_('Undetermined'), 'title' => $t->_('Undetermined'),'style' => 'height: 12px; width: 11px')) ?></td>
		</tr>
	</table>
</div>
