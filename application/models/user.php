<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * This model uses ORM and regular db->query()
 *
 * This model controls variables (and a few methods) for the
 * users objects (and thus also database tables).
 * Authentication stuff should *not* end up here.
 */
class User_Model extends Auth_User_Model {
	public static $auth_table = 'ninja_user_authorization';

	public function update_password($username, $password)
	{
		$password = ninja_auth::hash_password($password);

		$etc_path = Kohana::config('config.nagios_etc_path')?
			Kohana::config('config.nagios_etc_path')
			: System_Model::get_nagios_base_path() . '/etc';
		$htpasswd_path = $etc_path . '/htpasswd.users';
		$htpasswd = @file($htpasswd_path);
		if ($htpasswd === false)
			throw new Exception("Could not read {$htpasswd_path}");

		$found = false;
		foreach($htpasswd as $n => $line)
		{
			$uname = strtok($line, ':');
			if ($uname !== false && $uname == $username)
			{
				$htpasswd[$n] = $username . ':{SHA}' . $password . "\n";
				$found = true;
				break;
			}
		}
		if (!$found)
			$htpasswd[] = $username . ':{SHA}' . $password . "\n";

		if (@file_put_contents($htpasswd_path, $htpasswd) === false)
			throw new Exception("Could not write {$htpasswd_path}");;

		return $password;
	}

	public function save_user($user_obj)
	{
		$db = Database::instance();
		$ary = array();
		foreach ($user_obj as $key => $val) {
			$ary[] = "$key = {$db->escape($val)}";
		}
		$str = implode(', ', $ary);
		$db->query("UPDATE users SET {$str} WHERE id={$db->escape($user_obj->id)}");
	}

	/**
	 * Takes care of setting session variables etc
	 */
	public function complete_login($user_data=false)
	{
		if (!$this->session->get(Kohana::config('auth.session_key'), false)) {
			url::redirect(Kohana::config('routes._default'));
		}

		# save user object data to session
		#$this->session->set('user_data', $user_data);

		# set logged_in to current timestamp if db
		$auth_type = Kohana::config('auth.driver');
		if ($auth_type == 'db' || $auth_type === 'Ninja' || $auth_type === 'LDAP' || $auth_type == 'apache') {
			$db = Database::instance();
			$sql = "UPDATE users SET last_login=".time()." WHERE username=".
				$db->escape(Auth::instance()->get_user()->username);
			$db->query($sql);
		}

		$requested_uri = Session::instance()->get('requested_uri', false);

		# cache nagios_access session information
		System_Model::nagios_access();

		# Check that user has access to view some objects
		# or logout with a message
		$access = Session::instance()->get('nagios_access', false);
		if (empty($access)) {
			# not any authorized_for_ variables set so lets check
			# if user is authorized for any objects
			$auth = new Nagios_auth_Model();
			$hosts = $auth->get_authorized_hosts();

			$redirect = false;
			if (empty($hosts)) {
				$services = $auth->get_authorized_services();
				if (empty($services)) {
					$redirect = true;
				}
			}

			if ($redirect !== false) {
				if ($auth_type == 'apache') {
					url::redirect('default/no_objects');
				} else {
					$this->session->set_flash('error_msg',
						$this->translate->_("You have been denied access since you aren't authorized for any objects."));
					url::redirect('default/show_login');
				}
			}
		}


		# make sure we don't end up in infinite loop
		# if user managed to request show_login
		if ($requested_uri == Kohana::config('routes.log_in_form')) {
			$requested_uri = Kohana::config('routes.logged_in_default');
		}
		if ($requested_uri !== false) {
			# remove 'requested_uri' from session
			$this->session->delete('requested_uri');
			url::redirect($requested_uri);
		} else {
			# we have no requested uri
			# using logged_in_default from routes config
			#die('going to default');
			url::redirect(Kohana::config('routes.logged_in_default'));
		}
	}

	public function username_available($id) {
		return ! $this->username_exists($id);
	}

	/**
	 * Validate input when editing user
	 */
	public function user_validate_edit(array & $array, $save = FALSE)
	{
#		echo Kohana::debug($array);
#		die();
		$array = Validation::factory($array)
			->pre_filter('trim')
			->add_rules('realname', 'required', 'length[3,50]')
			#->add_rules('password', 'required', 'length[5,42]')
			#->add_rules('password_confirm', 'matches[password]')
			;

		return ORM::validate($array, $save);
	}

	/**
	 * Takes care of setting a user as logged out
	 * and destroying the session
	 */
	public function logout_user()
	{
		$auth_type = Kohana::config('auth.driver');
		if ($auth_type == 'db') {
			$this->db->query('UPDATE user SET logged_in = 0 WHERE id='.(int)user::session('id'));

			# reset users logged_in value when they have been logged in
			# more than sesssion.expiration (default 7200 sec)
			$session_length = Kohana::config('session.expiration');
			$this->db->query('UPDATE user SET logged_in = 0 WHERE logged_in!=0 AND logged_in < '.(time()-$session_length));
		}
		$this->session->destroy();
		return true;
	}

	/**
	*	Check if user exists and if so we pass the supplied
	* 	$options data to Nninja_user_authorization_Model to let
	* 	it decide if to update or insert.
	*/
	public function user_auth_data($username=false, $options=false)
	{
		if (empty($username) || empty($options))
			return false;
		$username = trim($username);
		$result = false;

		$auth_fields = Ninja_user_authorization_Model::$auth_fields;

		# authorization data fields and order
		$auth_options = false;

		# check that we have the correct number of auth options
		# return false otherwise
		if (count($options) != count($auth_fields)) {
			return false;
		}

		# merge the two arrays into one with auth_fields as key
		for ($i=0;$i<count($options);$i++) {
			$auth_options[$auth_fields[$i]] = $options[$i];
		}

		$db = Database::instance();
		$sql = "SELECT * FROM users WHERE username=".$db->escape($username);
		$res = $db->query($sql);
		if (count($res)!=0) {
			# user found in db
			# does authorization data exist for this user?
			$user = $res->current();
			$result = ninja_user_authorization_Model::insert_user_auth_data($user->id, $auth_options);
			unset($user);
		} else {
			# this should never happen
			$result = "Tried to save authorization data for a non existing user.\n";
		}
		return array($result);
	}

	/**
	* Truncate ninja_user_authentication table
	*/
	public function truncate_auth_data()
	{
		$db = Database::instance();
		$sql = "TRUNCATE TABLE ninja_user_authorization";
		$db->query($sql);
	}

	/**
	*	Create the ninja_user_authorization table if not exists
	*	(this will totally break in anything that isn't mysql)
	*
	*/
	public static function create_auth_table()
	{
		$db = Database::instance();
		$sql = "CREATE TABLE IF NOT EXISTS ".self::$auth_table." ( ".
					"id int(11) NOT NULL auto_increment, ".
					"user_id int(11) NOT NULL, ".
					"system_information int(11) NOT NULL default '0', ".
					"configuration_information int(11) NOT NULL default '0', ".
					"system_commands int(11) NOT NULL default '0', ".
					"all_services int(11) NOT NULL default '0', ".
					"all_hosts int(11) NOT NULL default '0', ".
					"all_service_commands int(11) NOT NULL default '0', ".
					"all_host_commands int(11) NOT NULL default '0', ".
					"PRIMARY KEY  (id), ".
					"KEY user_id (user_id));";
		$db->query($sql);
	}

	/**
	*	Fetch userinfo based on login
	*	Will return first user with login role found (for CLI access)
	* 	if username is set to false.
	*/
	public function get_user($username=false)
	{
		$db = Database::instance();
		if (!empty($username)) {
			$username = trim($username);
			$sql = 'SELECT * FROM users WHERE username='.$db->escape($username);
		} else {
			$sql = 'SELECT * FROM users'.
				' INNER JOIN roles_users ON roles_users.user_id=users.id'.
				' INNER JOIN roles ON roles.id=1'.
				' WHERE roles_users.user_id=users.id';
		}
		$user = $db->query($sql);
		if (count($user)>0) {
			$cur = $user->current();
			return $cur;
		}
		return false;
	}

	/**
	*	Fetch an array of all usernames in users table
	*	@return array of usernames or false on error
	*/
	public function get_all_usernames()
	{
		$db = Database::instance();
		$query = 'SELECT * FROM users';
		$user_res = $db->query($query);

		if (count($user_res)==0) {
			return false;
		}
		$users = false;
		foreach ($user_res as $user) {
			$users[] = $user->username;
		}
		return $users;
	}

	/**
	*	Add a user to db
	* 	A login role will be created for new users
	* 	Checks are made that the user doesn't exist
	*/
	public function add_user($data=false)
	{
		if (empty($data)) {
			return false;
		}
		$username = isset($data['username']) ? $data['username'] : false;
		$password = isset($data['password']) ? $data['password'] : false;
		$password_algo = isset($data['password_algo']) ? $data['password_algo'] : false;
		$db = Database::instance();

		$user = self::get_user($username);
		if ($user !== false) {
			# update
			$user->password = $password;
			$user->password_algo = $password_algo;
			$sql = "UPDATE users SET password=".$db->escape($password).", password_algo=".$db->escape($password_algo);
			$db->query($sql);
		} else {
			# create new
			$sql = "INSERT INTO users(password, username, password_algo) ".
				"VALUES(".$db->escape($password).", ".$db->escape($username).
				", ".$db->escape($password_algo).")";
			$res = $db->query($sql);

			# fetch id of inserted user
			$user_id = false;
			$user_data = self::get_user($username);
			if (count($user_data)!=0) {
				$user_id = $user_data->id;

				# add login role for user
				$sql = "INSERT INTO roles_users(user_id, role_id) VALUES(".(int)$user_id.", 1)";
				unset($res);
				$db->query($sql);
				return true;
			}
		}
		return false;
	}
}
