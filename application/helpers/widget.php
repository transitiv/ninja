<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * widget helper class.
 */
class widget_Core
{
	public $result = false; 	# widget content result
	public $js = false;			# required js resources?
	public $css = false;		# additional css?
	public $widget_base_path = false;# base_path to widget
	public $widget_full_path = false;
	public $master_obj = false;
	public $widgetname = false;
	public $translate = false;

	public function __construct()
	{
		$this->widget_base_path = Kohana::config('widget.path').Kohana::config('widget.dirname');
		$this->auto_render = FALSE;

		$this->theme_path = zend::instance('Registry')->get('theme_path');

		# fetch our translation instance
		$this->translate = zend::instance('Registry')->get('Zend_Translate');

		# suppress output until widget is done
		ob_implicit_flush(0);
		ob_start();
	}

	/**
	 * Add a new widget
	 * @param $name string: Name of the widget
	 * @param $arguments array: Widget arguments
	 * @param $master ?
	 */
	public function add($name=false, $arguments=false, &$master=false)
	{

		try {
			# first try custom path
			$path = Kohana::find_file(Kohana::config('widget.custom_dirname').$name, $name, false);
			if ($path === false) {
				# try core path if not found in custom
				$path = Kohana::find_file(Kohana::config('widget.dirname').$name, $name, true);
			}
			require_once($path);
			$classname = $name.'_Widget';
			$obj = new $classname;
			# if we have a requested widget method - let's call it
			# always call index method of widget
			$widget_method = 'index';
			return $obj->$widget_method($arguments, $master);
		} catch (Exception $ex) {
			$master->widgets[] = "<div id=\"widget-$name\" class='widget editable movable collapsable removable closeconfirm'><div class='widget-header'>$name</div><div class='widget-content'>The widget $name couldn't be loaded.</div></div>";
		}

	}

	/**
	 * Find name of input class and set wiidget_full path for later use
	 * @param $input string: Widget name
	 * @param $dirname Directory name
	 * @return false on error. true on success
	 */
	public function set_widget_name($input=false, $dirname=false)
	{
		if (empty($input))
			return false;

		$this->widgetname = strtolower(str_replace('_Widget', '',$input));

		$path = Kohana::find_file(Kohana::config('widget.custom_dirname').$this->widgetname, $this->widgetname, false);
		if ($path === false) {
			# try core path if not found in custom
			$path = Kohana::find_file(Kohana::config('widget.dirname').$this->widgetname, $this->widgetname, false);
		}

		if (strstr($path, Kohana::config('widget.custom_dirname'))) {
			# we have a custom_widget
			$this->widget_base_path = Kohana::config('widget.path').Kohana::config('widget.custom_dirname');
		}

		$this->widget_full_path = $this->widget_base_path.$this->widgetname;
		return true;
	}

	/**
	 * Find path of widget viewer
	 * @param $view Template object
	 * @return str path to viewer
	 */
	public function view_path($view=false)
	{
		if (empty($view))
			return false;

		# first try custom path
		$path = Kohana::find_file(Kohana::config('widget.custom_dirname').$this->widgetname, $view, false);
		if ($path === false) {
			# try core path if not found in custom
			$path = Kohana::find_file(Kohana::config('widget.dirname').$this->widgetname, $view, true);
		}

		return $path;
	}

	/**
	 * Fetch content from output buffer for widget.
	 * Assign required external files (js, css) on to master controller
	 * variables.
	 */
	public function fetch()
	{
		$content = $this->output();
		$this->resources($this->js, 'js');
		$this->resources($this->css, 'css');
		$this->master_obj->widgets = array_merge($this->master_obj->widgets, array($content));
		#return array('content' => $content, 'js' => $this->js, 'css' => $this->css);
	}

	/**
	 * Fetch content from output buffer for widget ajax call
	 * and clean up output buffer.
	 * @return Buffered output content
	 */
	public function output()
	{
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	/**
	 * Merge current widgets resource files with other
	 * widgets to be printed to HTML head
	 *
	 * @param $in_files array: Paths to widget files
	 * @param $type string: File type { css, js }
	 * @return false on errors, true on success.
	 */
	public function resources($in_files=false, $type='js')
	{
		if (empty($in_files) || empty($this->master_obj) || empty($type))
			return false;
		$type = strtolower($type);
		$files = false;
		foreach ($in_files as $file) {
			$files[] = $this->widget_base_path.$this->widgetname.$file;
		}
		switch ($type) {
		 case 'css':
			$this->master_obj->xtra_css = array_merge($this->master_obj->xtra_css, $files);
			break;
		 case 'js': default:
			$this->master_obj->xtra_js = array_merge($this->master_obj->xtra_js, $files);
			break;
		}
		return true;
	}

	/**
	 * Set correct paths considering
	 * the path to current theme.
	 * @param $rel_path string: Relative path
	 * @return false on errors, "full relative" path on success.
	 */
	public function add_path($rel_path)
	{
		$rel_path = trim($rel_path);
		if (empty($rel_path)) {
			return false;
		}

		$path = false;
		# assume rel_path is relative from current theme
		$path = 'application/views/'.$this->theme_path.$rel_path;
		# make sure we didn't mix up start/end slashes
		$path = str_replace('//', '/', $path);
		return $path;
	}
}
