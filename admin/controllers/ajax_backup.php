<?php
class Ajax_backup extends Controller {

    var $json_data=Array(
        'error' => 1,
        'html' => 'Ajax Error: Invalid Request'
    );

    function __construct() {
        parent::Controller();
        $this->load->model("backup");
        require_once(APPPATH."/legacy/defines.php");
        require_once(ADMINFUNCS);

        $this->Auth_model->EnforceAuth('web_admin');
        $this->Auth_model->enforce_policy('web_admin','administer', 'admin');
        load_lang("bubba",THEME.'/i18n/'.LANGUAGE);

        $this->output->set_header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');
        $this->output->set_header('Expires: '.gmdate('D, d M Y H:i:s', time()).' GMT');
        $this->output->set_header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0");
        $this->output->set_header("Pragma: no-cache");
    }
    function get_backup_jobs() {
        $data = array();
        foreach( $this->backup->get_jobs() as $job ) {
            try {
                $settings = $this->backup->get_settings($job);

            } catch( NoSettingsException $e ) {
                # as we might have bad data, ignore the job for now
                continue;
            }
            try {
                $schedule = $this->backup->get_schedule($job);
            } catch( NoScheduleException $e ) {
                $schedule = array(
                    "type" => "disabled",
                );
            }
            $status = $this->backup->get_status($job);
            $date = "";
            switch($schedule["type"]) {
            case "hourly":
                $date = t("Hourly");
                break;
            case "daily":
                $date = t("Each day");
                break;
            case "weekly":
                $date = t("Once a week");
                break;
            case "monthly":
                $date = t("Every month");
                break;
            case "disabled":
                $date = t("Never");
                break;
            default:
                $date = t("Once in a while");
            }

            $target = $settings["target_protocol"];

            switch($target) {
            case "file":
                $target = "USB";
                break;
            case "FTP":
            case "SSH":
                break;
            default:
                $target = "???";
            }
            $cur = array(
                "name" => $job,
                "target" => $target,
                "schedule" => $date,
                "status" => "N/A"
            );

            if( $status["running"] ) {
                $cur["running"] = true;
                $cur["status"] = t("Running");
            } else {
                if( $status["error"] ) {
                    $cur["status"] = t("Failed");
                    $cur["failed"] = true;
                } elseif($status["done"]) {
                    $cur["status"] = t("OK");
                } else {
                    $cur["status"] = t("Not run");
                }
            }
            unset($status);
            unset($schedule);
            unset($settings);
            $data[] = $cur;
        }
        $this->json_data = $data;
    }

    function get_backup_job_information() {
        $name = $this->input->post("name");
        $this->json_data = $this->backup->list_backups($name);
    }

    function dirs() {
        function formatBytes($bytes, $precision = 2) {
            $units = array('B', 'KB', 'MB', 'GB', 'TB');

            $bytes = max($bytes, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);

            $bytes /= (1 << (10 * $pow));

            return round($bytes, $precision) . ' ' . $units[$pow];
        }
	    $subpath = $this->input->post('path');
        $modified_subpath = preg_replace("#(^|\/)\.\.?(\/|$)#", '/', $subpath);
		$path = "/home/$modified_subpath";

        $data = array(
            'meta' => array(),
            'root' => $modified_subpath,
            'aaData'  => array()
        );
        if (file_exists($path) && is_dir($path) && is_readable($path)) {
            if ($dh = opendir($path)) {
                while (($file = readdir($dh)) !== false) {
                    if( $file == '.'  || $file == '..' || !is_dir($path) ) {
                        continue;
                    }
                    $filename = $path . '/' . $file;
                    $data['aaData'][] = array(
                        filetype($filename),
                        $file,
                        date ("o-m-d H:i:s", filemtime($filename)),
                        formatBytes(filesize($filename))
                    );
                }
                closedir($dh);
            }
        } else {
            $data["meta"]["permission_denied"]=true;
        }
        $this->json_data = $data;

    }

    function get_available_devices() {

        $this->load->model("Disk_model");

        $disks = $this->Disk_model->list_disks();

        $usable_disks = array();

        foreach($disks as $disk) {
            if(preg_match("#/dev/sda#",$disk["dev"])) {
                continue;
            }
            if(isset($disk["partitions"]) && is_array($disk["partitions"])) {
                foreach($disk["partitions"] as $partition) {
                    if( !strcmp($partition["usage"],"mounted") || !strcmp($partition["usage"],"unused") && $partition["uuid"]) {
                        if($partition["label"]) {
                            $diskdata["label"] = $partition["label"];
                        } else {
                            if(preg_match("#dev/\w+(\d+)#",$disk["dev"],$partition_number)) {
                                $diskdata["label"] = "$disk[model]:$partition_number[1]";
                            } else {
                                $diskdata["label"] = "$disk[model]:1";
                            }
                        }
                        $diskdata["uuid"] = $partition["uuid"];
                        $usable_disks[$disk["model"]][]=$diskdata;
                    } else {

                    }
                }
            } else {
                if( !strcmp($disk["usage"],"mounted") || !strcmp($disk["usage"],"unused") && $disk["uuid"]) {
                    if($disk["label"]) {
                        $diskdata["label"] = $disk["label"];
                    } else {
                        if(preg_match("#dev/\w+(\d+)#",$disk["dev"],$partition_number)) {
                            $diskdata["label"] = "$disk[model]:$partition_number[1]";
                        } else {
                            $diskdata["label"] = "$disk[model]:1";
                        }
                    }
                    $diskdata["uuid"] = $disk["uuid"];
                    $usable_disks[$disk["model"]][]=$diskdata;
                }
            }
        }

        $this->json_data = array( "disks" => $usable_disks );
    }

    function _output($output) {
        echo json_encode($this->json_data);
    }

}
