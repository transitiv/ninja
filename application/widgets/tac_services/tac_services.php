<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Services widget for tactical overview
 *
 * @package    NINJA
 * @author     op5 AB
 * @license    GPL
 */
class Tac_services_Widget extends widget_Core {

	public function __construct()
	{
		parent::__construct();

		# needed to figure out path to widget
		$this->set_widget_name(__CLASS__, basename(__FILE__));
	}

	public function index($arguments=false, $master=false)
	{
		# required to enable us to assign the correct
		# variables to the calling controller
		$this->master_obj = $master;

		# fetch widget view path
		$view_path = $this->view_path('view');

		if (is_object($arguments[0])) {
			$current_status = $arguments[0];
			array_shift($arguments);
		} else {
			$current_status = new Current_status_Model();
			$current_status->analyze_status_data();
		}

		# assign variables for our view
		$widget_id = $this->widgetname;
		$refresh_rate = 60;
		if (isset($arguments['refresh_interval'])) {
			$refresh_rate = $arguments['refresh_interval'];
		}

		$title = $this->translate->_('Services');
		if (isset($arguments['widget_title'])) {
			$title = $arguments['widget_title'];
		}

		# let view template know if wrapping div should be hidden or not
		$ajax_call = request::is_ajax() ? true : false;

		$default_links = array(
			'critical' => 'status/service/all/?servicestatustypes='.nagstat::SERVICE_CRITICAL,
			'warning' => 'status/service/all/?servicestatustypes='.nagstat::SERVICE_WARNING,
			'unknown' => 'status/service/all/?servicestatustypes='.nagstat::SERVICE_UNKNOWN,
			'ok' => 'status/service/all/?servicestatustypes='.nagstat::SERVICE_OK,
			'pending' => 'status/service/all/?servicestatustypes='.nagstat::SERVICE_PENDING
		);

		# alter filter depending on config.show_passive_as_active value in config/checks.php
		$service_fiiter =
			config::get('checks.show_passive_as_active')
			? ((nagstat::SERVICE_NO_SCHEDULED_DOWNTIME|nagstat::SERVICE_STATE_UNACKNOWLEDGED))
			: (nagstat::SERVICE_NO_SCHEDULED_DOWNTIME|nagstat::SERVICE_STATE_UNACKNOWLEDGED|nagstat::SERVICE_CHECKS_ENABLED);

		# SERVICES CRITICAL
		$services_critical = array();
		if ($current_status->svcs_critical_unacknowledged) {
			$services_critical['status/service/all/?hoststatustypes='.(nagstat::HOST_UP|nagstat::HOST_PENDING).'&servicestatustypes='.nagstat::SERVICE_CRITICAL.'&service_props='.$service_fiiter] =
				$current_status->svcs_critical_unacknowledged.' '.$this->translate->_('Unhandled Problems');
		}

		if ($current_status->services_critical_host_problem) {
			$services_critical['status/service/all/?hoststatustypes='.(nagstat::HOST_DOWN|nagstat::HOST_UNREACHABLE).'&servicestatustypes='.nagstat::SERVICE_CRITICAL] = $current_status->services_critical_host_problem.' '.$this->translate->_('on Problem Hosts');
		}

		if ($current_status->services_critical_scheduled) {
			$services_critical['status/service/all/0/?servicestatustypes='.nagstat::SERVICE_CRITICAL.'&service_props='.nagstat::SERVICE_SCHEDULED_DOWNTIME] = $current_status->services_critical_scheduled.' '.$this->translate->_('Scheduled');
		}

		if ($current_status->services_critical_acknowledged) {
			$services_critical['status/service/all/0/?servicestatustypes='.nagstat::SERVICE_CRITICAL.'&service_props='.nagstat::SERVICE_STATE_ACKNOWLEDGED] = $current_status->services_critical_acknowledged.' '.$this->translate->_('Acknowledged');
		}

		if ($current_status->services_critical_disabled) {
			$services_critical['status/service/all/0/?servicestatustypes='.nagstat::SERVICE_CRITICAL.'&service_props='.nagstat::SERVICE_CHECKS_DISABLED ] = $current_status->services_critical_disabled.' '.$this->translate->_('Disabled');
		}


		# SERVICES WARNING
		$services_warning = array();
		# HOST_UP|HOST_PENDING
		# SERVICE_NO_SCHEDULED_DOWNTIME|SERVICE_STATE_UNACKNOWLEDGED|SERVICE_CHECKS_ENABLED
		if ($current_status->svcs_warning_unacknowledged) {
			$services_warning['status/service/all/?hoststatustypes='.(nagstat::HOST_UP|nagstat::HOST_PENDING).'&servicestatustypes='.nagstat::SERVICE_WARNING.'&service_props='.$service_fiiter] =
				$current_status->svcs_warning_unacknowledged.' '.$this->translate->_('Unhandled Problems');
		}

		if ($current_status->services_warning_host_problem) {
			$services_warning['status/service/all/?hoststatustypes='.(nagstat::HOST_DOWN|nagstat::HOST_UNREACHABLE).'&servicestatustypes='.nagstat::SERVICE_WARNING] = $current_status->services_warning_host_problem.' '.$this->translate->_('on Problem Hosts');
		}

		if ($current_status->services_warning_scheduled) {
			$services_warning['status/service/all/?servicestatustypes='.nagstat::SERVICE_WARNING.'&service_props='.nagstat::SERVICE_SCHEDULED_DOWNTIME] = $current_status->services_warning_scheduled.' '.$this->translate->_('Scheduled');
		}

		if ($current_status->services_warning_acknowledged) {
			$services_warning['status/service/all/?servicestatustypes='.nagstat::SERVICE_WARNING.'&service_props='.nagstat::SERVICE_STATE_ACKNOWLEDGED] = $current_status->services_warning_acknowledged.' '.$this->translate->_('Acknowledged');
		}

		if ($current_status->services_warning_disabled) {
			$services_warning['status/service/all/?servicestatustypes='.nagstat::SERVICE_WARNING.'&service_props='.nagstat::SERVICE_CHECKS_DISABLED ] = $current_status->services_warning_disabled.' '.$this->translate->_('Disabled');
		}


		# SERVICES UNKNOWN
		$services_unknown = array();
		if ($current_status->svcs_unknown_unacknowledged) {
			$services_unknown['status/service/all/?servicestatustypes='.nagstat::SERVICE_UNKNOWN.'&hoststatustypes='.(nagstat::HOST_UP|nagstat::HOST_PENDING).'&service_props='.$service_fiiter] =
				$current_status->svcs_unknown_unacknowledged.' '.$this->translate->_('Unhandled Problems');
		}

		if ($current_status->services_unknown_host_problem) {
			$services_unknown['status/service/all/?servicestatustypes='.nagstat::SERVICE_UNKNOWN.'&hoststatustypes='.(nagstat::HOST_DOWN|nagstat::HOST_UNREACHABLE)] = $current_status->services_unknown_host_problem.' '.$this->translate->_('on Problem Hosts');
		}

		if ($current_status->services_unknown_scheduled) {
			$services_unknown['status/service/all/?servicestatustypes='.nagstat::SERVICE_UNKNOWN.'&service_props='.nagstat::SERVICE_SCHEDULED_DOWNTIME] = $current_status->services_unknown_scheduled.' '.$this->translate->_('Scheduled');
		}

		if ($current_status->services_unknown_acknowledged) {
			$services_unknown['status/service/all/?servicestatustypes='.nagstat::SERVICE_UNKNOWN.'&service_props='.nagstat::SERVICE_STATE_ACKNOWLEDGED] = $current_status->services_unknown_acknowledged.' '.$this->translate->_('Acknowledged');
		}

		if ($current_status->services_unknown_disabled) {
			$services_unknown['status/service/all/?servicestatustypes='.nagstat::SERVICE_UNKNOWN.'&service_props='.nagstat::SERVICE_CHECKS_DISABLED ] = $current_status->services_unknown_disabled.' '.$this->translate->_('Disabled');
		}


		# SERVICES OK DISABLED
		$services_ok_disabled = array();
		if ($current_status->services_ok_disabled) {
			$services_ok_disabled['status/service/all/?servicestatustypes='.nagstat::SERVICE_OK.'&service_props='.nagstat::SERVICE_CHECKS_DISABLED] = $current_status->services_ok_disabled.' '.$this->translate->_('Disabled');
		}

		# SERVICES PENDING
		$services_pending = array();
		if ($current_status->services_pending) {
			$services_pending['status/service/all/?servicestatustypes='.nagstat::SERVICE_PENDING] = $current_status->services_pending.' '.$this->translate->_('Pending');
		}

		# fetch widget content
		require_once($view_path);

		if(request::is_ajax()) {
			# output widget content
			echo json::encode( $this->output());
		} else {

			# set required extra resources
			$this->js = array('/js/tac_services');

			# call parent helper to assign all
			# variables to master controller
			return $this->fetch();
		}
	}
}

