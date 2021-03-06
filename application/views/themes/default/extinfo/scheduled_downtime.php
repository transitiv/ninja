<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>
<div class="widget left w98">

	<h2><?php echo $host_title_str ?></h2>
	<span style="float: right; margin-top: -30px"><?php echo html::anchor('command/submit?cmd_typ=SCHEDULE_HOST_DOWNTIME', html::image($this->add_path('icons/16x16/scheduled-downtime.png')), array('style' => 'border: 0px; float: left; margin-right: 5px;')).
				  html::anchor('command/submit?cmd_typ=SCHEDULE_HOST_DOWNTIME',$host_link_text).' &nbsp; ';
				  echo html::anchor('recurring_downtime', html::image($this->add_path('icons/16x16/recurring-downtime.png'), array('alt' => '', 'title' => 'Schedule recurring downtime')), array('style' => 'border: 0px')).' &nbsp;';
	echo html::anchor('recurring_downtime', 'Schedule recurring downtime');
	?>
				  <div style="clear:both"></div></span>



	<?php if (!empty($host_data)) { ?>
	<table id="scheduled_host_downtime">
		<!--<caption>
			<?php echo $host_title_str ?>
		</caption>-->
		<thead>
			<tr>
				<th class="headerNone"><?php echo $label_host_name ?></th>
				<th class="headerNone"><?php echo $label_entry_time ?></th>
				<th class="headerNone"><?php echo $label_author ?></th>
				<th class="headerNone"><?php echo $label_comment ?></th>
				<th class="headerNone"><?php echo $label_start_time ?></th>
				<th class="headerNone"><?php echo $label_end_time ?></th>
				<th class="headerNone"><?php echo $label_type ?></th>
				<th class="headerNone"><?php echo $label_duration ?></th>
				<th class="headerNone"><?php echo $label_trigger_id ?></th>
				<th class="headerNone" style="width: 45px"><?php echo $label_actions ?></th>
			</tr>
		</thead>
		<tbody>
		<?php $i=0; foreach ($host_data as $row) { $i++; ?>
		<tr class="<?php echo ($i%2 == 0) ? 'odd' : 'even'; ?>">
			<td><?php echo html::anchor('extinfo/details/host/'.$row->host_name, $row->host_name) ?></td>
			<td><?php echo date($date_format, $row->entry_time) ?></td>
			<td><?php echo $row->author_name ?></td>
			<td><?php echo $row->comment_data ?></td>
			<td><?php echo date($date_format, $row->start_time) ?></td>
			<td><?php echo date($date_format, $row->end_time) ?></td>
			<td><?php echo $row->fixed ? $fixed : $flexible ?></td>
			<td><?php echo time::to_string($row->duration) ?></td>
			<td><?php echo empty($row->trigger_id) ? $na_str : $row->trigger_id ?></td>
			<td style="text-align: center">
				<?php
					echo html::anchor('command/submit?cmd_typ=DEL_HOST_DOWNTIME&downtime_id='.$row->downtime_id, html::image($this->add_path('icons/16x16/delete-downtime.png'), array('alt' => $link_titlestring, 'title' => $link_titlestring)), array('style' => 'border: 0px')).' &nbsp;';
					echo html::anchor('recurring_downtime?host='.$row->host_name, html::image($this->add_path('icons/16x16/recurring-downtime.png'), array('alt' => '', 'title' => 'Schedule recurring downtime')), array('style' => 'border: 0px'));
				?>
			</td>
		</tr>
		<?php } ?>
		</tbody>
	</table>
	<br />
	<br />
	<?php } else { echo $this->translate->_('No hosts scheduled for downtime'); }

	echo '<h2>'.$service_title_str.'</h2><span style="float: right; margin-top: -30px; ">';
	echo html::anchor('command/submit?cmd_typ=SCHEDULE_SVC_DOWNTIME', html::image($this->add_path('icons/16x16/scheduled-downtime.png')), array('style' => 'border: 0px; float: left; margin-right: 5px;')).html::anchor('command/submit?cmd_typ=SCHEDULE_SVC_DOWNTIME',$service_link_text).' &nbsp; ';
	echo html::anchor('recurring_downtime', html::image($this->add_path('icons/16x16/recurring-downtime.png'), array('alt' => '', 'title' => 'Schedule recurring downtime')), array('style' => 'border: 0px')).' &nbsp;';
	echo html::anchor('recurring_downtime', 'Schedule recurring downtime');
	?>
	</span>

	<?php if (!empty($service_data)) { ?>

	<table id="scheduled_service_downtime" style="margin-bottom: 15px">
		<!--<caption><?php echo $service_title_str ?></caption>-->
		<thead>
			<tr>
				<th class="headerNone"><?php echo $label_host_name ?></th>
				<th class="headerNone"><?php echo $label_service ?></th>
				<th class="headerNone"><?php echo $label_entry_time ?></th>
				<th class="headerNone"><?php echo $label_author ?></th>
				<th class="headerNone"><?php echo $label_comment ?></th>
				<th class="headerNone"><?php echo $label_start_time ?></th>
				<th class="headerNone"><?php echo $label_end_time ?></th>
				<th class="headerNone"><?php echo $label_type ?></th>
				<th class="headerNone"><?php echo $label_duration ?></th>
				<th class="headerNone"><?php echo $label_trigger_id ?></th>
				<th class="headerNone" style="width: 45px"><?php echo $label_actions ?></th>
			</tr>
		</thead>
		<tbody>
		<?php $i = 0; foreach ($service_data as $row) { $i++; ?>
		<tr class="<?php echo ($i%2 == 0) ? 'odd' : 'even'; ?>">
			<td><?php echo html::anchor('extinfo/details/host/'.$row->host_name, $row->host_name) ?></td>
			<td><?php echo html::anchor('extinfo/details/service/'.$row->host_name.'?service='.urlencode($row->service_description), $row->service_description) ?></td>
			<td><?php echo date($date_format, $row->entry_time) ?></td>
			<td><?php echo $row->author_name ?></td>
			<td><?php echo $row->comment_data ?></td>
			<td><?php echo date($date_format, $row->start_time) ?></td>
			<td><?php echo date($date_format, $row->end_time) ?></td>
			<td><?php echo $row->fixed ? $fixed : $flexible ?></td>
			<td><?php echo time::to_string($row->duration) ?></td>
			<td><?php echo empty($row->trigger_id) ? $na_str : $row->trigger_id ?></td>
			<td style="text-align: center">
				<?php
					echo html::anchor('command/submit?cmd_typ=DEL_SVC_DOWNTIME&downtime_id='.$row->downtime_id, html::image($this->add_path('icons/16x16/delete-downtime.png'), array('alt' => $link_titlestring, 'title' => $link_titlestring)), array('style' => 'border: 0px')).' &nbsp;';
					echo html::anchor('recurring_downtime?host='.$row->host_name.'&service='.urlencode($row->service_description), html::image($this->add_path('icons/16x16/recurring-downtime.png'), array('alt' => '', 'title' => 'Schedule recurring downtime')), array('style' => 'border: 0px'));
				?>
			</td>
		</tr>
		<?php } ?>
		</tbody>
	</table>
	<?php } ?>
</div>
