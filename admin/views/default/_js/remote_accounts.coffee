$ ->
  reload = ->
    cb = (accounts) ->
      $('#accounts tbody tr').remove()
      return unless accounts
      for account in accounts
        html = """
        <tr>
          <td>#{if account.type is 'ssh' then "ssh://#{account.host}" else account.type}</td>
          <td>#{account.username}</td>
          <td><a href="#{config.prefix}/ajax_settings/get_remote_account_pubkey/#{account.uuid}" class="pubkey">#{_("public key")}</a></td>
          <td>
            <button class="submit account-remove">#{_("Remove")}</button>
            <button class="submit account-edit">#{_("Edit")}</button>
          </td>
        </tr>
        """
        $node = $(html)
        $node.data('account_data', account)
        $('#accounts tbody').append($node)


    $.post "#{config.prefix}/ajax_settings/get_remote_accounts", {}, cb, 'json'

  reload()

  $("#add-new").click (e) ->
    $dialog = $('#create-account').clone().removeAttr 'id'
    open_cb = =>
      $dialog.find('select[name=type]').change()
      form = $dialog.find('form')
      form.validate
        rules:
          username:
            required: true
          password:
            required: true
      form.ajaxForm
        dataType: 'json'
        beforeSubmit: (arr, $form, options) ->
          $.throbber.show()
        success: (data) ->
          $.throbber.hide()
          if data.error == 1
            alert data.html
            return

          reload()
          $dia.dialog 'close'
          if data.uuid and data.type isnt 'ssh'
            txt = switch data.type
              when 'HiDrive'
                _ """Please click <a href="%s/ajax_settings/get_remote_account_pubkey/%s">here</a> to download the openssh key needed for backup. Upload it to <a target="_blank" href="https://hidrive.strato.com/">HiDrive</a> under Account → Settings → Account management → OpenSSH key"""
              else
                _ """Please click <a href="%s/ajax_settings/get_remote_account_pubkey/%s">here</a> to download the openssh key needed for backup"""
            html = $.sprintf txt, config.prefix, data.uuid
            $.alert html


    options =
      width: 600
      minWidth: 600
      minHeight: 300
      resizable: true
      position: ["center", 200]
      open: open_cb
    $dia = $.dialog($dialog, "", null, options)


  $('.account-edit').live 'click', (e) ->

    e.stopPropagation()
    account = $(@).closest("tr").data("account_data")

    $dialog = $('#edit-account').clone().removeAttr 'id'

    open_cb = =>
      form = $dialog.find('form')

      form.find('input.username').val(account.username)

      if account.type is 'ssh'
        form.find('input[name=host]').val(account.host)
        form.find('input[name=username]').val(account.username)
        form.find('input[name=password]').attr('disabled', 'disabled').closest('tr').hide()
        form.validate
          rules:
            username:
              required: true
            host:
              required: true
      else if account.type is 'HiDrive'
        form.find('input[name=username]').attr('disabled', 'disabled').closest('tr').hide()
        form.find('input[name=host]').attr('disabled', 'disabled').closest('tr').hide()
        form.validate
          rules:
            password:
              required: true

      form.ajaxForm
        data:
          id: account.id
        dataType: 'json'
        beforeSubmit: (arr, $form, options) ->
          return false unless form.valid()
          $.throbber.show()
        success: (data) ->
          $.throbber.hide()
          if data.error == 1
            alert data.html
            return

          reload()
          $dia.dialog 'close'

    options =
      width: 600
      minWidth: 600
      minHeight: 300
      resizable: true
      position: ["center", 200]
      open: open_cb
    $dia = $.dialog($dialog, "", null, options)

  $('.account-remove').live 'click', (e) ->
    e.stopPropagation()
    account = $(@).closest("tr").data("account_data")
    $.confirm _("Are you sure you want to permanently remove this remote account?"), _("Remove remote account"), [
      text: _("Remove remote account")
      click: ->
        cb = (data) =>
          $.throbber.hide()
          reload()
          $(@).dialog "close"

        $.throbber.show()
        $.post "#{config.prefix}/ajax_settings/remove_remote_account",
          host: account.host
          type: account.type
          username: account.username
        , cb, "json"
    ]
    false

  $('select[name=type]').live 'change', ->
    switch $(@).val()
      when 'ssh'
        $('input[name=host]').closest('tr').show()
      else
        $('input[name=host]').closest('tr').hide()
