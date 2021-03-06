<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Status controller
 *
 *  op5, and the op5 logo are trademarks, servicemarks, registered servicemarks
 *  or registered trademarks of op5 AB.
 *  All other trademarks, servicemarks, registered trademarks, and registered
 *  servicemarks mentioned herein may be the property of their respective owner(s).
 *  The information contained herein is provided AS IS with NO WARRANTY OF ANY
 *  KIND, INCLUDING THE WARRANTY OF DESIGN, MERCHANTABILITY, AND FITNESS FOR A
 *  PARTICULAR PURPOSE.
 */
class Status_Controller extends Authenticated_Controller {
	public $current = false;
	public $img_sort_up = false;
	public $img_sort_down = false;
	public $logos_path = '';
	public $hoststatustypes = false;
	public $servicestatustypes = false;
	public $hostprops = false;
	public $serviceprops = false;
	public $cmd_ok = false;
	public $cmd_host_ok = false;
	public $cmd_svc_ok = false;

	public function __construct()
	{
		parent::__construct();

		# load current status for host/service status totals
		$this->current = new Current_status_Model();
		$this->current->analyze_status_data();
		$this->xtra_js[] = $this->add_path('/js/widgets.js');

		$this->logos_path = Kohana::config('config.logos_path');

		# decide what kind of commands
		# that the current user is authorized for
		$contact = Contact_Model::get_contact();
		if (!empty($contact)) {
			$contact = $contact->current();
			$this->cmd_ok = $contact->can_submit_commands;
		}
		unset($contact);

		$auth = new Nagios_auth_Model();
		$this->cmd_host_ok = $auth->command_hosts_root;
		$this->cmd_svc_ok = $auth->command_services_root;
		unset($auth);

		# add context menu items (hidden in html body)
		$this->template->context_menu = $this->add_view('status/context_menu');
	}

	/**
	 * Equivalent to style=hostdetail
	 *
	 * @param $host
	 * @param $hoststatustypes
	 * @param $sort_order
	 * @param $sort_field
	 * @param $show_services
	 */
	public function host($host='all', $hoststatustypes=false, $sort_order='ASC', $sort_field='host_name', $show_services=false, $group_type=false, $serviceprops=false, $hostprops=false)
	{
		$host = urldecode($this->input->get('host', $host));
		$page = urldecode($this->input->get('page', false));
		$items_per_page = urldecode($this->input->get('items_per_page', config::get('pagination.default.items_per_page', '*')));
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$sort_order = urldecode($this->input->get('sort_order', $sort_order));
		$sort_field = urldecode($this->input->get('sort_field', $sort_field));
		$show_services = urldecode($this->input->get('show_services', $show_services));
		$group_type = urldecode($this->input->get('group_type', $group_type));
		$serviceprops = urldecode($this->input->get('serviceprops', $serviceprops));
		$hostprops = urldecode($this->input->get('hostprops', $hostprops));
		$noheader = urldecode($this->input->get('noheader', false));
		$group_type = urldecode(strtolower($group_type));

		$host = trim($host);
		$hoststatustypes = strtolower($hoststatustypes);
		$hoststatustypes = $hoststatustypes ==='false' || $hoststatustypes ==='true' ? false : $hoststatustypes;

		$replace = array(
			1  => $this->translate->_('UP'),
			2  => $this->translate->_('Down'),
			4  => $this->translate->_('Unreachable'),
			6  => $this->translate->_('All problems'),
			64 => $this->translate->_('Pending')
		);

		$title = $this->translate->_('Monitoring » Host details').($hoststatustypes != false ? ' » '.$replace[$hoststatustypes] : '');
		$this->template->title = $title;

		$this->hoststatustypes = $hoststatustypes;
		$this->hostprops = $hostprops;
		$this->serviceprops = $serviceprops;
		$filters = $this->_show_filters();

		$this->template->content = $this->add_view('status/host');
		$this->template->content->filters = $filters;
		$this->template->content->noheader = $noheader;
		$this->template->js_header = $this->add_view('js_header');
		$this->template->css_header = $this->add_view('css_header');

		widget::add('status_totals', array($this->current, $host, $hoststatustypes, false, $group_type, $serviceprops, $hostprops), $this);
		//$this->xtra_css = array_merge($this->xtra_css, array($this->add_path('/css/default/common.css')));
		$this->template->content->widgets = $this->widgets;
		$this->template->js_header->js = $this->xtra_js;
		$this->template->css_header->css = $this->xtra_css;

		# set sort images, used in header_links() below
		$this->img_sort_up = $this->img_path('icons/16x16/up.gif');
		$this->img_sort_down = $this->img_path('icons/16x16/down.gif');

		# assign specific header fields and values for current method
		$header_link_fields = array(
			array('title' => $this->translate->_('Status'), 'sort_field_db' => 'current_state', 'sort_field_str' => 'host status'),
			array('title' => $this->translate->_('Host'), 'sort_field_db' => 'host_name', 'sort_field_str' => 'host name'),
			array('title' => $this->translate->_('Last Check'), 'sort_field_db' => 'last_check', 'sort_field_str' => 'last check time'),
			array('title' => $this->translate->_('Duration'), 'sort_field_db' => 'duration', 'sort_field_str' => 'state duration'),
			array('title' => $this->translate->_('Status Information'), 'sort_field_db' => 'host.output', 'sort_field_str' => 'status information')
		);

		$show_display_name = config::get('config.show_display_name', '*');
		if ($show_display_name) {
			$header_link_fields[] = array('title' => $this->translate->_('Display Name'), 'sort_field_db' => 'host.display_name', 'sort_field_str' => 'display name');
		}
		$this->template->content->show_display_name = $show_display_name;

		$show_notes = config::get('config.show_notes', '*');
		if ($show_notes) {
			$header_link_fields[] = array('title' => $this->translate->_('Notes'), 'sort_field_db' => 'host.notes', 'sort_field_str' => 'notes');
		}
		$this->template->content->show_notes = $show_notes;

		# build header links array
		foreach ($header_link_fields as $fields) {
			if (sizeof($fields) > 1) {
				$header_links[] = $this->header_links(Router::$method, $host, $fields['title'], Router::$method, $fields['sort_field_db'], $fields['sort_field_str'], $hoststatustypes, false);
			} else {
				$header_links[] = $this->header_links(Router::$method, $host, $fields['title']);
			}
		}

		$this->template->content->header_links = $header_links;

		$shown = strtolower($host) == 'all' ? $this->translate->_('All Hosts') : $this->translate->_('Host')." '".$host."'";
		$sub_title = $this->translate->_('Host Status Details For').' '.$shown;
		$this->template->content->sub_title = $sub_title;
		$this->template->content->pending_output = $this->translate->_('Host check scheduled for %s');
		$this->template->content->nocheck_output = $this->translate->_('Host is not scheduled to be checked...');

		# here we should fetch members of group if group_type is set and pass to get_host_status()
		$host_model = new Host_Model();
		$host_model->show_services = $show_services;
		$host_model->state_filter = $hoststatustypes;
		$host_model->set_sort_field($sort_field);
		$host_model->set_sort_order($sort_order);
		$host_model->serviceprops = $serviceprops;
		$host_model->hostprops = $hostprops;
		$host_model->count = true;

		if (!empty($group_type)) {
			# we ned to remove the 'group' part of the group_type variable value
			# since the method we are about to call expects 'host' or 'service'
			$grouptype = str_replace('group', '', $group_type);
			$group_info_res = Group_Model::get_group_info($grouptype, $host);
			if ($group_info_res) {
				$group_members = false;
				foreach ($group_info_res as $row) {
					$group_members[] = $row->host_name;
				}
			}

			$host_model->set_host_list($group_members);

		} else {
			if (strstr($host, ',')) {
				$host = explode(',', $host);
			}

			$host_model->set_host_list($host);
		}

		$result_cnt = $host_model->get_host_status();

		$tot = $result_cnt !== false ? $result_cnt : 0;
		$pagination = new Pagination(
			array(
				'total_items'=> $tot,
				'items_per_page' => $items_per_page
			)
		);
		$offset = $pagination->sql_offset;
		$host_model->count = false;
		$host_model->num_per_page = $items_per_page;
		$host_model->offset = $offset;

		$result = $host_model->get_host_status();

		$this->template->content->date_format_str = nagstat::date_format();
		$this->template->content->result = $result;
		$this->template->content->pagination = $pagination;
		$this->template->content->total_items = $tot;
		$this->template->content->logos_path = $this->logos_path;
		$this->template->content->na_str = $this->translate->_('N/A');

		if (empty($group_type)) {
			if ($host == 'all') {
				$label_host_status_details = $this->translate->_('Service status detail');
				$label_host_status_overview = $this->translate->_('Status overview');
				$label_host_status_summary = $this->translate->_('Status summary');
				$label_host_status_grid = $this->translate->_('Status grid');
				$label_view_for = $this->translate->_('for all host groups');
				$page_links = array(
					 $label_host_status_details => Router::$controller.'/hostgroup/all?style=detail',
					 $label_host_status_overview => Router::$controller.'/hostgroup/all',
					 $label_host_status_summary => Router::$controller.'/hostgroup/all?style=summary',
					 $label_host_status_grid => Router::$controller.'/hostgroup_grid/all'
				);
			} else {
				$label_host_history = $this->translate->_('Alert history');
				$label_host_notifications = $this->translate->_('Notifications');
				$label_host_status_details = $this->translate->_('Service status detail for All hosts');
				$label_view_for = $this->translate->_('for this host');
				$page_links = array(
					 $label_host_history => 'showlog/alert_history/'.$host,
					 $label_host_notifications => 'notifications/host/'.$host,
					 $label_host_status_details => Router::$controller.'/service/all'
				);
			}
		} else {
			if ($group_type == 'hostgroup') {
				$label_group_status_details_all = $this->translate->_('Host Status detail');
				$label_group_status_details = $this->translate->_('Service Status detail');
				$label_group_status_overview = $this->translate->_('Status overview');
				$label_group_status_summary = $this->translate->_('Status summary');
				$label_group_status_grid = $this->translate->_('Status grid');
				$label_view_for = $this->translate->_('for this host group');
				$page_links = array(
					$label_group_status_details_all => Router::$controller.'/host/all',
					$label_group_status_details => Router::$controller.'/hostgroup/'.$host.'?style=detail',
					$label_group_status_overview => Router::$controller.'/'.$group_type.'/'.$host,
					$label_group_status_summary => Router::$controller.'/'.$group_type.'_summary/'.$host,
					$label_group_status_grid => Router::$controller.'/'.$group_type.'_grid/'.$host
				);

			} else {
				$label_group_status_overview = $this->translate->_('Status overview');
				$label_group_status_summary = $this->translate->_('Status summary');
				$label_group_status_grid = $this->translate->_('Service status grid');
				$label_service_status_details = $this->translate->_('Service status detail for All service groups');
				$label_view_for = $this->translate->_('for this service group');
				$page_links = array(
					$label_group_status_overview => Router::$controller.'/'.$group_type.'group/'.$host,
					$label_group_status_summary => Router::$controller.'/'.$group_type.'group/'.$host.'?style=summary',
					$label_group_status_grid => Router::$controller.'/'.$group_type.'group_grid/'.$host,
					$label_service_status_details => Router::$controller.'/'.$group_type.'group/all?style=detail'
				);
			}
		}

		# fetch all comments to be able to detect if we should display comment icon
		$host_comments = Comment_Model::count_comments_by_object();
		$this->template->content->host_comments = $host_comments;
		$this->template->content->items_per_page = $items_per_page;
		$this->template->content->page = $page;


		if (isset($page_links)) {
			$this->template->content->page_links = $page_links;
			$this->template->content->label_view_for = $label_view_for;
		}
	}

	/**
	 * List status details for hosts and services
	 *
	 * @param $name
	 * @param $servicestatustypes
	 * @param $hoststatustypes
	 * @param $sort_order
	 * @param $sort_field
	 * @param $group_type
	 */
	public function service($name='all', $hoststatustypes=false, $servicestatustypes=false, $service_props=false, $sort_order='ASC', $sort_field='host_name', $group_type=false, $hostprops=false)
	{
		$name = urldecode($this->input->get('name', $name));
		$page = urldecode($this->input->get('page', false));
		$items_per_page = urldecode($this->input->get('items_per_page', config::get('pagination.default.items_per_page', '*')));
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$servicestatustypes = urldecode($this->input->get('servicestatustypes', $servicestatustypes));
		$service_props = urldecode($this->input->get('serviceprops', $service_props));
		$service_props = urldecode($this->input->get('service_props', $service_props));
		$hostprops = urldecode($this->input->get('hostprops', $hostprops));
		$sort_order = urldecode($this->input->get('sort_order', $sort_order));
		$sort_field = urldecode($this->input->get('sort_field', $sort_field));
		$group_type = urldecode($this->input->get('group_type', $group_type));
		$noheader = urldecode($this->input->get('noheader', false));
		$group_type = strtolower($group_type);

		$name = trim($name);
		$hoststatustypes = strtolower($hoststatustypes);
		$hoststatustypes = $hoststatustypes ==='false' || $hoststatustypes ==='true' ? false : $hoststatustypes;

		$servicestatustypes = strtolower($servicestatustypes);
		$servicestatustypes = $servicestatustypes ==='false' || $servicestatustypes==='true' ? false : $servicestatustypes;

		$srv_replace = array(
			1  => $this->translate->_('OK'),
			2  => $this->translate->_('Warning'),
			4  => $this->translate->_('Critical'),
			8  => $this->translate->_('Unknown'),
			14 => $this->translate->_('All problems'),
			64 => $this->translate->_('Pending'),
			65 => $this->translate->_('Non-problem services'),
			71 => $this->translate->_('All services'),
			78 => $this->translate->_('All problems')
		);

		$host_replace = array(
			1  => $this->translate->_('Host OK'),
			2  => $this->translate->_('Host Down'),
			4  => $this->translate->_('Host Unreachable'),
			6  => $this->translate->_('All host problems'),
			64 => $this->translate->_('Host Pending'),
			65 => $this->translate->_('Non-problem hosts'),
			71 => $this->translate->_('All hosts'),
		);

		$title = $this->translate->_('Monitoring » Service details').
			($hoststatustypes != false ? ' » '.$host_replace[$hoststatustypes] : '').
			($servicestatustypes != false ? ' » '.$srv_replace[$servicestatustypes] : '');

		$this->template->title = $title;

		$sort_order = $sort_order == 'false' || empty($sort_order) ? 'ASC' : $sort_order;
		$sort_field = $sort_field == 'false' || empty($sort_field) ? 'host_name' : $sort_field;

		$this->hoststatustypes = $hoststatustypes;
		$this->hostprops = $hostprops;
		$this->servicestatustypes = $servicestatustypes;
		$this->serviceprops = $service_props;
		$filters = $this->_show_filters();

		$this->template->content = $this->add_view('status/service');
		$this->template->content->noheader = $noheader;
		$this->template->content->filters = $filters;
		$this->template->content->group_type = $group_type;
		$this->template->js_header = $this->add_view('js_header');
		$this->template->css_header = $this->add_view('css_header');

		widget::add('status_totals', array($this->current, $name, $hoststatustypes, $servicestatustypes, $group_type), $this);
		//$this->xtra_css = array_merge($this->xtra_css, array($this->add_path('/css/default/common.css')));
		$this->template->content->widgets = $this->widgets;
		$this->template->js_header->js = $this->xtra_js;
		$this->template->css_header->css = $this->xtra_css;
		$this->template->content->na_str = $this->translate->_('N/A');
		$this->template->content->date_format_str = nagstat::date_format();

		# set sort images, used in header_links() below
		$this->img_sort_up = $this->img_path('icons/arrow-up.gif');
		$this->img_sort_down = $this->img_path('icons/arrow-down.gif');

		# assign specific header fields and values for current method
		$header_link_fields = array(
			array('title' => $this->translate->_('Host'), 'sort_field_db' => 'h.host_name', 'sort_field_str' => 'host name'),
			array('title' => $this->translate->_('Status'), 'sort_field_db' => 's.current_state', 'sort_field_str' => 'service status'),
			array('title' => $this->translate->_('Service'), 'sort_field_db' => 's.service_description', 'sort_field_str' => 'service name'),
			array('title' => $this->translate->_('Last Check'), 'sort_field_db' => 'last_check', 'sort_field_str' => 'last check time'),
			array('title' => $this->translate->_('Duration'), 'sort_field_db' => 'duration', 'sort_field_str' => 'state duration'),
			array('title' => $this->translate->_('Attempt'), 'sort_field_db' => 's.current_attempt', 'sort_field_str' => 'attempt'),
			array('title' => $this->translate->_('Status Information'), 'sort_field_db' => 'service.output', 'sort_field_str' => 'status information')
		);

		$show_display_name = config::get('config.show_display_name', '*');
		if ($show_display_name) {
			$header_link_fields[] = array('title' => $this->translate->_('Display Name'), 'sort_field_db' => 'service.display_name', 'sort_field_str' => 'display name');
		}
		$this->template->content->show_display_name = $show_display_name;

		$show_notes = config::get('config.show_notes', '*');
		if ($show_notes) {
			$header_link_fields[] = array('title' => $this->translate->_('Notes'), 'sort_field_db' => 'host.notes', 'sort_field_str' => 'notes');
		}
		$this->template->content->show_notes = $show_notes;


		# build header links array
		foreach ($header_link_fields as $fields) {
			if (sizeof($fields) > 1) {
				$header_links[] = $this->header_links('service', $name, $fields['title'], Router::$method, $fields['sort_field_db'], $fields['sort_field_str'], $hoststatustypes, $servicestatustypes, $service_props,$hostprops);
			} else {
				$header_links[] = $this->header_links('service', $name, $fields['title']);
			}
		}

		$this->template->content->header_links = $header_links;

		$shown = strtolower($name) == 'all' ? $this->translate->_('All Hosts') : $this->translate->_('Host')." '".$name."'";

		# handle host- or servicegroup details
		$host_model = new Host_Model();
		$host_model->show_services = true;
		$host_model->state_filter = $hoststatustypes;
		$host_model->service_filter = $servicestatustypes;
		$host_model->set_sort_field($sort_field);
		$host_model->set_sort_order($sort_order);
		$host_model->serviceprops = $service_props;
		$host_model->hostprops = $hostprops;

		$this->template->content->is_svc_details = false;

		if (!empty($group_type)) {
			$shown = $group_type == 'servicegroup' ? $this->translate->_('Service Group') : $this->translate->_('Host Group');
			$shown .= " '".$name."'";
			# convert 'servicegroup' to 'service' and 'hostgroup' to 'host'
			$grouptype = str_replace('group', '', $group_type);
			$hostlist = Group_Model::get_group_hoststatus($grouptype, $name, $hoststatustypes, $servicestatustypes);
			$group_hosts = false;
			if ($hostlist !== false)
				foreach ($hostlist as $host_info) {
					$group_hosts[] = $host_info->host_name;
				}

			# servicegroups should only show services in the group
			if ($group_type == 'servicegroup') {
				$result = Group_Model::get_group_info($grouptype, $name, $hoststatustypes, $servicestatustypes, $service_props, $hostprops);
				$tot = $result !== false ? count($result) : 0;
				unset($result);
				$pagination = new Pagination(
					array(
						'total_items'=> $tot,
						'items_per_page' => $items_per_page
					)
				);
				$limit = $pagination->sql_limit;
				$result = Group_Model::get_group_info($grouptype, $name, $hoststatustypes,
					$servicestatustypes, $service_props, $hostprops, $limit, $sort_field, $sort_order);
				$this->template->content->is_svc_details = true;
			} else {
				$host_model->num_per_page = false;
				$host_model->offset = false;
				$host_model->count = true;

				$host_model->set_host_list($group_hosts);
				$result_cnt = $host_model->get_host_status();

				$tot = $result_cnt !== false ? $result_cnt : 0;
				$pagination = new Pagination(
					array(
						'total_items'=> $tot,
						'items_per_page' => $items_per_page
					)
				);
				$offset = $pagination->sql_offset;
				$host_model->count = false;
				$host_model->num_per_page = $items_per_page;
				$host_model->offset = $offset;

				$host_model->set_host_list($group_hosts);
				$host_model->set_sort_field($sort_field);
				$host_model->set_sort_order($sort_order);

				$result = $host_model->get_host_status();
			}
		} else {
			$host_model->num_per_page = false;
			$host_model->offset = false;
			$host_model->count = true;

			if (strstr($name, ',')) {
				$name = explode(',', $name);
			}

			$host_model->set_host_list($name);
			$result_cnt = $host_model->get_host_status();
			$tot = $result_cnt !== false ? $result_cnt : 0;
			$pagination = new Pagination(
				array(
					'total_items'=> $tot,
					'items_per_page' => $items_per_page
				)
			);
			$offset = $pagination->sql_offset;
			$host_model->count = false;
			$host_model->num_per_page = $items_per_page;
			$host_model->offset = $offset;

			$host_model->set_sort_field($sort_field);
			$host_model->set_sort_order($sort_order);

			$host_model->set_host_list($name);
			$result = $host_model->get_host_status();
		}
		$sub_title = $this->translate->_('Service Status Details For').' '.$shown;
		$this->template->content->sub_title = $sub_title;

		$this->template->content->pending_output = $this->translate->_('Service check scheduled for %s');
		$this->template->content->nocheck_output = $this->translate->_('Service is not scheduled to be checked...');
		$this->template->content->result = $result;
		$this->template->content->pagination = isset($pagination) ? $pagination : false;
		$this->template->content->logos_path = $this->logos_path;
		$this->template->content->style = 'detail';
		if (empty($group_type)) {
			if ($name == 'all') {
				$label_host_history = $this->translate->_('Alert history');
				$label_host_notifications = $this->translate->_('Notifications');
				$label_host_status_details = $this->translate->_('Host status detail');
				$label_view_for = $this->translate->_('for all hosts');
				$page_links = array(
					 $label_host_history => 'showlog/alert_history/',
					 $label_host_notifications => 'notifications/host/'.$name,
					 $label_host_status_details => Router::$controller.'/host/all'
				);
			} else {
				$label_host_history = $this->translate->_('Alert history');
				$label_host_notifications = $this->translate->_('Notifications');
				$label_host_status_details = $this->translate->_('Service status detail for All hosts');
				$label_view_for = $this->translate->_('for this host');
				$page_links = array(
					 $label_host_history => 'showlog/alert_history/'.$name,
					 $label_host_notifications => 'notifications/host/'.$name,
					 $label_host_status_details => Router::$controller.'/service/all',
				);
			}
		} else {
			if ($group_type == 'hostgroup') {
				if ($name == 'all') {
					$label_group_status_details = $this->translate->_('Host status detail');
					$label_group_status_overview = $this->translate->_('Status overview');
					$label_group_status_summary = $this->translate->_('Status summary');
					$label_group_status_grid = $this->translate->_('Status grid');
					$label_view_for = $this->translate->_('for all host groups');
					$page_links = array(
						$label_group_status_details => Router::$controller.'/host/all',
						$label_group_status_overview => Router::$controller.'/'.$group_type.'/all',
						$label_group_status_summary => Router::$controller.'/'.$group_type.'/all?style=summary',
						$label_group_status_grid => Router::$controller.'/'.$group_type.'_grid/all',
					);
				} else {
					$label_group_status_details_all = $this->translate->_('Service status detail for All host groups');
					$label_group_status_details = $this->translate->_('Host status detail');
					$label_group_status_overview = $this->translate->_('Status overview');
					$label_group_status_summary = $this->translate->_('Status summary');
					$label_group_status_grid = $this->translate->_('Status grid');
					$label_view_for = $this->translate->_('for this host group');
					$page_links = array(
						$label_group_status_details_all => Router::$controller.'/'.$group_type.'/all?style=detail',
						$label_group_status_details => Router::$controller.'/host/'.$name.'?group_type='.$group_type,
						$label_group_status_overview => Router::$controller.'/'.$group_type.'/'.$name,
						$label_group_status_summary => Router::$controller.'/'.$group_type.'_summary/'.$name,
						$label_group_status_grid => Router::$controller.'/'.$group_type.'_grid/'.$name,
					);
				}
			} else {
				# servicegroup links
				if ($name == 'all') {
					$label_service_status_overview = $this->translate->_('Status overview');
					$label_group_status_summary = $this->translate->_('Status summary');
					$label_host_status_grid = $this->translate->_('Service status grid');
					$label_view_for = $this->translate->_('for all service groups');
					$page_links = array(
						$label_service_status_overview => Router::$controller.'/'.$group_type.'/all',
						$label_group_status_summary => Router::$controller.'/'.$group_type.'/all?style=summary',
						$label_host_status_grid => Router::$controller.'/'.$group_type.'_grid/all'
					);
				} else {
					$label_group_status_overview = $this->translate->_('Status overview');
					$label_group_status_summary = $this->translate->_('Status summary');
					$label_group_status_grid = $this->translate->_('Service status grid');
					$label_group_status_details = $this->translate->_('Service status detail for All service groups');
					$label_view_for = $this->translate->_('for this service group');
					$page_links = array(
						$label_group_status_overview => Router::$controller.'/'.$group_type.'/'.$name,
						$label_group_status_summary => Router::$controller.'/'.$group_type.'/'.$name.'?style=summary',
						$label_group_status_grid => Router::$controller.'/'.$group_type.'_grid/'.$name,
						$label_group_status_details => Router::$controller.'/'.$group_type.'/all'
					);
				}
			}
		}

		# fetch all comments to be able to detect if we should display comment icon
		$host_comments = Comment_Model::count_comments_by_object();
		$this->template->content->host_comments = $host_comments;

		$svc_comments = Comment_Model::count_comments_by_object(true);
		$this->template->content->comments = $svc_comments;
		$this->template->content->items_per_page = $items_per_page;
		$this->template->content->page = $page;

		if (isset($page_links)) {
			$this->template->content->page_links = $page_links;
			$this->template->content->label_view_for = $label_view_for;
		}
	}

	/**
	 * Show servicegroup status, wrapper for group('service', ...)
	 * @param $group
	 * @param $hoststatustypes
	 * @param $servicestatustypes
	 * @param $style
	 *
	 */
	public function servicegroup($group='all', $hoststatustypes=false, $servicestatustypes=false, $style='overview', $serviceprops=false, $hostprops=false)
	{
		$group = urldecode($this->input->get('group', $group));
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$servicestatustypes = urldecode($this->input->get('servicestatustypes', $servicestatustypes));
		$serviceprops = urldecode($this->input->get('serviceprops', $serviceprops));
		$hostprops = urldecode($this->input->get('hostprops', $hostprops));
		$style = urldecode($this->input->get('style', $style));
		$grouptype = 'service';
		$this->template->title = 'Servicegroup';
		return $this->group($grouptype, $group, $hoststatustypes, $servicestatustypes, $style, $serviceprops, $hostprops);
	}

	/**
	 * Show hostgroup status, wrapper for group('host', ...)
	 * @param $group
	 * @param $hoststatustypes
	 * @param $servicestatustypes
	 * @param $style
	 *
	 */
	public function hostgroup($group='all', $hoststatustypes=false, $servicestatustypes=false, $style='overview', $serviceprops=false, $hostprops=false)
	{
		$group = urldecode($this->input->get('group', $group));
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$servicestatustypes = urldecode($this->input->get('servicestatustypes', $servicestatustypes));
		$serviceprops = urldecode($this->input->get('serviceprops', $serviceprops));
		$hostprops = urldecode($this->input->get('hostprops', $hostprops));
		$style = urldecode($this->input->get('style', $style));
		$grouptype = 'host';
		return $this->group($grouptype, $group, $hoststatustypes, $servicestatustypes, $style, $serviceprops, $hostprops);
	}

	/**
	 * Show status for host- or servicegroups
	 *
	 * @param $grouptype
	 * @param $group
	 * @param $hoststatustypes
	 * @param $servicestatustypes
	 * @param $style
	 */
	public function group($grouptype='service', $group='all', $hoststatustypes=false, $servicestatustypes=false, $style='overview', $serviceprops=false, $hostprops=false)
	{
		$items_per_page = urldecode($this->input->get('items_per_page', config::get('pagination.default.items_per_page', '*')));
		$grouptype = urldecode($this->input->get('grouptype', $grouptype));
		$group = urldecode($this->input->get('group', $group));
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$servicestatustypes = urldecode($this->input->get('servicestatustypes', $servicestatustypes));
		$serviceprops = urldecode($this->input->get('serviceprops', $serviceprops));
		$hostprops = urldecode($this->input->get('hostprops', $hostprops));
		$style = urldecode($this->input->get('style', $style));
		$noheader = urldecode($this->input->get('noheader', false));
		$group = trim($group);
		$hoststatustypes = strtolower($hoststatustypes)==='false' ? false : $hoststatustypes;

		$this->hoststatustypes = $hoststatustypes;
		$this->hostprops = $hostprops;
		$this->servicestatustypes = $servicestatustypes;
		$this->serviceprops = $serviceprops;
		$filters = $this->_show_filters();

		switch ($style) {
			case 'overview':
				$this->template->title = $this->translate->_('Monitoring » ').$grouptype.$this->translate->_('group overview');
				$this->template->header = $this->translate->_('Monitoring » ').$grouptype.$this->translate->_('group overview');
				$this->template->content = $this->add_view('status/group_overview');
				$this->template->content->filters = $filters;
				$this->template->content->noheader = $noheader;
				break;
			case 'detail': case 'details':
				$this->template->title = $grouptype.$this->translate->_('group » Details');
				return $this->service($group, $hoststatustypes, $servicestatustypes, $serviceprops, false, false, $grouptype.'group', $hostprops);
				break;
			case 'summary':
				return $this->_group_summary($grouptype, $group, $hoststatustypes, $servicestatustypes, $serviceprops, $hostprops);
				break;
		}
		$group_details = false;
		$groupname_tmp = false;
		if ($group == 'all') {
			$auth = new Nagios_auth_Model();
			if ($grouptype == 'host') {
				$auth_groups = $auth->get_authorized_hostgroups();
			} else {
				$auth_groups = $auth->get_authorized_servicegroups();
			}
			$tot = count($auth_groups);
			$pagination = new Pagination(
				array(
					'total_items'=> $tot,
					'items_per_page' => $items_per_page
				)
			);
			$offset = $pagination->sql_offset;
			$this->template->content->pagination = $pagination;
			$group_details = $grouptype == 'service' ? Servicegroup_Model::get_all($items_per_page, $offset) : Hostgroup_Model::get_all($items_per_page, $offset);
		} else {
			$group_details = $grouptype == 'service' ?
				Servicegroup_Model::get_by_field_value('servicegroup_name', $group) :
				Hostgroup_Model::get_by_field_value('hostgroup_name', $group);
		}

		$this->template->content->group_details = $group_details;

		$this->template->js_header = $this->add_view('js_header');
		$this->template->css_header = $this->add_view('css_header');

		$content = $this->template->content;
		$t = $this->translate;

		if ($grouptype == 'service') {
			$content->lable_header = strtolower($group) == 'all' ? $t->_("Service Overview For All Service Groups") : $t->_("Service Overview For Service Group");
		} else {
			$content->lable_header = strtolower($group) == 'all' ? $t->_("Service Overview For All Host Groups") : $t->_("Service Overview For Host Group");
		}
		$content->lable_host = $t->_('Host');
		$content->lable_status = $t->_('Status');
		$content->lable_services = $t->_('Services');
		$content->lable_actions = $t->_('Actions');
		$content->grouptype = $grouptype;
		$content->hoststatustypes = $hoststatustypes;
		$content->servicestatustypes = $servicestatustypes;
		if (empty($group_details)) {
			$this->template->content->error_message = $t->_("No data found");
		}

		widget::add('status_totals', array($this->current, $group, $hoststatustypes, $servicestatustypes, $grouptype.'group', $serviceprops, $hostprops), $this);
		//$this->xtra_css = array_merge($this->xtra_css, array($this->add_path('/css/default/common.css')));
		$this->template->content->widgets = $this->widgets;
		$this->template->js_header->js = $this->xtra_js;
		$this->template->css_header->css = $this->xtra_css;

		if ($grouptype == 'host') {
			if ($group == 'all') {
				$label_host_status_details = $this->translate->_('Service status detail');
				$label_group_status_details = $this->translate->_('Host status detail');
				$label_group_status_summary = $this->translate->_('Status summary');
				$label_host_status_grid = $this->translate->_('Status grid');
				$label_view_for = $this->translate->_('for all host groups');
				$page_links = array(
					$label_host_status_details => Router::$controller.'/'.$grouptype.'group/all?style=detail',
					$label_group_status_details => Router::$controller.'/host/all',
					$label_group_status_summary => Router::$controller.'/'.$grouptype.'group/all?style=summary',
					$label_host_status_grid => Router::$controller.'/'.$grouptype.'group_grid/all'
				);
			} else {

				$label_group_status_overview_all = $this->translate->_('Status overview for All host groups');
				$label_group_status_service_details = $this->translate->_('Service status detail');
				$label_group_status_host_details = $this->translate->_('Host status detail');
				$label_group_status_summary = $this->translate->_('Status summary');
				$label_group_status_grid = $this->translate->_('Service status grid');
				$label_view_for = $this->translate->_('for this host groups');
				$page_links = array(

					$label_group_status_overview_all => Router::$controller.'/'.$grouptype.'group/all?style=summary',
					$label_group_status_service_details => Router::$controller.'/'.$grouptype.'group/'.$group.'?style=detail',
					$label_group_status_host_details => Router::$controller.'/host/'.$group.'?group_type='.$grouptype.'group',
					$label_group_status_summary => Router::$controller.'/'.$grouptype.'group/'.$group.'?style=summary',
					$label_group_status_grid => Router::$controller.'/'.$grouptype.'group_grid/'.$group.'?style=summary'
				);
			}
		} else {
			if ($group == 'all') {
				$label_service_status_details = $this->translate->_('Service status detail');
				$label_group_status_summary = $this->translate->_('Status summary');
				$label_host_status_grid = $this->translate->_('Service status grid');
				$label_view_for = $this->translate->_('for all service groups');
				$page_links = array(
					$label_service_status_details => Router::$controller.'/'.$grouptype.'group/all?style=detail',
					$label_group_status_summary => Router::$controller.'/'.$grouptype.'group/all?style=summary',
					$label_host_status_grid => Router::$controller.'/'.$grouptype.'group_grid/all'
				);
			} else {
				$label_group_status_overview = $this->translate->_('Status overview');
				$label_group_status_summary = $this->translate->_('Status summary');
				$label_group_status_grid = $this->translate->_('Service status grid');
				$label_group_status_details = $this->translate->_('Service status detail for All service groups');
				$label_view_for = $this->translate->_('for this service groups');
				$page_links = array(
					$label_group_status_overview => Router::$controller.'/'.$grouptype.'group/'.$group,
					$label_group_status_summary => Router::$controller.'/'.$grouptype.'group/'.$group.'?style=summary',
					$label_group_status_grid => Router::$controller.'/'.$grouptype.'group_grid/'.$group,
					$label_group_status_details => Router::$controller.'/'.$grouptype.'/all'
				);
			}
		}
		if (isset($page_links)) {
			$this->template->content->page_links = $page_links;
			$this->template->content->label_view_for = $label_view_for;
		}
	}

	/**
	 * Display servicegroup summary
	 */
	public function servicegroup_summary($group='all', $hoststatustypes=false, $servicestatustypes=false, $serviceprops=false, $hostprops=false)
	{
		$group = urldecode($this->input->get('group', $group));
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$servicestatustypes = urldecode($this->input->get('servicestatustypes', $servicestatustypes));
		$serviceprops = urldecode($this->input->get('serviceprops', $serviceprops));
		$hostprops = urldecode($this->input->get('hostprops', $hostprops));
		$grouptype = 'service';
		$this->template->title = $this->translate->_('Servicegroup » Summary');
		return $this->_group_summary($grouptype, $group, $hoststatustypes, $servicestatustypes, $serviceprops, $hostprops);
	}

	public function hostgroup_summary($group='all', $hoststatustypes=false, $servicestatustypes=false, $serviceprops=false, $hostprops=false)
	{
		$group = urldecode($this->input->get('group', $group));
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$servicestatustypes = urldecode($this->input->get('servicestatustypes', $servicestatustypes));
		$serviceprops = urldecode($this->input->get('serviceprops', $serviceprops));
		$hostprops = urldecode($this->input->get('hostprops', $hostprops));
		$grouptype = 'host';
		return $this->_group_summary($grouptype, $group, $hoststatustypes, $servicestatustypes, $serviceprops, $hostprops);
	}

	/**
	*	Create group summary page
	*/
	public function _group_summary($grouptype='service', $group='all', $hoststatustypes=false, $servicestatustypes=false, $serviceprops=false, $hostprops=false)
	{
		$items_per_page = urldecode($this->input->get('items_per_page', config::get('pagination.default.items_per_page', '*')));
		$grouptype = urldecode($this->input->get('grouptype', $grouptype));
		$group = urldecode($this->input->get('group', $group));
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$servicestatustypes = urldecode($this->input->get('servicestatustypes', $servicestatustypes));
		$serviceprops = urldecode($this->input->get('serviceprops', $serviceprops));
		$hostprops = urldecode($this->input->get('hostprops', $hostprops));
		$noheader = urldecode($this->input->get('noheader', false));
		$this->template->title = $this->translate->_('Monitoring » ').$grouptype.$this->translate->_('group summary');

		$group = trim($group);
		$this->template->content = $this->add_view('status/group_summary');
		$this->template->content->noheader = $noheader;
		$content = $this->template->content;
		$t = $this->translate;

		$this->hoststatustypes = $hoststatustypes;
		$this->hostprops = $hostprops;
		$this->servicestatustypes = $servicestatustypes;
		$this->serviceprops = $serviceprops;
		$filters = $this->_show_filters();
		$this->template->content->filters = $filters;

		$this->template->js_header = $this->add_view('js_header');
		$this->template->css_header = $this->add_view('css_header');

		widget::add('status_totals', array($this->current, $group, $hoststatustypes, $servicestatustypes, $grouptype.'group', $serviceprops, $hostprops), $this);
		$this->template->content->widgets = $this->widgets;
		$this->template->js_header->js = $this->xtra_js;

		$group_details = false;
		$auth_groups = false;
		$group_info_res = false;
		if (strtolower($group) == 'all') {
			$auth = new Nagios_auth_Model();
			if ($grouptype == 'host') {
				$auth_groups = $auth->get_authorized_hostgroups();
			} else {
				$auth_groups = $auth->get_authorized_servicegroups();
			}
			$tot = count($auth_groups);
			$pagination = new Pagination(
				array(
					'total_items'=> $tot,
					'items_per_page' => $items_per_page
				)
			);
			$offset = $pagination->sql_offset;
			$content->pagination = $pagination;

			$content->lable_header = $grouptype == 'service' ? $t->_('Status Summary For All Service Groups') : $t->_('Status Summary For All Host Groups');
			$group_info_res = $grouptype == 'service'
				? Servicegroup_Model::summary($group, $items_per_page, $offset, $hostprops, $serviceprops, $hoststatustypes, $servicestatustypes)
				: Hostgroup_Model::summary($group, $items_per_page, $offset, $hostprops, $serviceprops, $hoststatustypes, $servicestatustypes);
		} else {
			# make sure we have the correct group
			$group_info_res = $grouptype == 'service' ?
				Servicegroup_Model::get_by_field_value('servicegroup_name', $group) :
				Hostgroup_Model::get_by_field_value('hostgroup_name', $group);
			if ($group_info_res) {
				$group_info_res = $group_info_res->current();
				$group = $group_info_res->{$grouptype.'group_name'}; # different field depending on object type
			} else {
				# overwrite previous view with the error view, add some text and bail out
				$this->template->content = $this->add_view('error');
				$this->template->content->error_message = sprintf($t->_("The requested group ('%s') wasn't found"), $group);
				return;
			}
			$label_header = $grouptype == 'service' ? $t->_('Status Summary For Service Group ') : $t->_('Status Summary For Host Group ');
			$content->lable_header = $label_header."'".$group."'";
			$group_info_res = $grouptype == 'service' ? Servicegroup_Model::summary($group) : Hostgroup_Model::summary($group);
		}
		if (count($group_info_res)) {
			$content->group_details = $group_info_res;
		}

		$content->grouptype = $grouptype;
		$content->hoststatustypes = $hoststatustypes;
		$content->hostproperties = $hostprops;
		$content->servicestatustypes = $servicestatustypes;
		$content->serviceproperties = $serviceprops;
		$content->label_group_name = $t->_('Service Group');
		$content->label_host_summary = $t->_('Host Status Summary');
		$content->label_service_summary = $t->_('Service Status Summary');
		$content->label_no_data = $t->_('No data found');
		$content->label_up = $t->_('UP');
		$content->label_down = $t->_('DOWN');
		$content->label_unhandled = $t->_('Unhandled');
		$content->label_scheduled = $t->_('Scheduled');
		$content->label_acknowledged = $t->_('Acknowledged');
		$content->label_disabled = $t->_('Disabled');
		$content->label_unreachable = $t->_('UNREACHABLE');
		$content->label_pending = $t->_('PENDING');
		$content->label_ok = $t->_('OK');
		$content->label_warning = $t->_('WARNING');
		$content->label_on_problem_hosts = $t->_('on Problem Hosts');
		$content->label_unknown = $t->_('UNKNOWN');
		$content->label_critical = $t->_('CRITICAL');
		$content->label_no_servicedata = $t->_('No matching services');

		if ($grouptype == 'host') {
			$content->label_group_name = $t->_('Host Group');
			if ($group == 'all') {
				$label_host_status_details = $this->translate->_('Service status detail');
				$label_group_status_details = $this->translate->_('Host status detail');
				$label_group_status_overview = $this->translate->_('Status overview');
				$label_host_status_grid = $this->translate->_('Status grid');
				$label_view_for = $this->translate->_('For all host groups');
				$page_links = array(
					$label_host_status_details => Router::$controller.'/'.$grouptype.'group/all?style=detail',
					$label_group_status_details => Router::$controller.'/host/all?group_type='.$grouptype.'group',
					$label_group_status_overview => Router::$controller.'/'.$grouptype.'group/all',
					$label_host_status_grid => Router::$controller.'/'.$grouptype.'group_grid/all'
				);
			} else {
				$label_group_status_summary = $this->translate->_('Status summary for All host groups');
				$label_group_service_status_details = $this->translate->_('Service status detail');
				$label_group_host_status_details = $this->translate->_('Host status detail');
				$label_group_status_overview = $this->translate->_('Status overview');
				$label_group_status_grid = $this->translate->_('Status grid');
				$label_view_for = $this->translate->_('For this host groups');
				$page_links = array(
					$label_group_status_summary => Router::$controller.'/'.$grouptype.'group/all?style=summary',
					$label_group_service_status_details => Router::$controller.'/'.$grouptype.'group/'.$group.'?style=detail',
					$label_group_host_status_details => Router::$controller.'/host/'.$group.'?group_type='.$grouptype.'group',
					$label_group_status_overview => Router::$controller.'/'.$grouptype.'group/'.$group,
					$label_group_status_grid => Router::$controller.'/'.$grouptype.'_grid/'.$group
				);
			}

		} else {
			$content->label_group_name = $t->_('Service Group');
			if ($group == 'all') {
				$label_service_status_details = $this->translate->_('Service status detail');
				$label_service_status_overview = $this->translate->_('Status overview');
				$label_service_status_grid = $this->translate->_('Service status grid');
				$label_view_for = $this->translate->_('For all service groups');
				$page_links = array(
					$label_service_status_details => Router::$controller.'/servicegroup/all?style=detail',
					$label_service_status_overview => Router::$controller.'/servicegroup/all',
					$label_service_status_grid => Router::$controller.'/servicegroup_grid/all'
				);
			} else {
				$label_service_status_details = $this->translate->_('Service status detail');
				$label_group_status_overview = $this->translate->_('Status overview');
				$label_group_status_grid = $this->translate->_('Service status grid');
				$label_group_status_summary = $this->translate->_('Status summary for All service groups');
				$label_view_for = $this->translate->_('For this service group');
				$page_links = array(
						$label_service_status_details => Router::$controller.'/host/'.$group.'?group_type='.$grouptype.'group',
						$label_group_status_overview => Router::$controller.'/'.$grouptype.'group/'.$group,
						$label_group_status_grid => Router::$controller.'/'.$grouptype.'group_grid/'.$group,
						$label_group_status_summary => Router::$controller.'/'.$grouptype.'group/all?style=summary'
					);
			}
		}
		if (isset($page_links)) {
			$this->template->content->page_links = $page_links;
			$this->template->content->label_view_for = $label_view_for;
		}
	}

	/**
	*	Show a grid of hostgroup(s)
	* 	A wrapper for group_grid('host')
	*
	*/
	public function hostgroup_grid($group='all', $hoststatustypes=false, $servicestatustypes=false)
	{
		$group = urldecode($this->input->get('group', $group));
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$servicestatustypes = urldecode($this->input->get('servicestatustypes', $servicestatustypes));

		$grouptype = 'host';
		return $this->group_grid($grouptype, $group, $hoststatustypes, $servicestatustypes);
	}

	/**
	*	Show a grid of servicegroup(s)
	* 	A wrapper for group_grid('services')
	*
	*/
	public function servicegroup_grid($group='all', $hoststatustypes=false, $servicestatustypes=false)
	{
		$group = urldecode($this->input->get('group', $group));
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$servicestatustypes = urldecode($this->input->get('servicestatustypes', $servicestatustypes));

		$grouptype = 'service';
		return $this->group_grid($grouptype, $group, $hoststatustypes, $servicestatustypes);
	}

	/**
	*	show a grid layout of host- or servicegroup(s)
	*
	*/
	public function group_grid($grouptype='service', $group='all', $hoststatustypes=false, $servicestatustypes=false)
	{
		$items_per_page = urldecode($this->input->get('items_per_page', config::get('pagination.default.items_per_page', '*')));
		$grouptype = urldecode($this->input->get('grouptype', $grouptype));
		$group = urldecode($this->input->get('group', $group));
		$hoststatustypes = urldecode($this->input->get('hoststatustypes', $hoststatustypes));
		$servicestatustypes = urldecode($this->input->get('servicestatustypes', $servicestatustypes));
		$noheader = urldecode($this->input->get('noheader', false));
		$group = trim($group);
		$this->template->content = $this->add_view('status/group_grid');
		$this->template->content->noheader = $noheader;
		$content = $this->template->content;
		$t = $this->translate;

		$this->hoststatustypes = $hoststatustypes;
		$this->servicestatustypes = $servicestatustypes;
		$filters = $this->_show_filters();
		$this->template->content->filters = $filters;

		$this->template->title = $this->translate->_('Monitoring » ').$grouptype.$this->translate->_('group grid');

		$this->template->js_header = $this->add_view('js_header');
		$this->template->css_header = $this->add_view('css_header');

		widget::add('status_totals', array($this->current, $group, $hoststatustypes, $servicestatustypes, $grouptype.'group'), $this);
		$this->template->content->widgets = $this->widgets;
		$this->template->js_header->js = $this->xtra_js;
		//$this->template->css_header->css = array_merge($this->xtra_css, array($this->add_path('/css/default/common.css')));

		$content->label_host = $t->_('Host');
		$content->label_services = $t->_('Services');
		$content->label_actions = $t->_('Actions');

		$group_details = false;
		if (strtolower($group) == 'all') {
			$auth = new Nagios_auth_Model();
			if ($grouptype == 'host') {
				$auth_groups = $auth->get_authorized_hostgroups();
			} else {
				$auth_groups = $auth->get_authorized_servicegroups();
			}

			$tot = count($auth_groups);
			$pagination = new Pagination(
				array(
					'total_items'=> $tot,
					'items_per_page' => $items_per_page
				)
			);
			$offset = $pagination->sql_offset;
			$content->pagination = $pagination;

			$content->label_header = $grouptype == 'service' ? $t->_('Status Grid For All Service Groups') : $t->_('Status Grid For All Host Groups');
			$group_info_res = $grouptype == 'service' ? Servicegroup_Model::get_all($items_per_page, $offset) : Hostgroup_Model::get_all($items_per_page, $offset);
		} else {
			# make sure we have the correct servicegroup
			$group_info_res = $grouptype == 'service' ?
				Servicegroup_Model::get_by_field_value('servicegroup_name', $group) :
				Hostgroup_Model::get_by_field_value('hostgroup_name', $group);
			$label_header = $grouptype == 'service' ? $t->_('Status Grid For Service Group ') : $t->_('Status Grid For Host Group ');
			$content->label_header = $label_header."'".$group."'";
		}

		$content->group_details = $group_info_res;
		$content->error_message = sprintf($t->_('No %s group data found'), $grouptype);
		$content->grouptype = $grouptype;
		$content->logos_path = $this->logos_path;
		$content->icon_path	= $this->img_path('icons/16x16/');
		$content->label_host_extinfo = $t->_('View Extended Information For This Host');
		$content->label_service_status = $t->_('View Service Details For This Host');
		$content->label_status_map = $t->_('Locate Host On Map');
		$nacoma_link = false;
		/**
		 * Modify config/config.php to enable NACOMA
		 * and set the correct path in config/config.php,
		 * if installed, to use this
		 */
		if (nacoma::link()===true) {
			$content->label_nacoma = $t->_('Configure this host using NACOMA (Nagios Configuration Manager)');
			$content->nacoma_path = Kohana::config('config.nacoma_path');
		}

		/**
		 * Enable PNP4Nagios integration
		 * Set correct path in config/config.php
		 */
		$pnp_link = false;
		if (Kohana::config('config.pnp4nagios_path')!==false) {
			$content->label_pnp = $t->_('Show performance graph');
			$content->pnp_path = url::base(true) . 'pnp/?';
		}

		if ($grouptype == 'host') {
			if ($group == 'all') {
				$label_host_status_details = $this->translate->_('Service status detail');
				$label_group_status_details = $this->translate->_('Host status detail');
				$label_group_status_overview = $this->translate->_('Status overview');
				$label_group_status_summary = $this->translate->_('Status summary');
				$label_view_for = $this->translate->_('for all host groups');
				$page_links	 = array(
					$label_host_status_details => Router::$controller.'/'.$grouptype.'group/all?style=detail',
					$label_group_status_details => Router::$controller.'/host/all',
					$label_group_status_overview => Router::$controller.'/'.$grouptype.'group/all',
					$label_group_status_summary => Router::$controller.'/'.$grouptype.'group/all?style=summary'
				);
			} else {
				$label_host_status_grid = $this->translate->_('Status grid for All host groups');
				$label_group_service_status_details = $this->translate->_('Service status detail');
				$label_group_host_status_details = $this->translate->_('Host status detail');
				$label_group_status_overview = $this->translate->_('Status overview');
				$label_group_status_summary = $this->translate->_('Status summary');
				$label_view_for = $this->translate->_('for this host group');
				$page_links = array(
					$label_host_status_grid => Router::$controller.'/'.$grouptype.'group_grid/all',
					$label_group_service_status_details => Router::$controller.'/'.$grouptype.'group/'.$group.'?style=detail',
					$label_group_host_status_details => Router::$controller.'/host/'.$group.'?group_type='.$grouptype.'group',
					$label_group_status_overview => Router::$controller.'/'.$grouptype.'group/'.$group,
					$label_group_status_summary => Router::$controller.'/'.$grouptype.'group/'.$group.'?style=summary'
				);
			}
		} else {
			if ($group == 'all') {
				$label_service_status_details = $this->translate->_('Service status detail');
				$label_service_status_overview = $this->translate->_('Status overview');
				$label_group_status_summary = $this->translate->_('Status summary');
				$label_view_for = $this->translate->_('for all service groups');
				$page_links = array(
					$label_service_status_details => Router::$controller.'/'.$grouptype.'group/all?style=detail',
					$label_service_status_overview => Router::$controller.'/'.$grouptype.'group/all',
					$label_group_status_summary => Router::$controller.'/'.$grouptype.'group/all?style=summary'
				);
			} else {
				$label_service_status_details = $this->translate->_('Service status detail');
				$label_group_status_overview = $this->translate->_('Status overview');
				$label_group_status_summary = $this->translate->_('Status summary');
				$label_host_status_grid = $this->translate->_('Service status grid for All service groups');
				$label_view_for = $this->translate->_('for this service group');
				$page_links = array(
						$label_service_status_details => Router::$controller.'/host/'.$group.'?group_type='.$grouptype.'group',
						$label_group_status_overview => Router::$controller.'/'.$grouptype.'group/'.$group,
						$label_group_status_summary => Router::$controller.'/'.$grouptype.'group/'.$group.'?style=summary',
						$label_host_status_grid => Router::$controller.'/'.$grouptype.'group_grid/all'
					);
			}

		}
		if (isset($page_links)) {
			$this->template->content->page_links = $page_links;
			$this->template->content->label_view_for = $label_view_for;
		}
	}

	/**
	*	displays status grid for a specific host- or servicegroup
	*
	*/
	public function _show_grid($grouptype='service', $group=false)
	{
		$grouptype = urldecode($this->input->get('grouptype', $grouptype));
		$group = urldecode($this->input->get('group', $group));

		$service_info = array();
		$result = Group_Model::get_group_info($grouptype, $group);
		$content = false;
		$hosts = array();
		$seen_hosts = array();
		if (!empty($result) && count($result) > 0) {
			foreach ($result as $row) {
				# loop over result and assign to return variable
				if (!in_array($row->host_name, $seen_hosts)) {
					$hosts[] = array(
						'host_name' => $row->host_name,
						'current_state' => $row->current_state,
						'notes_url' => nagstat::process_macros($row->host_notes_url, $row),
						'action_url' => nagstat::process_macros($row->host_action_url, $row),
						'icon_image' => $row->host_icon_image,
						'icon_image_alt' => $row->host_icon_image_alt
					);
					$seen_hosts[] = $row->host_name;
				}
				$service_info[$row->host_name][] = array(
					'current_state' => $row->service_state,
					'service_description' => $row->service_description
				);
			}
		} else {
			return false;
		}
		$content->hosts = $hosts;
		$content->services = $service_info;
		$content->group_name = $group;
		$content->group_type = $grouptype;
		return $content;
	}

	/**
	 * Create header links for status listing
	 */
	private function header_links(
			$type='host',
			$filter_object='all',
			$title=false,
			$method=false,
			$sort_field_db=false,
			$sort_field_str=false,
			$host_status=false,
			$service_status=false,
			$service_props=false,
			$host_props=false)
	{

		$type = trim($type);
		$filter_object = trim($filter_object);
		$title = trim($title);
		if (empty($type) || empty($title))  {
			return false;
		}
		$header = false;
		$lable_ascending = $this->translate->_('ascending');
		$lable_descending = $this->translate->_('descending');
		$lable_sort_by = $this->translate->_('Sort by');
		$lable_last = $this->translate->_('last');
		switch ($type) {
			case 'host':
				$header['title'] = $title;
				if (!empty($method) &&!empty($filter_object) && !empty($sort_field_db)) {
					$header['url_asc'] = Router::$controller.'/'.$method.'/'.$filter_object.'?hoststatustypes='.$host_status.'&sort_order='.nagstat::SORT_ASC.'&sort_field='.$sort_field_db;
					$header['alt_asc'] = $lable_sort_by.' '.$lable_last.' '.$sort_field_str.' ('.$lable_ascending.')';
					$header['img_asc'] = $this->img_sort_up;
					$header['url_desc'] = Router::$controller.'/'.$method.'/'.$filter_object.'?hoststatustypes='.$host_status.'&sort_order='.nagstat::SORT_DESC.'&sort_field='.$sort_field_db;
					$header['img_desc'] = $this->img_sort_down;
					$header['alt_desc'] = $lable_sort_by.' '.$sort_field_str.' ('.$lable_descending.')';
				}
				break;
			case 'service':
				$header['title'] = $title;
				if (!empty($method) &&!empty($filter_object) && !empty($sort_field_db)) {
					$header['url_asc'] = Router::$controller.'/'.$method.'/'.$filter_object.'?hoststatustypes='.$host_status.'&hostprops='.(int)$host_props.'&servicestatustypes='.$service_status.'&service_props='.(int)$service_props.'&sort_order='.nagstat::SORT_ASC.'&sort_field='.$sort_field_db;
					$header['img_asc'] = $this->img_sort_up;
					$header['alt_asc'] = $lable_sort_by.' '.$lable_last.' '.$sort_field_str.' ('.$lable_ascending.')';
					$header['url_desc'] = Router::$controller.'/'.$method.'/'.$filter_object.'?hoststatustypes='.$host_status.'&hostprops='.(int)$host_props.'&servicestatustypes='.$service_status.'&service_props='.(int)$service_props.'&sort_order='.nagstat::SORT_DESC.'&sort_field='.$sort_field_db;
					$header['img_desc'] = $this->img_sort_down;
					$header['alt_desc'] = $lable_sort_by.' '.$sort_field_str.' ('.$lable_descending.')';
				}
				break;
		}
		return $header;
	}

	/**
	*	shows service and host filters in use
	*/
	public function _show_filters()
	{
		$all_host_status_types =
			nagstat::HOST_PENDING|nagstat::HOST_UP|nagstat::HOST_DOWN|
			nagstat::HOST_UNREACHABLE;

		$all_service_status_types =
			nagstat::SERVICE_PENDING|nagstat::SERVICE_OK|
			nagstat::SERVICE_UNKNOWN|nagstat::SERVICE_WARNING|nagstat::SERVICE_CRITICAL;

		$hostprops = $this->hostprops;

		$hoststatustypes = $this->hoststatustypes === false || $this->hoststatustypes ==='' ?
			$all_host_status_types : $this->hoststatustypes;

		$serviceprops = $this->serviceprops;

		$servicestatustypes = $this->servicestatustypes === false || $this->servicestatustypes ==='' ?
			$all_service_status_types : $this->servicestatustypes;

		/* Don't show filters box if not necessary */
		if (empty($hostprops) && empty($serviceprops) &&
			$hoststatustypes == $all_host_status_types &&
			$servicestatustypes == $all_service_status_types) {
			return false;
		}

		$service_status_types =
			nagstat::SERVICE_PENDING|nagstat::SERVICE_OK|
			nagstat::SERVICE_UNKNOWN|nagstat::SERVICE_WARNING|
			nagstat::SERVICE_CRITICAL;

		$host_status_types = nagstat::HOST_PENDING|nagstat::HOST_UP|
			nagstat::HOST_DOWN|nagstat::HOST_UNREACHABLE;

		$all_service_problems = nagstat::SERVICE_UNKNOWN|
			nagstat::SERVICE_WARNING|nagstat::SERVICE_CRITICAL;

		$all_host_problems = nagstat::HOST_DOWN|nagstat::HOST_UNREACHABLE;

		$filters = $this->add_view('status/filters');
		$t = $this->translate;
		$filters->header_title = $t->_('Display Filters');
		$filters->lable_host_status_types = $t->_('Host Status Types');
		$filters->lable_host_properties = $t->_('Host Properties');
		$filters->lable_service_status_types = $t->_('Service Status Types');
		$filters->lable_service_properties = $t->_('Service Properties');
		$host_status_type_val = '';
		$hostprop_val = '';
		$service_status_type_val = '';
		$serviceprop_val = '';
		$found = 0;

		# Host status types
		if ($hoststatustypes == $all_host_status_types)
			$host_status_type_val = $t->_("All");
		elseif ($hoststatustypes == $all_host_problems)
			$host_status_type_val = $t->_("All problems");
		else {
			$found = 0;
			if ($hoststatustypes & nagstat::HOST_PENDING) {
				$host_status_type_val .= $t->_(" Pending");
				$found = 1;
			}
			if ($hoststatustypes & nagstat::HOST_UP) {
				$host_status_type_val .= sprintf($t->_("%s Up"), ($found == 1) ? " |" : '');
				$found = 1;
		                }
			if ($hoststatustypes & nagstat::HOST_DOWN) {
				$host_status_type_val .= sprintf($t->_("%s Down"), ($found == 1) ? " |" : '');
				$found = 1;
			}
			if ($hoststatustypes & nagstat::HOST_UNREACHABLE)
				$host_status_type_val .= sprintf($t->_("%s Unreachable"), ($found == 1) ? " |" : '');
		}
		$filters->host_status_type_val = $host_status_type_val;

		# Host properties
		if (!$hostprops)
			$hostprop_val = $t->_("Any");
		else {
			$found = 0;
			if ($hostprops & nagstat::HOST_SCHEDULED_DOWNTIME) {
				$hostprop_val .= ' ' . $t->_('In Scheduled Downtime');
				$found = 1;
			}
			if ($hostprops & nagstat::HOST_NO_SCHEDULED_DOWNTIME) {
				$hostprop_val .= sprintf($t->_("%s Not In Scheduled Downtime"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($hostprops & nagstat::HOST_STATE_ACKNOWLEDGED) {
				$hostprop_val .= sprintf($t->_("%s Has Been Acknowledged"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($hostprops & nagstat::HOST_STATE_UNACKNOWLEDGED) {
				$hostprop_val .= sprintf($t->_("%s Has Not Been Acknowledged"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($hostprops & nagstat::HOST_CHECKS_DISABLED) {
				$hostprop_val .= sprintf($t->_("%s Checks Disabled"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($hostprops & nagstat::HOST_CHECKS_ENABLED) {
				$hostprop_val .= sprintf($t->_("%s Checks Enabled"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($hostprops & nagstat::HOST_EVENT_HANDLER_DISABLED) {
				$hostprop_val .= sprintf($t->_("%s Event Handler Disabled"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
		                }
			if ($hostprops & nagstat::HOST_EVENT_HANDLER_ENABLED) {
				$hostprop_val .= sprintf($t->_("%s Event Handler Enabled"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($hostprops & nagstat::HOST_FLAP_DETECTION_DISABLED) {
				$hostprop_val .= sprintf($t->_("%s Flap Detection Disabled"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($hostprops & nagstat::HOST_FLAP_DETECTION_ENABLED) {
				$hostprop_val .= sprintf($t->_("%s Flap Detection Enabled"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($hostprops & nagstat::HOST_IS_FLAPPING) {
				$hostprop_val .= sprintf($t->_("%s Is Flapping"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($hostprops & nagstat::HOST_IS_NOT_FLAPPING) {
				$hostprop_val .= sprintf($t->_("%s Is Not Flapping"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($hostprops & nagstat::HOST_NOTIFICATIONS_DISABLED) {
				$hostprop_val .= sprintf($t->_("%s Notifications Disabled"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($hostprops & nagstat::HOST_NOTIFICATIONS_ENABLED) {
				$hostprop_val .= sprintf($t->_("%s Notifications Enabled"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($hostprops & nagstat::HOST_PASSIVE_CHECKS_DISABLED) {
				$hostprop_val .= sprintf($t->_("%s Passive Checks Disabled"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($hostprops & nagstat::HOST_PASSIVE_CHECKS_ENABLED) {
				$hostprop_val .= sprintf($t->_("%s Passive Checks Enabled"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($hostprops & nagstat::HOST_PASSIVE_CHECK) {
				$hostprop_val .= sprintf($t->_("%s Passive Checks"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($hostprops & nagstat::HOST_ACTIVE_CHECK) {
				$hostprop_val .= sprintf($t->_("%s Active Checks"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($hostprops & nagstat::HOST_HARD_STATE) {
				$hostprop_val .= sprintf($t->_("%s In Hard State"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($hostprops & nagstat::HOST_SOFT_STATE) {
				$hostprop_val .= sprintf($t->_("%s In Soft State"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
		}
		$filters->hostprop_val = $hostprop_val;

		# Service Status Types
		if ($servicestatustypes == $all_service_status_types)
			$service_status_type_val = $t->_("All");
		elseif ($servicestatustypes == $all_service_problems)
			$service_status_type_val = $t->_("All Problems");
		else {
			$found = 0;
			if ($servicestatustypes & nagstat::SERVICE_PENDING) {
				$service_status_type_val = ' ' . $t->_("Pending");
				$found = 1;
			}
			if ($servicestatustypes & nagstat::SERVICE_OK) {
				$service_status_type_val .= sprintf($t->_("%s OK"), ($found == 1) ? ' |' : '');
				$found = 1;
			}
			if ($servicestatustypes & nagstat::SERVICE_UNKNOWN) {
				$service_status_type_val .= sprintf($t->_("%s Unknown"), ($found == 1) ? ' |' : '');
				$found = 1;
			}
			if ($servicestatustypes & nagstat::SERVICE_WARNING) {
				$service_status_type_val .= sprintf($t->_("%s Warning"), ($found == 1) ? ' |' : '');
				$found = 1;
			}
			if ($servicestatustypes & nagstat::SERVICE_CRITICAL) {
				$service_status_type_val .= sprintf($t->_("%s Critical"), ($found == 1) ? ' |' : '');
				$found = 1;
			}
		}
		$filters->service_status_type_val = $service_status_type_val;

		# Service Properties
		if ($serviceprops == 0)
			$serviceprop_val = $t->_("Any");
		else {
			$found = 0;
			if ($serviceprops & nagstat::SERVICE_SCHEDULED_DOWNTIME){
				$serviceprop_val .= ' ' .  $t->_("In Scheduled Downtime");
				$found = 1;
			}
			if ($serviceprops & nagstat::SERVICE_NO_SCHEDULED_DOWNTIME) {
				$serviceprop_val .= sprintf($t->_("%s Not In Scheduled Downtime"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($serviceprops & nagstat::SERVICE_STATE_ACKNOWLEDGED) {
				$serviceprop_val .= sprintf($t->_("%s Has Been Acknowledged"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($serviceprops & nagstat::SERVICE_STATE_UNACKNOWLEDGED) {
				$serviceprop_val .= sprintf($t->_("%s Has Not Been Acknowledged"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($serviceprops & nagstat::SERVICE_CHECKS_DISABLED) {
				$serviceprop_val .= sprintf($t->_("%s Checks Disabled"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($serviceprops & nagstat::SERVICE_CHECKS_ENABLED) {
				$serviceprop_val .= sprintf($t->_("%s Checks Enabled"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($serviceprops & nagstat::SERVICE_EVENT_HANDLER_DISABLED) {
				$serviceprop_val .= sprintf($t->_("%s Event Handler Disabled"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($serviceprops & nagstat::SERVICE_EVENT_HANDLER_ENABLED) {
				$serviceprop_val .= sprintf($t->_("%s Event Handler Enabled"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($serviceprops & nagstat::SERVICE_FLAP_DETECTION_DISABLED) {
				$serviceprop_val .= sprintf($t->_("%s Flap Detection Disabled"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($serviceprops & nagstat::SERVICE_FLAP_DETECTION_ENABLED) {
				$serviceprop_val .= sprintf($t->_("%s Flap Detection Enabled"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($serviceprops & nagstat::SERVICE_IS_FLAPPING) {
				$serviceprop_val .= sprintf($t->_("%s Is Flapping"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($serviceprops & nagstat::SERVICE_IS_NOT_FLAPPING) {
				$serviceprop_val .= sprintf($t->_("%s Is Not Flapping"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($serviceprops & nagstat::SERVICE_NOTIFICATIONS_DISABLED) {
				$serviceprop_val .= sprintf($t->_("%s Notifications Disabled"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($serviceprops & nagstat::SERVICE_NOTIFICATIONS_ENABLED) {
				$serviceprop_val .= sprintf($t->_("%s Notifications Enabled"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($serviceprops & nagstat::SERVICE_PASSIVE_CHECKS_DISABLED) {
				$serviceprop_val .= sprintf($t->_("%s Passive Checks Disabled"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($serviceprops & nagstat::SERVICE_PASSIVE_CHECKS_ENABLED) {
				$serviceprop_val .= sprintf($t->_("%s Passive Checks Enabled"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($serviceprops & nagstat::SERVICE_PASSIVE_CHECK) {
				$serviceprop_val .= sprintf($t->_("%s Passive Checks"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($serviceprops & nagstat::SERVICE_ACTIVE_CHECK) {
				$serviceprop_val .= sprintf($t->_("%s Active Checks"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($serviceprops & nagstat::SERVICE_HARD_STATE) {
				$serviceprop_val .= sprintf($t->_("%s In Hard State"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
			if ($serviceprops & nagstat::SERVICE_SOFT_STATE) {
				$serviceprop_val .= sprintf($t->_("%s In Soft State"), ($found == 1) ? ' &amp;' : '');
				$found = 1;
			}
		}
		$filters->serviceprop_val = $serviceprop_val;
		return $filters;
	}

	/**
	* Translated helptexts for this controller
	*/
	public static function _helptexts($id)
	{
		$translate = zend::instance('Registry')->get('Zend_Translate');

		# No helptexts defined yet - this is just an example
		# Tag unfinished helptexts with @@@HELPTEXT:<key> to make it
		# easier to find those later
		$helptexts = array(
			'edit' => $translate->_('@@@HELPTEXT:edit')
		);
		if (array_key_exists($id, $helptexts)) {
			echo $helptexts[$id];
		}
		else
			echo sprintf($translate->_("This helptext ('%s') is yet not translated"), $id);
	}
}
