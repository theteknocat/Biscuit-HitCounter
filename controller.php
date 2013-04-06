<?php
/**
 * Basic hit counter.
 *
 * @package Modules
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 **/
class HitCounter extends AbstractModuleController {
	/**
	 * Register the Hit Counter JS file
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function run() {
		$this->register_js('footer','hit_counter.js');
		parent::run();
	}
	/**
	 * Fetch the hit counter info from the database for use in the page
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function action_secondary() {
		if (!empty($this->params['count_hit'])) {
			$this->Biscuit->set_never_cache(true);
		}
		Console::log("Hit counter running secondary action");
		$this->count_hit();
		$query = "SELECT `count`, DATE_FORMAT(`start_date`,'%b %e, %Y') AS `since_date` FROM `hit_counter` LIMIT 1";
		$counter_data = DB::fetch_one($query);
		$counter_output = '';
		if (!empty($counter_data)) {
			$counter = $counter_data['count'];
			$since_date = $counter_data['since_date'];
			$counter_output = $counter.'&nbsp;Visit'.(($counter > 1) ? 's' : '').'&nbsp;Since&nbsp;'.$since_date;
		}
		$counter_output .= '
<script type="text/javascript" charset="utf-8">';
		if (Session::var_exists('counted')) {
			$counter_output .= '
	var hit_already_counted = true;';
		} else {
			// Don't cache when doing a hit, otherwise this will be output from the cache every time and a hit will be counted every time
			$this->Biscuit->set_never_cache();
			$counter_output .= '
	var hit_url = "/'.$this->Biscuit->Page->slug().'";';
		}
		$counter_output .= '
</script>';
		$this->set_view_var("hit_counter",$counter_output);
	}
	/**
	 * Count a new hit
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function count_hit() {
		$hit_count = DB::fetch_one("SELECT `count` FROM `hit_counter` WHERE `id` = 1");
		// Count a hit from the current user if a hit counted is requested and they have not been counted already, or there have been no hits previously counted:
		if ((!empty($this->params['count_hit']) && !Session::var_exists('counted')) || !$hit_count) {
			if ($hit_count) {
				DB::query("UPDATE `hit_counter` SET `count` = (`count`+1), `last_updated` = NOW() WHERE `id` = 1");
			} else {
				DB::query("INSERT INTO `hit_counter` SET `id` = 1, `count` = 1, `start_date` = NOW(), `last_updated` = NOW()");
			}
			Console::log('                        A new hit was counted');
			DB::query("OPTIMIZE TABLE `hit_counter`");
			Session::set('counted',1); // Set this user's hit as counted so it won't count another hit each time they navigate to another page
		}
		else {
			Console::log('                        No hit counted, visitor has already been here once this session');
		}
	}
	/**
	 * When logout occurs, add the "counted" session variable to the list of ones to keep after logout
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_logout() {
		$this->Biscuit->modules['Authenticator']->keep_session_var("counted");
	}
	/**
	 * Add timestamp when the last hit count was recorded
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_check_for_content_updates() {
		$last_counted = DB::fetch_one('SELECT `last_updated` FROM `hit_counter` WHERE `id` = 1');
		if ($last_counted) {
			$this->Biscuit->add_updated_timestamp(strtotime($last_counted));
		}
	}
	/**
	 * Prevent indexing by the site search module when it's a request to count a hit
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_search_indexing_page() {
		if (!empty($this->params['count_hit'])) {
			$this->Biscuit->ModuleSiteSearch()->set_no_index();
		}
	}
	public static function install_migration() {
		DB::query("CREATE TABLE IF NOT EXISTS `hit_counter` (
		  `id` tinyint(3) unsigned NOT NULL default '0',
		  `count` int(15) unsigned NOT NULL default '0',
		  `start_date` datetime NOT NULL default '0000-00-00 00:00:00',
		  `last_updated` datetime NOT NULL default '0000-00-00 00:00:00',
		  PRIMARY KEY  (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8");
		DB::query("UPDATE `modules` SET `installed` = 1 WHERE `name` = 'HitCounter'");
		$module_id = DB::fetch_one("SELECT `id` FROM `modules` WHERE `name` = 'Contact'");
		DB::query("INSERT INTO `module_pages` SET `module_id` = ".$module_id.", `page_name` = '*', `is_primary` = 0");
	}
	public static function uninstall_migration() {
		DB::query("UPDATE `modules` SET `installed` = 0 WHERE `name` = 'HitCounter'");
		$module_id = DB::fetch_one("SELECT `id` FROM `modules` WHERE `name` = 'HitCounter'");
		DB::query("DELETE FROM `module_pages` WHERE `module_id` = ".$module_id);
		DB::query("DROP TABLE IF EXISTS `hit_counter`");
	}
}
?>