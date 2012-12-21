<?php

class System extends Model {
  private $version;
  public function __construct() {
    parent::Model();
  }

  public function set_timezone($timezone) {

    $target = "/usr/share/zoneinfo/$timezone";
    if(!file_exists($target)) {
      throw new Exception("Timezone $timezone doesn't exists");
    }
    unlink('/etc/localtime');
    symlink($target, '/etc/localtime');
    file_put_contents('/etc/timezone', $timezone);
  }

  public function get_timezone() {
    return trim(file_get_contents('/etc/timezone'));
  }

  # Lists all timezones with region, UTC has region false
  public function list_timezones() {
    $timezones = array();
    foreach(DateTimeZone::listIdentifiers() as $ts) {
      if(strpos($ts,'/')) {
        list($region, $country) = explode('/', $ts);
        $timezones[$country] = $region;
      } else {
        $timezones[$ts] = false;
      }
    }
    ksort($timezones);
    return $timezones;
  }

  public function get_raw_uptime() {
    $upt=file("/proc/uptime");
    sscanf($upt[0],"%d",$secs_tot);
    return $secs_tot;
  }

  public function get_uptime() {
    $start = new DateTime();
    $start->sub(DateInterval::createFromDateString("{$this->get_raw_uptime()} seconds"));
    return $start->diff(new DateTime());
  }

  public function get_system_version() {
    if(!$this->version) {
      $this->version = file_get_contents(BUBBA_VERSION);
    }
    return $this->version;
  }

  public function get_hardware_id() {
    return getHWType();
  }

  public function list_printers() {
    $json =  _system('cups-list-printers');
    return json_decode(implode($json),true);
  }

  const accounts_file = '/etc/bubba/remote_accounts.yml';
  const fstab_file = '/etc/fstab';
  const webdav_secrets_file  = '/etc/davfs2/secrets';
  const ssh_keydir = '/etc/bubba/ssh-keys';

  # faithfully stolen from http://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid
  private function gen_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      // 32 bits for "time_low"
      mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

      // 16 bits for "time_mid"
      mt_rand( 0, 0xffff ),

      // 16 bits for "time_hi_and_version",
      // four most significant bits holds version number 4
      mt_rand( 0, 0x0fff ) | 0x4000,

      // 16 bits, 8 bits for "clk_seq_hi_res",
      // 8 bits for "clk_seq_low",
      // two most significant bits holds zero and one for variant DCE1.1
      mt_rand( 0, 0x3fff ) | 0x8000,

      // 48 bits for "node"
      mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
  }
  private function create_ssh_key($uuid) {
    $priv_path = implode(DIRECTORY_SEPARATOR, array(self::ssh_keydir, $uuid));
    if(file_exists($priv_path)) {
      # XXX forcefully removing old key
      @unlink($priv_path);
      @unlink($priv_path.".pub");
    }
    _system("ssh-keygen", '-f', $priv_path, '-N', '', '-q');
    $pubkey = file_get_contents($priv_path.'.pub');
    return $pubkey;
  }

  private function get_pubkey($uuid) {
    $priv_path = implode(DIRECTORY_SEPARATOR, array(self::ssh_keydir, $uuid));
    $pub_path = $priv_path.'.pub';
    $pubkey = file_get_contents($pub_path);
    return $pubkey;
  }


  public function add_remote_account($type, $username, $password, $host) {
    $accounts = array();
    if(file_exists(self::accounts_file)) {
      $accounts = spyc_load_file(self::accounts_file);
    }


    $arr = array(
      'type' => $type,
      'username' => $username,
      'password' => $password
    );
    if($host) {
      $arr['host'] = $host;
      $key = "$type|$host|$username";
    } else {
      $key = "$type|$username";
    }
    $uuid = $this->gen_uuid();
    $pubkey = $this->create_ssh_key($uuid);
    $arr['uuid'] = $uuid;

    if(isset($accounts[$key])) {
      throw new Exception('Account allready defined');
    } else {
      $accounts[$key] = $arr;
      file_put_contents(self::accounts_file,Spyc::YAMLDump($accounts));
      return array('key' => $key, 'uuid' => $uuid, 'pubkey' => $pubkey);
    }
  }

  public function remove_remote_account($type, $username, $host) {
    $accounts = array();
    if(file_exists(self::accounts_file)) {
      $accounts = spyc_load_file(self::accounts_file);
    }
    if($host) {
      unset($accounts["$type|$host|$username"]);
    } else {
      unset($accounts["$type|$username"]);
    }
    file_put_contents(self::accounts_file,Spyc::YAMLDump($accounts));
    return true;
  }

  public function get_remote_accounts() {
    $targets = array();
    if(file_exists(self::accounts_file)) {
      $accounts = spyc_load_file(self::accounts_file);
      foreach($accounts as $id => $account) {
        $target = array(
          'id' => $id,
          'type' => $account['type'],
          'username' => $account['username'],
          'pubkey' => $this->get_pubkey($account['uuid']),
        );
        if(isset($account['host'])) {
          $target['host'] = $account['host'];
        }
        $targets[] = $target;
      }
    }
    return $targets;
  }

  public function get_webdav_path($type, $username) {
    return "/home/admin/$type/$username";
  }

  public function create_webdav_path($type, $username) {
    $path = "/home/admin/$type";
    if(! file_exists($path) ) {
      mkdir($path, 0700);
      chown($path, 'admin');
      chgrp($path, 'admin');
    }
    $path = "/home/admin/$type/$username";
    if(! file_exists($path) ) {
      mkdir($path, 0700);
      chown($path, 'admin');
      chgrp($path, 'admin');
    }
  }

  public function get_webdav_url($type) {
    switch($type) {
    case 'HiDrive':
      return 'http://webdav.hidrive.strato.com';
    }
  }

  public function add_webdav($type, $username, $password) {
    $url = $this->get_webdav_url($type);
    $path = $this->get_webdav_path($type, $username);
    $oldsecrets = file_get_contents(self::webdav_secrets_file);

    # Remove old path if allready there
    $secrets = preg_replace("#^".preg_quote($path).".*#m", "", $oldsecrets);

    $secrets .= sprintf("\n%s\t\"%s\"\t\"%s\"\n", addslashes($path), addslashes($username), addslashes($password));

    if($oldsecrets != $secrets) {
      file_put_contents(self::webdav_secrets_file, $secrets);
    }

    $oldfstab = file_get_contents(self::fstab_file);
    $fstab = preg_replace("#^".preg_quote($url)."\s+".preg_quote($path).".*#m", "", $oldfstab);

    $fstab .= "\n$url $path davfs defaults,gid=users,dir_mode=775,file_mode=664 0 0\n";

    if(! file_exists($path) ) {
      $this->create_webdav_path($type, $username);
    }

    if($fstab != $oldfstab) {
      file_put_contents(self::fstab_file, $fstab);
      _system("mount", "-a");
    }

  }

  public function remove_webdav($type, $username) {
    $url = $this->get_webdav_url($type);
    $path = $this->get_webdav_path($type, $username);

    _system('umount', '-f', $path);
    $oldsecrets = file_get_contents(self::webdav_secrets_file);

    # Remove old path if allready there
    $secrets = preg_replace("#^".preg_quote($path).".*#m", "", $oldsecrets);
    file_put_contents(self::webdav_secrets_file, $secrets);

    $oldfstab = file_get_contents(self::fstab_file);
    $fstab = preg_replace("#^".preg_quote($url)."\s+".preg_quote($path).".*#m", "", $oldfstab);
    file_put_contents(self::webdav_secrets_file, $secrets);
    if( file_exists($path) ) {
      @rmdir($path);
    }
  }
}
