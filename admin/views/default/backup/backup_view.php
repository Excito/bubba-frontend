<?$this->load->helper("ordinal")?>
<table id="backup-jobs" class="ui-table-outline">
  <thead>
    <tr>
      <th colspan="4" class="ui-state-default ui-widget-header"><?=_("Backup jobs")?></th>
    </tr>
    <tr class="ui-filemanager-state-header">
      <th><?=_("Backup job")?></th>
      <th><?=_('Schedule')?></th>
      <th><?=_("Status")?></th>
      <th>&nbsp;</th>
    </tr>
  </thead>
  <tbody>
  </tbody>
  <tfoot>
    <tr><td colspan="4">
        <button class="submit" id="backup-job-add"><?=_("Add new backup job")?></button>
    </td></tr>
  </tfoot>
</table>

<div id="templates" class="ui-helper-hidden">

  <div id="create-target">
    <h3><?=_("Define a new remote target")?></h3>
    <form action="<?=FORMPREFIX?>/ajax_settings/new_remote_account" method="post">
      <table>
        <tr>
          <td>
            <select id="type" name="type">
              <option value="HiDrive">HiDrive</option>
              <option disabled="disabled" value="Google Drive">Google Drive</option>
              <option value="ssh">SSH</option>
            </select>
          </td>
        </tr>
        <tr>
          <td>
            <label for="username"><?=_("Username")?></label>
            <input type="text" id="username" name="username"/>
          </td>
        </tr>
        <tr>
          <td>
            <label for="password"><?=_("Password")?></label>
            <input type="password" id="password" name="password"/>
          </td>
        </tr>
        <tr>
          <td>
            <label for="host"><?=_("Host")?></label>
            <input type="text" id="host" name="host"></textarea>
          </td>
        </tr>
        <tr>
          <td>
            <input type="submit" value="<?=_("Create target")?>" />
          </td>
        </tr>
      </table>
    </form>
  </div>

  <div id="backup-create-dialog">
    <h2 class="ui-text-center">
      <?=_("Add new backup job")?>
    </h2>

    <form id="backup-create">
      <div class="ui-form-wrapper">
        <div id="select-destination" class="step">
          <h3><?=_("Select backup destination")?></h3>
          <table id="destinations-table">
          <tbody>
          </tbody>
        </table>
        <div>
          <button id="add-new-target"><?=_("Add new target")?>

          </button>
        </div>
        </div>


        <div id="select-selection" class="step">
          <h3><?=_("Select what you want to backup")?></h3>
          <table>
            <tr>
              <td>
                <input type="radio" name="selection" id="selection-data" value="data"/>
                <label for="selection-data"><?=_("All user's data (/home/&lt;all users&gt;)")?></label>
              </td>
            </tr>
            <tr>
              <td>
                <input type="radio" name="selection" id="selection-music" value="music"/>
                <label for="selection-music"><?=_("All music (/home/storage/music)")?></label>
              </td>
            </tr>
            <tr>
              <td>
                <input type="radio" name="selection" id="selection-pictures" value="pictures"/>
                <label for="selection-pictures"><?=_("All pictures (/home/storage/pictures)")?></label>
              </td>
            </tr>
            <tr>
              <td>
                <input type="radio" name="selection" id="selection-video" value="video"/>
                <label for="selection-video"><?=_("All videos (/home/storage/video)")?></label>
              </td>
            </tr>
            <tr>
              <td>
                <input type="radio" name="selection" id="selection-storage" value="storage"/>
                <label for="selection-storage"><?=_("Storage (/home/storage)")?></label>
              </td>
            </tr>
          </table>
        </div>

        <div id="select-schedule" class="step submit_step">
          <h3><?=_("Select when to run the backups")?></h3>
          <table>
            <tr>
              <td>
                <input type="radio" id="schedule-weekly" name="schedule" value="weekly"/>
                <label for="schedule-weekly"><?=_("Weekly (every sunday night)")?></label>
              </td>
            </tr>
            <tr>
              <td>
                <input type="radio" id="schedule-halfweekly" name="schedule" value="halfweekly"/>
                <label for="schedule-halfweekly"><?=_("Twice a week (every wednesday and sunday night)")?></label>
              </td>
            </tr>
            <tr>
              <td>
                <input type="radio" id="schedule-daily" name="schedule" value="daily">
                <label for="schedule-daily"><?=_("Daily (every night at two a clock")?></label>
              </td>
            </tr>
          </table>
        </div>



      </div>
    </form>
  </div>
</div>
