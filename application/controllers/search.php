<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Search controller
 *
 * @package NINJA
 * @author op5 AB
 * @license GPL
 */
class Search_Controller extends Authenticated_Controller {

	const FILTER_CHAR = ':';

	/**
	*	Provide search functionality for all object types
	*/
	public function lookup($query=false, $obj_type=false)
	{
		$obj_type = urldecode($this->input->get('obj_type', $obj_type));
		$query = urldecode($this->input->get('query', $query));
		if (strstr($query, self::FILTER_CHAR)) {
			$options = explode(self::FILTER_CHAR, $query);
			if (is_array($options) && !empty($options[0])) {
				$obj_type = trim($options[0]);
				if (isset($options[1])) {
					$query = $options[1];
				}
			}
		}

		switch ($obj_type) {
			case 'host': case 'h':
				$obj_type = 'host';
				$settings = array(
					'class' => 'Host_Model',
					'template' => 'status/service'
					);
				break;
			case 'service': case 's':
				$obj_type = 'service';
				$settings = array(
					'class' => 'Service_Model',
					'path' => 'extinfo/index'
				);
				break;
			case 'hostgroup': case 'hg':
				$obj_type = 'hostgroup';
				$settings = array(
					'class' => 'Hostgroup_Model',
					'path' => 'status/group_overview'
				);
				break;
			case 'servicegroup': case 'sg':
				$obj_type = 'servicegroup';
				$settings = array(
					'class' => 'Servicegroup_Model',
					'path' => 'status/group_overview'
				);
				break;
			default:
				$settings = false;
		}

		$this->template->content = $this->add_view('search/result');
		$content = $this->template->content;
		$limit = 10; # limit search result to max items returned @@@FIXME should be configurable?
		$content->query = $query;
		# user requested a special object type
		if (!empty($settings)) {
			$obj_class_name = $settings['class'];
			$obj_class = new $obj_class_name();
			# find requested object

			$data = $obj_class->search($query, $limit);
			if ($data!==false) {
				$content->{$obj_type.'_result'} = $data;
			} else {
				$content->no_data = $this->translate->_('Nothing found');
			}
		} else {
			# search through everything
			$objects = array('host' => 'Host_Model', 'service' => 'Service_Model', 'hostgroup' => 'Hostgroup_Model', 'servicegroup' => 'Servicegroup_Model');
			foreach ($objects as $obj => $model) {
				$obj_class_name = $model;
				$obj_class = new $obj_class_name();
				$data = $obj_class->search($query, $limit);
				$obj_info = false;
				/**
				 * Modify config/config.php to enable NACOMA
				 * and set the correct path in config/config.php,
				 * if installed, to use this
				 */
				if (Kohana::config('config.nacoma_path')!==false) {
					$lable_nacoma = $this->translate->_('Configure this object using NACOMA (Nagios Configuration Manager)');
					$content->nacoma_link = '<a href="'.Kohana::config('config.nacoma_path').'edit.php?obj_type=%s&amp;%s=%s" style="border: 0px">'.html::image($this->img_path('images/icons/16x16/nacoma.png'), array('alt' => $lable_nacoma, 'title' => $lable_nacoma)).'</a>';
				}


				if (count($data) > 0) {
					$content->{$obj.'_result'} = $data;
				}
			}
		}

	}
}